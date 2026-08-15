<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Contracts\UI\FieldContainerContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Database\TransactionManager;
use MB\Bitrix\AdminKit\Field\File;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Support\ExceptionDiagnostics;
use MB\Bitrix\AdminKit\Support\UserFieldFileColumns;
use RuntimeException;
use Throwable;

final class EntityObjectFormSaver
{
    public function __construct(
        private readonly RelationMetadataResolver $metadataResolver = new RelationMetadataResolver(),
        private readonly OrmObjectRelationSynchronizer $relationSynchronizer = new OrmObjectRelationSynchronizer(),
    ) {
    }

    /**
     * @param DataManagerResourceContract&object $resource
     * @param iterable<FieldContract|Tab|FieldContainerContract> $fields Плоский список полей либо раскладка (Tabs, Grid, Column, Box) — разворачивается автоматически.
     * @param array<string,mixed> $rawPost
     * @param array<string,mixed> $validatedScalars Values from FormPage hooks (e.g. beforeValidate), including readonly defaults.
     */
    public function save(
        DataManagerResourceContract $resource,
        mixed $itemId,
        iterable $fields,
        array $rawPost,
        DbOperationContext $context,
        array $validatedScalars = [],
    ): EntityObjectSaveResult {
        $fields = $this->flattenFields($fields);

        if ($itemId !== null && $itemId !== '') {
            $filteredFields = [];
            foreach ($fields as $field) {
                if (array_key_exists($field->getColumn(), $rawPost)) {
                    $filteredFields[] = $field;
                }
            }
            $fields = $filteredFields;
        }

        [$scalarFields, $relationFields] = $this->splitFields($fields);
        $this->markUserFieldFileFields($resource, $scalarFields);
        if ($fields === []) {
            $scalarFormData = new FormData($rawPost, $rawPost, $rawPost);
            $relationFormData = new FormData([], [], []);
        } else {
            $rawScalar = $this->extractRaw($scalarFields, $rawPost);
            $rawRelations = $this->extractRaw($relationFields, $rawPost);

            $scalarFormData = (new DataPipeline())->process($scalarFields, $rawScalar);
            if ($scalarFormData->hasErrors()) {
                return new EntityObjectSaveResult(false, fieldErrors: $scalarFormData->errors());
            }

            $relationFormData = $relationFields === []
                ? new FormData([], [], [])
                : (new DataPipeline())->process($relationFields, $rawRelations);

            if ($relationFormData->hasErrors()) {
                return new EntityObjectSaveResult(false, fieldErrors: $relationFormData->errors());
            }
        }

        $useTransactions = method_exists($resource, 'useTransactions') && $resource->useTransactions();

        try {
            return (new TransactionManager())->run(
                fn (): EntityObjectSaveResult => $this->persistEntityObject(
                    $resource,
                    $itemId,
                    $scalarFields,
                    $relationFields,
                    $scalarFormData,
                    $relationFormData,
                    $context,
                    $validatedScalars,
                ),
                $useTransactions,
            );
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                return new EntityObjectSaveResult(
                    false,
                    globalErrors: explode('; ', $exception->getMessage()),
                );
            }
            return new EntityObjectSaveResult(
                false,
                globalErrors: ExceptionDiagnostics::toGlobalErrors($exception, 'EntityObject save failed.'),
            );
        }
    }

    /**
     * @param DataManagerResourceContract&object $resource
     * @param list<FieldContract> $scalarFields
     * @param list<RelationField> $relationFields
     * @param array<string,mixed> $validatedScalars
     */
    private function persistEntityObject(
        DataManagerResourceContract $resource,
        mixed $itemId,
        array $scalarFields,
        array $relationFields,
        FormData $scalarFormData,
        FormData $relationFormData,
        DbOperationContext $context,
        array $validatedScalars,
    ): EntityObjectSaveResult {
        $entityObject = $this->loadOrCreateObject($resource, $itemId, $relationFields);

        $this->applyScalarValues($entityObject, $scalarFormData, $resource, $scalarFields, $itemId, $validatedScalars);

        $primaryKey = $resource->getPrimaryKey();
        $deferRelationSync = $this->shouldDeferRelationSync($itemId, $entityObject, $primaryKey);
        if (!$deferRelationSync) {
            $this->syncRelations($resource, $entityObject, $relationFields, $relationFormData, $context);
        }

        $saveResult = $entityObject->save();
        if (method_exists($saveResult, 'isSuccess') && !$saveResult->isSuccess()) {
            throw new RuntimeException(implode('; ', $this->extractSaveErrors($saveResult)));
        }

        if ($deferRelationSync) {
            $this->syncRelations($resource, $entityObject, $relationFields, $relationFormData, $context);
            $saveResult = $entityObject->save();
            if (method_exists($saveResult, 'isSuccess') && !$saveResult->isSuccess()) {
                throw new RuntimeException(implode('; ', $this->extractSaveErrors($saveResult)));
            }
        }

        $savedId = $this->resolveSavedId($saveResult, $entityObject, $primaryKey, $itemId);

        $this->persistUserFieldFiles($resource, $savedId, $scalarFields, $scalarFormData);

        return new EntityObjectSaveResult(true, $savedId);
    }

    /**
     * Persist UserField "file" columns via the DataManager add/update API.
     *
     * Highload-block UF file fields are processed by the DataManager
     * add()/update() layer (which runs CFile::SaveFile on a file array), not by
     * the ORM EntityObject save() path — so these columns are skipped in
     * {@see self::applyScalarValues()} and written here instead.
     *
     * @param DataManagerResourceContract&object $resource
     * @param list<FieldContract> $scalarFields
     */
    private function persistUserFieldFiles(
        DataManagerResourceContract $resource,
        mixed $savedId,
        array $scalarFields,
        FormData $scalarFormData,
    ): void {
        if ($savedId === null || $savedId === '') {
            return;
        }

        $class = $resource->getDataManagerClass();
        if ($class === null || $class === '') {
            return;
        }

        $ufFileColumns = UserFieldFileColumns::forDataManager($class);
        if ($ufFileColumns === []) {
            return;
        }

        $validated = $scalarFormData->validated();
        $updates = [];
        foreach ($scalarFields as $field) {
            $column = $field->getColumn();
            if (isset($ufFileColumns[$column]) && array_key_exists($column, $validated)) {
                $updates[$column] = $validated[$column];
            }
        }

        if ($updates === []) {
            return;
        }

        /** @phpstan-ignore staticMethod.notFound */
        $result = $class::update($savedId, $updates);
        if (method_exists($result, 'isSuccess') && !$result->isSuccess()) {
            throw new RuntimeException(implode('; ', $this->extractSaveErrors($result)));
        }
    }

    /**
     * Enable file-array ORM output on File fields that map to UserField "file"
     * columns (e.g. Highload-block UF_* files), whose save layer rejects a
     * pre-saved integer id.
     *
     * @param DataManagerResourceContract&object $resource
     * @param list<FieldContract> $scalarFields
     */
    private function markUserFieldFileFields(DataManagerResourceContract $resource, array $scalarFields): void
    {
        $class = $resource->getDataManagerClass();
        if ($class === null || $class === '') {
            return;
        }

        $ufFileColumns = UserFieldFileColumns::forDataManager($class);
        if ($ufFileColumns === []) {
            return;
        }

        foreach ($scalarFields as $field) {
            if ($field instanceof File && isset($ufFileColumns[$field->getColumn()])) {
                $field->ormExpectsFileArray(true);
            }
        }
    }

    /**
     * Разворачивает раскладку в плоский список полей.
     *
     * Resource::formFields() возвращает верхнеуровневые layout-компоненты (Tabs, Grid,
     * Column, Box), поэтому программные точки сохранения (createItemResult /
     * updateItemResult, а через них массовые действия грида) получают не поля, а
     * контейнеры. Без разворачивания вызов getColumn() на контейнере роняет операцию.
     *
     * @param iterable<mixed> $fields
     * @return list<FieldContract>
     */
    public function flattenFields(iterable $fields): array
    {
        $flat = [];

        foreach ($fields as $item) {
            if ($item instanceof FieldContract) {
                $flat[] = $item;
                continue;
            }

            if ($item instanceof Tab) {
                if (!$item->isVisible()) {
                    continue;
                }
                foreach ($this->flattenFields($item->getItems()) as $field) {
                    $flat[] = $field;
                }
                continue;
            }

            if ($item instanceof FieldContainerContract) {
                foreach ($item->extractFields() as $field) {
                    $flat[] = $field;
                }
            }
        }

        return $flat;
    }

    /**
     * @param list<FieldContract> $fields
     * @return array{0:list<FieldContract>,1:list<RelationField>}
     */
    public function splitFields(array $fields): array
    {
        $scalar = [];
        $relations = [];

        foreach ($fields as $field) {
            if ($field instanceof RelationField && $this->isRelationPayloadField($field)) {
                $relations[] = $field;
                continue;
            }

            $scalar[] = $field;
        }

        return [$scalar, $relations];
    }

    private function isRelationPayloadField(RelationField $field): bool
    {
        if ($field instanceof BelongsToMany) {
            return $field->isOrmRelationMode();
        }

        return $field->relationName() !== null
            || $field->hasExplicitRelationDefinition();
    }

    /**
     * @param list<FieldContract> $fields
     * @param array<string,mixed> $rawPost
     * @return array<string,mixed>
     */
    private function extractRaw(array $fields, array $rawPost): array
    {
        $raw = [];
        foreach ($fields as $field) {
            $column = $field->getColumn();
            $raw[$column] = $field->serializePostValue($rawPost[$column] ?? null);
        }

        return $raw;
    }

    /**
     * @param DataManagerResourceContract&object $resource
     * @param list<RelationField> $relationFields
     */
    private function loadOrCreateObject(
        DataManagerResourceContract $resource,
        mixed $itemId,
        array $relationFields,
    ): object {
        $dataManagerClass = $resource->getDataManagerClass();
        if ($dataManagerClass === null) {
            throw new RuntimeException('DataManager class is not configured.');
        }

        $select = $this->metadataResolver->relationSelects($dataManagerClass, $relationFields);

        if ($itemId !== null && $itemId !== '') {
            $object = $resource->findObject($itemId, $select);
            if (!is_object($object) || !method_exists($object, 'set') || !method_exists($object, 'save')) {
                throw new RuntimeException('Item was not found.');
            }

            return $object;
        }

        return $resource->newObject();
    }

    /**
     * @param list<FieldContract> $scalarFields
     * @param array<string,mixed> $validatedScalars
     */
    private function applyScalarValues(
        object $entityObject,
        FormData $formData,
        DataManagerResourceContract $resource,
        array $scalarFields,
        mixed $itemId,
        array $validatedScalars = [],
    ): void {
        $primaryKey = $resource->getPrimaryKey();
        $forcedColumns = array_keys($validatedScalars);
        $values = array_merge($formData->validated(), $validatedScalars);

        // UserField "file" columns are written via the DataManager add/update
        // API in persistUserFieldFiles(); the EntityObject save() path does not
        // process them. Skip them here so they are not set twice / lost.
        $ufFileColumns = UserFieldFileColumns::forDataManager((string) $resource->getDataManagerClass());

        if ($scalarFields === []) {
            foreach ($values as $column => $value) {
                $column = (string) $column;
                if ($itemId !== null && $itemId !== '' && $column === $primaryKey) {
                    continue;
                }
                if (isset($ufFileColumns[$column])) {
                    continue;
                }
                $entityObject->set($column, $value);
            }
            return;
        }

        $formContext = array_merge($formData->validated(), $validatedScalars, [
            '_mode' => ($itemId !== null && $itemId !== '') ? 'edit' : 'create',
            '_id' => $itemId ?? '',
            $primaryKey => $itemId,
        ]);
        $readonlyColumns = [];
        foreach ($scalarFields as $field) {
            if ($field->isReadOnlyFor($formContext)) {
                $readonlyColumns[] = $field->getColumn();
            }
        }

        $writableColumns = [];
        foreach ($scalarFields as $field) {
            $writableColumns[] = $field->getColumn();
        }

        foreach ($values as $column => $value) {
            $column = (string) $column;
            if (!in_array($column, $writableColumns, true)) {
                continue;
            }

            if ($itemId !== null && $itemId !== '' && $column === $primaryKey) {
                continue;
            }

            if (in_array($column, $readonlyColumns, true) && !in_array($column, $forcedColumns, true)) {
                continue;
            }

            if (isset($ufFileColumns[$column])) {
                continue;
            }

            $entityObject->set($column, $value);
        }
    }

    /**
     * @param DataManagerResourceContract&object $resource
     * @param list<RelationField> $relationFields
     */
    private function syncRelations(
        DataManagerResourceContract $resource,
        object $entityObject,
        array $relationFields,
        FormData $relationFormData,
        DbOperationContext $context,
    ): void {
        if (method_exists($resource, 'getRelationSyncStrategies')) {
            foreach ($resource->getRelationSyncStrategies() as $strategy) {
                $this->relationSynchronizer->registerStrategy($strategy);
            }
        }

        $dataManagerClass = (string) $resource->getDataManagerClass();

        foreach ($relationFields as $field) {
            $metadata = $this->metadataResolver->resolve($dataManagerClass, $field);
            if ($metadata === null) {
                throw new RuntimeException('Relation metadata could not be resolved for "' . $field->getColumn() . '".');
            }

            $value = $relationFormData->validated()[$field->getColumn()] ?? null;
            $this->relationSynchronizer->sync($entityObject, $field, $metadata, $value, $context);
        }
    }

    private function shouldDeferRelationSync(mixed $itemId, object $entityObject, string $primaryKey): bool
    {
        if ($itemId === null || $itemId === '') {
            return true;
        }

        $ownerId = $this->extractOwnerId($entityObject, $primaryKey);

        return $ownerId === null || $ownerId === '';
    }

    private function extractOwnerId(object $entityObject, string $primaryKey): mixed
    {
        if (method_exists($entityObject, 'getId')) {
            $id = $entityObject->getId();
            if ($id !== null && $id !== '') {
                return $id;
            }
        }

        if (method_exists($entityObject, 'get')) {
            return $entityObject->get($primaryKey);
        }

        return null;
    }

    private function resolveSavedId(object $saveResult, object $entityObject, string $primaryKey, mixed $itemId): mixed
    {
        $savedId = $this->extractOwnerId($entityObject, $primaryKey);
        if ($savedId !== null && $savedId !== '') {
            return $savedId;
        }

        if ($itemId !== null && $itemId !== '') {
            return $itemId;
        }

        return $this->extractSaveResultId($saveResult);
    }

    private function extractSaveResultId(object $saveResult): mixed
    {
        if (!method_exists($saveResult, 'getPrimary')) {
            return null;
        }

        $primary = $saveResult->getPrimary();
        if (!is_array($primary) || $primary === []) {
            return null;
        }

        if (!method_exists($saveResult, 'getId')) {
            return count($primary) === 1 ? reset($primary) : $primary;
        }

        return $saveResult->getId();
    }

    /** @return list<string> */
    private function extractSaveErrors(object $saveResult): array
    {
        if (method_exists($saveResult, 'getErrorMessages')) {
            $messages = $saveResult->getErrorMessages();

            return is_array($messages)
                ? array_values(array_map('strval', $messages))
                : ['EntityObject save failed.'];
        }

        return ['EntityObject save failed.'];
    }
}

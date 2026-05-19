<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
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
     * @param DataManagerResource<DataManager> $resource
     * @param list<FieldContract> $fields
     * @param array<string,mixed> $rawPost
     */
    public function save(
        DataManagerResource $resource,
        mixed $itemId,
        array $fields,
        array $rawPost,
        DbOperationContext $context,
    ): EntityObjectSaveResult {
        [$scalarFields, $relationFields] = $this->splitFields($fields);
        $rawScalar = $this->extractRaw($scalarFields, $rawPost);
        $rawRelations = $this->extractRaw($relationFields, $rawPost);

        $formData = (new DataPipeline())->process($scalarFields, $rawScalar);
        if ($formData->hasErrors()) {
            return new EntityObjectSaveResult(false, fieldErrors: $formData->errors());
        }

        try {
            $entityObject = $this->loadOrCreateObject($resource, $itemId, $relationFields);
            $this->applyScalarValues($entityObject, $formData);
            $this->syncRelations($resource, $entityObject, $relationFields, $rawRelations, $context);

            $saveResult = $entityObject->save();
            if (method_exists($saveResult, 'isSuccess') && !$saveResult->isSuccess()) {
                return new EntityObjectSaveResult(
                    false,
                    globalErrors: $this->extractSaveErrors($saveResult),
                );
            }

            $savedId = method_exists($saveResult, 'getId') ? $saveResult->getId() : $this->extractOwnerId($entityObject, $resource->getPrimaryKey());

            return new EntityObjectSaveResult(true, $savedId);
        } catch (Throwable $exception) {
            return new EntityObjectSaveResult(
                false,
                globalErrors: [$exception->getMessage() !== '' ? $exception->getMessage() : 'EntityObject save failed.'],
            );
        }
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
            if (array_key_exists($column, $rawPost)) {
                $raw[$column] = $field->serializePostValue($rawPost[$column]);
            }
        }

        return $raw;
    }

    /**
     * @param DataManagerResource<DataManager> $resource
     * @param list<RelationField> $relationFields
     */
    private function loadOrCreateObject(DataManagerResource $resource, mixed $itemId, array $relationFields): EntityObject
    {
        $dataManagerClass = $resource->getDataManagerClass();
        if ($dataManagerClass === null) {
            throw new RuntimeException('DataManager class is not configured.');
        }

        $select = $this->metadataResolver->relationSelects($dataManagerClass, $relationFields);

        if ($itemId !== null && $itemId !== '') {
            $object = $resource->findObject($itemId, $select);
            if ($object === null) {
                throw new RuntimeException('Item was not found.');
            }

            return $object;
        }

        if (!method_exists($dataManagerClass, 'createObject')) {
            throw new RuntimeException('DataManager does not support createObject().');
        }

        return $dataManagerClass::createObject();
    }

    private function applyScalarValues(EntityObject $entityObject, FormData $formData): void
    {
        foreach ($formData->validated() as $column => $value) {
            $entityObject->set((string) $column, $value);
        }
    }

    /**
     * @param DataManagerResource<DataManager> $resource
     * @param list<RelationField> $relationFields
     * @param array<string,mixed> $rawRelations
     */
    private function syncRelations(
        DataManagerResource $resource,
        EntityObject $entityObject,
        array $relationFields,
        array $rawRelations,
        DbOperationContext $context,
    ): void {
        $dataManagerClass = (string) $resource->getDataManagerClass();

        foreach ($relationFields as $field) {
            $metadata = $this->metadataResolver->resolve($dataManagerClass, $field);
            if ($metadata === null) {
                throw new RuntimeException('Relation metadata could not be resolved for "' . $field->getColumn() . '".');
            }

            $value = $rawRelations[$field->getColumn()] ?? null;
            $this->relationSynchronizer->sync($entityObject, $field, $metadata, $value, $context);
        }
    }

    private function extractOwnerId(EntityObject $entityObject, string $primaryKey): mixed
    {
        if (method_exists($entityObject, 'getId')) {
            $id = $entityObject->getId();
            if ($id !== null) {
                return $id;
            }
        }

        return $entityObject->get($primaryKey);
    }

    /** @return list<string> */
    private function extractSaveErrors(object $saveResult): array
    {
        if (method_exists($saveResult, 'getErrorMessages')) {
            return array_values(array_map('strval', $saveResult->getErrorMessages()));
        }

        return ['EntityObject save failed.'];
    }
}

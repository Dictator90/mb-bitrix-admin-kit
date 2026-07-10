<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Random;
use JsonException;
use MB\Bitrix\AdminKit\Manager\AssetManager;

/**
 * Interactive Yandex Maps marker editor. The stored value is a single JSON
 * object holding the map center/zoom and a list of colored, titled markers:
 *
 *   YandexMap::make('Карта проектов', 'home_map')
 *       ->center(55.751244, 37.618423)
 *       ->zoom(3)
 *       ->apiKey('...')
 *       ->height(460)
 *
 * The marker list below the map is the source of truth for the hidden input
 * and stays fully editable even when the Yandex Maps script fails to load or
 * no API key is configured — the map itself is a progressive enhancement on
 * top of it. Every change (map click, drag, row edit, add/remove) rebuilds
 * the JSON payload via a single client-side sync() call.
 */
class YandexMap extends Field
{
    protected float $centerLat = 55.751244;

    protected float $centerLng = 37.618423;

    protected int $defaultZoom = 3;

    protected ?string $apiKey = null;

    protected int $height = 460;

    public function center(float $lat, float $lon): static
    {
        $this->centerLat = $lat;
        $this->centerLng = $lon;

        return $this;
    }

    public function zoom(int $zoom): static
    {
        $this->defaultZoom = $zoom;

        return $this;
    }

    public function apiKey(?string $key): static
    {
        $this->apiKey = $key;

        return $this;
    }

    public function height(int $px): static
    {
        $this->height = $px;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    public function renderFormField(mixed $value = null): string
    {
        $data = $this->normalizeMapData($this->resolveOwnValue($value));
        $readonly = $this->formReadonlyAttr() !== '';

        $uid = 'adminkit_ymap_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $this->column) . '_' . Random::getString(6);
        $name = htmlspecialcharsbx($this->column);
        $label = htmlspecialcharsbx($this->label);
        $hint = $this->renderHint();
        $jsonValue = htmlspecialcharsbx($this->serializeOptionValue($data));

        $rowsHtml = '';
        foreach ($data['markers'] as $marker) {
            $rowsHtml .= $this->renderMarkerRow($marker, $readonly);
        }

        $addButton = $readonly
            ? ''
            : <<<HTML
            <button type="button" class="ui-btn ui-btn-light-border ui-btn-sm ui-btn-icon-add adminkit-ymap-add" data-uid="{$uid}">Добавить метку</button>
            HTML;

        $this->loadYandexMapsApi();

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-yandex-map');
        $readonlyAttr = $readonly ? '1' : '0';

        return <<<HTML
        <div{$wrapperAttrs}>
            <div class="adminkit-ymap-label">{$label}{$hint}</div>
            <input type="hidden" name="{$name}" id="{$uid}_value" value="{$jsonValue}">
            <div class="adminkit-ymap" id="{$uid}" data-readonly="{$readonlyAttr}"
                data-center-lat="{$data['center']['lat']}" data-center-lng="{$data['center']['lng']}" data-zoom="{$data['zoom']}">
                <div class="adminkit-ymap-canvas" style="height: {$this->height}px"></div>
                <div class="adminkit-ymap-meta">
                    <label class="adminkit-ymap-meta-item">Центр (lat)
                        <div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">
                            <input type="text" class="ui-ctl-element adminkit-ymap-center-lat" value="{$data['center']['lat']}" readonly>
                        </div>
                    </label>
                    <label class="adminkit-ymap-meta-item">Центр (lng)
                        <div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">
                            <input type="text" class="ui-ctl-element adminkit-ymap-center-lng" value="{$data['center']['lng']}" readonly>
                        </div>
                    </label>
                    <label class="adminkit-ymap-meta-item">Zoom
                        <div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">
                            <input type="text" class="ui-ctl-element adminkit-ymap-zoom" value="{$data['zoom']}" readonly>
                        </div>
                    </label>
                </div>
                <div class="adminkit-ymap-rows">{$rowsHtml}</div>
                {$addButton}
            </div>
        </div>
        {$this->renderStyleOnce()}
        {$this->renderScriptOnce()}
        HTML;
    }

    /**
     * @param array{lat: float, lng: float, color: string, title: string, description: string} $marker
     */
    protected function renderMarkerRow(array $marker, bool $readonly): string
    {
        $color = htmlspecialcharsbx($marker['color']);
        $title = htmlspecialcharsbx($marker['title']);
        $description = htmlspecialcharsbx($marker['description']);
        $lat = htmlspecialcharsbx((string)$marker['lat']);
        $lng = htmlspecialcharsbx((string)$marker['lng']);
        $disabledAttr = $readonly ? ' disabled' : '';

        $removeButton = $readonly
            ? ''
            : <<<HTML
            <button type="button" class="ui-btn ui-btn-icon-remove ui-btn-link ui-btn-sm adminkit-ymap-remove" title="Удалить"></button>
            HTML;

        return <<<HTML
        <div class="adminkit-ymap-row" data-ymap-row>
            <input type="color" class="adminkit-ymap-row-color" value="{$color}"{$disabledAttr}>
            <div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">
                <input type="text" class="ui-ctl-element adminkit-ymap-row-title" placeholder="Заголовок" value="{$title}"{$disabledAttr}>
            </div>
            <div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">
                <input type="text" class="ui-ctl-element adminkit-ymap-row-desc" placeholder="Описание" value="{$description}"{$disabledAttr}>
            </div>
            <div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">
                <input type="text" class="ui-ctl-element adminkit-ymap-row-lat" value="{$lat}" readonly>
            </div>
            <div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">
                <input type="text" class="ui-ctl-element adminkit-ymap-row-lng" value="{$lng}" readonly>
            </div>
            <div class="adminkit-ymap-row-action">{$removeButton}</div>
        </div>
        HTML;
    }

    /**
     * Resolves this field's own stored value out of a form-data map keyed by
     * columns, or passes the value through as-is when it already IS the
     * field's value (a JSON object stored in options/properties).
     */
    protected function resolveOwnValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists($this->column, $value)) {
            return $value[$this->column];
        }

        return $value ?? $this->value ?? $this->default;
    }

    /**
     * @return array{center: array{lat: float, lng: float}, zoom: int, markers: list<array{lat: float, lng: float, color: string, title: string, description: string}>}
     */
    protected function normalizeMapData(mixed $value): array
    {
        if (is_string($value)) {
            $value = $this->decodeJson($value) ?? [];
        }

        $source = is_array($value) ? $value : [];
        $center = is_array($source['center'] ?? null) ? $source['center'] : [];

        $markers = [];
        foreach ((is_array($source['markers'] ?? null) ? $source['markers'] : []) as $marker) {
            $normalizedMarker = $this->normalizeMarker($marker);
            if ($normalizedMarker !== null) {
                $markers[] = $normalizedMarker;
            }
        }

        return [
            'center' => [
                'lat' => $this->toFloat($center['lat'] ?? null, $this->centerLat),
                'lng' => $this->toFloat($center['lng'] ?? null, $this->centerLng),
            ],
            'zoom' => $this->toInt($source['zoom'] ?? null, $this->defaultZoom),
            'markers' => $markers,
        ];
    }

    /**
     * @return array{lat: float, lng: float, color: string, title: string, description: string}|null
     */
    protected function normalizeMarker(mixed $marker): ?array
    {
        if (!is_array($marker) || !is_numeric($marker['lat'] ?? null) || !is_numeric($marker['lng'] ?? null)) {
            return null;
        }

        return [
            'lat' => (float)$marker['lat'],
            'lng' => (float)$marker['lng'],
            'color' => $this->normalizeColor($marker['color'] ?? null),
            'title' => (string)($marker['title'] ?? ''),
            'description' => (string)($marker['description'] ?? ''),
        ];
    }

    protected function normalizeColor(mixed $value): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : '#de172f';
    }

    protected function toFloat(mixed $value, float $fallback): float
    {
        return is_numeric($value) ? (float)$value : $fallback;
    }

    protected function toInt(mixed $value, int $fallback): int
    {
        return is_numeric($value) ? (int)$value : $fallback;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    protected function decodeJson(string $value): ?array
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array{center: array{lat: float, lng: float}, zoom: int, markers: list<array<string,mixed>>}
     */
    protected function defaultMapData(): array
    {
        return $this->normalizeMapData(null);
    }

    /**
     * Resolves the effective API key: explicit $this->apiKey if set, otherwise
     * from Bitrix fileman option (yandex_map_api_key), otherwise empty string.
     */
    protected function resolveApiKey(): string
    {
        if ($this->apiKey !== null && $this->apiKey !== '') {
            return $this->apiKey;
        }

        return Option::get('fileman', 'yandex_map_api_key', '');
    }

    protected function yandexApiUrl(): string
    {
        $url = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';
        $apiKey = $this->resolveApiKey();
        if ($apiKey !== '') {
            $url .= '&apikey=' . rawurlencode($apiKey);
        }

        return $url;
    }

    protected function loadYandexMapsApi(): void
    {
        static $loadedUrls = [];
        $url = $this->yandexApiUrl();
        if (isset($loadedUrls[$url])) {
            return;
        }
        $loadedUrls[$url] = true;

        (new AssetManager())->addJs($url)->load();
    }

    public function normalize(mixed $value): mixed
    {
        return $this->normalizeMapData($value);
    }

    public function serializePostValue(mixed $value): mixed
    {
        return $this->normalize($value);
    }

    public function serializeOptionValue(mixed $value): string
    {
        $data = $this->normalizeMapData($value);

        try {
            return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return '{"center":{"lat":' . $this->centerLat . ',"lng":' . $this->centerLng . '},"zoom":' . $this->defaultZoom . ',"markers":[]}';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function unserializeOptionValue(string $value): array
    {
        return $this->normalizeMapData($this->decodeJson($value) ?? []);
    }

    public function previewValue(mixed $value): string
    {
        $count = count($this->normalizeMapData($value)['markers']);

        return $count . ' ' . $this->pluralizeMarkers($count);
    }

    protected function pluralizeMarkers(int $count): string
    {
        $mod100 = $count % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'меток';
        }

        return match ($count % 10) {
            1 => 'метка',
            2, 3, 4 => 'метки',
            default => 'меток',
        };
    }

    public function runValidation(mixed $value, array $data = []): array
    {
        return parent::runValidation($this->normalizeMapData($value), $data);
    }

    protected function renderStyleOnce(): string
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        return <<<'HTML'
        <style>
        .adminkit-ymap-label {
            font-weight: 600;
            margin-bottom: 6px;
        }
        .adminkit-ymap {
            border: 1px solid #e1e7ec;
            border-radius: var(--ui-border-radius-md, 8px);
            padding: 12px;
        }
        .adminkit-ymap-canvas {
            width: 100%;
            border-radius: var(--ui-border-radius-md, 8px);
            background: #eef1f4;
        }
        .adminkit-ymap-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .adminkit-ymap-meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 12px;
            color: var(--ui-color-base-70, #828b95);
        }
        .adminkit-ymap-meta-item .ui-ctl-inline {
            width: 120px;
        }
        .adminkit-ymap-rows {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 14px;
            max-height: 300px;
            overflow: auto;
        }
        .adminkit-ymap-rows:empty::after {
            content: "Меток нет";
            display: block;
            padding: 16px;
            text-align: center;
            color: var(--ui-color-base-70, #828b95);
            font-size: 13px;
        }
        .adminkit-ymap-row {
            display: grid;
            grid-template-columns: 34px 170px 220px 90px 90px auto;
            gap: 8px;
            align-items: center;
        }
        .adminkit-ymap-row .ui-ctl-inline {
            width: 100%;
        }
        .adminkit-ymap-row-color {
            width: 34px;
            height: 30px;
            padding: 0;
            border: none;
            background: none;
            cursor: pointer;
        }
        .adminkit-ymap-add {
            margin-top: 12px;
        }
        @media (max-width: 782px) {
            .adminkit-ymap-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        HTML;
    }

    protected function renderScriptOnce(): string
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        return <<<'HTML'
        <script>
        (function () {
            if (window.__adminKitYandexMapInit) { return; }
            window.__adminKitYandexMapInit = true;

            var placemarksByRow = new WeakMap();

            function escapeAttr(value) {
                var div = document.createElement('div');
                div.textContent = value === null || value === undefined ? '' : String(value);
                return div.innerHTML.replace(/"/g, '&quot;');
            }

            function buildPlacemarkOptions(color) {
                return { preset: 'islands#circleIcon', iconColor: color || '#de172f' };
            }

            function readRowState(row) {
                return {
                    lat: parseFloat(row.querySelector('.adminkit-ymap-row-lat').value) || 0,
                    lng: parseFloat(row.querySelector('.adminkit-ymap-row-lng').value) || 0,
                    color: row.querySelector('.adminkit-ymap-row-color').value || '#de172f',
                    title: row.querySelector('.adminkit-ymap-row-title').value || '',
                    description: row.querySelector('.adminkit-ymap-row-desc').value || ''
                };
            }

            function createRowElement(readonly, state) {
                var disabledAttr = readonly ? ' disabled' : '';
                var wrapper = document.createElement('div');
                wrapper.innerHTML =
                    '<div class="adminkit-ymap-row" data-ymap-row>' +
                        '<input type="color" class="adminkit-ymap-row-color" value="' + escapeAttr(state.color) + '"' + disabledAttr + '>' +
                        '<div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">' +
                            '<input type="text" class="ui-ctl-element adminkit-ymap-row-title" placeholder="Заголовок" value="' + escapeAttr(state.title) + '"' + disabledAttr + '>' +
                        '</div>' +
                        '<div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">' +
                            '<input type="text" class="ui-ctl-element adminkit-ymap-row-desc" placeholder="Описание" value="' + escapeAttr(state.description) + '"' + disabledAttr + '>' +
                        '</div>' +
                        '<div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">' +
                            '<input type="text" class="ui-ctl-element adminkit-ymap-row-lat" value="' + state.lat + '" readonly>' +
                        '</div>' +
                        '<div class="ui-ctl-inline ui-ctl-textbox ui-ctl-sm">' +
                            '<input type="text" class="ui-ctl-element adminkit-ymap-row-lng" value="' + state.lng + '" readonly>' +
                        '</div>' +
                        '<div class="adminkit-ymap-row-action">' +
                            (readonly ? '' : '<button type="button" class="ui-btn ui-btn-icon-remove ui-btn-link ui-btn-sm adminkit-ymap-remove" title="Удалить"></button>') +
                        '</div>' +
                    '</div>';
                return wrapper.firstElementChild;
            }

            function collectState(container) {
                var rows = container.querySelectorAll('.adminkit-ymap-rows > [data-ymap-row]');
                var markers = [];
                rows.forEach(function (row) {
                    markers.push(readRowState(row));
                });

                return {
                    center: {
                        lat: parseFloat(container.querySelector('.adminkit-ymap-center-lat').value) || 0,
                        lng: parseFloat(container.querySelector('.adminkit-ymap-center-lng').value) || 0
                    },
                    zoom: parseInt(container.querySelector('.adminkit-ymap-zoom').value, 10) || 0,
                    markers: markers
                };
            }

            function sync(container) {
                var valueInput = document.getElementById(container.id + '_value');
                if (valueInput) {
                    valueInput.value = JSON.stringify(collectState(container));
                }
            }

            function bindRowEvents(container, row) {
                var colorInput = row.querySelector('.adminkit-ymap-row-color');
                var titleInput = row.querySelector('.adminkit-ymap-row-title');
                var descInput = row.querySelector('.adminkit-ymap-row-desc');
                var removeBtn = row.querySelector('.adminkit-ymap-remove');

                colorInput.addEventListener('input', function () {
                    var placemark = placemarksByRow.get(row);
                    if (placemark) {
                        placemark.options.set(buildPlacemarkOptions(colorInput.value));
                    }
                    sync(container);
                });

                titleInput.addEventListener('input', function () {
                    var placemark = placemarksByRow.get(row);
                    if (placemark) {
                        placemark.properties.set('hintContent', titleInput.value);
                    }
                    sync(container);
                });

                descInput.addEventListener('input', function () {
                    var placemark = placemarksByRow.get(row);
                    if (placemark) {
                        placemark.properties.set('balloonContent', descInput.value);
                    }
                    sync(container);
                });

                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        var placemark = placemarksByRow.get(row);
                        var map = container.__ymapInstance;
                        if (placemark && map) {
                            map.geoObjects.remove(placemark);
                        }
                        row.remove();
                        sync(container);
                    });
                }
            }

            function addMarker(container, state, existingRow) {
                var readonly = container.dataset.readonly === '1';
                var row = existingRow;
                if (!row) {
                    row = createRowElement(readonly, state);
                    container.querySelector('.adminkit-ymap-rows').appendChild(row);
                    bindRowEvents(container, row);
                }

                var map = container.__ymapInstance;
                if (map && window.ymaps) {
                    var placemark = new ymaps.Placemark([state.lat, state.lng], {
                        hintContent: state.title,
                        balloonContent: state.description
                    }, buildPlacemarkOptions(state.color));
                    placemark.options.set('draggable', !readonly);
                    map.geoObjects.add(placemark);
                    placemarksByRow.set(row, placemark);

                    placemark.events.add('dragend', function () {
                        var coords = placemark.geometry.getCoordinates();
                        row.querySelector('.adminkit-ymap-row-lat').value = coords[0].toFixed(6);
                        row.querySelector('.adminkit-ymap-row-lng').value = coords[1].toFixed(6);
                        sync(container);
                    });
                }

                sync(container);

                return row;
            }

            function init(container) {
                var readonly = container.dataset.readonly === '1';
                var existingRows = Array.prototype.slice.call(
                    container.querySelectorAll('.adminkit-ymap-rows > [data-ymap-row]')
                );

                existingRows.forEach(function (row) {
                    bindRowEvents(container, row);
                });

                var addBtn = document.querySelector('.adminkit-ymap-add[data-uid="' + container.id + '"]');
                if (addBtn) {
                    addBtn.addEventListener('click', function () {
                        addMarker(container, {
                            lat: parseFloat(container.querySelector('.adminkit-ymap-center-lat').value) || 0,
                            lng: parseFloat(container.querySelector('.adminkit-ymap-center-lng').value) || 0,
                            color: '#de172f',
                            title: '',
                            description: ''
                        });
                    });
                }

                if (window.ymaps) {
                    ymaps.ready(function () {
                        var map = new ymaps.Map(container.querySelector('.adminkit-ymap-canvas'), {
                            center: [
                                parseFloat(container.dataset.centerLat),
                                parseFloat(container.dataset.centerLng)
                            ],
                            zoom: parseInt(container.dataset.zoom, 10) || 3,
                            controls: ['zoomControl']
                        });
                        container.__ymapInstance = map;

                        existingRows.forEach(function (row) {
                            addMarker(container, readRowState(row), row);
                        });

                        if (!readonly) {
                            map.events.add('click', function (e) {
                                var coords = e.get('coords');
                                addMarker(container, {
                                    lat: coords[0],
                                    lng: coords[1],
                                    color: '#de172f',
                                    title: '',
                                    description: ''
                                });
                            });
                        }

                        map.events.add('boundschange', function (e) {
                            var newCenter = e.get('newCenter');
                            container.querySelector('.adminkit-ymap-center-lat').value = newCenter[0].toFixed(6);
                            container.querySelector('.adminkit-ymap-center-lng').value = newCenter[1].toFixed(6);
                            container.querySelector('.adminkit-ymap-zoom').value = e.get('newZoom');
                            sync(container);
                        });
                    });
                }

                sync(container);
            }

            function boot() {
                document.querySelectorAll('.adminkit-ymap').forEach(init);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
        </script>
        HTML;
    }
}

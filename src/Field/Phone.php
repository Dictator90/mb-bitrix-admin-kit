<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Field\Concerns\RepeatableScalar;

/**
 * Телефонное поле (`input[type=tel]`) с маской ввода.
 *
 * Одиночное по умолчанию; {@see multiple()} включает повторяемый список
 * телефонов с добавлением/удалением (значение — плоский массив строк).
 *
 * Маска страно-независимая. Шаблон: `9` — слот под цифру, прочие символы —
 * литералы (в т.ч. цифры кода страны, например `+7`/`+1`). Можно задать
 * НЕСКОЛЬКО масок — тогда JS сам выбирает подходящую по коду страны, который
 * ввёл пользователь (по литеральному префиксу маски), а если совпадений нет —
 * берёт первую маску. Ведущий код страны, совпадающий с маской, срезается;
 * длина ввода ограничена ёмкостью маски (лишние цифры не влезают — атрибут
 * `maxlength` + обрезка хвоста), поэтому «прокрутки» уже введённого номера нет.
 *
 * Управление маской:
 *  - `->mask('+1 (999) 999-9999')` — одна маска;
 *  - `->mask([Phone::MASK_RU, Phone::MASK_US])` — несколько (авто-выбор);
 *  - `->withoutMask()` / `->mask(null)` — отключить.
 */
class Phone extends Field
{
    use RepeatableScalar;

    public const MASK_RU = '+7 (999) 999-99-99';
    public const MASK_US = '+1 (999) 999-9999';

    /** Свободный международный ввод (E.164, до 15 цифр, без группировки). */
    public const MASK_INTL = '+999999999999999';

    /** @var array<int, string> */
    public const DEFAULT_MASKS = [self::MASK_RU, self::MASK_US];

    /** @var string|array<int, string>|null */
    protected string|array|null $mask = self::DEFAULT_MASKS;

    /**
     * Задаёт маску(и) или отключает её (null). Строка — одна маска, массив —
     * несколько с авто-выбором по коду страны.
     *
     * @param string|array<int, string>|null $mask
     */
    public function mask(string|array|null $mask): static
    {
        $this->mask = $mask;

        return $this;
    }

    /** Полностью отключить маску ввода. */
    public function withoutMask(): static
    {
        $this->mask = null;

        return $this;
    }

    /**
     * Приводит телефон к каноничному виду для хранения: `+` (если был во вводе)
     * и только цифры. Пример: `+7 (999) 999-99-99` → `+79999999999`. На вывод
     * значение снова форматируется маской. Пустое/без цифр → null.
     */
    protected function normalizeScalar(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $hasPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        return ($hasPlus ? '+' : '') . $digits;
    }

    protected function scalarInputType(): string
    {
        return 'tel';
    }

    protected function scalarInputExtraAttrs(): string
    {
        $attrs = ' inputmode="tel"';

        $masks = $this->normalizedMasks();
        if ($masks !== []) {
            $encoded = count($masks) === 1
                ? $masks[0]
                : (string)json_encode(array_values($masks), JSON_UNESCAPED_UNICODE);

            $attrs .= ' data-phone-mask="' . htmlspecialcharsbx($encoded) . '"';

            $maxLength = max(array_map('strlen', $masks));
            $attrs .= ' maxlength="' . $maxLength . '"';
        }

        return $attrs;
    }

    protected function defaultAddButtonLabel(): string
    {
        return 'Добавить телефон';
    }

    protected function fieldAssets(): string
    {
        return $this->normalizedMasks() === [] ? '' : $this->renderMaskScriptOnce();
    }

    /**
     * @return array<int, string>
     */
    protected function normalizedMasks(): array
    {
        $masks = is_array($this->mask) ? $this->mask : ($this->mask === null ? [] : [$this->mask]);

        $result = [];
        foreach ($masks as $mask) {
            $mask = trim((string)$mask);
            if ($mask !== '') {
                $result[] = $mask;
            }
        }

        return array_values($result);
    }

    protected function renderMaskScriptOnce(): string
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        return <<<'HTML'
        <script>
        (function () {
            if (window.__adminKitPhoneMask) { return; }
            window.__adminKitPhoneMask = true;

            function slotCount(mask) {
                var n = 0;
                for (var i = 0; i < mask.length; i++) {
                    if (mask[i] === '9') { n++; }
                }
                return n;
            }

            function fixedPrefix(mask) {
                var p = '';
                for (var i = 0; i < mask.length; i++) {
                    if (mask[i] === '9') { break; }
                    if (mask[i] >= '0' && mask[i] <= '9') { p += mask[i]; }
                }
                return p;
            }

            function parseMasks(attr) {
                attr = attr || '';
                if (attr.charAt(0) === '[') {
                    try {
                        var arr = JSON.parse(attr);
                        if (Array.isArray(arr)) {
                            return arr.filter(function (m) { return typeof m === 'string' && m !== ''; });
                        }
                    } catch (e) { /* not JSON — treat as single mask */ }
                }
                return attr ? [attr] : [];
            }

            function pickMask(masks, digitStr) {
                var best = null, bestLen = -1;
                for (var i = 0; i < masks.length; i++) {
                    var fp = fixedPrefix(masks[i]);
                    if (fp !== '' && digitStr.indexOf(fp) === 0 && fp.length > bestLen) {
                        best = masks[i];
                        bestLen = fp.length;
                    }
                }
                return best || masks[0];
            }

            function format(raw, masks) {
                if (!masks.length) { return raw; }

                var digits = (String(raw).match(/\d/g) || []);
                var digitStr = digits.join('');
                var mask = pickMask(masks, digitStr);
                var fp = fixedPrefix(mask);

                if (fp !== '' && digitStr.indexOf(fp) === 0) {
                    digits = digits.slice(fp.length);
                }

                var need = slotCount(mask);
                if (digits.length > need) {
                    digits = digits.slice(0, need);
                }

                var res = '';
                var di = 0;
                for (var i = 0; i < mask.length && di < digits.length; i++) {
                    if (mask[i] === '9') {
                        res += digits[di++];
                    } else {
                        res += mask[i];
                    }
                }
                return res;
            }

            function apply(el) {
                var masks = parseMasks(el.getAttribute('data-phone-mask'));
                if (!masks.length) { return; }
                var formatted = format(el.value, masks);
                if (el.value !== formatted) {
                    el.value = formatted;
                }
            }

            document.addEventListener('input', function (e) {
                var el = e.target;
                if (el && el.matches && el.matches('input[data-phone-mask]')) {
                    apply(el);
                }
            });

            function initAll() {
                document.querySelectorAll('input[data-phone-mask]').forEach(apply);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAll);
            } else {
                initAll();
            }
        })();
        </script>
        HTML;
    }
}

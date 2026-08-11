import {ValueItem} from "./valueitem";
import {Type, Dom, Tag, Loc, bind} from 'main.core';

export class ValueItemCollection
{
    #container: HTMLElement;
    #items: Array;

    constructor(valueItems: Array <ValueItem> = []) {
        this.#items = valueItems;
        this.#fillContainer();
    }

    /**
     * Добавляет скрытый инпут значения, если такого значения ещё нет.
     *
     * Проверка обязательна: значение попадает в коллекцию из двух независимых
     * источников — `DialogSelector` перебирает `dialog.selectedItems` при
     * инициализации, а событие `onTagAdd` срабатывает ещё и на предвыбранные
     * теги, которые `TagSelector` восстанавливает из тех же selectedItems.
     * Без проверки каждое сохранение формы удваивало сохранённый список.
     */
    add(item: ValueItem)
    {
        if (this.get(item.getValue()) !== null) {
            return;
        }

        this.#items.push(item);
        Dom.append(item.getNode(), this.#container)
    }

    /**
     * Значения сравниваются как строки: из `dialog.selectedItems` приходит `id`
     * сущности, из `onTagAdd` — `tag.getId()`, и для числовых ID это могут быть
     * число и строка соответственно.
     */
    get(value: string|number): ?ValueItem
    {
        let key = String(value);

        return this.#items.find((item: ValueItem) => String(item.getValue()) === key) ?? null;
    }

    delete(value: string|number)
    {
        let key = String(value);

        this.#items = this.#items.filter((item: ValueItem) => {
            if (String(item.getValue()) !== key) {
                return true;
            }

            Dom.remove(item.getNode());

            return false;
        });
    }

    /**
     * Переупорядочивает скрытые инпуты согласно переданному списку значений.
     * Значения, которых нет в списке, сохраняются в конце в исходном порядке.
     *
     * @param {Array<string|number>} orderedValues
     */
    reorder(orderedValues: Array)
    {
        let byValue = new Map(this.#items.map((item: ValueItem) => [String(item.getValue()), item]));
        let ordered = [];

        orderedValues.forEach((value) => {
            let key = String(value);
            if (byValue.has(key)) {
                ordered.push(byValue.get(key));
                byValue.delete(key);
            }
        });

        byValue.forEach((item: ValueItem) => ordered.push(item));

        this.#items = ordered;
        ordered.forEach((item: ValueItem) => Dom.append(item.getNode(), this.#container));
    }

    #fillContainer()
    {
        this.#container = Dom.create('div', {
            attrs: {
                class: 'ui-tag-selector-input-container'
            }
        });
    }

    renderTo(node: HTMLElement)
    {
        Dom.append(this.#container, node);
    }
}

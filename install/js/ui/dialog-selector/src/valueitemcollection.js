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

    add(item: ValueItem)
    {
        this.#items.push(item);
        Dom.append(item.getNode(), this.#container)
    }

    get(value: string|number): ?ValueItem
    {
        this.#items.forEach((e: ValueItem) => {
            return e.getValue === value;
        });

        return null;
    }

    delete(value: string|number)
    {
        this.#items.forEach((e: ValueItem, i: number) => {
            if (e.getValue() === value) {
                Dom.remove(e.getNode());
                this.#items.splice(i, 1);
            }
        });
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

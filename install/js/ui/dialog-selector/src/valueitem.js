import {ValueOptionsType} from "./options";
import {Type, Dom, Tag, Loc, bind} from 'main.core';

export class ValueItem
{
    #name: string;
    #value: string|number;
    #node: HTMLElement;

    constructor(options: ValueOptionsType)
    {
        this.#name = options.multiple ? options.name + '[]' : options.name;
        this.#value = options.value;

        this.#fillNode();
    }

    getNode(): HTMLElement
    {
        return this.#node;
    }

    getValue(): string|number
    {
        return this.#value;
    }

    #fillNode(): void
    {
        this.#node = Dom.create('input', {
            attrs: {
                type: 'hidden',
                name: this.#name,
                value: this.#value
            }
        })
    }
}

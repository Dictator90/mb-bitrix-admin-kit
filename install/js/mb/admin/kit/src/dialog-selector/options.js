import {Entity, ItemOptions} from "ui.entity-selector";

export type DialogSelectorOptionsType = {
    target: HTMLElement|string,
    name: string,
    items?: Array<ItemOptions>,
    dialog: Array,
    selected?: Array|number|string,
    multiple: boolean,
    readonly?: boolean,
    sortable?: boolean
}

export type ValueOptionsType = {
    name: string,
    multiple: boolean,
    value: string|number
}

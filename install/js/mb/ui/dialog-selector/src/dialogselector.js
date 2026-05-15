import {TagSelector, Dialog} from 'ui.entity-selector';
import {DialogSelectorOptionsType} from "./options";
import {Type, Dom, Tag, Loc, bind} from 'main.core';
import {ValueItemCollection} from "./valueitemcollection";
import {ValueItem} from "./valueitem";
import './css/style.css';

/**
 * @namespace MB.UI
 */
export class DialogSelector
{
    name: string;
    multiple: boolean = false;

    #target: HTMLElement;

    #inputValueCollection: ValueItemCollection;
    entitySelector: TagSelector;
    entityDialog: null|Dialog;

    constructor(options: DialogSelectorOptionsType)
    {
        this.#fillOptions(options);
    }

    #fillOptions(options: DialogSelectorOptionsType)
    {
        this.name = options.name;
        this.multiple = options.multiple ?? false;

        if (Type.isDomNode(options.target)) {
            this.#target = options.target;
        } else if (Type.isStringFilled(options.target)) {
            let target = document.querySelector(options.target);
            if (Type.isDomNode(target)) {
                this.#target = target;
            }
        }

        if (!this.#target) {
            throw new Error('container must be HTMLElement');
        }

        this.#inputValueCollection = new ValueItemCollection();
        this.entitySelector = (new TagSelector(this.#getTagSelectorOptions(options)));
        this.entityDialog = this.entitySelector.getDialog();
        this.#setSelectedInputs();
    }

    #setSelectedInputs()
    {
        this.entityDialog.selectedItems.forEach(e => {
            this.#inputValueCollection.add(
                new ValueItem({
                    name: this.name,
                    multiple: this.multiple,
                    value: e.value?.id || e.id,
                })
            );
        });
    }

    #getTagSelectorOptions(options: DialogSelectorOptionsType)
    {
        let tagSelectorOptions = {
            id: 'mb-ui-selector-' + options.name,
            context: 'MB_SETTINGS'
        }

        tagSelectorOptions.multiple = options.multiple ?? false;
        tagSelectorOptions.items = options.items ?? [];
        tagSelectorOptions.dialogOptions = options.dialog ?? null;
        tagSelectorOptions.deselectable = options.deselectable ?? false;
        tagSelectorOptions.readonly = options.readonly ?? false;
        tagSelectorOptions.locked = options.locked ?? false;

        tagSelectorOptions.events = {
            onTagAdd: (event) => {
                let {tag} = event.getData();
                this.#inputValueCollection.add(
                    new ValueItem({
                        name: this.name,
                        multiple: this.multiple,
                        value: tag.getId(),
                    })
                );
            },
            onTagRemove: (event) => {
                let {tag} = event.getData();
                this.#inputValueCollection.delete(tag.getId());
            }
        }

        return tagSelectorOptions;
    }

    render()
    {
        if (Type.isDomNode(this.#target)) {
            this.#inputValueCollection.renderTo(this.#target);
            this.entitySelector.renderTo(this.#target);
        }

        return null;
    }

    static buildFromSelect(targetNode: HTMLElement|string): DialogSelector
    {
        let target = null;
        if (Type.isDomNode(targetNode)) {
            target = targetNode;
        } else if (Type.isStringFilled(targetNode)) {
            target = document.querySelector(targetNode);
        }

        if (!Type.isDomNode(target)) {
            throw new Error(`${target} is not Dom Node`);
        }
        if (target.nodeName !== 'SELECT') {
            throw new Error(`target type must be 'select'`);
        }
        let options = DialogSelector.#parseSelectNode(target);
        options.target = Dom.create('div', {
            attrs: {
                id: (options.id || options.name) + '_dialogselector'
            }
        });
        Dom.insertAfter(options.target, target);
        Dom.remove(target);

        return new DialogSelector(options);
    }

    static #parseSelectNode(target: HTMLElement)
    {
        let items = [];
        Array.prototype.forEach.call(target.options, (option) => {
            items.push({
                id: option.value,
                entityId: 'select-custom',
                title: option.textContent,
                selected: option.selected,
                tabs: 'select-tab'
            })
        });

        return {
            name: target.name,
            multiple: target.multiple,
            id: target.id || null,
            dialog: {
                items: items,
                tabs: [
                    {
                        id: 'select-tab',
                        title: 'Значения'
                    }
                ],
                dropdownMode: true,
            }
        }
    }
}

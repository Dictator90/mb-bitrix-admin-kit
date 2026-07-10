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
    sortable: boolean = false;

    #target: HTMLElement;
    #sortableBound: boolean = false;

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
        this.sortable = (options.sortable ?? false) && this.multiple;

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
                this.#refreshDraggable();
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
            this.#setupSortable();
        }

        return null;
    }

    /**
     * Помечает чипсы выбранных элементов как draggable (для новых тегов, добавленных
     * после инициализации, вызывается повторно из onTagAdd).
     */
    #refreshDraggable()
    {
        if (!this.sortable) {
            return;
        }

        this.entitySelector.getTags().forEach((tag) => {
            let node = tag.getContainer();
            if (Type.isDomNode(node) && node.getAttribute('draggable') !== 'true') {
                node.setAttribute('draggable', 'true');
                Dom.addClass(node, 'mb-adminkit-sortable-tag');
            }
        });
    }

    /**
     * Включает перетаскивание чипсов для смены порядка. При завершении перетаскивания
     * порядок скрытых инпутов синхронизируется с DOM-порядком чипсов.
     */
    #setupSortable()
    {
        if (!this.sortable || this.#sortableBound) {
            return;
        }

        let container = this.entitySelector.getItemsContainer();
        if (!Type.isDomNode(container)) {
            return;
        }

        this.#sortableBound = true;
        this.#refreshDraggable();

        let dragged = null;

        bind(container, 'dragstart', (e) => {
            let item = e.target.closest('.ui-tag-selector-item');
            if (!item || !container.contains(item)) {
                return;
            }
            dragged = item;
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', ''); } catch (err) { /* IE */ }
            Dom.addClass(item, 'mb-adminkit-sortable-tag--dragging');
        });

        bind(container, 'dragover', (e) => {
            if (!dragged) {
                return;
            }
            let over = e.target.closest('.ui-tag-selector-item');
            if (!over || over === dragged || !container.contains(over) || over.getAttribute('draggable') !== 'true') {
                return;
            }
            e.preventDefault();
            let rect = over.getBoundingClientRect();
            if ((e.clientX - rect.left) > rect.width / 2) {
                over.after(dragged);
            } else {
                over.before(dragged);
            }
        });

        bind(container, 'drop', (e) => {
            if (dragged) {
                e.preventDefault();
            }
        });

        bind(container, 'dragend', () => {
            if (!dragged) {
                return;
            }
            Dom.removeClass(dragged, 'mb-adminkit-sortable-tag--dragging');
            dragged = null;
            this.#syncOrderFromDom();
        });
    }

    /**
     * Читает текущий DOM-порядок чипсов и переупорядочивает под него скрытые инпуты.
     */
    #syncOrderFromDom()
    {
        let container = this.entitySelector.getItemsContainer();
        if (!Type.isDomNode(container)) {
            return;
        }

        let nodeToId = new Map(this.entitySelector.getTags().map((tag) => [tag.getContainer(), String(tag.getId())]));
        let order = [];
        container.querySelectorAll('.ui-tag-selector-item').forEach((node) => {
            if (nodeToId.has(node)) {
                order.push(nodeToId.get(node));
            }
        });

        this.#inputValueCollection.reorder(order);
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

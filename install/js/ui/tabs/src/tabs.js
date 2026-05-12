import { Tag, Dom, Event, Type, Runtime } from 'main.core';
import { OrderedArray } from 'main.core.collections';
import { TabsOptionsType, TabOptionsType } from './types';
import { EventEmitter } from 'main.core.events';
import { Tab } from './tab';

const justCounter = {
	localId: 0,
};

export class Tabs extends EventEmitter
{
	#index: number;
	#id: string;
	#items: OrderedArray<string, Tab>;
	#activeItem: ?Tab = null;
	#body: ?HTMLElement;
	#hashNavigation: boolean = false;
	#scrollable: boolean = true;
	#hashChangeHandler: ?Function = null;

	title: string;
	titleIconClasses: string;

	content: ?HTMLElement;

	constructor(options: ?TabsOptionsType)
	{
		super();

		options = Type.isObjectLike(options) ? options : {};
		this.#index = (++justCounter.localId);
		this.#id = Type.isStringFilled(options.id) ? options.id : ('TabsId' + this.#index);
		this.setEventNamespace('UI:Tabs:' + this.#id);

		this.#hashNavigation = options.hashNavigation === true;
		this.#scrollable = options.scrollable !== false;

		this.#items = new OrderedArray((tabA: Tab, tabB: Tab) => {
			return tabA.getSort() > tabB.getSort() ? 1 : -1;
		});

		Array.from(options.items ?? []).forEach(
			(TabOptionsType: TabOptionsType) => this.addItem(new Tab(TabOptionsType))
		);

		this.activateItemDebounced = Runtime.debounce(this.activateItemDebounced, 100, this);

		// Restore from hash before activating first item
		if (this.#hashNavigation && window.location.hash)
		{
			const hashId = window.location.hash.slice(1);
			const hashTab = this.#findById(hashId);
			if (hashTab)
			{
				this.activateItem(hashTab, false);
			}
		}

		if (this.#items.count() > 0 && !(this.#activeItem instanceof Tab))
		{
			this.activateItem(this.#items.getFirst(), false);
		}

		if (this.#hashNavigation)
		{
			this.#hashChangeHandler = () => {
				const hashId = window.location.hash.slice(1);
				const tab = this.#findById(hashId);
				if (tab && tab !== this.#activeItem)
				{
					this.activateItem(tab, true);
				}
			};
			Event.bind(window, 'hashchange', this.#hashChangeHandler);
		}
	}

	getIndex(): string
	{
		return this.#index;
	}

	getId(): string
	{
		return this.#id;
	}

	addItem(tab: Tab)
	{
		tab.setParent(this);
		this.#items.add(tab);
		if (tab.isActive())
		{
			this.activateItem(tab);
		}
		tab.subscribe('changeTab', () => {
			this.activateItem(tab);
		});
	}

	activateItem(tab: Tab, withAnimation: boolean = false)
	{
		if (this.#items.has(tab) && this.#activeItem !== tab)
		{
			let inactiveTab = null;
			if (this.#activeItem instanceof Tab)
			{
				inactiveTab = this.#activeItem;
			}
			this.#activeItem = tab;
			this.activateItemDebounced(tab, inactiveTab, withAnimation);

			if (this.#hashNavigation)
			{
				const newHash = '#' + tab.getId();
				if (window.location.hash !== newHash)
				{
					history.replaceState(null, '', newHash);
				}
			}

			this.#scrollHeaderIntoView(tab);
			this.emit('onTabChange', { tab, previousTab: inactiveTab });
		}
	}

	activateItemDebounced(activeTab: Tab, inactiveTab: ?Tab = null, withAnimation: boolean = true)
	{
		if (inactiveTab)
		{
			inactiveTab.inactivate(withAnimation);
		}

		activeTab.activate(withAnimation);
	}

	#findById(id: string): ?Tab
	{
		let found = null;
		this.#items.forEach((tab: Tab) => {
			if (tab.getId() === id)
			{
				found = tab;
			}
		});
		return found;
	}

	#scrollHeaderIntoView(tab: Tab)
	{
		const container = this.content?.querySelector('[data-bx-role="headers"]');
		if (!container || !this.#scrollable)
		{
			return;
		}

		const header = tab.getHeader();
		if (!header)
		{
			return;
		}

		const containerRect = container.getBoundingClientRect();
		const headerRect = header.getBoundingClientRect();

		if (headerRect.left < containerRect.left)
		{
			container.scrollLeft -= containerRect.left - headerRect.left + 8;
		}
		else if (headerRect.right > containerRect.right)
		{
			container.scrollLeft += headerRect.right - containerRect.right + 8;
		}
	}

	#attachKeyboardNavigation(headersEl: HTMLElement)
	{
		Event.bind(headersEl, 'keydown', (e: KeyboardEvent) => {
			const headers = Array.from(headersEl.querySelectorAll('[data-bx-role="tab-header"]'));
			const activeIdx = headers.findIndex(h => h.classList.contains('--header-active'));

			let nextIdx = activeIdx;

			if (e.key === 'ArrowRight' || e.key === 'ArrowDown')
			{
				nextIdx = (activeIdx + 1) % headers.length;
				e.preventDefault();
			}
			else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp')
			{
				nextIdx = (activeIdx - 1 + headers.length) % headers.length;
				e.preventDefault();
			}
			else if (e.key === 'Home')
			{
				nextIdx = 0;
				e.preventDefault();
			}
			else if (e.key === 'End')
			{
				nextIdx = headers.length - 1;
				e.preventDefault();
			}

			if (nextIdx !== activeIdx && headers[nextIdx])
			{
				headers[nextIdx].click();
				headers[nextIdx].focus();
			}
		});

		// Make headers keyboard-focusable
		this.#items.forEach((tab: Tab) => {
			tab.getHeader().setAttribute('tabindex', '0');
			tab.getHeader().setAttribute('role', 'tab');
		});

		headersEl.setAttribute('role', 'tablist');
	}

	getBodyContainer(): HTMLElement
	{
		if (!this.#body)
		{
			this.#body = Tag.render`
				<div class="ui-tabs__tabs-body-container" data-bx-role="bodies"></div>
			`;
		}

		return this.#body;
	}

	getContainer(): HTMLElement
	{
		if (this.content)
		{
			return this.content;
		}

		const scrollableClass = this.#scrollable ? ' --scrollable' : '';

		this.content = Tag.render`
			<div class="ui-tabs__tabs-container">
				<div class="ui-tabs__tabs-header-container${scrollableClass}" data-bx-role="headers"></div>
				${this.getBodyContainer()}
			</div>`;

		const headers = this.content.querySelector('[data-bx-role="headers"]');

		this.#items.forEach(
			(tab: Tab) => {
				Dom.append(tab.getHeader(), headers);
				Dom.append(tab.getBody(), this.getBodyContainer());
			}
		);

		this.#attachKeyboardNavigation(headers);

		return this.content;
	}

	getItems(): Array<Tab>
	{
		return this.#items;
	}

	destroy()
	{
		if (this.#hashChangeHandler)
		{
			Event.unbind(window, 'hashchange', this.#hashChangeHandler);
			this.#hashChangeHandler = null;
		}
	}
}

import { Tabs } from './tabs';
import { Dom, Type } from 'main.core';

/**
 * @typedef {Object} TabsConfig
 * @property {string} id
 * @property {Array} items
 * @property {Array} bodies
 * @property {boolean} remember
 */

/**
 * Initialize tabs from config
 * @param {TabsConfig} config
 */
export function initTabs(config) {
	if (!Type.isObject(config))
	{
		return;
	}

	const { id, items, bodies, remember } = config;
	const tabs = new Tabs({ id, items });
	const container = tabs.getContainer();

	if (Type.isArray(bodies))
	{
		bodies.forEach((bodyData) => {
			const bodyInner = container.querySelector(`.ui-tabs__tab-body_inner[data-id="${bodyData.id}"]`);
			if (!bodyInner)
			{
				return;
			}

			const bodyContainer = bodyInner.querySelector('.ui-tabs__tab-body_data');
			if (bodyContainer)
			{
				bodyContainer.innerHTML = bodyData.html;
				bodyContainer.querySelectorAll('script').forEach((oldScript) => {
					const s = document.createElement('script');
					s.textContent = oldScript.textContent;
					oldScript.parentNode.replaceChild(s, oldScript);
				});
			}

			if (bodyData.active)
			{
				bodyInner.classList.add('--body-active');
				const header = container.querySelector(`[data-bx-name="${bodyData.id}"]`);
				if (header)
				{
					header.classList.add('--header-active');
				}
			}
		});
	}

	const targetContainer = document.getElementById(id);
	if (targetContainer)
	{
		Dom.append(container, targetContainer);
	}

	if (window.BX && window.BX.UI && window.BX.UI.Hint)
	{
		window.BX.UI.Hint.init(container);
	}

	if (remember)
	{
		const activeTabInput = document.querySelector('input[name="adminkit_active_tab"]');
		container.addEventListener('click', (event) => {
			const header = event.target.closest('[data-bx-name]');
			if (!header || !activeTabInput)
			{
				return;
			}
			const tabId = header.getAttribute('data-bx-name') || '';
			if (tabId !== '')
			{
				activeTabInput.value = tabId;
			}
		});
	}
}

/**
 * Initialize all tabs on the page
 * @param {HTMLElement|Document} root
 */
export function initAll(root = document) {
	const tabsContainers = [];
	if (Type.isElementNode(root) && root.hasAttribute('data-adminkit-tabs'))
	{
		tabsContainers.push(root);
	}

	root.querySelectorAll('[data-adminkit-tabs]').forEach((el) => {
		tabsContainers.push(el);
	});

	tabsContainers.forEach((container) => {
		if (container.dataset.adminkitTabsInitialized)
		{
			return;
		}

		const configStr = container.getAttribute('data-adminkit-tabs-config');
		if (configStr)
		{
			try
			{
				const config = JSON.parse(configStr);
				initTabs(config);
				container.dataset.adminkitTabsInitialized = 'true';
			}
			catch (e)
			{
				console.error('Failed to parse tabs config', e);
			}
		}
	});
}

import { Type } from 'main.core';

/**
 * @typedef {Object} BulkActionConfig
 * @property {string} gridId
 * @property {string} actionId
 * @property {string} actionButtonKey
 * @property {string} forAllKey
 * @property {string} [emptySelectionMessage]
 */

/**
 * Run bulk action
 * @param {BulkActionConfig} config
 */
export function runBulkAction(config) {
	const { gridId, actionId, actionButtonKey, forAllKey, emptySelectionMessage } = config;

	const manager = window.BX && window.BX.Main && window.BX.Main.gridManager && window.BX.Main.gridManager.getById(gridId);
	const grid = manager && (manager.instance || manager.grid);
	if (!grid)
	{
		return;
	}

	const rows = (typeof grid.getRows === 'function') ? grid.getRows() : null;
	const ids = (rows && typeof rows.getSelectedIds === 'function') ? rows.getSelectedIds() : [];
	const panel = (typeof grid.getActionsPanel === 'function') ? grid.getActionsPanel() : null;
	const values = (panel && typeof panel.getValues === 'function') ? panel.getValues() : {};
	const forAll = (values && values[forAllKey] === 'Y') ? 'Y' : 'N';

	if ((!ids || ids.length === 0) && forAll !== 'Y')
	{
		if (window.BX.UI && window.BX.UI.Notification && window.BX.UI.Notification.Center)
		{
			window.BX.UI.Notification.Center.notify({
				content: emptySelectionMessage || 'Select at least one row'
			});
		}
		return;
	}

	const data = {};
	data[actionButtonKey] = actionId;
	data[forAllKey] = forAll;
	data['adminkit_bulk_action'] = actionId;
	data['adminkit_bulk_ajax'] = 'Y';

	if (window.BX && typeof window.BX.bitrix_sessid === 'function')
	{
		data['sessid'] = window.BX.bitrix_sessid();
	}

	data.ID = ids;
	data.id = ids;
	data.rows = ids;

	const showResult = (response) => {
		if (!window.BX.UI || !window.BX.UI.Notification || !window.BX.UI.Notification.Center)
		{
			return;
		}

		let content = response.message || '';
		let details = [];

		if (response.errors && Object.keys(response.errors).length > 0)
		{
			details.push('<strong>Errors:</strong>');
			Object.entries(response.errors).slice(0, 10).forEach(([id, errors]) => {
				details.push(`#${id}: ${errors.join(', ')}`);
			});
		}

		if (response.skipped && Object.keys(response.skipped).length > 0)
		{
			details.push('<strong>Skipped:</strong>');
			Object.entries(response.skipped).slice(0, 10).forEach(([id, reason]) => {
				details.push(`#${id}: ${reason}`);
			});
		}

		if (details.length > 0)
		{
			content += '<div style="margin-top: 10px; font-size: 12px; max-height: 200px; overflow-y: auto;">' + details.join('<br>') + '</div>';
		}

		window.BX.UI.Notification.Center.notify({
			content: content,
			autoClose: response.success ? 5000 : 0,
			category: 'adminkit-bulk-result'
		});
	};

	window.BX.ajax({
		method: 'POST',
		dataType: 'json',
		url: window.location.pathname + window.location.search,
		data: data,
		onsuccess: (response) => {
			showResult(response);
			if (typeof grid.reloadTable === 'function')
			{
				grid.reloadTable();
			}
		},
		onfailure: () => {
			showResult({ success: false, message: 'Server error occurred during bulk operation.' });
			if (typeof grid.reloadTable === 'function')
			{
				grid.reloadTable();
			}
		}
	});
}

/**
 * Export selected rows
 * @param {BulkActionConfig} config
 */
export function exportSelected(config) {
	const { gridId, actionId, forAllKey, emptySelectionMessage } = config;

	const manager = window.BX && window.BX.Main && window.BX.Main.gridManager && window.BX.Main.gridManager.getById(gridId);
	const grid = manager && (manager.instance || manager.grid);
	if (!grid)
	{
		return;
	}

	const rows = (typeof grid.getRows === 'function') ? grid.getRows() : null;
	const ids = (rows && typeof rows.getSelectedIds === 'function') ? rows.getSelectedIds() : [];

	if (!ids || ids.length === 0)
	{
		if (window.BX.UI && window.BX.UI.Notification && window.BX.UI.Notification.Center)
		{
			window.BX.UI.Notification.Center.notify({
				content: emptySelectionMessage || 'Select at least one row'
			});
		}
		return;
	}

	const form = document.createElement('form');
	form.method = 'POST';
	form.action = window.location.pathname + window.location.search;

	const actionInput = document.createElement('input');
	actionInput.type = 'hidden';
	actionInput.name = 'action';
	actionInput.value = actionId;
	form.appendChild(actionInput);

	const forAllInput = document.createElement('input');
	forAllInput.type = 'hidden';
	forAllInput.name = forAllKey;
	forAllInput.value = 'N';
	form.appendChild(forAllInput);

	if (window.BX && typeof window.BX.bitrix_sessid === 'function')
	{
		const sessidInput = document.createElement('input');
		sessidInput.type = 'hidden';
		sessidInput.name = 'sessid';
		sessidInput.value = window.BX.bitrix_sessid();
		form.appendChild(sessidInput);
	}

	for (let i = 0; i < ids.length; i++)
	{
		const idInput = document.createElement('input');
		idInput.type = 'hidden';
		idInput.name = 'ID[]';
		idInput.value = ids[i];
		form.appendChild(idInput);
	}

	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
}

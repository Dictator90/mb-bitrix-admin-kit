/**
 * Drag-сортировка строк грида с сохранением порядка на сервер.
 *
 * Слушает нативное событие main.ui.grid `Grid::rowMoved` (упорядоченный список id),
 * POST-ит порядок на серверный эндпоинт (action=rowsort), который вызывает Resource::reorder().
 *
 * Регистрируется как MB.AdminKit.GridRowSort (см. index.js).
 * Инициализация: AdminKitJs::renderInit('GridRowSort', { gridId, url }).
 *
 * @typedef {Object} RowSortConfig
 * @property {string} gridId
 * @property {string} [url]
 */

const boundGrids = {};

function notify(message, success) {
	if (window.BX && BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
		BX.UI.Notification.Center.notify({
			content: message,
			autoClose: success ? 4000 : 0,
			category: 'adminkit-rowsort-result',
		});
	}
}

/**
 * Перезагружает грид после ответа — синхронизирует UI с БД (порядок, группы,
 * счётчики), а на ошибке откатывает неудавшееся перемещение к серверному состоянию.
 *
 * @param {RowSortConfig} config
 * @param {Array<string|number>} ids
 * @param {Object} grid  инстанс BX.Main.grid
 */
function saveOrder(config, ids, grid) {
	if (!ids || ids.length === 0 || !window.BX || typeof BX.ajax !== 'function') {
		return;
	}

	const data = {
		action: 'rowsort',
		ids: ids,
	};

	if (typeof BX.bitrix_sessid === 'function') {
		data.sessid = BX.bitrix_sessid();
	}

	const reloadGrid = () => {
		if (grid && typeof grid.reloadTable === 'function') {
			grid.reloadTable();
		}
	};

	BX.ajax({
		method: 'POST',
		dataType: 'json',
		url: config.url || (window.location.pathname + window.location.search),
		data: data,
		onsuccess: (response) => {
			if (response && response.success === false) {
				notify(response.message || 'Не удалось сохранить порядок.', false);
			}
			reloadGrid();
		},
		onfailure: () => {
			notify('Ошибка сервера при сохранении порядка.', false);
			reloadGrid();
		},
	});
}

/**
 * @param {RowSortConfig} config
 */
export function init(config) {
	if (!config || !config.gridId || boundGrids[config.gridId]) {
		return;
	}
	boundGrids[config.gridId] = true;

	BX.addCustomEvent(window, 'Grid::rowMoved', (ids, dragItem, grid) => {
		const currentId = (grid && typeof grid.getId === 'function') ? grid.getId() : null;
		if (currentId !== config.gridId) {
			return;
		}
		saveOrder(config, ids || [], grid);
	});
}

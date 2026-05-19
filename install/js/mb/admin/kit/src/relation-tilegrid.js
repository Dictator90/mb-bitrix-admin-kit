/**
 * Read-only HasMany preview as a table using Bitrix ui.tilegrid (BX.TileGrid.Grid).
 */

function isColumnDefinition(value) {
	return typeof value === 'object'
		&& value !== null
		&& typeof value.id === 'string'
		&& value.id !== '';
}

function columnId(columnDef) {
	if (typeof columnDef === 'string') {
		return columnDef;
	}

	return isColumnDefinition(columnDef) ? columnDef.id : '';
}

function columnLabel(columnDef) {
	if (typeof columnDef === 'string') {
		return columnDef;
	}

	if (!isColumnDefinition(columnDef)) {
		return '';
	}

	const label = columnDef.label;

	if (typeof label === 'string') {
		return label;
	}

	if (label === null || label === undefined) {
		return columnDef.id;
	}

	if (typeof label === 'number' || typeof label === 'boolean') {
		return String(label);
	}

	if (typeof label === 'object') {
		if (typeof label.message === 'string') {
			return label.message;
		}

		if (typeof label.MESSAGE === 'string') {
			return label.MESSAGE;
		}

		if (typeof label.text === 'string') {
			return label.text;
		}

		if (typeof label.TEXT === 'string') {
			return label.TEXT;
		}
	}

	return columnDef.id;
}

function normalizeColumns(columns) {
	if (!columns || !columns.length) {
		return [];
	}

	if (isColumnDefinition(columns[0])) {
		return columns.map(function(columnDef) {
			return {
				id: columnId(columnDef),
				label: columnLabel(columnDef),
			};
		});
	}

	return columns.map(function(column) {
		const id = columnId(column);

		return {
			id: id,
			label: columnLabel(column) || id,
		};
	});
}

function createRow(columnDefs, cells, isHeader) {
	const row = BX.create('div', {
		props: {
			className: isHeader
				? 'adminkit-relation-tilegrid__row adminkit-relation-tilegrid__row--head'
				: 'adminkit-relation-tilegrid__row',
		},
	});

	columnDefs.forEach(function(columnDef) {
		const id = columnId(columnDef);
		const text = isHeader
			? columnLabel(columnDef)
			: (cells[id] === null || cells[id] === undefined || cells[id] === ''
				? '—'
				: String(cells[id]));

		row.appendChild(BX.create('div', {
			props: {
				className: isHeader
					? 'adminkit-relation-tilegrid__cell adminkit-relation-tilegrid__cell--head'
					: 'adminkit-relation-tilegrid__cell',
				title: text,
			},
			text: text,
		}));
	});

	return row;
}

function registerItemTypes() {
	BX.namespace('BX.AdminKit.RelationTileGrid');

	BX.AdminKit.RelationTileGrid.TableItem = function(options) {
		BX.TileGrid.Item.apply(this, arguments);
		this.columns = normalizeColumns(options.columns || []);
		this.cells = options.cells || {};
	};
	BX.extend(BX.AdminKit.RelationTileGrid.TableItem, BX.TileGrid.Item);

	BX.AdminKit.RelationTileGrid.TableItem.prototype.getContent = function() {
		return createRow(this.columns, this.cells, false);
	};

	BX.AdminKit.RelationTileGrid.TableItem.prototype.handleClick = function() {};

	BX.AdminKit.RelationTileGrid.TableItem.prototype.handleDblClick = function() {};

	BX.AdminKit.RelationTileGrid.HeaderItem = function(options) {
		BX.TileGrid.Item.apply(this, arguments);
		this.columns = normalizeColumns(options.columns || []);
	};
	BX.extend(BX.AdminKit.RelationTileGrid.HeaderItem, BX.TileGrid.Item);

	BX.AdminKit.RelationTileGrid.HeaderItem.prototype.getContent = function() {
		return createRow(this.columns, {}, true);
	};

	BX.AdminKit.RelationTileGrid.HeaderItem.prototype.handleClick = function() {};

	BX.AdminKit.RelationTileGrid.HeaderItem.prototype.handleDblClick = function() {};
}

function buildItems(config) {
	const columns = normalizeColumns(config.columns || []);
	const items = [
		{
			id: 'header',
			itemType: 'BX.AdminKit.RelationTileGrid.HeaderItem',
			columns: columns,
		},
	];

	(config.rows || []).forEach(function(row, index) {
		items.push({
			id: 'row_' + index,
			itemType: 'BX.AdminKit.RelationTileGrid.TableItem',
			columns: columns,
			cells: row,
		});
	});

	return items;
}

function init(config) {
	if (!BX.TileGrid || !BX.TileGrid.Grid) {
		return;
	}

	registerItemTypes();

	const container = document.getElementById(config.containerId);
	if (!container) {
		return;
	}

	const width = container.offsetWidth || container.parentNode?.offsetWidth || 800;
	const itemMinWidth = Math.max(Math.floor(width * 0.95), 280);

	const grid = new BX.TileGrid.Grid({
		id: config.gridId,
		container: container,
		items: buildItems(config),
		checkBoxing: false,
		itemHeight: config.itemHeight || 40,
		itemMinWidth: itemMinWidth,
		tileMargin: 0,
	});

	grid.draw();
}

if (typeof window !== 'undefined') {
	window.MB = window.MB || {};
	window.MB.AdminKit = window.MB.AdminKit || {};
	window.MB.AdminKit.RelationTileGrid = {
		init: init,
	};
}
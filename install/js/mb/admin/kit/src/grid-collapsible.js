/**
 * Collapsible grid with preloaded rows (AdminKit grouped index).
 *
 * Bitrix sets data-child-loaded from row.expand. When expand is false, clicking +
 * triggers GRID_GET_CHILD_ROWS instead of showChildRows(). We mark parents as
 * preloaded and hide descendants while any ancestor group is collapsed.
 */
function patchCustomGroupRowChildren(grid)
{
	const rows = grid.getRows();

	rows.getBodyChild().forEach((row) => {
		if (!row.isCustom() || row.__adminkitChildrenPatched)
		{
			return;
		}

		row.__adminkitChildrenPatched = true;
		const originalGetChildren = row.getChildren.bind(row);

		row.getChildren = function () {
			const byParent = rows.getRowsByParentId(this.getId(), true);
			if (byParent.length > 0)
			{
				return byParent;
			}

			return originalGetChildren();
		};
	});
}

function markPreloadedParents(grid)
{
	const rows = grid.getRows();

	rows.getBodyChild().forEach((row) => {
		if (!row.getCollapseButton())
		{
			return;
		}

		BX.data(row.getNode(), 'child-loaded', 'true');
		row.childsLoaded = true;
	});
}

function isUnderCollapsedParent(row, rows)
{
	let parentId = row.getParentId();

	while (parentId && parentId !== '0')
	{
		const parent = rows.getById(parentId);
		if (!parent)
		{
			break;
		}

		if (parent.getCollapseButton() && !parent.isExpand())
		{
			return true;
		}

		parentId = parent.getParentId();
	}

	return false;
}

function applyCollapsedChildVisibility(grid)
{
	if (!grid || !grid.getParam('ENABLE_COLLAPSIBLE_ROWS'))
	{
		return;
	}

	markPreloadedParents(grid);
	patchCustomGroupRowChildren(grid);

	const rows = grid.getRows();

	rows.getBodyChild().forEach((child) => {
		const parentId = child.getParentId();
		if (!parentId || parentId === '0')
		{
			return;
		}

		if (isUnderCollapsedParent(child, rows))
		{
			child.hide();
		}
		else
		{
			child.show();
		}
	});
}

function applyAllCollapsibleGrids()
{
	const manager = BX.Main && BX.Main.gridManager;
	if (!manager || !Array.isArray(manager.data))
	{
		return;
	}

	manager.data.forEach((entry) => {
		if (entry && entry.instance)
		{
			applyCollapsedChildVisibility(entry.instance);
		}
	});
}

function init()
{
	const onUpdated = (grid) => {
		if (grid)
		{
			applyCollapsedChildVisibility(grid);
		}
	};

	BX.addCustomEvent(window, 'Grid::updated', onUpdated);

	BX.ready(() => {
		applyAllCollapsibleGrids();
	});
}

export {
	init,
	applyCollapsedChildVisibility,
	applyAllCollapsibleGrids,
};

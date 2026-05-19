
function notify(message) {
	if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
		BX.UI.Notification.Center.notify({ content: message });
	}
}

function clearFormErrors(form) {
	form.querySelectorAll('.adminkit-field-error').forEach(function(node) {
		node.remove();
	});

	if (form.parentNode) {
		form.parentNode.querySelectorAll('.adminkit-alert').forEach(function(node) {
			node.remove();
		});
	}
}

function appendGlobalError(form, message) {
	const top = document.createElement('div');
	top.className = 'ui-alert ui-alert-danger adminkit-alert';
	const text = String(message);

	if (text.indexOf('\n') !== -1) {
		const pre = document.createElement('pre');
		pre.className = 'adminkit-error-trace';
		pre.textContent = text;
		top.appendChild(pre);
	} else {
		top.innerHTML = '<span class="ui-alert-message">' + BX.util.htmlspecialchars(text) + '</span>';
	}

	if (form.parentNode) {
		form.parentNode.insertBefore(top, form);
	}
}

function hasSaveErrors(resp) {
	if (!resp || resp.validationError) {
		return true;
	}

	if (Array.isArray(resp.globalErrors) && resp.globalErrors.length > 0) {
		return true;
	}

	const fieldErrors = resp.fieldErrors || {};

	return Object.keys(fieldErrors).some(function(column) {
		const messages = fieldErrors[column];

		return Array.isArray(messages) && messages.length > 0;
	});
}

function scrollToFirstError(form) {
	if (!form.parentNode) {
		return;
	}

	const first = form.parentNode.querySelector('.adminkit-alert, .adminkit-field-error');
	if (first && typeof first.scrollIntoView === 'function') {
		first.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}
}

function resolveErrorNotification(resp, messages) {
	if (Array.isArray(resp.globalErrors) && resp.globalErrors.length > 0) {
		return String(resp.globalErrors[0]);
	}

	if (resp.validationError) {
		return messages.validationError || '';
	}

	return messages.saveFailed || '';
}

function renderValidationErrors(form, messages) {
	(messages || []).forEach(function(message) {
		appendGlobalError(form, message);
	});
}

function resolveGridWindow() {
	if (window.parent && window.parent !== window && window.parent.BX) {
		return window.parent;
	}

	if (window.top && window.top.BX) {
		return window.top;
	}

	return window;
}

function findGridInstance(manager, gridId) {
	if (!manager || !gridId) {
		return null;
	}

	let grid = manager.getInstanceById ? manager.getInstanceById(gridId) : null;
	if (!grid && manager.getById) {
		const pair = manager.getById(gridId);
		grid = pair && (pair.instance || pair.grid) ? (pair.instance || pair.grid) : null;
	}

	return grid;
}

function reloadParentGrid(gridId) {
	const win = resolveGridWindow();
	const manager = win.BX && win.BX.Main && win.BX.Main.gridManager;
	const grid = findGridInstance(manager, gridId);

	if (!grid) {
		return false;
	}

	if (typeof grid.reloadTable === 'function') {
		grid.reloadTable();

		return true;
	}

	if (typeof grid.reload === 'function') {
		grid.reload();

		return true;
	}

	return false;
}

function renderFieldErrors(form, fieldErrors) {
	Object.keys(fieldErrors || {}).forEach(function(column) {
		const content = form.querySelector('[data-field-column="' + column + '"] .ui-form-content');
		if (!content) {
			return;
		}

		(fieldErrors[column] || []).forEach(function(message) {
			const box = document.createElement('div');
			box.className = 'ui-alert ui-alert-inline ui-alert-xs ui-alert-danger adminkit-field-error';
			box.innerHTML = '<span class="ui-alert-message">' + BX.util.htmlspecialchars(String(message)) + '</span>';
			content.appendChild(box);
		});
	});
}

function submitAsync(form, submitBtn, messages, gridId) {
	if (submitBtn) {
		submitBtn.disabled = true;
		submitBtn.classList.add('ui-btn-wait');
	}

	const data = new FormData(form);
	data.set('adminkit_async_save', 'Y');

	fetch(form.action || window.location.href, {
		method: 'POST',
		body: data,
		headers: { 'X-Requested-With': 'XMLHttpRequest' },
	})
		.then(function(response) {
			return response.json();
		})
		.then(function(resp) {
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.classList.remove('ui-btn-wait');
			}

			clearFormErrors(form);

			if (resp.validationError) {
				const validationTop = document.createElement('div');
				validationTop.className = 'ui-alert ui-alert-danger adminkit-alert';
				validationTop.innerHTML = '<span class="ui-alert-message">' + (messages.validationError || '') + '</span>';
				form.parentNode.insertBefore(validationTop, form);
			}

			renderValidationErrors(form, resp.globalErrors);
			renderFieldErrors(form, resp.fieldErrors);

			if (hasSaveErrors(resp) || !resp.success) {
				const errorMessage = resolveErrorNotification(resp, messages);
				if (errorMessage) {
					notify(errorMessage);
				}
				scrollToFirstError(form);

				return;
			}

			const effectiveGridId = gridId || resp.gridId || '';
			if (resp.reloadParentGrid && effectiveGridId) {
				reloadParentGrid(effectiveGridId);
			}

			const panelWindow = resolveGridWindow();
			if (resp.closeSidePanel && panelWindow.BX && panelWindow.BX.SidePanel) {
				panelWindow.BX.SidePanel.Instance.getTopSlider().close();
			} else {
				notify(messages.saved || '');
			}
		})
		.catch(function(err) {
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.classList.remove('ui-btn-wait');
			}

			notify('Ошибка запроса: ' + err.message);
		});
}

export function init(config) {
	const form = document.getElementById(config.formId);
	if (!form) {
		return;
	}

	const submitBtn = document.getElementById(config.formId + '-submit');
	const messages = config.messages || {};

	const onSubmit = function(event) {
		event.preventDefault();
		submitAsync(form, submitBtn, messages, config.gridId);
	};

	form.addEventListener('submit', onSubmit);

	if (submitBtn) {
		submitBtn.addEventListener('click', function(event) {
			if (event.defaultPrevented) {
				return;
			}
			event.preventDefault();
			submitAsync(form, submitBtn, messages, config.gridId);
		});
	}
}

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
	top.innerHTML = '<span class="ui-alert-message">' + BX.util.htmlspecialchars(String(message)) + '</span>';
	form.parentNode.insertBefore(top, form);
}

function renderValidationErrors(form, messages) {
	(messages || []).forEach(function(message) {
		appendGlobalError(form, message);
	});
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

export function init(config) {
	const form = document.getElementById(config.formId);
	if (!form) {
		return;
	}

	const submitBtn = document.getElementById(config.formId + '-submit');
	const messages = config.messages || {};

	form.addEventListener('submit', function(event) {
		event.preventDefault();

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

				if (resp.success) {
					if (resp.closeSidePanel && window.top && window.top.BX && window.top.BX.SidePanel) {
						window.top.BX.SidePanel.Instance.getTopSlider().close();
					} else {
						notify(messages.saved || '');
					}
				}
			})
			.catch(function(err) {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.classList.remove('ui-btn-wait');
				}

				notify('Ошибка запроса: ' + err.message);
			});
	});
}

function notify(content, isError) {
	if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
		BX.UI.Notification.Center.notify({
			content: content,
			autoHideDelay: isError ? 6000 : 4000,
		});
	}
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

		fetch(form.action, {
			method: 'POST',
			body: new FormData(form),
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

				if (resp.status === 'success') {
					notify(resp.message || messages.saved || '', false);
				} else {
					const errors = resp.errors || [resp.message || messages.error || ''];
					notify(errors.join('<br>'), true);
				}
			})
			.catch(function(err) {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.classList.remove('ui-btn-wait');
				}

				notify('Ошибка запроса: ' + err.message, true);
			});
	});
}

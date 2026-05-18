function getSourceValue(form, srcCol) {
	const els = form.querySelectorAll('[name="' + srcCol + '"]');

	for (let i = 0; i < els.length; i++) {
		if (els[i].value !== '') {
			return els[i].value;
		}
	}

	return '';
}

function sourcesHaveValues(form, dependsMap, col) {
	return (dependsMap[col] || []).every(function(sourceCol) {
		return getSourceValue(form, sourceCol) !== '';
	});
}

function updateDisabledStates(form, dependsMap) {
	Object.keys(dependsMap).forEach(function(col) {
		const row = form.querySelector('[data-field-column="' + col + '"]');
		if (!row) {
			return;
		}

		const content = row.querySelector('.ui-form-content');
		if (!content || content.classList.contains('adminkit-field-loading')) {
			return;
		}

		if (sourcesHaveValues(form, dependsMap, col)) {
			content.classList.remove('adminkit-field-disabled');
		} else {
			content.classList.add('adminkit-field-disabled');
		}
	});
}

function executeScripts(container) {
	container.querySelectorAll('script').forEach(function(scriptNode) {
		const script = document.createElement('script');
		script.textContent = scriptNode.textContent;
		document.head.appendChild(script).parentNode.removeChild(script);
	});
}

export function init(config) {
	const form = document.getElementById(config.formId);
	const sourceCols = config.sourceCols || [];
	const dependsMap = config.dependsMap || {};

	if (!form || !sourceCols.length) {
		return;
	}

	let initPhase = true;
	setTimeout(function() {
		initPhase = false;
	}, 800);

	updateDisabledStates(form, dependsMap);
	setTimeout(function() {
		updateDisabledStates(form, dependsMap);
	}, 600);

	let debounceTimer = null;

	function triggerReactive() {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(function() {
			Object.keys(dependsMap).forEach(function(col) {
				const row = form.querySelector('[data-field-column="' + col + '"]');
				if (!row) {
					return;
				}

				const content = row.querySelector('.ui-form-content');
				if (content) {
					content.classList.remove('adminkit-field-disabled');
					content.classList.add('adminkit-field-loading');
				}
			});

			const fd = new FormData(form);
			fd.set('adminkit_action', 'reactive');

			fetch(form.action || window.location.href, {
				method: 'POST',
				body: fd,
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function(response) {
					return response.json();
				})
				.then(function(resp) {
					if (resp.status === 'success') {
						Object.keys(resp.fields || {}).forEach(function(col) {
							const row = form.querySelector('[data-field-column="' + col + '"]');
							if (!row) {
								return;
							}

							const content = row.querySelector('.ui-form-content');
							if (!content) {
								return;
							}

							content.classList.remove('adminkit-field-loading');
							content.innerHTML = resp.fields[col].html;
							executeScripts(content);
						});
					}

					updateDisabledStates(form, dependsMap);
				})
				.catch(function() {
					updateDisabledStates(form, dependsMap);
				});
		}, 200);
	}

	sourceCols.forEach(function(col) {
		form.querySelectorAll('[name="' + col + '"]').forEach(function(el) {
			el.addEventListener('change', triggerReactive);
		});
	});

	const observer = new MutationObserver(function(mutations) {
		for (let i = 0; i < mutations.length; i++) {
			const nodes = Array.prototype.slice.call(mutations[i].addedNodes)
				.concat(Array.prototype.slice.call(mutations[i].removedNodes));

			for (let j = 0; j < nodes.length; j++) {
				const node = nodes[j];
				if (node.nodeType === 1 && node.tagName === 'INPUT'
					&& node.type === 'hidden'
					&& sourceCols.indexOf(node.name) !== -1) {
					if (initPhase) {
						updateDisabledStates(form, dependsMap);
					} else {
						triggerReactive();
					}

					return;
				}
			}
		}
	});

	observer.observe(form, { childList: true, subtree: true });
}

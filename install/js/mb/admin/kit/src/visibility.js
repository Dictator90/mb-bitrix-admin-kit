export function getFieldValue(form, col) {
	const inputs = form.querySelectorAll('[name="' + col + '"]');
	let fallback = '';

	for (let i = 0; i < inputs.length; i++) {
		const el = inputs[i];
		if (el.type === 'checkbox' || el.type === 'radio') {
			if (el.checked) {
				return el.value;
			}
		} else if (el.type === 'hidden') {
			if (fallback === '') {
				fallback = el.value;
			}
		} else if (el.value !== '') {
			return el.value;
		}
	}

	if (fallback !== '') {
		return fallback;
	}

	const multi = form.querySelectorAll('[name="' + col + '[]"]');
	if (multi.length > 0) {
		return multi[0].value;
	}

	return '';
}

export function matchesRule(rule, val) {
	if (rule.values) {
		return rule.values.indexOf(val) !== -1;
	}

	const operator = rule.operator || '=';
	const expected = rule.value != null ? String(rule.value) : '';

	if (operator === 'in') {
		return Array.isArray(rule.value) && rule.value.map(String).indexOf(val) !== -1;
	}

	if (operator === 'not in') {
		return !Array.isArray(rule.value) || rule.value.map(String).indexOf(val) === -1;
	}

	if (operator === '=' || operator === '==' || operator === '===') {
		return val === expected;
	}

	if (operator === '!=' || operator === '<>' || operator === '!==') {
		return val !== expected;
	}

	return val === expected;
}

export function updateVisibility(form) {
	const els = form.querySelectorAll('[data-visible-when]');

	for (let i = 0; i < els.length; i++) {
		const el = els[i];
		const rule = JSON.parse(el.getAttribute('data-visible-when'));
		const val = getFieldValue(form, rule.column);

		if (matchesRule(rule, val)) {
			el.classList.remove('adminkit-conditional-hidden');
		} else {
			el.classList.add('adminkit-conditional-hidden');
		}
	}
}

export function init(config) {
	const form = document.getElementById(config.formId);
	if (!form) {
		return;
	}

	form.addEventListener('change', function() {
		updateVisibility(form);
	});

	const visObserver = new MutationObserver(function() {
		updateVisibility(form);
	});
	visObserver.observe(form, { childList: true, subtree: true });

	updateVisibility(form);
	setTimeout(function() {
		updateVisibility(form);
	}, 900);
}

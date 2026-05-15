/* eslint-disable */
this.MB = this.MB || {};
(function (exports) {
    'use strict';

    function notify(message) {
      if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
        BX.UI.Notification.Center.notify({
          content: message
        });
      }
    }
    function clearFormErrors(form) {
      form.querySelectorAll('.adminkit-field-error').forEach(function (node) {
        node.remove();
      });
      if (form.parentNode) {
        form.parentNode.querySelectorAll('.adminkit-alert').forEach(function (node) {
          node.remove();
        });
      }
    }
    function appendGlobalError(form, message) {
      var top = document.createElement('div');
      top.className = 'ui-alert ui-alert-danger adminkit-alert';
      top.innerHTML = '<span class="ui-alert-message">' + BX.util.htmlspecialchars(String(message)) + '</span>';
      form.parentNode.insertBefore(top, form);
    }
    function renderValidationErrors(form, messages) {
      (messages || []).forEach(function (message) {
        appendGlobalError(form, message);
      });
    }
    function renderFieldErrors(form, fieldErrors) {
      Object.keys(fieldErrors || {}).forEach(function (column) {
        var content = form.querySelector('[data-field-column="' + column + '"] .ui-form-content');
        if (!content) {
          return;
        }
        (fieldErrors[column] || []).forEach(function (message) {
          var box = document.createElement('div');
          box.className = 'ui-alert ui-alert-inline ui-alert-xs ui-alert-danger adminkit-field-error';
          box.innerHTML = '<span class="ui-alert-message">' + BX.util.htmlspecialchars(String(message)) + '</span>';
          content.appendChild(box);
        });
      });
    }
    function init(config) {
      var form = document.getElementById(config.formId);
      if (!form) {
        return;
      }
      var submitBtn = document.getElementById(config.formId + '-submit');
      var messages = config.messages || {};
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.classList.add('ui-btn-wait');
        }
        var data = new FormData(form);
        data.set('adminkit_async_save', 'Y');
        fetch(form.action || window.location.href, {
          method: 'POST',
          body: data,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).then(function (response) {
          return response.json();
        }).then(function (resp) {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('ui-btn-wait');
          }
          clearFormErrors(form);
          if (resp.validationError) {
            var validationTop = document.createElement('div');
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
        })["catch"](function (err) {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('ui-btn-wait');
          }
          notify('Ошибка запроса: ' + err.message);
        });
      });
    }

    var formSave = /*#__PURE__*/Object.freeze({
        init: init
    });

    function getSourceValue(form, srcCol) {
      var els = form.querySelectorAll('[name="' + srcCol + '"]');
      for (var i = 0; i < els.length; i++) {
        if (els[i].value !== '') {
          return els[i].value;
        }
      }
      return '';
    }
    function sourcesHaveValues(form, dependsMap, col) {
      return (dependsMap[col] || []).every(function (sourceCol) {
        return getSourceValue(form, sourceCol) !== '';
      });
    }
    function updateDisabledStates(form, dependsMap) {
      Object.keys(dependsMap).forEach(function (col) {
        var row = form.querySelector('[data-field-column="' + col + '"]');
        if (!row) {
          return;
        }
        var content = row.querySelector('.ui-form-content');
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
      container.querySelectorAll('script').forEach(function (scriptNode) {
        var script = document.createElement('script');
        script.textContent = scriptNode.textContent;
        document.head.appendChild(script).parentNode.removeChild(script);
      });
    }
    function init$1(config) {
      var form = document.getElementById(config.formId);
      var sourceCols = config.sourceCols || [];
      var dependsMap = config.dependsMap || {};
      if (!form || !sourceCols.length) {
        return;
      }
      var initPhase = true;
      setTimeout(function () {
        initPhase = false;
      }, 800);
      updateDisabledStates(form, dependsMap);
      setTimeout(function () {
        updateDisabledStates(form, dependsMap);
      }, 600);
      var debounceTimer = null;
      function triggerReactive() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          Object.keys(dependsMap).forEach(function (col) {
            var row = form.querySelector('[data-field-column="' + col + '"]');
            if (!row) {
              return;
            }
            var content = row.querySelector('.ui-form-content');
            if (content) {
              content.classList.remove('adminkit-field-disabled');
              content.classList.add('adminkit-field-loading');
            }
          });
          var fd = new FormData(form);
          fd.set('adminkit_action', 'reactive');
          fetch(form.action || window.location.href, {
            method: 'POST',
            body: fd,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          }).then(function (response) {
            return response.json();
          }).then(function (resp) {
            if (resp.status === 'success') {
              Object.keys(resp.fields || {}).forEach(function (col) {
                var row = form.querySelector('[data-field-column="' + col + '"]');
                if (!row) {
                  return;
                }
                var content = row.querySelector('.ui-form-content');
                if (!content) {
                  return;
                }
                content.classList.remove('adminkit-field-loading');
                content.innerHTML = resp.fields[col].html;
                executeScripts(content);
              });
            }
            updateDisabledStates(form, dependsMap);
          })["catch"](function () {
            updateDisabledStates(form, dependsMap);
          });
        }, 200);
      }
      sourceCols.forEach(function (col) {
        form.querySelectorAll('[name="' + col + '"]').forEach(function (el) {
          el.addEventListener('change', triggerReactive);
        });
      });
      var observer = new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var nodes = Array.prototype.slice.call(mutations[i].addedNodes).concat(Array.prototype.slice.call(mutations[i].removedNodes));
          for (var j = 0; j < nodes.length; j++) {
            var node = nodes[j];
            if (node.nodeType === 1 && node.tagName === 'INPUT' && node.type === 'hidden' && sourceCols.indexOf(node.name) !== -1) {
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
      observer.observe(form, {
        childList: true,
        subtree: true
      });
    }

    var dependencies = /*#__PURE__*/Object.freeze({
        init: init$1
    });

    function getFieldValue(form, col) {
      var inputs = form.querySelectorAll('[name="' + col + '"]');
      var fallback = '';
      for (var i = 0; i < inputs.length; i++) {
        var el = inputs[i];
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
      var multi = form.querySelectorAll('[name="' + col + '[]"]');
      if (multi.length > 0) {
        return multi[0].value;
      }
      return '';
    }
    function matchesRule(rule, val) {
      if (rule.values) {
        return rule.values.indexOf(val) !== -1;
      }
      var operator = rule.operator || '=';
      var expected = rule.value != null ? String(rule.value) : '';
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
    function updateVisibility(form) {
      var els = form.querySelectorAll('[data-visible-when]');
      for (var i = 0; i < els.length; i++) {
        var el = els[i];
        var rule = JSON.parse(el.getAttribute('data-visible-when'));
        var val = getFieldValue(form, rule.column);
        if (matchesRule(rule, val)) {
          el.classList.remove('adminkit-conditional-hidden');
        } else {
          el.classList.add('adminkit-conditional-hidden');
        }
      }
    }
    function init$2(config) {
      var form = document.getElementById(config.formId);
      if (!form) {
        return;
      }
      form.addEventListener('change', function () {
        updateVisibility(form);
      });
      var visObserver = new MutationObserver(function () {
        updateVisibility(form);
      });
      visObserver.observe(form, {
        childList: true,
        subtree: true
      });
      updateVisibility(form);
      setTimeout(function () {
        updateVisibility(form);
      }, 900);
    }

    var visibility = /*#__PURE__*/Object.freeze({
        getFieldValue: getFieldValue,
        matchesRule: matchesRule,
        updateVisibility: updateVisibility,
        init: init$2
    });

    function notify$1(content, isError) {
      if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
        BX.UI.Notification.Center.notify({
          content: content,
          autoHideDelay: isError ? 6000 : 4000
        });
      }
    }
    function init$3(config) {
      var form = document.getElementById(config.formId);
      if (!form) {
        return;
      }
      var submitBtn = document.getElementById(config.formId + '-submit');
      var messages = config.messages || {};
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.classList.add('ui-btn-wait');
        }
        fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).then(function (response) {
          return response.json();
        }).then(function (resp) {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('ui-btn-wait');
          }
          if (resp.status === 'success') {
            notify$1(resp.message || messages.saved || '', false);
          } else {
            var errors = resp.errors || [resp.message || messages.error || ''];
            notify$1(errors.join('<br>'), true);
          }
        })["catch"](function (err) {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('ui-btn-wait');
          }
          notify$1('Ошибка запроса: ' + err.message, true);
        });
      });
    }

    var optionsPage = /*#__PURE__*/Object.freeze({
        init: init$3
    });

    exports.Form = formSave;
    exports.Dependencies = dependencies;
    exports.Visibility = visibility;
    exports.OptionsPage = optionsPage;

}((this.MB.AdminKit = this.MB.AdminKit || {})));
//# sourceMappingURL=kit.bundle.js.map

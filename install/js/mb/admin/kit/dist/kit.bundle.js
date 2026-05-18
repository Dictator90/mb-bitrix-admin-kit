/* eslint-disable */
this.MB = this.MB || {};
(function (exports,main_core_collections,main_core_events,main_loader,ui_entitySelector,main_core) {
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
    function reloadParentGrid(gridId) {
      if (!gridId || !window.top || !window.top.BX || !window.top.BX.Main || !window.top.BX.Main.gridManager) {
        return;
      }
      var manager = window.top.BX.Main.gridManager;
      var grid = manager.getInstanceById ? manager.getInstanceById(gridId) : null;
      if (!grid && manager.getById) {
        var pair = manager.getById(gridId);
        grid = pair && (pair.instance || pair.grid) ? pair.instance || pair.grid : null;
      }
      if (grid && typeof grid.reload === 'function') {
        grid.reload();
      }
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
    function submitAsync(form, submitBtn, messages) {
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
            if (resp.reloadParentGrid && config.gridId) {
              reloadParentGrid(config.gridId);
            }
          }
        }
      })["catch"](function (err) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.classList.remove('ui-btn-wait');
        }
        notify('Ошибка запроса: ' + err.message);
      });
    }
    function init(config) {
      var form = document.getElementById(config.formId);
      if (!form) {
        return;
      }
      var submitBtn = document.getElementById(config.formId + '-submit');
      var messages = config.messages || {};
      var onSubmit = function onSubmit(event) {
        event.preventDefault();
        submitAsync(form, submitBtn, messages);
      };
      form.addEventListener('submit', onSubmit);
      if (submitBtn) {
        submitBtn.addEventListener('click', function (event) {
          if (event.defaultPrevented) {
            return;
          }
          event.preventDefault();
          submitAsync(form, submitBtn, messages);
        });
      }
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
    function buildFormData(form) {
      var temporarilyEnabled = [];
      form.querySelectorAll('input, select, textarea').forEach(function (element) {
        if (!element.name || !element.disabled) {
          return;
        }
        temporarilyEnabled.push(element);
        element.disabled = false;
      });
      var formData = new FormData(form);
      temporarilyEnabled.forEach(function (element) {
        element.disabled = true;
      });
      return formData;
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
          body: buildFormData(form),
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

    /**
     * Collapsible grid with preloaded rows (AdminKit grouped index).
     *
     * Bitrix sets data-child-loaded from row.expand. When expand is false, clicking +
     * triggers GRID_GET_CHILD_ROWS instead of showChildRows(). We mark parents as
     * preloaded and hide descendants while any ancestor group is collapsed.
     */
    function patchCustomGroupRowChildren(grid) {
      var rows = grid.getRows();
      rows.getBodyChild().forEach(function (row) {
        if (!row.isCustom() || row.__adminkitChildrenPatched) {
          return;
        }
        row.__adminkitChildrenPatched = true;
        var originalGetChildren = row.getChildren.bind(row);
        row.getChildren = function () {
          var byParent = rows.getRowsByParentId(this.getId(), true);
          if (byParent.length > 0) {
            return byParent;
          }
          return originalGetChildren();
        };
      });
    }
    function markPreloadedParents(grid) {
      var rows = grid.getRows();
      rows.getBodyChild().forEach(function (row) {
        if (!row.getCollapseButton()) {
          return;
        }
        BX.data(row.getNode(), 'child-loaded', 'true');
        row.childsLoaded = true;
      });
    }
    function isUnderCollapsedParent(row, rows) {
      var parentId = row.getParentId();
      while (parentId && parentId !== '0') {
        var parent = rows.getById(parentId);
        if (!parent) {
          break;
        }
        if (parent.getCollapseButton() && !parent.isExpand()) {
          return true;
        }
        parentId = parent.getParentId();
      }
      return false;
    }
    function applyCollapsedChildVisibility(grid) {
      if (!grid || !grid.getParam('ENABLE_COLLAPSIBLE_ROWS')) {
        return;
      }
      markPreloadedParents(grid);
      patchCustomGroupRowChildren(grid);
      var rows = grid.getRows();
      rows.getBodyChild().forEach(function (child) {
        var parentId = child.getParentId();
        if (!parentId || parentId === '0') {
          return;
        }
        if (isUnderCollapsedParent(child, rows)) {
          child.hide();
        } else {
          child.show();
        }
      });
    }
    function applyAllCollapsibleGrids() {
      var manager = BX.Main && BX.Main.gridManager;
      if (!manager || !Array.isArray(manager.data)) {
        return;
      }
      manager.data.forEach(function (entry) {
        if (entry && entry.instance) {
          applyCollapsedChildVisibility(entry.instance);
        }
      });
    }
    function init$4() {
      var onUpdated = function onUpdated(grid) {
        if (grid) {
          applyCollapsedChildVisibility(grid);
        }
      };
      BX.addCustomEvent(window, 'Grid::updated', onUpdated);
      BX.ready(function () {
        applyAllCollapsibleGrids();
      });
    }

    var gridCollapsible = /*#__PURE__*/Object.freeze({
        init: init$4,
        applyCollapsedChildVisibility: applyCollapsedChildVisibility,
        applyAllCollapsibleGrids: applyAllCollapsibleGrids
    });

    function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
    var _inputNode = /*#__PURE__*/new WeakMap();
    var _targetNode = /*#__PURE__*/new WeakMap();
    var _textTargetClass = /*#__PURE__*/new WeakMap();
    var _passwordTargetClass = /*#__PURE__*/new WeakMap();
    var _init = /*#__PURE__*/new WeakSet();
    var PasswordField = /*#__PURE__*/function () {
      function PasswordField(options) {
        babelHelpers.classCallCheck(this, PasswordField);
        _classPrivateMethodInitSpec(this, _init);
        _classPrivateFieldInitSpec(this, _inputNode, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec(this, _targetNode, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec(this, _textTargetClass, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec(this, _passwordTargetClass, {
          writable: true,
          value: void 0
        });
        babelHelpers.classPrivateFieldSet(this, _inputNode, BX(options.inputId));
        babelHelpers.classPrivateFieldSet(this, _targetNode, BX(options.targetId));
        if (!options.textTargetClass) {
          babelHelpers.classPrivateFieldSet(this, _textTargetClass, 'ui-ctl-icon-opened-eye');
        }
        if (!options.passwordTargetClass) {
          babelHelpers.classPrivateFieldSet(this, _passwordTargetClass, 'ui-ctl-icon-crossed-eye');
        }
        if (!babelHelpers.classPrivateFieldGet(this, _inputNode) || !babelHelpers.classPrivateFieldGet(this, _targetNode)) {
          return;
        }
        this.switchToPassword();
        _classPrivateMethodGet(this, _init, _init2).call(this);
      }
      babelHelpers.createClass(PasswordField, [{
        key: "switch",
        value: function _switch() {
          if (babelHelpers.classPrivateFieldGet(this, _inputNode).type === 'password') {
            this.switchToText();
          } else {
            this.switchToPassword();
          }
        }
      }, {
        key: "switchToText",
        value: function switchToText() {
          babelHelpers.classPrivateFieldGet(this, _inputNode).type = 'text';
          babelHelpers.classPrivateFieldGet(this, _targetNode).classList.remove(babelHelpers.classPrivateFieldGet(this, _passwordTargetClass));
          babelHelpers.classPrivateFieldGet(this, _targetNode).classList.add(babelHelpers.classPrivateFieldGet(this, _textTargetClass));
        }
      }, {
        key: "switchToPassword",
        value: function switchToPassword() {
          babelHelpers.classPrivateFieldGet(this, _inputNode).type = 'password';
          babelHelpers.classPrivateFieldGet(this, _targetNode).classList.remove(babelHelpers.classPrivateFieldGet(this, _textTargetClass));
          babelHelpers.classPrivateFieldGet(this, _targetNode).classList.add(babelHelpers.classPrivateFieldGet(this, _passwordTargetClass));
        }
      }]);
      return PasswordField;
    }();
    function _init2() {
      var _this = this;
      babelHelpers.classPrivateFieldGet(this, _targetNode).addEventListener('click', function (e) {
        e.preventDefault();
        _this["switch"]();
      });
    }

    var passwordField = /*#__PURE__*/Object.freeze({
        PasswordField: PasswordField
    });

    var charts = new WeakMap();
    function toNumber(value, fallback) {
      var parsed = Number(value);
      return Number.isFinite(parsed) ? parsed : fallback;
    }
    function resolveChartCtor() {
      if (typeof window === "undefined") {
        return null;
      }
      return window.Chart || null;
    }
    function initNode(node) {
      if (!(node instanceof HTMLCanvasElement)) {
        return;
      }
      var ChartCtor = resolveChartCtor();
      if (!ChartCtor) {
        return;
      }
      var rawConfig = node.getAttribute("data-adminkit-chart");
      if (!rawConfig) {
        return;
      }
      var config;
      try {
        config = JSON.parse(rawConfig);
      } catch (_unused) {
        return;
      }
      var heightFromAttr = toNumber(node.getAttribute("data-adminkit-chart-height"), 300);
      var wrapper = node.closest(".adminkit-chart-widget__canvas");
      if (wrapper instanceof HTMLElement) {
        wrapper.style.setProperty("--adminkit-chart-height", "".concat(heightFromAttr, "px"));
      }
      var existing = charts.get(node);
      if (existing && typeof existing.destroy === "function") {
        existing.destroy();
      }
      charts.set(node, new ChartCtor(node, config));
    }
    function init$5() {
      var root = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
      if (!root) {
        return;
      }
      root.querySelectorAll("canvas[data-adminkit-chart]").forEach(function (node) {
        initNode(node);
      });
    }

    var chartWidget = /*#__PURE__*/Object.freeze({
        init: init$5
    });

    var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;
    function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
    var justCounter = {
      localId: 0,
      localSorting: 0
    };
    var _parentElement = /*#__PURE__*/new WeakMap();
    var _id = /*#__PURE__*/new WeakMap();
    var _sort = /*#__PURE__*/new WeakMap();
    var _head = /*#__PURE__*/new WeakMap();
    var _body = /*#__PURE__*/new WeakMap();
    var _dataContainer = /*#__PURE__*/new WeakMap();
    var _active = /*#__PURE__*/new WeakMap();
    var _restricted = /*#__PURE__*/new WeakMap();
    var _bannerCode = /*#__PURE__*/new WeakMap();
    var _helpDeskCode = /*#__PURE__*/new WeakMap();
    var _loader = /*#__PURE__*/new WeakMap();
    var _initHead = /*#__PURE__*/new WeakSet();
    var _buildHeaderInner = /*#__PURE__*/new WeakSet();
    var _initBody = /*#__PURE__*/new WeakSet();
    var _loadBody = /*#__PURE__*/new WeakSet();
    var _showLoader = /*#__PURE__*/new WeakSet();
    var _removeLoader = /*#__PURE__*/new WeakSet();
    var Tab = /*#__PURE__*/function (_EventEmitter) {
      babelHelpers.inherits(Tab, _EventEmitter);
      function Tab(_options) {
        var _this;
        var parentElement = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
        babelHelpers.classCallCheck(this, Tab);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Tab).call(this, {}));
        _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _removeLoader);
        _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _showLoader);
        _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _loadBody);
        _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _initBody);
        _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _buildHeaderInner);
        _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _initHead);
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _parentElement, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _id, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _sort, {
          writable: true,
          value: 0
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _head, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _body, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _dataContainer, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _active, {
          writable: true,
          value: false
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _restricted, {
          writable: true,
          value: true
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _bannerCode, {
          writable: true,
          value: null
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _helpDeskCode, {
          writable: true,
          value: null
        });
        _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _loader, {
          writable: true,
          value: null
        });
        _this.setEventNamespace('UI:Tabs:');
        _this.setParent(parentElement);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _id, main_core.Type.isStringFilled(_options.id) ? _options.id : 'TabId' + ++justCounter.localId);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _sort, main_core.Type.isInteger(_options.sort) ? _options.sort : ++justCounter.localSorting);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _active, main_core.Type.isBoolean(_options.active) ? _options.active : false);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _restricted, _options.restricted === true);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _bannerCode, main_core.Type.isStringFilled(_options.bannerCode) ? _options.bannerCode : null);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _helpDeskCode, main_core.Type.isStringFilled(_options.helpDeskCode) ? _options.helpDeskCode : null);
        _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _initHead, _initHead2).call(babelHelpers.assertThisInitialized(_this), _options.head);
        _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _initBody, _initBody2).call(babelHelpers.assertThisInitialized(_this), _options.body);
        return _this;
      }
      babelHelpers.createClass(Tab, [{
        key: "getId",
        value: function getId() {
          return babelHelpers.classPrivateFieldGet(this, _id);
        }
      }, {
        key: "getSort",
        value: function getSort() {
          return babelHelpers.classPrivateFieldGet(this, _sort);
        }
      }, {
        key: "setParent",
        value: function setParent(parentElement) {
          if (parentElement instanceof Tabs) {
            babelHelpers.classPrivateFieldSet(this, _parentElement, parentElement);
          }
        }
      }, {
        key: "isRestricted",
        value: function isRestricted() {
          return babelHelpers.classPrivateFieldGet(this, _restricted);
        }
      }, {
        key: "getBannerCode",
        value: function getBannerCode() {
          return babelHelpers.classPrivateFieldGet(this, _bannerCode);
        }
      }, {
        key: "showBanner",
        value: function showBanner(event) {
          if (this.getBannerCode()) {
            BX.UI.InfoHelper.show(this.getBannerCode());
          }
          if (event) {
            event.stopPropagation();
            event.preventDefault();
          }
        }
      }, {
        key: "getHeader",
        value: function getHeader() {
          return babelHelpers.classPrivateFieldGet(this, _head);
        }
      }, {
        key: "getBody",
        value: function getBody() {
          return babelHelpers.classPrivateFieldGet(this, _body);
        }
      }, {
        key: "getBodyDataContainer",
        value: function getBodyDataContainer() {
          return babelHelpers.classPrivateFieldGet(this, _dataContainer);
        }
      }, {
        key: "inactivate",
        value: function inactivate() {
          var withAnimation = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
          main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _body), 'ui-tabs__tab-active-animation');
          if (withAnimation !== false) {
            main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _body), 'ui-tabs__tab-active-animation');
          }
          if (babelHelpers.classPrivateFieldGet(this, _active) === true) {
            main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _head), '--header-active');
            main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _body), '--body-active');
            babelHelpers.classPrivateFieldSet(this, _active, false);
            this.emit('onInactive');
          }
          return this;
        }
      }, {
        key: "activate",
        value: function activate() {
          var withAnimation = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
          main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _body), 'ui-tabs__tab-active-animation');
          if (withAnimation !== false) {
            main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _body), 'ui-tabs__tab-active-animation');
          }
          if (babelHelpers.classPrivateFieldGet(this, _active) !== true) {
            main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _head), '--header-active');
            main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _body), '--body-active');
            babelHelpers.classPrivateFieldSet(this, _active, true);
            this.emit('onActive');
          }
          return this;
        }
      }, {
        key: "isActive",
        value: function isActive() {
          return babelHelpers.classPrivateFieldGet(this, _active);
        }
      }, {
        key: "showError",
        value: function showError(_ref) {
          var message = _ref.message,
            code = _ref.code;
          var errorContainer = this.getBody().querySelector('[data-bx-role="error-container"]');
          if (errorContainer) {
            errorContainer.innerText = message || code;
          }
          main_core.Dom.addClass(this.getBodyContainer(), 'ui-avatar-editor--error');
        }
      }]);
      return Tab;
    }(main_core_events.EventEmitter);
    function _initHead2(headOptions) {
      var _options$className,
        _this2 = this;
      var options = main_core.Type.isPlainObject(headOptions) ? headOptions : main_core.Type.isStringFilled(headOptions) ? {
        title: headOptions
      } : {};
      var innerHeader;
      if (main_core.Type.isDomNode(headOptions)) {
        innerHeader = headOptions;
      } else if (babelHelpers.classPrivateFieldGet(this, _restricted) !== true) {
        innerHeader = _classPrivateMethodGet$1(this, _buildHeaderInner, _buildHeaderInner2).call(this, options);
      } else {
        var _options$description;
        innerHeader = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-tabs__tab-header-container-inner\" title=\"", "\">\n\t\t\t\t<div class=\"ui-tabs__tab-header-container-inner-title\">", "</div>\n\t\t\t\t<div class=\"ui-tabs__tab-header-container-inner-lockbox\"><span class=\"ui-icon-set --lock field-has-lock\"></span></div>\n\t\t\t</div>"])), main_core.Text.encode((_options$description = options.description) !== null && _options$description !== void 0 ? _options$description : ''), main_core.Text.encode(options.title));
        main_core.Event.bind(innerHeader, 'click', this.showBanner.bind(this));
      }
      babelHelpers.classPrivateFieldSet(this, _head, main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-tabs__tab-header-container ", "\" data-bx-role=\"tab-header\" data-bx-name=\"", "\">", "</span>"])), main_core.Text.encode((_options$className = options.className) !== null && _options$className !== void 0 ? _options$className : ''), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _id)), innerHeader));
      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _head), 'click', function () {
        _this2.emit('changeTab');
      });
    }
    function _buildHeaderInner2(options) {
      var _options$description2, _Text$encode;
      var hasIcon = main_core.Type.isStringFilled(options.icon);
      var hasCount = main_core.Type.isNumber(options.count) || main_core.Type.isStringFilled(options.count);
      var titleEl = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-tabs__tab-header-title\" title=\"", "\">", "</span>"])), main_core.Text.encode((_options$description2 = options.description) !== null && _options$description2 !== void 0 ? _options$description2 : ''), (_Text$encode = main_core.Text.encode(options.title)) !== null && _Text$encode !== void 0 ? _Text$encode : '&nbsp;');
      var iconEl = hasIcon ? main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-tabs__tab-header-icon ui-icon-set ", "\"></span>"])), main_core.Text.encode(options.icon)) : null;
      var countEl = hasCount ? main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-tabs__tab-header-count\">", "</span>"])), main_core.Text.encode(String(options.count))) : null;
      var inner = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-tabs__tab-header-inner\"></span>"])));
      if (iconEl) main_core.Dom.append(iconEl, inner);
      main_core.Dom.append(titleEl, inner);
      if (countEl) main_core.Dom.append(countEl, inner);
      return inner;
    }
    function _initBody2(body) {
      var _this3 = this;
      babelHelpers.classPrivateFieldSet(this, _dataContainer, main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-tabs__tab-body_data\"></div>"]))));
      babelHelpers.classPrivateFieldSet(this, _body, main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-tabs__tab-body_inner\"></div>"]))));
      babelHelpers.classPrivateFieldGet(this, _body).dataset.id = babelHelpers.classPrivateFieldGet(this, _id);
      babelHelpers.classPrivateFieldGet(this, _body).dataset.role = 'body';
      babelHelpers.classPrivateFieldGet(this, _body).appendChild(babelHelpers.classPrivateFieldGet(this, _dataContainer));
      if (body) {
        this.subscribe('onActive', function () {
          _classPrivateMethodGet$1(_this3, _loadBody, _loadBody2).call(_this3, body);
        });
      }
    }
    function _loadBody2(body) {
      var _this4 = this;
      var resultBody = body;
      if (main_core.Type.isFunction(body)) {
        resultBody = body(this);
      }
      var promiseBody;
      if (!resultBody || Object.prototype.toString.call(resultBody) === "[object Promise]" || resultBody.toString() === "[object BX.Promise]") {
        promiseBody = resultBody;
        _classPrivateMethodGet$1(this, _showLoader, _showLoader2).call(this);
      } else {
        promiseBody = Promise.resolve(resultBody);
      }
      promiseBody.then(function (result) {
        _classPrivateMethodGet$1(_this4, _removeLoader, _removeLoader2).call(_this4);
        if (main_core.Type.isDomNode(result)) {
          babelHelpers.classPrivateFieldGet(_this4, _dataContainer).appendChild(result);
        } else if (main_core.Type.isString(result)) {
          babelHelpers.classPrivateFieldGet(_this4, _dataContainer).innerHTML = result; //HTML! Not Text.encoded
        } else {
          throw new Error('Tab body has to be a text or a dom-element.');
        }
        _this4.emit('onLoad');
      }, function (reason) {
        console.log('reason: ', reason);
        _classPrivateMethodGet$1(_this4, _removeLoader, _removeLoader2).call(_this4);
        babelHelpers.classPrivateFieldGet(_this4, _dataContainer).innerHTML = reason;
        _this4.emit('onLoadErrored');
      });
    }
    function _showLoader2() {
      babelHelpers.classPrivateFieldSet(this, _loader, new main_loader.Loader({
        target: babelHelpers.classPrivateFieldGet(this, _dataContainer),
        color: 'rgba(82, 92, 105, 0.9)',
        mode: 'inline'
      }));
      babelHelpers.classPrivateFieldGet(this, _loader).show().then(function () {
        console.log('The loader is shown');
      });
    }
    function _removeLoader2() {
      if (babelHelpers.classPrivateFieldGet(this, _loader)) {
        babelHelpers.classPrivateFieldGet(this, _loader).destroy();
        babelHelpers.classPrivateFieldSet(this, _loader, null);
      }
    }

    var _templateObject$1, _templateObject2$1;
    function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
    var justCounter$1 = {
      localId: 0
    };
    var _index = /*#__PURE__*/new WeakMap();
    var _id$1 = /*#__PURE__*/new WeakMap();
    var _items = /*#__PURE__*/new WeakMap();
    var _activeItem = /*#__PURE__*/new WeakMap();
    var _body$1 = /*#__PURE__*/new WeakMap();
    var _hashNavigation = /*#__PURE__*/new WeakMap();
    var _scrollable = /*#__PURE__*/new WeakMap();
    var _hashChangeHandler = /*#__PURE__*/new WeakMap();
    var _findById = /*#__PURE__*/new WeakSet();
    var _scrollHeaderIntoView = /*#__PURE__*/new WeakSet();
    var _attachKeyboardNavigation = /*#__PURE__*/new WeakSet();
    var Tabs = /*#__PURE__*/function (_EventEmitter) {
      babelHelpers.inherits(Tabs, _EventEmitter);
      function Tabs(options) {
        var _options$items;
        var _this;
        babelHelpers.classCallCheck(this, Tabs);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Tabs).call(this));
        _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _attachKeyboardNavigation);
        _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _scrollHeaderIntoView);
        _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _findById);
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _index, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _id$1, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _items, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _activeItem, {
          writable: true,
          value: null
        });
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _body$1, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _hashNavigation, {
          writable: true,
          value: false
        });
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _scrollable, {
          writable: true,
          value: true
        });
        _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _hashChangeHandler, {
          writable: true,
          value: null
        });
        options = main_core.Type.isObjectLike(options) ? options : {};
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _index, ++justCounter$1.localId);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _id$1, main_core.Type.isStringFilled(options.id) ? options.id : 'TabsId' + babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _index));
        _this.setEventNamespace('UI:Tabs:' + babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _id$1));
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _hashNavigation, options.hashNavigation === true);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _scrollable, options.scrollable !== false);
        babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _items, new main_core_collections.OrderedArray(function (tabA, tabB) {
          return tabA.getSort() > tabB.getSort() ? 1 : -1;
        }));
        Array.from((_options$items = options.items) !== null && _options$items !== void 0 ? _options$items : []).forEach(function (TabOptionsType) {
          return _this.addItem(new Tab(TabOptionsType));
        });
        _this.activateItemDebounced = main_core.Runtime.debounce(_this.activateItemDebounced, 100, babelHelpers.assertThisInitialized(_this));

        // Restore from hash before activating first item
        if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _hashNavigation) && window.location.hash) {
          var hashId = window.location.hash.slice(1);
          var hashTab = _classPrivateMethodGet$2(babelHelpers.assertThisInitialized(_this), _findById, _findById2).call(babelHelpers.assertThisInitialized(_this), hashId);
          if (hashTab) {
            _this.activateItem(hashTab, false);
          }
        }
        if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _items).count() > 0 && !(babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _activeItem) instanceof Tab)) {
          _this.activateItem(babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _items).getFirst(), false);
        }
        if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _hashNavigation)) {
          babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _hashChangeHandler, function () {
            var hashId = window.location.hash.slice(1);
            var tab = _classPrivateMethodGet$2(babelHelpers.assertThisInitialized(_this), _findById, _findById2).call(babelHelpers.assertThisInitialized(_this), hashId);
            if (tab && tab !== babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _activeItem)) {
              _this.activateItem(tab, true);
            }
          });
          main_core.Event.bind(window, 'hashchange', babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _hashChangeHandler));
        }
        return _this;
      }
      babelHelpers.createClass(Tabs, [{
        key: "getIndex",
        value: function getIndex() {
          return babelHelpers.classPrivateFieldGet(this, _index);
        }
      }, {
        key: "getId",
        value: function getId() {
          return babelHelpers.classPrivateFieldGet(this, _id$1);
        }
      }, {
        key: "addItem",
        value: function addItem(tab) {
          var _this2 = this;
          tab.setParent(this);
          babelHelpers.classPrivateFieldGet(this, _items).add(tab);
          if (tab.isActive()) {
            this.activateItem(tab);
          }
          tab.subscribe('changeTab', function () {
            _this2.activateItem(tab);
          });
        }
      }, {
        key: "activateItem",
        value: function activateItem(tab) {
          var withAnimation = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
          if (babelHelpers.classPrivateFieldGet(this, _items).has(tab) && babelHelpers.classPrivateFieldGet(this, _activeItem) !== tab) {
            var inactiveTab = null;
            if (babelHelpers.classPrivateFieldGet(this, _activeItem) instanceof Tab) {
              inactiveTab = babelHelpers.classPrivateFieldGet(this, _activeItem);
            }
            babelHelpers.classPrivateFieldSet(this, _activeItem, tab);
            this.activateItemDebounced(tab, inactiveTab, withAnimation);
            if (babelHelpers.classPrivateFieldGet(this, _hashNavigation)) {
              var newHash = '#' + tab.getId();
              if (window.location.hash !== newHash) {
                history.replaceState(null, '', newHash);
              }
            }
            _classPrivateMethodGet$2(this, _scrollHeaderIntoView, _scrollHeaderIntoView2).call(this, tab);
            this.emit('onTabChange', {
              tab: tab,
              previousTab: inactiveTab
            });
          }
        }
      }, {
        key: "activateItemDebounced",
        value: function activateItemDebounced(activeTab) {
          var inactiveTab = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
          var withAnimation = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
          if (inactiveTab) {
            inactiveTab.inactivate(withAnimation);
          }
          activeTab.activate(withAnimation);
        }
      }, {
        key: "getBodyContainer",
        value: function getBodyContainer() {
          if (!babelHelpers.classPrivateFieldGet(this, _body$1)) {
            babelHelpers.classPrivateFieldSet(this, _body$1, main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-tabs__tabs-body-container\" data-bx-role=\"bodies\"></div>\n\t\t\t"]))));
          }
          return babelHelpers.classPrivateFieldGet(this, _body$1);
        }
      }, {
        key: "getContainer",
        value: function getContainer() {
          var _this3 = this;
          if (this.content) {
            return this.content;
          }
          var scrollableClass = babelHelpers.classPrivateFieldGet(this, _scrollable) ? ' --scrollable' : '';
          this.content = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-tabs__tabs-container\">\n\t\t\t\t<div class=\"ui-tabs__tabs-header-container", "\" data-bx-role=\"headers\"></div>\n\t\t\t\t", "\n\t\t\t</div>"])), scrollableClass, this.getBodyContainer());
          var headers = this.content.querySelector('[data-bx-role="headers"]');
          babelHelpers.classPrivateFieldGet(this, _items).forEach(function (tab) {
            main_core.Dom.append(tab.getHeader(), headers);
            main_core.Dom.append(tab.getBody(), _this3.getBodyContainer());
          });
          _classPrivateMethodGet$2(this, _attachKeyboardNavigation, _attachKeyboardNavigation2).call(this, headers);
          return this.content;
        }
      }, {
        key: "getItems",
        value: function getItems() {
          return babelHelpers.classPrivateFieldGet(this, _items);
        }
      }, {
        key: "destroy",
        value: function destroy() {
          if (babelHelpers.classPrivateFieldGet(this, _hashChangeHandler)) {
            main_core.Event.unbind(window, 'hashchange', babelHelpers.classPrivateFieldGet(this, _hashChangeHandler));
            babelHelpers.classPrivateFieldSet(this, _hashChangeHandler, null);
          }
        }
      }]);
      return Tabs;
    }(main_core_events.EventEmitter);
    function _findById2(id) {
      var found = null;
      babelHelpers.classPrivateFieldGet(this, _items).forEach(function (tab) {
        if (tab.getId() === id) {
          found = tab;
        }
      });
      return found;
    }
    function _scrollHeaderIntoView2(tab) {
      var _this$content;
      var container = (_this$content = this.content) === null || _this$content === void 0 ? void 0 : _this$content.querySelector('[data-bx-role="headers"]');
      if (!container || !babelHelpers.classPrivateFieldGet(this, _scrollable)) {
        return;
      }
      var header = tab.getHeader();
      if (!header) {
        return;
      }
      var containerRect = container.getBoundingClientRect();
      var headerRect = header.getBoundingClientRect();
      if (headerRect.left < containerRect.left) {
        container.scrollLeft -= containerRect.left - headerRect.left + 8;
      } else if (headerRect.right > containerRect.right) {
        container.scrollLeft += headerRect.right - containerRect.right + 8;
      }
    }
    function _attachKeyboardNavigation2(headersEl) {
      main_core.Event.bind(headersEl, 'keydown', function (e) {
        var headers = Array.from(headersEl.querySelectorAll('[data-bx-role="tab-header"]'));
        var activeIdx = headers.findIndex(function (h) {
          return h.classList.contains('--header-active');
        });
        var nextIdx = activeIdx;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
          nextIdx = (activeIdx + 1) % headers.length;
          e.preventDefault();
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
          nextIdx = (activeIdx - 1 + headers.length) % headers.length;
          e.preventDefault();
        } else if (e.key === 'Home') {
          nextIdx = 0;
          e.preventDefault();
        } else if (e.key === 'End') {
          nextIdx = headers.length - 1;
          e.preventDefault();
        }
        if (nextIdx !== activeIdx && headers[nextIdx]) {
          headers[nextIdx].click();
          headers[nextIdx].focus();
        }
      });

      // Make headers keyboard-focusable
      babelHelpers.classPrivateFieldGet(this, _items).forEach(function (tab) {
        tab.getHeader().setAttribute('tabindex', '0');
        tab.getHeader().setAttribute('role', 'tab');
      });
      headersEl.setAttribute('role', 'tablist');
    }

    /**
     * @typedef {Object} TabsConfig
     * @property {string} id
     * @property {Array} items
     * @property {Array} bodies
     * @property {boolean} remember
     */

    /**
     * Initialize tabs from config
     * @param {TabsConfig} config
     */
    function initTabs(config) {
      if (!main_core.Type.isObject(config)) {
        return;
      }
      var id = config.id,
        items = config.items,
        bodies = config.bodies,
        remember = config.remember;
      var tabs = new Tabs({
        id: id,
        items: items
      });
      var container = tabs.getContainer();
      if (main_core.Type.isArray(bodies)) {
        bodies.forEach(function (bodyData) {
          var bodyInner = container.querySelector(".ui-tabs__tab-body_inner[data-id=\"".concat(bodyData.id, "\"]"));
          if (!bodyInner) {
            return;
          }
          var bodyContainer = bodyInner.querySelector('.ui-tabs__tab-body_data');
          if (bodyContainer) {
            bodyContainer.innerHTML = bodyData.html;
            bodyContainer.querySelectorAll('script').forEach(function (oldScript) {
              var s = document.createElement('script');
              s.textContent = oldScript.textContent;
              oldScript.parentNode.replaceChild(s, oldScript);
            });
          }
          if (bodyData.active) {
            bodyInner.classList.add('--body-active');
            var header = container.querySelector("[data-bx-name=\"".concat(bodyData.id, "\"]"));
            if (header) {
              header.classList.add('--header-active');
            }
          }
        });
      }
      var targetContainer = document.getElementById(id);
      if (targetContainer) {
        main_core.Dom.append(container, targetContainer);
      }
      if (window.BX && window.BX.UI && window.BX.UI.Hint) {
        window.BX.UI.Hint.init(container);
      }
      if (remember) {
        var activeTabInput = document.querySelector('input[name="adminkit_active_tab"]');
        container.addEventListener('click', function (event) {
          var header = event.target.closest('[data-bx-name]');
          if (!header || !activeTabInput) {
            return;
          }
          var tabId = header.getAttribute('data-bx-name') || '';
          if (tabId !== '') {
            activeTabInput.value = tabId;
          }
        });
      }
    }

    /**
     * Initialize all tabs on the page
     * @param {HTMLElement|Document} root
     */
    function initAll() {
      var root = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
      var tabsContainers = [];
      if (main_core.Type.isElementNode(root) && root.hasAttribute('data-adminkit-tabs')) {
        tabsContainers.push(root);
      }
      root.querySelectorAll('[data-adminkit-tabs]').forEach(function (el) {
        tabsContainers.push(el);
      });
      tabsContainers.forEach(function (container) {
        if (container.dataset.adminkitTabsInitialized) {
          return;
        }
        var configStr = container.getAttribute('data-adminkit-tabs-config');
        if (configStr) {
          try {
            var config = JSON.parse(configStr);
            initTabs(config);
            container.dataset.adminkitTabsInitialized = 'true';
          } catch (e) {
            console.error('Failed to parse tabs config', e);
          }
        }
      });
    }



    var index = /*#__PURE__*/Object.freeze({
        Tabs: Tabs,
        Tab: Tab,
        initTabs: initTabs,
        initAll: initAll
    });

    function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
    var _name = /*#__PURE__*/new WeakMap();
    var _value = /*#__PURE__*/new WeakMap();
    var _node = /*#__PURE__*/new WeakMap();
    var _fillNode = /*#__PURE__*/new WeakSet();
    var ValueItem = /*#__PURE__*/function () {
      function ValueItem(options) {
        babelHelpers.classCallCheck(this, ValueItem);
        _classPrivateMethodInitSpec$3(this, _fillNode);
        _classPrivateFieldInitSpec$3(this, _name, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$3(this, _value, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$3(this, _node, {
          writable: true,
          value: void 0
        });
        babelHelpers.classPrivateFieldSet(this, _name, options.multiple ? options.name + '[]' : options.name);
        babelHelpers.classPrivateFieldSet(this, _value, options.value);
        _classPrivateMethodGet$3(this, _fillNode, _fillNode2).call(this);
      }
      babelHelpers.createClass(ValueItem, [{
        key: "getNode",
        value: function getNode() {
          return babelHelpers.classPrivateFieldGet(this, _node);
        }
      }, {
        key: "getValue",
        value: function getValue() {
          return babelHelpers.classPrivateFieldGet(this, _value);
        }
      }]);
      return ValueItem;
    }();
    function _fillNode2() {
      babelHelpers.classPrivateFieldSet(this, _node, main_core.Dom.create('input', {
        attrs: {
          type: 'hidden',
          name: babelHelpers.classPrivateFieldGet(this, _name),
          value: babelHelpers.classPrivateFieldGet(this, _value)
        }
      }));
    }

    function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
    var _container = /*#__PURE__*/new WeakMap();
    var _items$1 = /*#__PURE__*/new WeakMap();
    var _fillContainer = /*#__PURE__*/new WeakSet();
    var ValueItemCollection = /*#__PURE__*/function () {
      function ValueItemCollection() {
        var valueItems = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
        babelHelpers.classCallCheck(this, ValueItemCollection);
        _classPrivateMethodInitSpec$4(this, _fillContainer);
        _classPrivateFieldInitSpec$4(this, _container, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$4(this, _items$1, {
          writable: true,
          value: void 0
        });
        babelHelpers.classPrivateFieldSet(this, _items$1, valueItems);
        _classPrivateMethodGet$4(this, _fillContainer, _fillContainer2).call(this);
      }
      babelHelpers.createClass(ValueItemCollection, [{
        key: "add",
        value: function add(item) {
          babelHelpers.classPrivateFieldGet(this, _items$1).push(item);
          main_core.Dom.append(item.getNode(), babelHelpers.classPrivateFieldGet(this, _container));
        }
      }, {
        key: "get",
        value: function get(value) {
          babelHelpers.classPrivateFieldGet(this, _items$1).forEach(function (e) {
            return e.getValue === value;
          });
          return null;
        }
      }, {
        key: "delete",
        value: function _delete(value) {
          var _this = this;
          babelHelpers.classPrivateFieldGet(this, _items$1).forEach(function (e, i) {
            if (e.getValue() === value) {
              main_core.Dom.remove(e.getNode());
              babelHelpers.classPrivateFieldGet(_this, _items$1).splice(i, 1);
            }
          });
        }
      }, {
        key: "renderTo",
        value: function renderTo(node) {
          main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _container), node);
        }
      }]);
      return ValueItemCollection;
    }();
    function _fillContainer2() {
      babelHelpers.classPrivateFieldSet(this, _container, main_core.Dom.create('div', {
        attrs: {
          "class": 'ui-tag-selector-input-container'
        }
      }));
    }

    function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec$5(obj, privateMap, value) { _checkPrivateRedeclaration$5(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }
    function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
    function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

    /**
     * @namespace MB.UI
     */
    var _target = /*#__PURE__*/new WeakMap();
    var _inputValueCollection = /*#__PURE__*/new WeakMap();
    var _fillOptions = /*#__PURE__*/new WeakSet();
    var _setSelectedInputs = /*#__PURE__*/new WeakSet();
    var _getTagSelectorOptions = /*#__PURE__*/new WeakSet();
    var DialogSelector = /*#__PURE__*/function () {
      function DialogSelector(_options) {
        babelHelpers.classCallCheck(this, DialogSelector);
        _classPrivateMethodInitSpec$5(this, _getTagSelectorOptions);
        _classPrivateMethodInitSpec$5(this, _setSelectedInputs);
        _classPrivateMethodInitSpec$5(this, _fillOptions);
        babelHelpers.defineProperty(this, "multiple", false);
        _classPrivateFieldInitSpec$5(this, _target, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$5(this, _inputValueCollection, {
          writable: true,
          value: void 0
        });
        _classPrivateMethodGet$5(this, _fillOptions, _fillOptions2).call(this, _options);
      }
      babelHelpers.createClass(DialogSelector, [{
        key: "render",
        value: function render() {
          if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _target))) {
            babelHelpers.classPrivateFieldGet(this, _inputValueCollection).renderTo(babelHelpers.classPrivateFieldGet(this, _target));
            this.entitySelector.renderTo(babelHelpers.classPrivateFieldGet(this, _target));
          }
          return null;
        }
      }], [{
        key: "buildFromSelect",
        value: function buildFromSelect(targetNode) {
          var target = null;
          if (main_core.Type.isDomNode(targetNode)) {
            target = targetNode;
          } else if (main_core.Type.isStringFilled(targetNode)) {
            target = document.querySelector(targetNode);
          }
          if (!main_core.Type.isDomNode(target)) {
            throw new Error("".concat(target, " is not Dom Node"));
          }
          if (target.nodeName !== 'SELECT') {
            throw new Error("target type must be 'select'");
          }
          var options = _classStaticPrivateMethodGet(DialogSelector, DialogSelector, _parseSelectNode).call(DialogSelector, target);
          options.target = main_core.Dom.create('div', {
            attrs: {
              id: (options.id || options.name) + '_dialogselector'
            }
          });
          main_core.Dom.insertAfter(options.target, target);
          main_core.Dom.remove(target);
          return new DialogSelector(options);
        }
      }]);
      return DialogSelector;
    }();
    function _fillOptions2(options) {
      var _options$multiple;
      this.name = options.name;
      this.multiple = (_options$multiple = options.multiple) !== null && _options$multiple !== void 0 ? _options$multiple : false;
      if (main_core.Type.isDomNode(options.target)) {
        babelHelpers.classPrivateFieldSet(this, _target, options.target);
      } else if (main_core.Type.isStringFilled(options.target)) {
        var target = document.querySelector(options.target);
        if (main_core.Type.isDomNode(target)) {
          babelHelpers.classPrivateFieldSet(this, _target, target);
        }
      }
      if (!babelHelpers.classPrivateFieldGet(this, _target)) {
        throw new Error('container must be HTMLElement');
      }
      babelHelpers.classPrivateFieldSet(this, _inputValueCollection, new ValueItemCollection());
      this.entitySelector = new ui_entitySelector.TagSelector(_classPrivateMethodGet$5(this, _getTagSelectorOptions, _getTagSelectorOptions2).call(this, options));
      this.entityDialog = this.entitySelector.getDialog();
      _classPrivateMethodGet$5(this, _setSelectedInputs, _setSelectedInputs2).call(this);
    }
    function _setSelectedInputs2() {
      var _this = this;
      this.entityDialog.selectedItems.forEach(function (e) {
        var _e$value;
        babelHelpers.classPrivateFieldGet(_this, _inputValueCollection).add(new ValueItem({
          name: _this.name,
          multiple: _this.multiple,
          value: ((_e$value = e.value) === null || _e$value === void 0 ? void 0 : _e$value.id) || e.id
        }));
      });
    }
    function _getTagSelectorOptions2(options) {
      var _options$multiple2,
        _options$items,
        _options$dialog,
        _options$deselectable,
        _options$readonly,
        _options$locked,
        _this2 = this;
      var tagSelectorOptions = {
        id: 'mb-ui-selector-' + options.name,
        context: 'MB_SETTINGS'
      };
      tagSelectorOptions.multiple = (_options$multiple2 = options.multiple) !== null && _options$multiple2 !== void 0 ? _options$multiple2 : false;
      tagSelectorOptions.items = (_options$items = options.items) !== null && _options$items !== void 0 ? _options$items : [];
      tagSelectorOptions.dialogOptions = (_options$dialog = options.dialog) !== null && _options$dialog !== void 0 ? _options$dialog : null;
      tagSelectorOptions.deselectable = (_options$deselectable = options.deselectable) !== null && _options$deselectable !== void 0 ? _options$deselectable : false;
      tagSelectorOptions.readonly = (_options$readonly = options.readonly) !== null && _options$readonly !== void 0 ? _options$readonly : false;
      tagSelectorOptions.locked = (_options$locked = options.locked) !== null && _options$locked !== void 0 ? _options$locked : false;
      tagSelectorOptions.events = {
        onTagAdd: function onTagAdd(event) {
          var _event$getData = event.getData(),
            tag = _event$getData.tag;
          babelHelpers.classPrivateFieldGet(_this2, _inputValueCollection).add(new ValueItem({
            name: _this2.name,
            multiple: _this2.multiple,
            value: tag.getId()
          }));
        },
        onTagRemove: function onTagRemove(event) {
          var _event$getData2 = event.getData(),
            tag = _event$getData2.tag;
          babelHelpers.classPrivateFieldGet(_this2, _inputValueCollection)["delete"](tag.getId());
        }
      };
      return tagSelectorOptions;
    }
    function _parseSelectNode(target) {
      var items = [];
      Array.prototype.forEach.call(target.options, function (option) {
        items.push({
          id: option.value,
          entityId: 'select-custom',
          title: option.textContent,
          selected: option.selected,
          tabs: 'select-tab'
        });
      });
      return {
        name: target.name,
        multiple: target.multiple,
        id: target.id || null,
        dialog: {
          items: items,
          tabs: [{
            id: 'select-tab',
            title: 'Значения'
          }],
          dropdownMode: true
        }
      };
    }



    var index$1 = /*#__PURE__*/Object.freeze({
        ValueItem: ValueItem,
        ValueItemCollection: ValueItemCollection,
        DialogSelector: DialogSelector
    });

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
    function runBulkAction(config) {
      var gridId = config.gridId,
        actionId = config.actionId,
        actionButtonKey = config.actionButtonKey,
        forAllKey = config.forAllKey,
        emptySelectionMessage = config.emptySelectionMessage;
      var manager = window.BX && window.BX.Main && window.BX.Main.gridManager && window.BX.Main.gridManager.getById(gridId);
      var grid = manager && (manager.instance || manager.grid);
      if (!grid) {
        return;
      }
      var rows = typeof grid.getRows === 'function' ? grid.getRows() : null;
      var ids = rows && typeof rows.getSelectedIds === 'function' ? rows.getSelectedIds() : [];
      var panel = typeof grid.getActionsPanel === 'function' ? grid.getActionsPanel() : null;
      var values = panel && typeof panel.getValues === 'function' ? panel.getValues() : {};
      var forAll = values && values[forAllKey] === 'Y' ? 'Y' : 'N';
      if ((!ids || ids.length === 0) && forAll !== 'Y') {
        if (window.BX.UI && window.BX.UI.Notification && window.BX.UI.Notification.Center) {
          window.BX.UI.Notification.Center.notify({
            content: emptySelectionMessage || 'Select at least one row'
          });
        }
        return;
      }
      var data = {};
      data[actionButtonKey] = actionId;
      data[forAllKey] = forAll;
      data['adminkit_bulk_action'] = actionId;
      data['adminkit_bulk_ajax'] = 'Y';
      if (window.BX && typeof window.BX.bitrix_sessid === 'function') {
        data['sessid'] = window.BX.bitrix_sessid();
      }
      data.ID = ids;
      data.id = ids;
      data.rows = ids;
      var showResult = function showResult(response) {
        if (!window.BX.UI || !window.BX.UI.Notification || !window.BX.UI.Notification.Center) {
          return;
        }
        var content = response.message || '';
        var details = [];
        if (response.errors && Object.keys(response.errors).length > 0) {
          details.push('<strong>Errors:</strong>');
          Object.entries(response.errors).slice(0, 10).forEach(function (_ref) {
            var _ref2 = babelHelpers.slicedToArray(_ref, 2),
              id = _ref2[0],
              errors = _ref2[1];
            details.push("#".concat(id, ": ").concat(errors.join(', ')));
          });
        }
        if (response.skipped && Object.keys(response.skipped).length > 0) {
          details.push('<strong>Skipped:</strong>');
          Object.entries(response.skipped).slice(0, 10).forEach(function (_ref3) {
            var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
              id = _ref4[0],
              reason = _ref4[1];
            details.push("#".concat(id, ": ").concat(reason));
          });
        }
        if (details.length > 0) {
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
        onsuccess: function onsuccess(response) {
          showResult(response);
          if (typeof grid.reloadTable === 'function') {
            grid.reloadTable();
          }
        },
        onfailure: function onfailure() {
          showResult({
            success: false,
            message: 'Server error occurred during bulk operation.'
          });
          if (typeof grid.reloadTable === 'function') {
            grid.reloadTable();
          }
        }
      });
    }

    /**
     * Export selected rows
     * @param {BulkActionConfig} config
     */
    function exportSelected(config) {
      var gridId = config.gridId,
        actionId = config.actionId,
        forAllKey = config.forAllKey,
        emptySelectionMessage = config.emptySelectionMessage;
      var manager = window.BX && window.BX.Main && window.BX.Main.gridManager && window.BX.Main.gridManager.getById(gridId);
      var grid = manager && (manager.instance || manager.grid);
      if (!grid) {
        return;
      }
      var rows = typeof grid.getRows === 'function' ? grid.getRows() : null;
      var ids = rows && typeof rows.getSelectedIds === 'function' ? rows.getSelectedIds() : [];
      if (!ids || ids.length === 0) {
        if (window.BX.UI && window.BX.UI.Notification && window.BX.UI.Notification.Center) {
          window.BX.UI.Notification.Center.notify({
            content: emptySelectionMessage || 'Select at least one row'
          });
        }
        return;
      }
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = window.location.pathname + window.location.search;
      var actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = actionId;
      form.appendChild(actionInput);
      var forAllInput = document.createElement('input');
      forAllInput.type = 'hidden';
      forAllInput.name = forAllKey;
      forAllInput.value = 'N';
      form.appendChild(forAllInput);
      if (window.BX && typeof window.BX.bitrix_sessid === 'function') {
        var sessidInput = document.createElement('input');
        sessidInput.type = 'hidden';
        sessidInput.name = 'sessid';
        sessidInput.value = window.BX.bitrix_sessid();
        form.appendChild(sessidInput);
      }
      for (var i = 0; i < ids.length; i++) {
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'ID[]';
        idInput.value = ids[i];
        form.appendChild(idInput);
      }
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    }

    var bulkActions = /*#__PURE__*/Object.freeze({
        runBulkAction: runBulkAction,
        exportSelected: exportSelected
    });

    exports.Form = formSave;
    exports.Dependencies = dependencies;
    exports.Visibility = visibility;
    exports.OptionsPage = optionsPage;
    exports.GridCollapsible = gridCollapsible;
    exports.Fields = passwordField;
    exports.ChartWidget = chartWidget;
    exports.Tabs = index;
    exports.DialogSelector = index$1;
    exports.GridBulkActions = bulkActions;

}((this.MB.AdminKit = this.MB.AdminKit || {}),BX.Collections,BX.Event,BX,BX.UI.EntitySelector,BX));
//# sourceMappingURL=kit.bundle.js.map

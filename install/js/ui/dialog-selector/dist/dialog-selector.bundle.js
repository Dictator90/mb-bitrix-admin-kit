/* eslint-disable */
this.MB = this.MB || {};
this.MB.UI = this.MB.UI || {};
(function (exports,ui_entitySelector,main_core) {
    'use strict';

    function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
    var _name = /*#__PURE__*/new WeakMap();
    var _value = /*#__PURE__*/new WeakMap();
    var _node = /*#__PURE__*/new WeakMap();
    var _fillNode = /*#__PURE__*/new WeakSet();
    var ValueItem = /*#__PURE__*/function () {
      function ValueItem(options) {
        babelHelpers.classCallCheck(this, ValueItem);
        _classPrivateMethodInitSpec(this, _fillNode);
        _classPrivateFieldInitSpec(this, _name, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec(this, _value, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec(this, _node, {
          writable: true,
          value: void 0
        });
        babelHelpers.classPrivateFieldSet(this, _name, options.multiple ? options.name + '[]' : options.name);
        babelHelpers.classPrivateFieldSet(this, _value, options.value);
        _classPrivateMethodGet(this, _fillNode, _fillNode2).call(this);
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

    function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
    var _container = /*#__PURE__*/new WeakMap();
    var _items = /*#__PURE__*/new WeakMap();
    var _fillContainer = /*#__PURE__*/new WeakSet();
    var ValueItemCollection = /*#__PURE__*/function () {
      function ValueItemCollection() {
        var valueItems = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
        babelHelpers.classCallCheck(this, ValueItemCollection);
        _classPrivateMethodInitSpec$1(this, _fillContainer);
        _classPrivateFieldInitSpec$1(this, _container, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$1(this, _items, {
          writable: true,
          value: void 0
        });
        babelHelpers.classPrivateFieldSet(this, _items, valueItems);
        _classPrivateMethodGet$1(this, _fillContainer, _fillContainer2).call(this);
      }
      babelHelpers.createClass(ValueItemCollection, [{
        key: "add",
        value: function add(item) {
          babelHelpers.classPrivateFieldGet(this, _items).push(item);
          main_core.Dom.append(item.getNode(), babelHelpers.classPrivateFieldGet(this, _container));
        }
      }, {
        key: "get",
        value: function get(value) {
          babelHelpers.classPrivateFieldGet(this, _items).forEach(function (e) {
            return e.getValue === value;
          });
          return null;
        }
      }, {
        key: "delete",
        value: function _delete(value) {
          var _this = this;
          babelHelpers.classPrivateFieldGet(this, _items).forEach(function (e, i) {
            if (e.getValue() === value) {
              main_core.Dom.remove(e.getNode());
              babelHelpers.classPrivateFieldGet(_this, _items).splice(i, 1);
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

    function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
    function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
    function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
    function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }
    function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
    function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

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
        _classPrivateMethodInitSpec$2(this, _getTagSelectorOptions);
        _classPrivateMethodInitSpec$2(this, _setSelectedInputs);
        _classPrivateMethodInitSpec$2(this, _fillOptions);
        babelHelpers.defineProperty(this, "multiple", false);
        _classPrivateFieldInitSpec$2(this, _target, {
          writable: true,
          value: void 0
        });
        _classPrivateFieldInitSpec$2(this, _inputValueCollection, {
          writable: true,
          value: void 0
        });
        _classPrivateMethodGet$2(this, _fillOptions, _fillOptions2).call(this, _options);
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
      this.entitySelector = new ui_entitySelector.TagSelector(_classPrivateMethodGet$2(this, _getTagSelectorOptions, _getTagSelectorOptions2).call(this, options));
      this.entityDialog = this.entitySelector.getDialog();
      _classPrivateMethodGet$2(this, _setSelectedInputs, _setSelectedInputs2).call(this);
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

    exports.ValueItem = ValueItem;
    exports.ValueItemCollection = ValueItemCollection;
    exports.DialogSelector = DialogSelector;

}((this.MB.UI.DialogSelector = this.MB.UI.DialogSelector || {}),BX.UI.EntitySelector,BX));
//# sourceMappingURL=dialog-selector.bundle.js.map

import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Returns a boolean indicating if the current selected block has a block switcher or not.
 *
 * @return {Promise} Promise resolving with a boolean.
 */
export var hasBlockSwitcher = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            return _context.abrupt("return", page.evaluate(function (blockSwitcherSelector) {
              return !!document.querySelector(blockSwitcherSelector);
            }, '.block-editor-block-toolbar .block-editor-block-switcher'));

          case 1:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));

  return function hasBlockSwitcher() {
    return _ref.apply(this, arguments);
  };
}();
//# sourceMappingURL=has-block-switcher.js.map
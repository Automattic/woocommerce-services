import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Clears the local storage.
 */
export function clearLocalStorage() {
  return _clearLocalStorage.apply(this, arguments);
}

function _clearLocalStorage() {
  _clearLocalStorage = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.evaluate(function () {
              return window.localStorage.clear();
            });

          case 2:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _clearLocalStorage.apply(this, arguments);
}
//# sourceMappingURL=clear-local-storage.js.map
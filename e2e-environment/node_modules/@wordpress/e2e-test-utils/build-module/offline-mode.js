import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";
var _isOfflineMode = false;
export function toggleOfflineMode(_x) {
  return _toggleOfflineMode.apply(this, arguments);
}

function _toggleOfflineMode() {
  _toggleOfflineMode = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(isOffline) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _isOfflineMode = isOffline;
            page.setOfflineMode(isOffline);

          case 2:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _toggleOfflineMode.apply(this, arguments);
}

export function isOfflineMode() {
  return _isOfflineMode;
}
//# sourceMappingURL=offline-mode.js.map
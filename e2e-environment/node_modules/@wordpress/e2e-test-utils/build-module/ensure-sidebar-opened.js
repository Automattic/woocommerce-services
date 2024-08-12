import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Verifies that the edit post sidebar is opened, and if it is not, opens it.
 *
 * @return {Promise} Promise resolving once the edit post sidebar is opened.
 */
export function ensureSidebarOpened() {
  return _ensureSidebarOpened.apply(this, arguments);
}

function _ensureSidebarOpened() {
  _ensureSidebarOpened = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.prev = 0;
            return _context.abrupt("return", page.$eval('.edit-post-sidebar', function () {}));

          case 4:
            _context.prev = 4;
            _context.t0 = _context["catch"](0);
            return _context.abrupt("return", page.click('.edit-post-header__settings [aria-label="Settings"]'));

          case 7:
          case "end":
            return _context.stop();
        }
      }
    }, _callee, null, [[0, 4]]);
  }));
  return _ensureSidebarOpened.apply(this, arguments);
}
//# sourceMappingURL=ensure-sidebar-opened.js.map
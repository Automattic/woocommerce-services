import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { switchUserToAdmin } from './switch-user-to-admin';
import { switchUserToTest } from './switch-user-to-test';
import { visitAdminPage } from './visit-admin-page';
/**
 * Deactivates an active plugin.
 *
 * @param {string} slug Plugin slug.
 */

export function deactivatePlugin(_x) {
  return _deactivatePlugin.apply(this, arguments);
}

function _deactivatePlugin() {
  _deactivatePlugin = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(slug) {
    var deleteLink;
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return switchUserToAdmin();

          case 2:
            _context.next = 4;
            return visitAdminPage('plugins.php');

          case 4:
            _context.next = 6;
            return page.$("tr[data-slug=\"".concat(slug, "\"] .delete a"));

          case 6:
            deleteLink = _context.sent;

            if (!deleteLink) {
              _context.next = 11;
              break;
            }

            _context.next = 10;
            return switchUserToTest();

          case 10:
            return _context.abrupt("return");

          case 11:
            _context.next = 13;
            return page.click("tr[data-slug=\"".concat(slug, "\"] .deactivate a"));

          case 13:
            _context.next = 15;
            return page.waitForSelector("tr[data-slug=\"".concat(slug, "\"] .delete a"));

          case 15:
            _context.next = 17;
            return switchUserToTest();

          case 17:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _deactivatePlugin.apply(this, arguments);
}
//# sourceMappingURL=deactivate-plugin.js.map
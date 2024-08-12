import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { switchUserToAdmin } from './switch-user-to-admin';
import { switchUserToTest } from './switch-user-to-test';
import { visitAdminPage } from './visit-admin-page';
/**
 * Uninstalls a plugin.
 *
 * @param {string} slug Plugin slug.
 */

export function uninstallPlugin(_x) {
  return _uninstallPlugin.apply(this, arguments);
}

function _uninstallPlugin() {
  _uninstallPlugin = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(slug) {
    var confirmPromise;
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
            confirmPromise = new Promise(function (resolve) {
              page.once('dialog', function () {
                return resolve();
              });
            });
            _context.next = 7;
            return Promise.all([confirmPromise, page.click("tr[data-slug=\"".concat(slug, "\"] .delete a"))]);

          case 7:
            _context.next = 9;
            return page.waitForSelector("tr[data-slug=\"".concat(slug, "\"].deleted"));

          case 9:
            _context.next = 11;
            return switchUserToTest();

          case 11:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _uninstallPlugin.apply(this, arguments);
}
//# sourceMappingURL=uninstall-plugin.js.map
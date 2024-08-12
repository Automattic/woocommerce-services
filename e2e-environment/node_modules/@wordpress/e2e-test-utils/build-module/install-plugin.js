import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { switchUserToAdmin } from './switch-user-to-admin';
import { switchUserToTest } from './switch-user-to-test';
import { visitAdminPage } from './visit-admin-page';
/**
 * Installs a plugin from the WP.org repository.
 *
 * @param {string} slug        Plugin slug.
 * @param {string?} searchTerm If the plugin is not findable by its slug use an alternative term to search.
 */

export function installPlugin(_x, _x2) {
  return _installPlugin.apply(this, arguments);
}

function _installPlugin() {
  _installPlugin = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(slug, searchTerm) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return switchUserToAdmin();

          case 2:
            _context.next = 4;
            return visitAdminPage('plugin-install.php', 's=' + encodeURIComponent(searchTerm || slug) + '&tab=search&type=term');

          case 4:
            _context.next = 6;
            return page.click(".install-now[data-slug=\"".concat(slug, "\"]"));

          case 6:
            _context.next = 8;
            return page.waitForSelector(".activate-now[data-slug=\"".concat(slug, "\"]"));

          case 8:
            _context.next = 10;
            return switchUserToTest();

          case 10:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _installPlugin.apply(this, arguments);
}
//# sourceMappingURL=install-plugin.js.map
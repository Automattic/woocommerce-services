import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { loginUser } from './login-user';
import { WP_USERNAME, WP_ADMIN_USER } from './shared/config';
/**
 * Switches the current user to the admin user (if the user
 * running the test is not already the admin user).
 */

export function switchUserToAdmin() {
  return _switchUserToAdmin.apply(this, arguments);
}

function _switchUserToAdmin() {
  _switchUserToAdmin = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            if (!(WP_USERNAME === WP_ADMIN_USER.username)) {
              _context.next = 2;
              break;
            }

            return _context.abrupt("return");

          case 2:
            _context.next = 4;
            return loginUser(WP_ADMIN_USER.username, WP_ADMIN_USER.password);

          case 4:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _switchUserToAdmin.apply(this, arguments);
}
//# sourceMappingURL=switch-user-to-admin.js.map
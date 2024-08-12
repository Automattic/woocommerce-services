import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { loginUser } from './login-user';
import { WP_USERNAME, WP_ADMIN_USER } from './shared/config';
/**
 * Switches the current user to whichever user we should be
 * running the tests as (if we're not already that user).
 */

export function switchUserToTest() {
  return _switchUserToTest.apply(this, arguments);
}

function _switchUserToTest() {
  _switchUserToTest = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
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
            return loginUser();

          case 4:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _switchUserToTest.apply(this, arguments);
}
//# sourceMappingURL=switch-user-to-test.js.map
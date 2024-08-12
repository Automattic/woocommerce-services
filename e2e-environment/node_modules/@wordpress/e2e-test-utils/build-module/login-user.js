import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { WP_USERNAME, WP_PASSWORD } from './shared/config';
import { createURL } from './create-url';
import { isCurrentURL } from './is-current-url';
import { pressKeyWithModifier } from './press-key-with-modifier';
/**
 * Performs log in with specified username and password.
 *
 * @param {?string} username String to be used as user credential.
 * @param {?string} password String to be used as user credential.
 */

export function loginUser() {
  return _loginUser.apply(this, arguments);
}

function _loginUser() {
  _loginUser = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    var username,
        password,
        _args = arguments;
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            username = _args.length > 0 && _args[0] !== undefined ? _args[0] : WP_USERNAME;
            password = _args.length > 1 && _args[1] !== undefined ? _args[1] : WP_PASSWORD;

            if (isCurrentURL('wp-login.php')) {
              _context.next = 5;
              break;
            }

            _context.next = 5;
            return page.goto(createURL('wp-login.php'));

          case 5:
            _context.next = 7;
            return page.focus('#user_login');

          case 7:
            _context.next = 9;
            return pressKeyWithModifier('primary', 'a');

          case 9:
            _context.next = 11;
            return page.type('#user_login', username);

          case 11:
            _context.next = 13;
            return page.focus('#user_pass');

          case 13:
            _context.next = 15;
            return pressKeyWithModifier('primary', 'a');

          case 15:
            _context.next = 17;
            return page.type('#user_pass', password);

          case 17:
            _context.next = 19;
            return Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

          case 19:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _loginUser.apply(this, arguments);
}
//# sourceMappingURL=login-user.js.map
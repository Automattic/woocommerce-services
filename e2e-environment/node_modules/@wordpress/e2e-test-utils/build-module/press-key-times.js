import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Presses the given keyboard key a number of times in sequence.
 *
 * @param {string} key   Key to press.
 * @param {number} count Number of times to press.
 */
export function pressKeyTimes(_x, _x2) {
  return _pressKeyTimes.apply(this, arguments);
}

function _pressKeyTimes() {
  _pressKeyTimes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(key, count) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            if (!count--) {
              _context.next = 5;
              break;
            }

            _context.next = 3;
            return page.keyboard.press(key);

          case 3:
            _context.next = 0;
            break;

          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _pressKeyTimes.apply(this, arguments);
}
//# sourceMappingURL=press-key-times.js.map
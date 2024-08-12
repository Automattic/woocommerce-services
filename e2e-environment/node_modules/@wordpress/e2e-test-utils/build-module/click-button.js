import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Clicks a button based on the text on the button.
 *
 * @param {string} buttonText The text that appears on the button to click.
 */
export function clickButton(_x) {
  return _clickButton.apply(this, arguments);
}

function _clickButton() {
  _clickButton = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(buttonText) {
    var button;
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.waitForXPath("//button[contains(text(), '".concat(buttonText, "')]"));

          case 2:
            button = _context.sent;
            _context.next = 5;
            return button.click();

          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _clickButton.apply(this, arguments);
}
//# sourceMappingURL=click-button.js.map
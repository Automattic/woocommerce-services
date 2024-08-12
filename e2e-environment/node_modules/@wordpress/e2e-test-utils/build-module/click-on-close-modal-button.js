import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Click on the close button of an open modal.
 *
 * @param {?string} modalClassName Class name for the modal to close
 */
export function clickOnCloseModalButton(_x) {
  return _clickOnCloseModalButton.apply(this, arguments);
}

function _clickOnCloseModalButton() {
  _clickOnCloseModalButton = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(modalClassName) {
    var closeButtonClassName, closeButton;
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            closeButtonClassName = '.components-modal__header .components-button';

            if (modalClassName) {
              closeButtonClassName = "".concat(modalClassName, " ").concat(closeButtonClassName);
            }

            _context.next = 4;
            return page.$(closeButtonClassName);

          case 4:
            closeButton = _context.sent;

            if (!closeButton) {
              _context.next = 8;
              break;
            }

            _context.next = 8;
            return page.click(closeButtonClassName);

          case 8:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _clickOnCloseModalButton.apply(this, arguments);
}
//# sourceMappingURL=click-on-close-modal-button.js.map
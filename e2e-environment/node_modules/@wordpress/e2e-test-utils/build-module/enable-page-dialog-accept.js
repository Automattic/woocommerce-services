import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/** @typedef {import('puppeteer').Dialog} Dialog */

/**
 * Callback which automatically accepts dialog.
 *
 * @param {Dialog} dialog Dialog object dispatched by page via the 'dialog' event.
 */
function acceptPageDialog(_x) {
  return _acceptPageDialog.apply(this, arguments);
}
/**
 * Enables even listener which accepts a page dialog which
 * may appear when navigating away from Gutenberg.
 */


function _acceptPageDialog() {
  _acceptPageDialog = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(dialog) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return dialog.accept();

          case 2:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _acceptPageDialog.apply(this, arguments);
}

export function enablePageDialogAccept() {
  page.on('dialog', acceptPageDialog);
}
//# sourceMappingURL=enable-page-dialog-accept.js.map
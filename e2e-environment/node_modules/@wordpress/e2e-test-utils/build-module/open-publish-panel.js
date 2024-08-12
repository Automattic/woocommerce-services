import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Opens the publish panel.
 */
export function openPublishPanel() {
  return _openPublishPanel.apply(this, arguments);
}

function _openPublishPanel() {
  _openPublishPanel = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.waitForSelector('.editor-post-publish-panel__toggle:not([aria-disabled="true"])');

          case 2:
            _context.next = 4;
            return page.click('.editor-post-publish-panel__toggle');

          case 4:
            _context.next = 6;
            return page.waitForSelector('.editor-post-publish-button');

          case 6:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _openPublishPanel.apply(this, arguments);
}
//# sourceMappingURL=open-publish-panel.js.map
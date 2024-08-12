import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Sets code editor content
 *
 * @param {string} content New code editor content.
 *
 * @return {Promise} Promise resolving with an array containing all blocks in the document.
 */
export function setPostContent(_x) {
  return _setPostContent.apply(this, arguments);
}

function _setPostContent() {
  _setPostContent = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(content) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.evaluate(function (_content) {
              var dispatch = window.wp.data.dispatch;
              var blocks = wp.blocks.parse(_content);
              dispatch('core/block-editor').resetBlocks(blocks);
            }, content);

          case 2:
            return _context.abrupt("return", _context.sent);

          case 3:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _setPostContent.apply(this, arguments);
}
//# sourceMappingURL=set-post-content.js.map
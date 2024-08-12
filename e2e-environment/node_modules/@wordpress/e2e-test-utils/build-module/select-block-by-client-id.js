import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Given the clientId of a block, selects the block on the editor.
 *
 * @param {string} clientId Identified of the block.
 */
export function selectBlockByClientId(_x) {
  return _selectBlockByClientId.apply(this, arguments);
}

function _selectBlockByClientId() {
  _selectBlockByClientId = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(clientId) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.evaluate(function (id) {
              wp.data.dispatch('core/block-editor').selectBlock(id);
            }, clientId);

          case 2:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _selectBlockByClientId.apply(this, arguments);
}
//# sourceMappingURL=select-block-by-client-id.js.map
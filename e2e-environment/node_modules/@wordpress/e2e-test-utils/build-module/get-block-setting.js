import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Returns a string containing the block title associated with the provided block name.
 *
 * @param {string} blockName Block name.
 * @param {string} setting   Block setting e.g: title, attributes....
 *
 * @return {Promise} Promise resolving with a string containing the block title.
 */
export function getBlockSetting(_x, _x2) {
  return _getBlockSetting.apply(this, arguments);
}

function _getBlockSetting() {
  _getBlockSetting = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(blockName, setting) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            return _context.abrupt("return", page.evaluate(function (_blockName, _setting) {
              var blockType = wp.data.select('core/blocks').getBlockType(_blockName);
              return blockType && blockType[_setting];
            }, blockName, setting));

          case 1:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _getBlockSetting.apply(this, arguments);
}
//# sourceMappingURL=get-block-setting.js.map
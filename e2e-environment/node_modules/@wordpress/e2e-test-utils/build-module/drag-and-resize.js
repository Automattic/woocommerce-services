import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Clicks an element, drags a particular distance and releases the mouse button.
 *
 * @param {Object} element The puppeteer element handle.
 * @param {Object} delta   Object containing movement distances.
 * @param {number} delta.x Horizontal distance to drag.
 * @param {number} delta.y Vertical distance to drag.
 *
 * @return {Promise} Promise resolving when drag completes.
 */
export function dragAndResize(_x, _x2) {
  return _dragAndResize.apply(this, arguments);
}

function _dragAndResize() {
  _dragAndResize = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(element, delta) {
    var _yield$element$boundi, elementX, elementY, elementWidth, elementHeight, originX, originY;

    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return element.boundingBox();

          case 2:
            _yield$element$boundi = _context.sent;
            elementX = _yield$element$boundi.x;
            elementY = _yield$element$boundi.y;
            elementWidth = _yield$element$boundi.width;
            elementHeight = _yield$element$boundi.height;
            originX = elementX + elementWidth / 2;
            originY = elementY + elementHeight / 2;
            _context.next = 11;
            return page.mouse.move(originX, originY);

          case 11:
            _context.next = 13;
            return page.mouse.down();

          case 13:
            _context.next = 15;
            return page.mouse.move(originX + delta.x, originY + delta.y);

          case 15:
            _context.next = 17;
            return page.mouse.up();

          case 17:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _dragAndResize.apply(this, arguments);
}
//# sourceMappingURL=drag-and-resize.js.map
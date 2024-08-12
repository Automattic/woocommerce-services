import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * External dependencies
 */
import { first } from 'lodash';
/** @typedef {import('puppeteer').ElementHandle} ElementHandle */

/**
 * Finds a sidebar panel with the provided title.
 *
 * @param {string} panelTitle The name of sidebar panel.
 *
 * @return {?ElementHandle} Object that represents an in-page DOM element.
 */

export function findSidebarPanelToggleButtonWithTitle(_x) {
  return _findSidebarPanelToggleButtonWithTitle.apply(this, arguments);
}

function _findSidebarPanelToggleButtonWithTitle() {
  _findSidebarPanelToggleButtonWithTitle = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(panelTitle) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.t0 = first;
            _context.next = 3;
            return page.$x("//div[contains(@class,\"edit-post-sidebar\")]//button[@class=\"components-button components-panel__body-toggle\"][contains(text(),\"".concat(panelTitle, "\")]"));

          case 3:
            _context.t1 = _context.sent;
            return _context.abrupt("return", (0, _context.t0)(_context.t1));

          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _findSidebarPanelToggleButtonWithTitle.apply(this, arguments);
}
//# sourceMappingURL=find-sidebar-panel-toggle-button-with-title.js.map
import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Adds an event listener to the document which throws an error if there is a
 * loss of focus.
 */
export function enableFocusLossObservation() {
  return _enableFocusLossObservation.apply(this, arguments);
}
/**
 * Removes the focus loss listener that `enableFocusLossObservation()` adds.
 */

function _enableFocusLossObservation() {
  _enableFocusLossObservation = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.evaluate(function () {
              if (window._detectFocusLoss) {
                document.body.removeEventListener('focusout', window._detectFocusLoss);
              }

              window._detectFocusLoss = function (event) {
                if (!event.relatedTarget) {
                  throw new Error('Unexpected focus loss');
                }
              };

              document.body.addEventListener('focusout', window._detectFocusLoss);
            });

          case 2:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _enableFocusLossObservation.apply(this, arguments);
}

export function disableFocusLossObservation() {
  return _disableFocusLossObservation.apply(this, arguments);
}

function _disableFocusLossObservation() {
  _disableFocusLossObservation = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee2() {
    return _regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            _context2.next = 2;
            return page.evaluate(function () {
              if (window._detectFocusLoss) {
                document.body.removeEventListener('focusout', window._detectFocusLoss);
              }
            });

          case 2:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return _disableFocusLossObservation.apply(this, arguments);
}
//# sourceMappingURL=observe-focus-loss.js.map
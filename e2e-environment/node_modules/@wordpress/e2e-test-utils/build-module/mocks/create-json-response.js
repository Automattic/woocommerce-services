import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { getJSONResponse } from '../shared/get-json-response';
/**
 * Respond to a request with a JSON response.
 *
 * @param {string} mockResponse The mock object to wrap in a JSON response.
 * @return {Promise} Promise that responds to a request with the mock JSON response.
 */

export function createJSONResponse(mockResponse) {
  return /*#__PURE__*/function () {
    var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(request) {
      return _regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              return _context.abrupt("return", request.respond(getJSONResponse(mockResponse)));

            case 1:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }();
}
//# sourceMappingURL=create-json-response.js.map
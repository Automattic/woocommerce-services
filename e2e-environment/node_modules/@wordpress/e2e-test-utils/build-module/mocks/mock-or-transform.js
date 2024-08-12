import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * External dependencies
 */
import fetch from 'node-fetch';
/**
 * Internal dependencies
 */

import { getJSONResponse } from '../shared/get-json-response';
/**
 * Mocks a request with the supplied mock object, or allows it to run with an optional transform, based on the
 * deserialised JSON response for the request.
 *
 * @param {Function} mockCheck function that returns true if the request should be mocked.
 * @param {Object} mock A mock object to wrap in a JSON response, if the request should be mocked.
 * @param {Function|undefined} responseObjectTransform An optional function that transforms the response's object before the response is used.
 * @return {Promise} Promise that uses `mockCheck` to see if a request should be mocked with `mock`, and optionally transforms the response with `responseObjectTransform`.
 */

export function mockOrTransform(mockCheck, mock) {
  var responseObjectTransform = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : function (obj) {
    return obj;
  };
  return /*#__PURE__*/function () {
    var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(request) {
      var response, responseObject;
      return _regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _context.next = 2;
              return fetch(request.url(), {
                headers: request.headers(),
                method: request.method(),
                body: request.postData()
              });

            case 2:
              response = _context.sent;
              _context.next = 5;
              return response.json();

            case 5:
              responseObject = _context.sent;

              if (mockCheck(responseObject)) {
                request.respond(getJSONResponse(mock));
              } else {
                request.respond(getJSONResponse(responseObjectTransform(responseObject)));
              }

            case 7:
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
//# sourceMappingURL=mock-or-transform.js.map
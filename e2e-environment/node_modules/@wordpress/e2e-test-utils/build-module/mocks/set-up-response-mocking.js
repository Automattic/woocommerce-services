import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Track if we have already initialized the request interception.
 */
var interceptionInitialized = false;
/**
 * Array of mock responses.
 */

var requestMocks = [];
/**
 * Sets up mock checks and responses. Accepts a list of mock settings with the following properties:
 *
 * - `match`: function to check if a request should be mocked.
 * - `onRequestMatch`: async function to respond to the request.
 *
 * @example
 *
 * ```js
 * const MOCK_RESPONSES = [
 *   {
 *     match: isEmbedding( 'https://wordpress.org/gutenberg/handbook/' ),
 *     onRequestMatch: JSONResponse( MOCK_BAD_WORDPRESS_RESPONSE ),
 *   },
 *   {
 *     match: isEmbedding( 'https://wordpress.org/gutenberg/handbook/block-api/attributes/' ),
 *     onRequestMatch: JSONResponse( MOCK_EMBED_WORDPRESS_SUCCESS_RESPONSE ),
 *   }
 * ];
 * setUpResponseMocking( MOCK_RESPONSES );
 * ```
 *
 * If none of the mock settings match the request, the request is allowed to continue.
 *
 * @param {Array} mocks Array of mock settings.
 */

export function setUpResponseMocking(_x) {
  return _setUpResponseMocking.apply(this, arguments);
}

function _setUpResponseMocking() {
  _setUpResponseMocking = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee2(mocks) {
    return _regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            if (interceptionInitialized) {
              _context2.next = 5;
              break;
            }

            // We only want to set up the request interception once, or else we get a crash
            // when we try to process the same request twice.
            interceptionInitialized = true;
            _context2.next = 4;
            return page.setRequestInterception(true);

          case 4:
            page.on('request', /*#__PURE__*/function () {
              var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(request) {
                var i, mock;
                return _regeneratorRuntime.wrap(function _callee$(_context) {
                  while (1) {
                    switch (_context.prev = _context.next) {
                      case 0:
                        i = 0;

                      case 1:
                        if (!(i < requestMocks.length)) {
                          _context.next = 10;
                          break;
                        }

                        mock = requestMocks[i];

                        if (!mock.match(request)) {
                          _context.next = 7;
                          break;
                        }

                        _context.next = 6;
                        return mock.onRequestMatch(request);

                      case 6:
                        return _context.abrupt("return");

                      case 7:
                        i++;
                        _context.next = 1;
                        break;

                      case 10:
                        request.continue();

                      case 11:
                      case "end":
                        return _context.stop();
                    }
                  }
                }, _callee);
              }));

              return function (_x2) {
                return _ref.apply(this, arguments);
              };
            }());

          case 5:
            // Overwrite with the passed in mocks, so we can change the mocks mid-test to test
            // recovery from scenarios where a request had failed, but is working again.
            requestMocks = _toConsumableArray(mocks);

          case 6:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return _setUpResponseMocking.apply(this, arguments);
}
//# sourceMappingURL=set-up-response-mocking.js.map
/**
 * Creates a function to determine if a request is calling a URL with the substring present.
 *
 * @param {string} substring The substring to check for.
 * @return {Function} Function that determines if a request's URL contains substring.
 */
export function createURLMatcher(substring) {
  return function (request) {
    return -1 !== request.url().indexOf(substring);
  };
}
//# sourceMappingURL=create-url-matcher.js.map
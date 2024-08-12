/**
 * Internal dependencies
 */
import { createURL } from './create-url';
/**
 * Checks if current URL is a WordPress path.
 *
 * @param {string} WPPath String to be serialized as pathname.
 * @param {?string} query String to be serialized as query portion of URL.
 * @return {boolean} Boolean represents whether current URL is or not a WordPress path.
 */

export function isCurrentURL(WPPath) {
  var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var currentURL = new URL(page.url());
  currentURL.search = query;
  return createURL(WPPath, query) === currentURL.href;
}
//# sourceMappingURL=is-current-url.js.map
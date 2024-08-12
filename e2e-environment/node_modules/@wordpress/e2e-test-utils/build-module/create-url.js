/**
 * External dependencies
 */
import { join } from 'path';
/**
 * Internal dependencies
 */

import { WP_BASE_URL } from './shared/config';
/**
 * Creates new URL by parsing base URL, WPPath and query string.
 *
 * @param {string} WPPath String to be serialized as pathname.
 * @param {?string} query String to be serialized as query portion of URL.
 * @return {string} String which represents full URL.
 */

export function createURL(WPPath) {
  var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var url = new URL(WP_BASE_URL);
  url.pathname = join(url.pathname, WPPath);
  url.search = query;
  return url.href;
}
//# sourceMappingURL=create-url.js.map
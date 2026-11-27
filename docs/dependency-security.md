# JavaScript dependency security

Tracked in [WOOTAX-462](https://linear.app/a8c/issue/WOOTAX-462), under
[WOO13-274](https://linear.app/a8c/issue/WOO13-274). Audit snapshot: 2026-11-27.

CI uses `npm ci` and the root `npm-shrinkwrap.json`. The separate Playwright
package uses `tests/e2e-playwright/package-lock.json`.

| Scope | Before | After |
| --- | --- | --- |
| Root, all dependencies | 8 low, 90 moderate, 50 high, 7 critical | 0 |
| Root, `--omit=dev` | 8 low, 21 moderate, 37 high, 2 critical | 0 |
| Playwright package | 4 moderate | 0 |

The first revision reduced the root count to 117 (6 low, 90 moderate, 21 high).
This revision removes the remaining findings. Counts include inherited package
findings, not just distinct advisories. Build tools remain in `dependencies`, so
`--omit=dev` is not a shipped-code inventory. No findings are suppressed.

## Build and test migrations

- webpack 5, webpack-cli 7 and webpack-dev-server 6 replace the old webpack 4
  matching, watching and development-server stack. The server binds to loopback,
  validates Host headers, and permits validated localhost/127.0.0.1 origins, including
  their ports, for CORS.
- PostCSS 8, Autoprefixer 10 and css-minimizer-webpack-plugin replace the old CSS
  pipeline and SVGO 1. Preserve CSS scoping, absolute CSS URLs, quoted font names,
  the ES5 output target, existing asset names and runtime plugin-relative URLs.
- Explicit `util`, `url`, `path` and `process` browser support preserves the
  webpack 4 polyfills used by existing client code. DOMPurify uses its default
  ESM export. Unused D3 dependencies are removed.
- Jest 30 and Puppeteer 25 remove the old matching stack and ZIP extractor.
  Update removed Jest mock APIs, asset transforms and Puppeteer XPath/wait APIs.
  The E2E scripts enable VM modules for Puppeteer's ESM dependencies.
- ESLint 7 and @babel/eslint-parser keep the existing lint rules while supporting
  webpack 5 and correctly identifying used loop variables.

Native fetch is preserved, with an XHR fallback only when it is unavailable.
Resolved root custom properties are removed after PostCSS processing to prevent
styles from leaking outside the plugin. The CSS minifier retains its safe parser.

Node and React versions, public PHP APIs, hooks, script handles, shipping gates
and persisted site state do not change. Multisite merchant flows were not tested.

## Why the overrides remain

| Override | Legacy consumer / reason |
| --- | --- |
| local puppeteer-utils | Keep the prebuilt package; patched sources and build script reproduce Puppeteer compatibility changes. |
| react-addons-create-fragment | Existing compatibility pin for old React consumers. |
| underscore 1.13.8 | nomnom pins vulnerable 1.6.x. |
| path-to-regexp 1.9 | page pins 1.2.x; retain its 1.x route API. |
| node-fetch 2.7 | isomorphic-fetch pins 1.x; retain CommonJS fetch and response APIs. |
| debug 2.6.9 | i18n-calypso pins 2.2.0. |
| axios 0.34 | Slack and the legacy WooCommerce API client pin older 0.x versions. |
| modern Jest / WordPress test utilities | WooCommerce's legacy E2E environment must use the same Jest and Puppeteer stack as the project. |
| globby 11 | i18n-calypso-cli's globby 9 brings vulnerable micromatch through fast-glob 2; retain CommonJS and async/sync glob APIs. |
| Cheerio 1.0.0-rc.12 | Enzyme requires the old CommonJS/deep-import API. Also declare Cheerio directly for chai-enzyme's undeclared import. |
| @cypress/request | Maintained request-compatible fork replaces the vulnerable, unmaintained request used by the E2E environment. |
| i18n-calypso → moment-timezone 0.5.48+ | Patch its vulnerable pin within 0.5.x; leave @wordpress/date on its declared range and retain the build's 2000–2040 data window. |
| glob 14 / minimatch 11 | Jest, coverage tools and the CTRF reporter constrain older vulnerable majors; preserve their sync/async matching APIs. |
| uuid 11 | WordPress components uses only the supported named `v4()` API for notice IDs. |

The build declares moment-timezone 0.5.x directly to satisfy the timezone-data
plugin's peer dependency with legacy peer installation enabled.

Remove an override when its consumer accepts a patched version itself. Overrides
for removed webpack 4/Puppeteer 13 dependencies have been removed.

## Validation

- Clean `npm ci`, production build, ESLint and 246 client tests.
- Chromium tests cover shipping XPath/cart updates, field replacement, popup
  listener cleanup on success/failure, production bundle startup with relocated
  lazy assets, CSS isolation, and the shipping-disabled gate. These run in the JS CI job against local HTML fixtures, without WordPress
  or carrier calls. Run `npm run dist`, `npx puppeteer browsers install chrome`,
  then `npm run test:e2e-tooling` locally. Configuration tests check the safe CSS
  parser, allowed/rejected CORS origins, and that the legacy suite configures its WordPress URL and credentials before importing test utilities.
  The fixture tests also pass with an unreachable WordPress URL.
- The legacy suite explicitly loads the Babel runtime required by its precompiled
  environment. CI installs Chrome system dependencies with root privileges while
  retaining the runner user's browser cache.
- Development server returns the bundle for a local request and HTTP 403 for an
  unrecognized Host header.
- Playwright package typecheck and audit. The fast-uri update overlaps Dependabot
  PR #3070; the webpack-dev-server migration supersedes PR #2981's proposed bump.

These automated checks do not represent a complete merchant checkout or label
purchase test. Manual WordPress verification remains outstanding.

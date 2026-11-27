# JavaScript dependency security

Tracked in [WOOTAX-462](https://linear.app/a8c/issue/WOOTAX-462), under
[WOO13-274](https://linear.app/a8c/issue/WOO13-274). Audit snapshot: 2026-11-27.

CI uses `npm ci` and the root `npm-shrinkwrap.json`. The separate Playwright
package uses `tests/e2e-playwright/package-lock.json`.

| Scope | Before high / critical | After high / critical |
| --- | --- | --- |
| Root, all dependencies | 50 / 7 | 21 / 0 |
| Root, `--omit=dev` | 37 / 2 | 15 / 0 |
| Playwright package | 0 / 0 | 0 / 0 |

Counts are vulnerable packages, including inherited findings, not distinct
advisories or confirmed exploitable application paths. Several build tools are
listed under `dependencies`, so `--omit=dev` is not a shipped-code inventory.
Moderate and low findings remain outside this remediation's scope.

## Why the overrides exist

Legacy consumers pin vulnerable versions. Keep these overrides until those
consumers accept patched versions themselves; do not remove them solely because
the direct dependency has no advisory.

| Override | Consumer and compatibility check |
| --- | --- |
| form-data 2.5.6 | request pins 2.3.x; retain the 2.x multipart API |
| underscore 1.13.8 | nomnom, used by po2json; translation parsing smoke check |
| path-to-regexp 1.9 | page pins 1.2.x; retain the 1.x route API |
| debug 2.6.9 | i18n-calypso pins 2.2.0; patch within the same major |
| node-fetch 2.7 | isomorphic-fetch and WordPress test utilities; CommonJS fetch and response smoke check |
| node-forge 1.4.4 | selfsigned; certificate generation smoke check |
| flatted 3.4.4 | flat-cache; save and reload circular data |
| tar 7.5.24 | cacache dependency; archive creation/extraction roundtrip |
| serialize-javascript 7.1.4 | webpack's and the direct Terser plugin's worker serialization; production build and function serialization check |
| axios 0.34 | Slack and legacy WooCommerce API clients; mocked Slack HTTP request |
| tar-fs 2.1.4 | Puppeteer pins 2.1.1; patch within the same major |
| ws 8.21 | Puppeteer pins 8.5; update within the same major |
| tmp 0.2.6 | external-editor; temporary file creation and cleanup check |

The existing Node 24 runtime supports the updated transitive packages. These
changes preserve the current webpack, Jest, Puppeteer and React majors. The
production build, client suite and ESLint validate their existing consumers.
This does not replace manual merchant-flow or legacy browser-suite testing.

## Remaining high findings

These are unresolved, not dismissed or suppressed. All 21 package findings are
listed below. Clearing them requires a coordinated migration or a maintained
replacement, rather than forcing incompatible transitive APIs.

| Packages | Dependency path / reason remaining |
| --- | --- |
| autoprefixer, browserslist, postcss | webpack CSS pipeline uses old PostCSS and Autoprefixer APIs. Migrate loaders, plugins and configuration together to patched PostCSS 8 and Autoprefixer 10. Old PostCSS majors have no patch for all current advisories. |
| postcss-svgo, svgo, css-select, nth-check | optimize-css-assets-webpack-plugin → cssnano → postcss-svgo uses SVGO 1. A patched SVGO major changes its plugin API and must be migrated with the CSS optimizer. |
| braces, micromatch, chokidar, watchpack, watchpack-chokidar2, webpack | webpack 4's matching and watching stack pins old APIs. Migrate webpack/watchpack rather than globally replacing braces or micromatch across consumers. |
| webpack-dev-server, webpack-dev-middleware, http-proxy-middleware, ip | Development server 3 and its middleware stack need a coordinated major upgrade with webpack configuration and the start script. `ip` has no patched version for its advisory; remove it through a maintained server stack. |
| html-loader, html-minifier | html-loader 0.5 uses unmaintained html-minifier. Upgrade the loader and verify module exports/minification; html-minifier has no patched release. |
| puppeteer, extract-zip | Puppeteer 13's browser installer uses extract-zip, which has no patched release in the audit. Migrate browser downloading via a maintained Puppeteer version and verify the grandfathered shipping-label suite with its new browser. |

The remaining paths are build, development-server or test-browser tooling, not
imports in the merchant-facing JavaScript. They still matter for developer and CI
security. An existing Dependabot PR also proposes upgrading webpack-dev-server;
coordinate that migration rather than treating this partial remediation as its
replacement.

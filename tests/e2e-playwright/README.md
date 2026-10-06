# WooCommerce Tax E2E suite (Playwright, QIT)

A Playwright suite registered as a QIT custom E2E test package. It runs locally
against wp-env, locally through QIT, and in CI on the weekly Cron QIT workflow and
the Manual Test Runner. It does not run per PR.

This is separate from the legacy Puppeteer suite in `tests/e2e/`, which covers the
shipping-label flow for grandfathered stores and runs through `wc-e2e` from the
repository root.

## Coverage

| Spec | What it checks |
| --- | --- |
| `smoke.spec.ts` | wp-admin and the tax settings screen load with no fatal PHP error; the plugin is active. |
| `settings.spec.ts` | Automated taxes turn off and on through the tax settings screen, and the core tax options they manage are locked while on. |
| `tax.spec.ts` | With automated taxes on, the cart shows the TaxJar rates; the request sent to TaxJar has the store as the origin and the customer as the destination; a classic checkout places an order whose total includes the tax. |

## How TaxJar is stubbed

`support/wc-services-e2e-tax-stub/` is a test-only WordPress plugin, loaded through
`.wp-env.json` and `qit.json`. It never ships: the release zip is built from the
whitelist in `tasks/release.js`, which does not include `tests/`.

Automated taxes normally need a WordPress.com connection. While armed, the stub
stands in for one:

- it turns on Jetpack offline mode (`jetpack_offline_mode`), so the plugin loads;
- it accepts the terms of service if they are not accepted yet;
- it supplies a sentinel token through `wc_connect_jetpack_access_token`, so
  requests to the Connect server can be signed;
- it answers `taxjar/v2/taxes` with a 6.25% state rate and a 2% city rate for every
  line item, and `taxjar/v2/addresses/validate` with the address it was sent;
- it fails every other Connect server request with a `WP_Error`, so nothing leaves
  the site.

It records each TaxJar request it answers, so specs can assert on what the plugin
sent. The stub arms only through its REST route (`POST /arm`), and refuses to arm on
a store with a real WordPress.com connection. The Playwright global teardown
disarms it and undoes the terms acceptance it made.

Provisioning (`utils/provisioning.ts`) rewrites the store address, enables taxes and
the Check payments gateway, and overwrites the logged-in admin's billing and
shipping address. Run the suite only against wp-env or a disposable QIT site.

## Running locally

Docker is required. From the repository root:

```bash
source ~/.nvm/nvm.sh && nvm use
composer install
npm run dist                          # the plugin needs its built assets
npm run env:start                     # WordPress, WooCommerce, this plugin and the stub
npm run test:e2e-playwright:install   # one-time: the suite's own deps and Chromium
RESET_E2E_SESSION=1 npm run test:e2e-playwright:local
```

`npm run test:e2e-playwright:local:show` runs it headed. If something else holds
port 8888, add a `.wp-env.override.json` with a different `port`. It is gitignored,
and `bin/resolve-base-url.js` prefers it.

The suite is its own npm package with a committed `package-lock.json`. QIT installs
it in place. Do not add its dependencies to the root `package.json`.

## Through QIT

```bash
composer run qit:e2e
```

In CI: **Actions → Manual Test Runner**, with **QIT Tests** set to
`Custom Plugin E2E (tests/e2e-playwright)`.

## Shared utilities

`utils/{paths,wp-login,qit,admin-session,php-errors}.ts`, `fixtures/auth.setup.ts`
and `bin/resolve-base-url.js` are byte-identical to the sibling extension suites
(for example `woocommerce-shipping-australia-post`). Keep them that way, so fixes
carry across repositories.

The one exception is `bin/resolve-base-url.js`, which has no `'use strict';` line.
This repository's pre-commit hook runs `eslint --fix`, which removes it, and the
script behaves the same without it. When porting a fix from a sibling repository,
compare the rest of the file.

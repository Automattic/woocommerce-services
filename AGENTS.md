# Agent Instructions

## Product Scope
- CRITICAL: This repository is now Woo Tax-first; active maintenance and new development focus on tax functionality.
- CRITICAL: Shipping functionality is deprecated for new installs.
- CRITICAL: Shipping label purchase functionality remains supported only for grandfathered installs that purchased at least one label before deprecation.
- CRITICAL: Live rates remain supported only for grandfathered accounts.
- MUST NOT introduce new shipping product surface area for non-grandfathered installs.

## Project Layout
- PHP plugin bootstrap: `woocommerce-services.php`
- PHP classes: `classes/` and `src/`
- JS client code: `client/`
- Build artifacts: `dist/`
- Tests: `tests/`

## Package Managers
- Node/npm: `npm install`, `npm run dist`, `npm run test-client`, `npm run eslint`
- Composer: `composer install`, `composer test`, `composer phpcs`

## Runtime Compatibility
- CRITICAL: This repo is tied to an old Node runtime; use `.nvmrc` (`10.18.1`) for local work unless a migration is explicitly planned.
- CRITICAL: The old Node requirement is intentional because this project depends on a forked legacy Calypso package in the `wp-calypso` submodule (package name: `wp-calypso`).
- CRITICAL: Always run `source ~/.nvm/nvm.sh && nvm use` before any command that invokes Node (e.g., `npm install`, `npm run`, `git push` — the pre-push hook runs `npm test`). Without this, tests will fail with Node version incompatibilities.
- MUST NOT perform incidental Node/toolchain upgrades while making feature or bugfix changes.

## Common Commands
```bash
npm install
composer install
npm run dist
npm run test-client
npm run eslint
composer test          # PHPUnit; auto-checks the test env and repairs only what's broken
composer test:setup    # one-shot: bring up the Dockerized DB + install WP/WC test deps
composer phpcs
```

### PHPUnit test environment
- `composer test` is the single entry point. It runs `tests/bin/run-tests.sh`, which
  health-checks the environment (`tests/bin/check-test-env.sh`) and repairs only the
  components that fail their probe before running PHPUnit. No manual setup step is needed
  on a healthy machine.
- Output format: `composer test` uses PHPUnit's compact progress output when its stdout is
  captured (CI logs, pre-commit hooks, AI agents) and the readable `--testdox` format at an
  interactive terminal. Force it either way with `TESTDOX=1` / `TESTDOX=0` (e.g.
  `TESTDOX=1 composer test`).
- The test DB is a throwaway MariaDB container defined in `tests/docker-compose.yml`
  (published on `127.0.0.1:4416`; credentials in `tests/test.env`). Docker is required;
  a local mysql client is not (the installer materialises shims that proxy through the
  container when no client is present).
- `composer test:setup` installs/repairs every missing component; for a full rebuild that
  also wipes the DB volume run `bash tests/bin/install-wc-tests.sh --force`.
- WP/WC are installed under the system temp dir (honors `TMPDIR`). The WC clone builds
  with its own modern Node via the WC checkout's `.nvmrc`; this is independent of the
  plugin's own pinned Node (`.nvmrc` = 10.18.1) and PHPUnit needs no Node at all.
- By default the installer clones the **latest** WooCommerce release, whereas CI tests
  the 3 latest WC minors — so a local `composer test` can run against a different WC than
  the merge gate. To reproduce a specific version, pin it on a forced (re)install:
  `WC_VERSION=9.4.0 bash tests/bin/install-wc-tests.sh --force` (`EXPECTED_WP_VERSION`
  pins WordPress the same way).

## Key Conventions
- Base new behavior on tax-only mode rules and existing shipping eligibility checks.
- Keep shipping logic gated by eligibility; preserve grandfathered behavior.
- Keep changes in source files; do not hand-edit generated build outputs in `dist/`.
- Follow existing WordPress/WooCommerce coding standards and linting rules in this repository.

## Architecture Notes
- `has_only_tax_functionality()` and `should_load_shipping_features()` in `woocommerce-services.php` are core gates for tax-only vs shipping-enabled behavior.
- Shipping behavior must remain compatibility-only for eligible legacy stores.
- Tax behavior is the primary development path.

## Common Pitfalls
- Accidentally enabling shipping UI/features for non-grandfathered stores.
- Regressing legacy label purchase support for eligible installs.
- Treating shipping migrations as feature expansion instead of compatibility maintenance.

## Local Skills
- Use `create-pr` for PR preparation workflows. See `.agents/skills/create-pr/SKILL.md`.
- Use `write-changelog` for changelog entry workflows. See `.agents/skills/write-changelog/SKILL.md`.

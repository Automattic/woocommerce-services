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
- Composer: `composer install`, `composer test`, `composer check-all` (PHPCS — see Linting)

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
composer check-all     # PHPCS (see Linting below)
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

## Linting (PHPCS) — split by concern
The PHPCS ruleset is split into concern-specific files, all importing `.phpcs.common.xml` (shared file/exclude/arg/config). Run `check-all` (the `phpcs.xml.dist` aggregator) before pushing, or a single scope while iterating:
```bash
composer run check-all          # Aggregator (phpcs.xml.dist): runs security + identity + php together
composer run check-php          # Generic PHP rules only (WC-Core + WP-Core/Extra/Docs, minus security & i18n)
composer run check-php:fix      # Auto-fix for the check-php scope
composer run check-security     # Security sniffs (escaping, sanitization, nonces, eval)
composer run check-identity     # Plugin identity: text_domain (i18n) + global prefix whitelist
```
The husky pre-commit hook (`bin/wc-phpcbf.sh` via lint-staged) auto-fixes staged PHP using the same `phpcs.xml.dist` aggregator, so pre-commit fixes stay aligned with `check-all`.

## Shell Script Portability (GNU vs BSD)
The test-env scripts (`tests/bin/*.sh`) and hook helpers (`bin/*.sh`) run on Linux/CI (GNU userland) **and** on contributors' machines (stock macOS ships BSD `grep`/`sed`). CI is GNU-only, so BSD-only breakage never shows up in the merge gate — keep both in mind when editing any shell script:
- Use POSIX character classes, not GNU regex shorthands: `[[:space:]]` / `[[:digit:]]` / `[[:alnum:]]` instead of `\s` / `\d` / `\w`. BSD `grep -E`/`sed -E` treat `\s` as a literal `s`, so patterns silently stop matching — this cost us a full-reinstall misfire in the `wp-tests-config.php` DB_HOST probe.
- `sed -i` is not portable: GNU is `sed -i`, BSD needs a backup suffix (`sed -i .bak`). Branch on `[[ "$(uname -s)" == 'Darwin' ]]` and remove the `.bak` afterward — see `repair_wp_config()` in `install-wc-tests.sh`.
- Prefer ERE (`grep -E` / `sed -E`) with POSIX quantifiers; avoid GNU-only escapes like `\+`, `\?`, `\|` in BRE. When a script carries regex, test it on macOS or reason through the BSD case before merging.

## Key Conventions
- Base new behavior on tax-only mode rules and existing shipping eligibility checks.
- Keep shipping logic gated by eligibility; preserve grandfathered behavior.
- Keep changes in source files; do not hand-edit generated build outputs in `dist/`.
- Follow existing WordPress/WooCommerce coding standards and linting rules in this repository.

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, or method signature is **high-risk** and **must state its backward-compatibility impact in the PR description** - regardless of which namespace the symbol lives in. The `WC_Connect_*` classes live in the global namespace and are referenced by third-party code; treat all of them as externally exposed. Newer code under `Automattic\WCServices\*` is not automatically safe either: a modern namespace is a code-organization choice, not a privacy guarantee, and anything reachable from outside can already have consumers.

Treat a symbol as **externally exposed** when it is implemented or consumed outside this repository - by other plugins, themes, or site snippets - even if it looks internal. When in doubt, assume it is exposed and state the BC impact.

**Adding a method to an interface that external code can implement must be flagged explicitly.** It is a backward-incompatible change: existing implementers fatal on load because they no longer satisfy the contract. Likewise, **removing a required method from an interface is breaking** for existing implementers (they carry a now-dead method, which static analysis such as PHPStan will flag). Prefer a non-breaking alternative: add the method to the concrete class rather than the interface, introduce a separate new interface, or supply a default implementation via an abstract base class.

**Deprecate, don't rename.** For existing public symbols (classes, interfaces, methods, constants, hooks), never rename or remove them in place. Mark the old symbol `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window so external consumers have time to migrate.

> This rule exists because WooCommerce 10.9.0 was reverted on WP Cloud: a required method added to a published interface fataled every older WooCommerce Stripe Gateway version that implemented it. The same failure mode applies to any contract this plugin publishes.

Deprecation also cuts the other way here: because shipping is compatibility-only for grandfathered installs, removing or narrowing an existing shipping surface is a BC change for those stores, not cleanup. See Product Scope.

### The compatibility surface is wider than PHP signatures

WordPress exposes more contracts than class and function signatures. The following are equally binding: a change to any of them is **high-risk** and requires the same backward-compatibility impact statement in the PR description.

**Hooks and filters are public contracts.** Every `do_action` and `apply_filters` call is an interface that third-party callbacks depend on, including this plugin's `wc_connect_*`, `wc_services_*`, `wcservices_*`, and `wcship_*` hooks. Removing a hook, renaming it, or removing/reordering its arguments breaks every attached callback. Changing *when* or *whether* a hook fires can break consumers that depend on its timing. Additive is the safe path: append new arguments at the end, never remove or reorder existing ones. To retire a hook, fire it through `do_action_deprecated()` / `apply_filters_deprecated()` for a deprecation window instead of deleting it.

**Do not assume global state.** Tax calculation is reachable from the cart and checkout, the Store API, the REST API, cron, WP-CLI, and webhooks, and not all of those set the globals a front-end request does (`$post`, `$wp_query`, an initialized session or cart). A newly introduced read of a global, or of `WC()->...` state, in a path reachable outside a standard request is a fatal or a silent misbehavior in the contexts that do not set it - REST order updates are a repeat offender in this repo. Guard the exact dependency explicitly: use `function_exists`/`class_exists` for symbols, `isset` for variables, `did_action` for lifecycle state, and verify that `WC()` and the required component are initialized before dereferencing `WC()->...`.

**Do not assume single-site.** Multisite changes where data lives: site-scoped vs network-scoped options (`get_option` vs `get_site_option`), per-site tables, user roles and capabilities, and upload paths all differ. This plugin's settings, service schemas, and connection state are site-scoped, so a network-activated install holds one set per site. A change that reads or writes site state must state in its PR whether it behaves correctly under multisite - and if it was not tested there, say so explicitly.

**Do not assume install layout.** WordPress could be configured to run in a subdirectory, with relocated `wp-content`, and behind reverse proxies. Never build paths or URLs by concatenation from the domain root; derive them (`plugins_url()`, `plugin_dir_path()`, `wp_upload_dir()`, and mind the `home_url()` vs `site_url()` distinction). This matters for `dist/` asset URLs and for any store URL sent to the Connect server. A path that works on a root install and breaks elsewhere is a compatibility bug, not an edge case.

### Before changing any public or externally exposed surface (agent checklist)

1. Identify the contract you are touching: signature, hook, global/scope expectation, site topology, or install layout.
2. Assume unseen consumers. You cannot enumerate third-party code; if the surface is reachable from outside this plugin, someone consumes it.
3. Prefer the additive path (new optional method, appended hook argument, new symbol + deprecation) over changing what exists.
4. State the impact in the PR description: what changed, who could consume it, and why it is safe or what the deprecation path is.
5. If you cannot establish the impact, stop and flag it to the user as needing review.

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

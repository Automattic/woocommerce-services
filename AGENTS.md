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
composer test
composer check-all
```

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

## Key Conventions
- Base new behavior on tax-only mode rules and existing shipping eligibility checks.
- Keep shipping logic gated by eligibility; preserve grandfathered behavior.
- Keep changes in source files; do not hand-edit generated build outputs in `dist/`.
- Follow existing WordPress/WooCommerce coding standards and linting rules in this repository.

## Backward Compatibility

The `WC_Connect_*` classes live in the global namespace and are referenced by third-party code; treat them all as externally exposed.

Any change to the signature of a public or externally exposed class, interface, function, method, or hook is high-risk and must state its backward-compatibility impact in the PR description.

- Treat a symbol as externally exposed when code outside this repository (other plugins, themes, or site snippets) can call, extend, implement, or hook into it. When in doubt, assume it is exposed and state the BC impact.
- Adding a method to an interface (or an abstract method to a base class) that external code can implement is breaking: existing implementers fatal on load. Removing a required method is breaking too. Prefer a non-breaking alternative: add the method to the concrete class, or provide a default implementation in a base class.
- Deprecate, don't rename or remove. Keep the old symbol working with a deprecation notice, introduce the replacement alongside it, and give consumers a migration window before removal.

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

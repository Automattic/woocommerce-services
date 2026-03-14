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
- MUST NOT perform incidental Node/toolchain upgrades while making feature or bugfix changes.

## Common Commands
```bash
npm install
composer install
npm run dist
npm run test-client
npm run eslint
composer test
composer phpcs
```

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

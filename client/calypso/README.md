# Vendored wp-calypso client modules

These files are verbatim copies of `client/**` from
[Automattic/wp-calypso](https://github.com/Automattic/wp-calypso), taken from the commit
this repository's `wp-calypso` submodule was pinned to before it was removed:

    79b83cb343521370813bc052b2789d3245cd9698  (2019-04-17, Automattic/wp-calypso)

They are **frozen**, not maintained. Upstream Calypso deleted or moved every module here
years ago, so there is nothing to upgrade to — the pin was always the strategy, and this
directory is that pin without a 489 MB submodule behind it.

## Why these files and no others

The list is the transitive closure that a production build actually reaches, derived from
the webpack module graph rather than by hand:

```bash
NODE_ENV=production DISABLE_FEATURES=wpcom-user-bootstrap CALYPSO_CLIENT=true \
  npx webpack --json --output-path /tmp/wcs-build > /tmp/wcs-stats.json
```

then collecting every `wp-calypso/client/...` module name in `/tmp/wcs-stats.json`
(including the inner modules of concatenated ones).

Two things that graph does *not* show, and which were added on top:

- `state/action-types.js` — pure constants, fully tree-shaken out of the bundle, but
  imported by both these files and `client/extensions/`.
- The SCSS pulled in through `sass-loader`'s `includePaths` rather than through JS
  (`components/{dialog,popover,tooltip}/style.scss` and the partials reached from
  `assets/stylesheets/_components.scss`). Sass inlines `@import`s, so they never appear
  as webpack modules.

## Rules

- Do not edit, reformat, or lint these files — `client/calypso/*` is in `.eslintignore`
  so the repo's rules do not fight upstream's 2019 style.
- Do not add to this directory to "get a component". Anything new belongs in
  `client/components/`; this tree exists to be deleted, not grown.
- Shipping is compatibility-only for grandfathered installs (see `AGENTS.md`), and every
  consumer of this directory is shipping UI. When that UI goes, this directory goes with it.

Licensed GPL-2.0-or-later, same as upstream Calypso. See `CREDITS.md`.

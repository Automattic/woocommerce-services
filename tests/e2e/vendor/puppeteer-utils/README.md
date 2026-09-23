# Prebuilt @automattic/puppeteer-utils

A compiled copy of [Automattic/puppeteer-utils](https://github.com/Automattic/puppeteer-utils)
at commit `0f3ec50fc22d7bd2a4bd69fc172e8a66d958ef2d` - the commit
`@woocommerce/e2e-environment` pins as a git dependency.

## Why this exists

Upstream is installed from git and builds itself in a `postinstall`:

    babel --presets=@babel/preset-env src -d lib

It lists `@babel/cli`, `@babel/core` and `@babel/preset-env` as unpinned `^7.8.3`
dependencies, so that build resolves whatever is newest. An old `preset-env` then meets a
current `@babel/helper-compilation-targets`, whose `exports` map has no main entry, and
every Node from 12 up refuses the require. Picking another ref does not help: upstream
HEAD fails the same way, and `e2e-environment@0.3.0` pins the same commit.

That single step is what kept the whole e2e suite on Node 10. The package is ~490 lines of
source and the "build" is only ESM -> CommonJS, so it is compiled here once and the git
dependency is replaced through `overrides` in the root `package.json`. Nothing about the
compiled code needs an old runtime.

`package.json` lists the five packages `lib/` really requires. Upstream's manifest also
declared babel, eslint, jest, prettier and puppeteer as runtime dependencies; those were
build tooling and are left out.

## License

Upstream declares no license: there is no LICENSE file and no `license` field in its
`package.json`. It is a public Automattic repository, and this plugin has always installed
and run this exact commit as a git dependency of `@woocommerce/e2e-environment`. It is used
only by the end-to-end tests and is not part of the released plugin (`tasks/release.js`
does not copy `tests/`).

## Rebuilding

    curl -sSL https://codeload.github.com/Automattic/puppeteer-utils/tar.gz/0f3ec50fc22d7bd2a4bd69fc172e8a66d958ef2d | tar xz
    npx babel --no-babelrc --presets=@babel/preset-env puppeteer-utils-*/src -d tests/e2e/vendor/puppeteer-utils/lib

Do not edit `lib/` by hand.

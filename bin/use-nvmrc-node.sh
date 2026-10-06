#!/bin/sh

# Sourced by the git hooks in .husky/. Git does not load nvm, so a hook started from a
# GUI client, an IDE or a non-interactive shell gets whatever node is first on PATH.
# When nvm is installed, switch to the version in .nvmrc. Otherwise leave PATH alone.
NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
if [ -s "$NVM_DIR/nvm.sh" ]; then
	{ . "$NVM_DIR/nvm.sh" && nvm use --silent; } >/dev/null 2>&1 \
		|| echo "husky - could not switch to the Node.js version in .nvmrc; run: nvm install"
fi

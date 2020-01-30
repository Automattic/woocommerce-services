#!/usr/bin/env node

const { spawn } = require( 'child_process' );
const program = require( 'commander' );

const baseUrl = process.env.WP_BASE_URL;
const adminUserName = process.env.WP_ADMIN_USER_NAME;
const adminUserPassword = process.env.WP_ADMIN_USER_PW;

const envVariablesSet = baseUrl && adminUserName && adminUserPassword;

if( ! envVariablesSet ) {
	! baseUrl && console.error( "\033[31m WP_BASE_URL \x1b[0m environment variable is missing!");
	! adminUserName && console.error( "\033[31m WP_ADMIN_USER_NAME \x1b[0m environment variable is missing!");
	! adminUserPassword && console.error( "\033[31m WP_ADMIN_USER_PW \x1b[0m environment variable is missing!");
	throw new Error( "Test configutartion variables missing!" );
}

program
	.usage( '<file ...> [options]' )
	.option( '--dev', 'Development mode' )
	.parse( process.argv );

const testEnvVars = {
	NODE_ENV: 'test:e2e',
	JEST_PUPPETEER_CONFIG: 'tests/e2e-tests/config/jest-puppeteer.config.js',
	NODE_CONFIG_DIR: 'tests/e2e-tests/config',
};

if ( program.dev ) {
	testEnvVars.JEST_PUPPETEER_CONFIG = 'tests/e2e-tests/config/jest-puppeteer.dev.config.js';
}

const envVars = Object.assign( {}, process.env, testEnvVars );

spawn(
	'jest',
	[
		'--runInBand',
		'--config=tests/e2e-tests/config/jest.config.js',
		'--rootDir=./',
		'--verbose',
		program.args,
	],
	{
		stdio: 'inherit',
		env: envVars,
	}
);

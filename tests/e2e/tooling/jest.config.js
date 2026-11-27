const path = require( 'path' );

process.env.NODE_CONFIG_DIR = path.resolve( __dirname, '../config' );
process.env.NODE_CONFIG_ENV = 'development';
process.env.JEST_PUPPETEER_CONFIG = path.resolve( __dirname, 'puppeteer.config.js' );

module.exports = {
	preset: 'jest-puppeteer',
	rootDir: '../../..',
	testMatch: [ '<rootDir>/tests/e2e/tooling/*.test.js' ],
	testTimeout: 30000,
};

const { useE2EJestPuppeteerConfig } = require( '@woocommerce/e2e-environment' );

const puppeteerConfig = useE2EJestPuppeteerConfig( {
	launch: {
		headless: false,
		devtools: true,
		slowMo: 100,
		args: [ '--window-size=1920,1080', '--user-agent=chrome --runInBand --maxWorkers=4' ],
	}
} );

module.exports = puppeteerConfig;

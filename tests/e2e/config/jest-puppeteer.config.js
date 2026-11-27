const { useE2EJestPuppeteerConfig } = require( '@woocommerce/e2e-environment' );

module.exports = useE2EJestPuppeteerConfig( {
	browserContext: 'incognito',
	launch: {
		acceptInsecureCerts: true,
		slowMo: 100,
		args: [ '--window-size=1920,1080', '--user-agent=chrome --runInBand --maxWorkers=4' ],
	}
} );

const config = require( '../vendor/puppeteer-utils/lib/jest-puppeteer.config' );

module.exports = {
	...config,
	browserContext: 'incognito',
	launch: {
		...config.launch,
		headless: process.env.node_config_dev === 'yes' ? false : config.launch.headless,
		devtools: process.env.node_config_dev === 'yes' || config.launch.devtools,
		acceptInsecureCerts: true,
		slowMo: 100,
		args: [
			'--window-size=1920,1080',
			'--user-agent=chrome',
			...( process.env.CI ? [ '--no-sandbox' ] : [] ),
		],
	},
};

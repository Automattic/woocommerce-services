const config = require( '../config/jest-puppeteer.config' );

module.exports = {
	...config,
	launch: {
		...config.launch,
		headless: true,
		devtools: false,
		slowMo: 0,
	},
};

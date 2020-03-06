/** @format */

module.exports = {
	launch: {
		slowMo: process.env.PUPPETEER_SLOWMO ? false : 50,
		headless: false,//process.env.PUPPETEER_HEADLESS || false,
		// Required for the logged out and logged in tests so they don't share app state/token.
		browserContext: 'incognito',
		ignoreHTTPSErrors: true,
		// dumpio: true,
		devtools: true,
		defaultViewport: {
			width: 1280,
			height: 800,
		},
		args: [
			'--window-size=1280,800',
			'--remote-debugging-port=9222',
			"--proxy-server='direct://'",
			'--proxy-bypass-list=*',
			//'--hide-scrollbars',
			'--mute-audio',
			'--no-sandbox',
			//'--disable-gl-drawing-for-tests',
			// '--ignore-certificate-errors',
			// '--ignore-certificate-errors-spki-list',
		]
	}
};

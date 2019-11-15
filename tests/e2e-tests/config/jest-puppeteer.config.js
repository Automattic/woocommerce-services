/** @format */

module.exports = {
	launch: {
		// Required for the logged out and logged in tests so they don't share app state/token.
		browserContext: 'incognito',
		ignoreHTTPSErrors: true,
		// dumpio: true,
		devtools: false,
		defaultViewport: {
			width: 1280,
			height: 800,
		},
		args: [
			'--window-size=1280,1024',
			'--remote-debugging-port=9222',
			"--proxy-server='direct://'",
			'--proxy-bypass-list=*',
			'--disable-gl-drawing-for-tests',
			'--hide-scrollbars',
			'--mute-audio',
			'--no-sandbox',
			// '--ignore-certificate-errors',
			// '--ignore-certificate-errors-spki-list',
		]
	}
};

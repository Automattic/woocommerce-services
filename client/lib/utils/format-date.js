export default ( timestamp ) => {
	// Note: Safari ignores the 'locale' and 'options' argument. It will display
	// the full date (with timezone and everything) in the browser's locale (instead of WP lang)
	return new Date( timestamp ).toLocaleDateString( document.documentElement.lang, {
		day: 'numeric',
		month: 'long',
		year: 'numeric',
		hour: 'numeric',
		minute: 'numeric',
	} );
};

const { useE2EBabelConfig } = require( '@woocommerce/e2e-environment' );

module.exports = function( api ) {
	api.cache( true );

	return useE2EBabelConfig( {
		presets: [
			'@wordpress/babel-preset-default',
		],
	} );
};

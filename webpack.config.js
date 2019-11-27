const path = require( 'path' );
const _ = require( 'lodash' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );
const SassConfig = require( '@automattic/calypso-build/webpack/sass' );

function getWebpackConfig( env = { WP: true } , argv ) {
	const webpackConfig = getBaseWebpackConfig( env, argv );

	const combinedConfig = {
		...webpackConfig,
		context: __dirname,
		entry: {
			'woocommerce-services': [ './client/main.js' ],
			'woocommerce-services-banner': [ './client/banner.js' ],
			'woocommerce-services-admin-pointers': [ './client/admin-pointers.js' ],
			'woocommerce-services-new-order-taxjar': [ './client/new-order-taxjar.js' ],
		},
		output: {
			path: path.join( __dirname, 'dist' ),
			filename: '[name].js',
			devtoolModuleFilenameTemplate: 'app:///[resource-path]',
			publicPath: 'http://localhost:8085/',
		},
		resolve: {
			extensions: [ '.json', '.js', '.jsx' ],
			modules: [
				path.resolve( __dirname, 'client' ),
				path.resolve( __dirname, 'client', 'calypso-stubs' ),
				path.resolve( __dirname, 'client', 'calypso-stubs', 'extensions' ),
				path.resolve( __dirname, 'node_modules' ),
				path.resolve( __dirname, 'client', 'extensions' ),
				path.resolve( __dirname, 'wp-calypso', 'client' ),
				path.resolve( __dirname, 'wp-calypso', 'node_modules' ),
			],
			symlinks: false,
		},
	};

	/**
	 * This is a bit of a hack to modify the SassConfig rule without
	 * needing to redefine other rules. Ideally calypso-build would
	 * provide a way to modify these.
	 */
	combinedConfig.module.rules = _.map( combinedConfig.module.rules, r => {
		 return ( /\.(sc|sa|c)ss$/.toString() === r.test.toString() ) ?
			SassConfig.loader( {
				includePaths: [ 
					path.resolve( __dirname, 'client' ),
					path.resolve( __dirname, 'client', 'extensions' ),
					path.resolve( __dirname, 'wp-calypso', 'client' ),
					path.resolve( __dirname, 'wp-calypso', 'assets', 'stylesheets' ),
				],
				prelude: `@import '${ path.join(
					__dirname,
					'wp-calypso/assets/stylesheets/shared/_utils.scss'
				) }';`,
			} ) : r;
	} );

	return combinedConfig;
}

module.exports = getWebpackConfig;

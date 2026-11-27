const fs = require( 'fs' );
const path = require( 'path' );
const babel = require( '@babel/core' );

for ( const name of [ 'page-utils', 'jest-puppeteer.config' ] ) {
	const { code } = babel.transformFileSync( path.join( __dirname, 'src', name + '.js' ), {
		configFile: false,
		babelrc: false,
		presets: [ [ require.resolve( '@babel/preset-env' ), { targets: { node: '24' } } ] ],
	} );
	fs.writeFileSync( path.join( __dirname, 'lib', name + '.js' ), code + '\n' );
}

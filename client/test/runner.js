// Run the tests
const testsContext = require.context( '../', true, /\/test\/.+\.jsx?$/ );
const testsFiles = testsContext.keys();
testsFiles.forEach( testsContext );

// Require all the other files to make sure they are counted for coverage
const allContext = require.context( '../', true, /.+\.jsx?$/ );
const allFiles = allContext.keys();
allFiles.forEach( ( file ) => {
	if ( ! testsFiles.includes( file ) ) {
		allContext( file );
	}
} );

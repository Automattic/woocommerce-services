<?php
/**
 * Translatable strings in the TaxJar integration.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Translatable_Strings
 *
 * Reads the class source with the PHP tokenizer, so the check covers every string
 * passed to a translation function, including order notes no test drives.
 */
class WP_Test_WC_Connect_TaxJar_Translatable_Strings extends WP_UnitTestCase {

	/**
	 * Translation functions whose arguments are checked.
	 *
	 * @var string[]
	 */
	private static $functions = array(
		'__',
		'_e',
		'_x',
		'_ex',
		'_n',
		'_nx',
		'_n_noop',
		'_nx_noop',
		'esc_html__',
		'esc_html_e',
		'esc_html_x',
		'esc_attr__',
		'esc_attr_e',
		'esc_attr_x',
	);

	/**
	 * String literals passed to a translation function in a PHP file.
	 *
	 * @param string $file Path to the file.
	 * @return array[] Each entry: line number and the literal as written in the source.
	 */
	private function translatable_literals( $file ) {
		$tokens   = token_get_all( file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source file.
		$count    = count( $tokens );
		$literals = array();

		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! is_array( $tokens[ $i ] ) || T_STRING !== $tokens[ $i ][0] || ! in_array( $tokens[ $i ][1], self::$functions, true ) ) {
				continue;
			}

			$j = $i + 1;
			while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
				++$j;
			}
			if ( $j >= $count || '(' !== $tokens[ $j ] ) {
				continue;
			}

			// Walk to the matching closing parenthesis. Nested calls are rare here and
			// their literals are translated too, so they are checked as well.
			$depth = 0;
			for ( $k = $j; $k < $count; $k++ ) {
				if ( '(' === $tokens[ $k ] ) {
					++$depth;
				} elseif ( ')' === $tokens[ $k ] && 0 === --$depth ) {
					break;
				} elseif ( is_array( $tokens[ $k ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $k ][0] ) {
					$literals[] = array( $tokens[ $k ][2], $tokens[ $k ][1] );
				}
			}
		}

		return $literals;
	}

	/**
	 * @testdox No translatable string in the TaxJar integration contains a non-ASCII character.
	 */
	public function test_translatable_strings_are_ascii() {
		$literals = $this->translatable_literals( __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php' );

		// Guards the tokenizer walk itself: the class has hundreds of translated strings.
		$this->assertGreaterThan( 50, count( $literals ) );

		$found = array();
		foreach ( $literals as $literal ) {
			if ( preg_match( '/[^\x00-\x7F]/', $literal[1] ) ) {
				$found[] = 'line ' . $literal[0] . ': ' . $literal[1];
			}
		}

		$this->assertSame(
			array(),
			$found,
			'Characters such as arrows read backwards in right-to-left languages and get lost in translation. Use words.'
		);
	}
}

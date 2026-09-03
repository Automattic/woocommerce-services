<?php

if ( ! class_exists( 'WC_Connect_Functions' ) ) {
	require_once __DIR__ . '/../../classes/class-wc-connect-functions.php';
}

/**
 * Tests for the CSV serialisation used by the tax rate backup.
 */
class WP_Test_WC_Connect_Functions extends WC_Unit_Test_Case {

	/**
	 * Parses CSV text back into rows the way a spreadsheet or an importer would.
	 *
	 * @param string $csv CSV text.
	 * @return array List of rows.
	 */
	private function parse_csv( $csv ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions -- An in-memory stream is the only way
		// to read the text back with fgetcsv(); WP_Filesystem has no equivalent.
		$rows   = array();
		$stream = fopen( 'php://temp', 'r+' );

		fwrite( $stream, $csv );
		rewind( $stream );

		while ( true ) {
			$row = fgetcsv( $stream, 0, ',', '"', '' );

			if ( false === $row || null === $row ) {
				break;
			}

			$rows[] = $row;
		}

		fclose( $stream );
		// phpcs:enable WordPress.WP.AlternativeFunctions

		return $rows;
	}

	/* ──── escape_csv_value(): formula neutralisation ──── */

	/**
	 * Values that open a spreadsheet formula.
	 *
	 * @return array
	 */
	public function formula_leader_provider() {
		return array(
			'equals'          => array( '=1+1' ),
			'plus'            => array( '+1+1' ),
			'minus'           => array( '-1+1' ),
			'at'              => array( '@SUM(A1)' ),
			'tab'             => array( "\t=1+1" ),
			'carriage return' => array( "\r=1+1" ),
			'webservice'      => array( '=WEBSERVICE(CONCATENATE(CHAR(104)))' ),
			'text leader'     => array( '-Dash Town' ),
		);
	}

	/**
	 * @dataProvider formula_leader_provider
	 *
	 * @param string $value A value whose first character starts a formula.
	 */
	public function test_escape_csv_value_prefixes_formula_leaders( $value ) {
		$this->assertSame( "'" . $value, WC_Connect_Functions::escape_csv_value( $value ) );
	}

	/**
	 * Ordinary values are handed through untouched — in particular the entity encoding that
	 * esc_attr() used to apply must be gone, so the backup restores what it backed up.
	 */
	public function test_escape_csv_value_leaves_ordinary_values_verbatim() {
		$this->assertSame( "O'Brien & Sons", WC_Connect_Functions::escape_csv_value( "O'Brien & Sons" ) );
		$this->assertSame( 'Foo, Bar', WC_Connect_Functions::escape_csv_value( 'Foo, Bar' ) );
		$this->assertSame( 'Kingston upon Hull', WC_Connect_Functions::escape_csv_value( 'Kingston upon Hull' ) );
		$this->assertSame( '', WC_Connect_Functions::escape_csv_value( '' ) );
		$this->assertSame( '', WC_Connect_Functions::escape_csv_value( null ) );
	}

	/**
	 * A number cannot open a formula, and prefixing one would corrupt it on re-import.
	 * The negative tax rate is the case that matters: it must survive verbatim.
	 */
	public function test_escape_csv_value_leaves_numbers_verbatim() {
		$this->assertSame( '-5.0000', WC_Connect_Functions::escape_csv_value( '-5.0000' ) );
		$this->assertSame( '8.5000', WC_Connect_Functions::escape_csv_value( '8.5000' ) );
		$this->assertSame( '0', WC_Connect_Functions::escape_csv_value( '0' ) );
		$this->assertSame( '+1', WC_Connect_Functions::escape_csv_value( '+1' ) );
		$this->assertSame( '1', WC_Connect_Functions::escape_csv_value( 1 ) );
	}

	/* ──── rows_to_csv(): quoting, shape and round-trip ──── */

	/**
	 * A value containing the separator stays a single field instead of splitting the row.
	 */
	public function test_rows_to_csv_keeps_a_value_containing_a_comma_in_one_field() {
		$csv    = WC_Connect_Functions::rows_to_csv( array( array( 'US', 'Foo, Bar', '8.5000' ) ) );
		$parsed = $this->parse_csv( $csv );

		$this->assertCount( 1, $parsed );
		$this->assertSame( array( 'US', 'Foo, Bar', '8.5000' ), $parsed[0] );
	}

	/**
	 * Quotes and line breaks inside a value survive a write/read round trip.
	 */
	public function test_rows_to_csv_round_trips_quotes_and_line_breaks() {
		$row = array( 'US', 'Say "hi"', "line\nbreak", "O'Brien & Sons" );

		$parsed = $this->parse_csv( WC_Connect_Functions::rows_to_csv( array( $row ) ) );

		$this->assertSame( array( $row ), $parsed );
	}

	/**
	 * Neutralisation is applied to every column, not only the ones that look attacker-controlled.
	 */
	public function test_rows_to_csv_neutralises_every_column() {
		$csv    = WC_Connect_Functions::rows_to_csv( array( array( '=1+1', '=2+2', '=3+3' ) ) );
		$parsed = $this->parse_csv( $csv );

		$this->assertSame( array( "'=1+1", "'=2+2", "'=3+3" ), $parsed[0] );
	}

	/**
	 * The row shape is preserved: every row keeps its column count, empty cells included.
	 */
	public function test_rows_to_csv_preserves_row_shape() {
		$rows = array(
			array( 'Country Code', 'City', 'Rate %', 'Tax Class' ),
			array( 'US', 'Fargo', '8.5000', '' ),
		);

		$parsed = $this->parse_csv( WC_Connect_Functions::rows_to_csv( $rows ) );

		$this->assertCount( 2, $parsed );
		$this->assertCount( 4, $parsed[0] );
		$this->assertCount( 4, $parsed[1] );
		$this->assertSame( $rows[1], $parsed[1] );
	}

	/**
	 * Rows are separated by a newline, as the previous hand-rolled writer did.
	 */
	public function test_rows_to_csv_ends_every_row_with_a_newline() {
		$csv = WC_Connect_Functions::rows_to_csv( array( array( 'a' ), array( 'b' ) ) );

		$this->assertSame( "a\nb\n", $csv );
	}

	/**
	 * Nothing to serialise produces no file content.
	 */
	public function test_rows_to_csv_returns_an_empty_string_for_no_rows() {
		$this->assertSame( '', WC_Connect_Functions::rows_to_csv( array() ) );
		$this->assertSame( '', WC_Connect_Functions::rows_to_csv( 'not an array' ) );
	}
}

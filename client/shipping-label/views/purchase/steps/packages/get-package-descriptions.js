import { translate as __ } from 'i18n-calypso';
import { sprintf } from 'sprintf-js';
import _ from 'lodash';

export default ( selected, all, addNames ) => {
	let pckgCount = 0;

	return _.mapValues( selected, ( pckg ) => {
		if ( 'individual' === pckg.box_id ) {
			return pckg.items[ 0 ].name;
		}

		const pckgData = all[ pckg.box_id ];
		const pckgType = ( pckgData && pckgData.is_letter ) ? __( 'Envelope %d' ) : __( 'Package %d' );

		pckgCount++;
		const pckgName = addNames && pckgData ? ': ' + pckgData.name : '';
		return sprintf( pckgType, pckgCount ) + pckgName;
	} );
};

import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';
import _ from 'lodash';

export default ( selected, all, addNames ) => {
	let pckgCount = 0;

	return _.mapValues( selected, ( pckg ) => {
		if ( 'individual' === pckg.box_id ) {
			return pckg.items[ 0 ].name;
		}

		let pckgType = __( 'Package %d' );
		const pckgData = all[ pckg.box_id ];
		if ( pckgData ) {
			pckgType = all[ pckg.box_id ].is_letter ? __( 'Envelope %d' ) : __( 'Box %d' );
		}

		pckgCount++;
		const pckgName = addNames && pckgData ? ': ' + pckgData.name : '';
		return sprintf( pckgType, pckgCount ) + pckgName;
	} );
};

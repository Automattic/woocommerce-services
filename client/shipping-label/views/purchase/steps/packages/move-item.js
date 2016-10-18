import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import Dialog from 'components/dialog';
import FormRadio from 'components/forms/form-radio';
import FormLabel from 'components/forms/form-label';
import ActionButtons from 'components/action-buttons';
import { sprintf } from 'sprintf-js';
import getPackageDescriptions from './get-package-descriptions';

const MoveItemDialog = ( {
	showItemMoveDialog,
	movedItemIndex,
	targetPackageId,
	openedPackageId,
	selected,
	all,
	unpacked,
	closeItemMove,
	setTargetPackage,
	confirmItemMove } ) => {
	if ( -1 === movedItemIndex || ! showItemMoveDialog ) {
		return null;
	}

	const renderRadioButton = ( pckgId, label ) => {
		return (
			<FormLabel
				key={ pckgId }
				className="wcc-move-item-dialog__package-option">
				<FormRadio checked={ pckgId === targetPackageId } onChange={ () => ( setTargetPackage( pckgId ) ) } />
				{ label }
			</FormLabel>
		);
	};

	const openedPackage = selected[ openedPackageId ];
	const items = '' !== openedPackageId ? openedPackage.items : unpacked;
	const item = items[ movedItemIndex ];
	const itemLink = sprintf( '<a href="%s" target="_blank">%s</a>', item.url, item.name );
	let desc;

	const packageLabels = getPackageDescriptions( selected, all, true );

	const renderPackedOptions = () => {
		const elements = [];
		Object.keys( selected ).forEach( ( pckgId ) => {
			const pckg = selected[ pckgId ];
			if ( pckgId === openedPackageId || 'individual' === pckg.box_id ) {
				return;
			}

			elements.push( renderRadioButton( pckgId, packageLabels[ pckgId ] ) );
		} );

		return elements;
	};

	const renderIndividualOption = () => {
		if ( openedPackage && 'individual' === openedPackage.box_id ) {
			return null;
		}

		return renderRadioButton( 'individual', __( 'Ship in original packaging' ) );
	};

	const renderSaveForLaterOption = () => {
		if ( '' === openedPackageId ) {
			return null;
		}

		return renderRadioButton( '', __( 'Save for later' ) );
	};

	if ( '' === openedPackageId ) {
		desc = sprintf( '%s is currently saved for a later shipment.', itemLink );
	} else if ( 'individual' === openedPackage.box_id ) {
		desc = sprintf( '%s is currently shipped in its original packaging.', itemLink );
	} else {
		desc = sprintf(
			__( '%s is currently in %s.' ),
			itemLink,
			sprintf( '<span class="wcc-move-item-dialog__package-name">%s</a>', packageLabels[ openedPackageId ] )
		);
	}

	return (
		<Dialog isVisible={ showItemMoveDialog }
				isFullScreen={ false }
				onClickOutside={ closeItemMove }
				onClose={ closeItemMove }
				additionalClassNames="wcc-modal wcc-label-packages-dialog" >
			<div className="wcc-label-packages-dialog__content">
				<h1 className="form-section-heading">{ __( 'Move item' ) }</h1>
				<div className="wcc-label-packages-dialog__body">
					<p dangerouslySetInnerHTML={ { __html: desc } }/>
					<p>{ __( 'Where would you like to move it?' ) }</p>
					{ renderPackedOptions() }
					{ renderIndividualOption() }
					{ renderSaveForLaterOption() }
				</div>
				<ActionButtons buttons={ [
					{ label: __( 'Move' ), isPrimary: true, onClick: () => ( confirmItemMove( openedPackageId, movedItemIndex, targetPackageId ) ) },
					{ label: __( 'Cancel' ), onClick: closeItemMove },
				] } />
			</div>
		</Dialog>
	);
};

MoveItemDialog.propTypes = {
	showItemMoveDialog: PropTypes.bool.isRequired,
	movedItemIndex: PropTypes.number.isRequired,
	targetPackageId: PropTypes.string,
	openedPackageId: PropTypes.string.isRequired,
	selected: PropTypes.object.isRequired,
	all: PropTypes.object.isRequired,
	unpacked: PropTypes.array,
};

export default MoveItemDialog;

import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';
import Dialog from 'components/dialog';
import FormRadio from 'components/forms/form-radio';
import FormLabel from 'components/forms/form-label';
import ActionButtons from 'components/action-buttons';
import getPackageDescriptions from './get-package-descriptions';

const MoveItemDialog = ( {
	showItemMoveDialog,
	movedItemIndex,
	targetPackageId,
	openedPackageId,
	selected,
	all,
	closeItemMove,
	setTargetPackage,
	moveItem } ) => {
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
	const items = openedPackage.items;
	const item = items[ movedItemIndex ];
	const itemLink = <a href={ item.url } target="_blank">{ item.name }</a>;
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

	const renderNewPackageOption = () => {
		return renderRadioButton( 'new', __( 'Add to a New Package' ) );
	};

	const renderIndividualOption = () => {
		if ( openedPackage && 'individual' === openedPackage.box_id ) {
			return null;
		}

		return renderRadioButton( 'individual', __( 'Ship in original packaging' ) );
	};

	if ( '' === openedPackageId ) {
		desc = __( '{{itemLink/}} is currently saved for a later shipment.', { components: { itemLink } } );
	} else if ( 'individual' === openedPackage.box_id ) {
		desc = __( '{{itemLink/}} is currently shipped in its original packaging.', { components: { itemLink } } );
	} else {
		desc = __(
			'{{itemLink/}} is currently in {{pckg/}}.',
			{
				components: {
					itemLink,
					pckg: <span className="wcc-move-item-dialog__package-name">{ packageLabels[ openedPackageId ] }</span>,
				},
			}
		);
	}

	return (
		<Dialog isVisible={ showItemMoveDialog }
				isFullScreen={ false }
				onClickOutside={ closeItemMove }
				onClose={ closeItemMove }
				additionalClassNames="wcc-root wcc-label-packages-dialog" >
			<div className="wcc-label-packages-dialog__content">
				<h1 className="form-section-heading">{ __( 'Move item' ) }</h1>
				<div className="wcc-label-packages-dialog__body">
					<p>{ desc }</p>
					<p>{ __( 'Where would you like to move it?' ) }</p>
					{ renderPackedOptions() }
					{ renderNewPackageOption() }
					{ renderIndividualOption() }
				</div>
				<ActionButtons buttons={ [
					{ label: __( 'Move' ), isPrimary: true, onClick: () => ( moveItem( openedPackageId, movedItemIndex, targetPackageId ) ) },
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
	moveItem: PropTypes.func.isRequired,
};

export default MoveItemDialog;

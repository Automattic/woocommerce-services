import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import Dialog from 'components/dialog';
import FormRadio from 'components/forms/form-radio';
import FormLabel from 'components/forms/form-label';
import ActionButtons from 'components/action-buttons';
import { sprintf } from 'sprintf-js';
import getPackageDescriptions from './get-package-descriptions';

const AddItemDialog = ( {
	showAddItemDialog,
	movedItemIndex,
	sourcePackageId,
	openedPackageId,
	selected,
	all,
	unpacked,
	closeAddItem,
	setAddedItem,
	confirmAddItem } ) => {
	if ( ! showAddItemDialog ) {
		return null;
	}

	const renderRadioButton = ( pckgId, itemIdx, label ) => {
		return (
			<FormLabel
				key={ `${ pckgId }-${ itemIdx }` }
				className="wcc-move-item-dialog__package-option">
				<FormRadio checked={ pckgId === sourcePackageId && itemIdx === movedItemIndex } onChange={ () => ( setAddedItem( pckgId, itemIdx ) ) } />
				<span dangerouslySetInnerHTML={ { __html: label } } />
			</FormLabel>
		);
	};

	const packageLabels = getPackageDescriptions( selected, all, true );
	const getPackageNameHtml = ( pckgId ) => {
		return `<span class="wcc-move-item-dialog__package-name">${ packageLabels[ pckgId ] }</span>`;
	};

	const itemOptions = [];

	Object.keys( selected ).forEach( ( pckgId ) => {
		if ( pckgId === openedPackageId ) {
			return;
		}

		let itemIdx = 0;
		selected[ pckgId ].items.forEach( ( item ) => {
			const label = sprintf( '%s from %s', item.name, getPackageNameHtml( pckgId ) );
			itemOptions.push( renderRadioButton( pckgId, itemIdx, label ) );
			itemIdx++;
		} );
	} );

	let unpackedIdx = 0;
	unpacked.forEach( ( item ) => {
		itemOptions.push( renderRadioButton( '', unpackedIdx, item.name ) );
		unpackedIdx++;
	} );

	return (
		<Dialog isVisible={ showAddItemDialog }
				isFullScreen={ false }
				onClickOutside={ closeAddItem }
				onClose={ closeAddItem }
				additionalClassNames="wcc-root wcc-label-packages-dialog" >
			<div className="wcc-label-packages-dialog__content">
				<h1 className="form-section-heading">{ __( 'Add item' ) }</h1>
				<div className="wcc-label-packages-dialog__body">
					<p dangerouslySetInnerHTML={ { __html: sprintf( __( 'Which item would you like to add to %s?' ), getPackageNameHtml( openedPackageId ) ) } } />
					{ itemOptions }
				</div>
				<ActionButtons buttons={ [
					{ label: __( 'Add' ), isPrimary: true, onClick: () => ( confirmAddItem( sourcePackageId, movedItemIndex, openedPackageId ) ) },
					{ label: __( 'Cancel' ), onClick: closeAddItem },
				] } />
			</div>
		</Dialog>
	);
};

AddItemDialog.propTypes = {
	showAddItemDialog: PropTypes.bool.isRequired,
	movedItemIndex: PropTypes.number,
	sourcePackageId: PropTypes.string,
	openedPackageId: PropTypes.string.isRequired,
	selected: PropTypes.object.isRequired,
	all: PropTypes.object.isRequired,
	unpacked: PropTypes.array,
};

export default AddItemDialog;

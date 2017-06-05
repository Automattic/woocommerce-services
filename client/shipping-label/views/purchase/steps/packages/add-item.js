import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';
import Dialog from 'components/dialog';
import FormLabel from 'components/forms/form-label';
import ActionButtons from 'components/action-buttons';
import getPackageDescriptions from './get-package-descriptions';

const AddItemDialog = ( {
	showAddItemDialog,
	sourcePackageId,
	openedPackageId,
	selected,
	all,
	unpacked,
	closeAddItem,
	setAddedItem,
	moveItem } ) => {
	if ( ! showAddItemDialog ) {
		return null;
	}

	const packageLabels = getPackageDescriptions( selected, all, true );
	const getPackageNameElement = ( pckgId ) => {
		return <span className="wcc-move-item-dialog__package-name">{ packageLabels[ pckgId ] }</span>;
	};

	const renderRadioButton = ( pckgId, itemIdx, item ) => {
		return (
			<FormLabel
				key={ `${ pckgId }-${ itemIdx }` }
				className="wcc-move-item-dialog__package-option"
				onClick={ () => {
					setAddedItem( pckgId, itemIdx );
					moveItem( sourcePackageId, itemIdx, openedPackageId );
				} }>
				<span>{ item }</span>
			</FormLabel>
		);
	};

	const itemOptions = [];
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
					<p>
						{ __( 'Which items would you like to add to {{pckg/}}?', { components: { pckg: getPackageNameElement( openedPackageId ) } } ) }
					</p>
					{ itemOptions }
				</div>
				<ActionButtons buttons={ [
					{ label: __( 'Exit' ), onClick: closeAddItem },
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

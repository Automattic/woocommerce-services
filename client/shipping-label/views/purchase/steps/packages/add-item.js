/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import Dialog from 'components/dialog';
import FormCheckbox from 'components/forms/form-checkbox';
import FormLabel from 'components/forms/form-label';
import ActionButtons from 'components/action-buttons';
import getPackageDescriptions from './get-package-descriptions';

const AddItemDialog = ( {
	showAddItemDialog,
	addedItems,
	openedPackageId,
	selected,
	all,
	closeAddItem,
	setAddedItem,
	addItems } ) => {
	if ( ! showAddItemDialog ) {
		return null;
	}

	const packageLabels = getPackageDescriptions( selected, all, true );
	const getPackageNameElement = ( pckgId ) => {
		return <span className="wcc-move-item-dialog__package-name">{ packageLabels[ pckgId ] }</span>;
	};

	const renderCheckbox = ( pckgId, itemIdx, item ) => {
		const itemLabel = packageLabels[ pckgId ]
			? __( '%(item)s from {{pckg/}}', { args: { item: item.name }, components: { pckg: getPackageNameElement( pckgId ) } } )
			: item;

		const onChange = ( event ) => setAddedItem( pckgId, itemIdx, event.target.checked );
		return (
			<FormLabel
				key={ `${ pckgId }-${ itemIdx }` }
				className="wcc-move-item-dialog__package-option">
				<FormCheckbox checked={ _.includes( addedItems[ pckgId ], itemIdx ) }
						onChange={ onChange } />
				<span>{ itemLabel }</span>
			</FormLabel>
		);
	};

	const itemOptions = [];
	Object.keys( selected ).forEach( ( pckgId ) => {
		if ( pckgId === openedPackageId ) {
			return;
		}

		let itemIdx = 0;
		selected[ pckgId ].items.forEach( ( item ) => {
			itemOptions.push( renderCheckbox( pckgId, itemIdx, item ) );
			itemIdx++;
		} );
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
						{ __( 'Which items would you like to add to {{pckg/}}?', {
							components: {
								pckg: getPackageNameElement( openedPackageId ),
							}
						} ) }
					</p>
					{ itemOptions }
				</div>
				<ActionButtons buttons={ [
					{
						label: __( 'Add' ),
						isPrimary: true,
						isDisabled: ! _.some( addedItems, _.size ),
						onClick: () => addItems( openedPackageId ),
					},
					{ label: __( 'Close' ), onClick: closeAddItem },
				] } />
			</div>
		</Dialog>
	);
};

AddItemDialog.propTypes = {
	showAddItemDialog: PropTypes.bool.isRequired,
	addedItems: PropTypes.object,
	openedPackageId: PropTypes.string.isRequired,
	selected: PropTypes.object.isRequired,
	all: PropTypes.object.isRequired,
	closeAddItem: PropTypes.func.isRequired,
	setAddedItem: PropTypes.func.isRequired,
	addItems: PropTypes.func.isRequired,
};

export default AddItemDialog;

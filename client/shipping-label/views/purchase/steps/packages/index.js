import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import PackageList from './list';
import PackageInfo from './package-info';
import MoveItemDialog from './move-item';
import AddItemDialog from './add-item';
import Unpacked from './unpacked';
import FormButton from 'components/forms/form-button';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import { sprintf } from 'sprintf-js';
import StepContainer from '../../step-container';

const PackagesStep = ( {
	openedPackageId,
	selected,
	all,
	unpacked,
	storeOptions,
	labelActions,
	errors,
	expanded,
	showItemMoveDialog,
	movedItemIndex,
	targetPackageId,
	showAddItemDialog,
	sourcePackageId } ) => {
	const packageIds = Object.keys( selected );
	const itemsCount = packageIds.reduce( ( result, pId ) => ( result + selected[ pId ].items.length ), 0 );
	const totalWeight = packageIds.reduce( ( result, pId ) => ( result + selected[ pId ].weight ), 0 );
	const isValidWeight = packageIds.reduce( ( result, pId ) => ( result && 0 < selected[ pId ].weight ), true );
	const isValidPackages = 0 < packageIds.length;

	const renderSummary = () => {
		if ( ! isValidPackages ) {
			return __( 'No packages selected' );
		}

		if ( ! isValidWeight ) {
			return __( 'Weight not entered' );
		}

		if ( 1 === packageIds.length && 1 === itemsCount ) {
			return sprintf( __( '1 item in 1 package: %f %s total' ), totalWeight, storeOptions.weight_unit );
		}

		if ( 1 === packageIds.length ) {
			return sprintf( __( '%d items in 1 package: %f %s total' ), itemsCount, totalWeight, storeOptions.weight_unit );
		}

		return sprintf( __( '%d items in %d packages: %f %s total' ), itemsCount, packageIds.length, totalWeight, storeOptions.weight_unit );
	};

	const renderContents = () => {
		const elements = [
			<PackageList
				key="packages-list"
				openPackage={ labelActions.openPackage }
				packageId={ openedPackageId }
				selected={ selected }
				all={ all }
				unpacked={ unpacked }
				addPackage={ labelActions.addPackage }/>,
		];

		if ( ! packageIds.length && ! unpacked.length ) {
			elements.push(
				<div key="no-packages" className="wcc-package">{ __( 'There are no packages or items associated with this order' ) }</div>
			);
		} else {
			elements.push(
				<PackageInfo
					key="package-info"
					packageId={ openedPackageId }
					selected={ selected }
					all={ all }
					unpacked={ unpacked }
					dimensionUnit={ storeOptions.dimension_unit }
					weightUnit={ storeOptions.weight_unit }
					errors={ errors }
					updateWeight={ labelActions.updatePackageWeight }
					openItemMove={ labelActions.openItemMove }
					removeItem={ labelActions.removeItem }
					removePackage={ labelActions.removePackage }
					setPackageType={ labelActions.setPackageType }
					openAddItem={ labelActions.openAddItem } />
			);
			elements.push(
				<Unpacked
					key="unpacked"
					packageId={ openedPackageId }
					unpacked={ unpacked }
					openItemMove={ labelActions.openItemMove }
					moveItem={ labelActions.moveItem } />
			);
		}

		return (
			<div className="wcc-packages-container">
				{ elements }
			</div>
		);
	};

	return (
		<StepContainer
			title={ __( 'Packages' ) }
			isSuccess={ isValidWeight && isValidPackages }
			isError={ ! isValidWeight || ! isValidPackages }
			summary={ renderSummary() }
			expanded={ expanded }
			toggleStep={ () => labelActions.toggleStep( 'packages' ) } >
			{ renderContents() }
			<div className="step__confirmation-container">
				<FormButton
					type="button"
					className="packages__confirmation step__confirmation"
					disabled={ hasNonEmptyLeaves( errors ) || ! packageIds.length }
					onClick={ labelActions.confirmPackages }
					isPrimary >
					{ __( 'Save packages' ) }
				</FormButton>
			</div>
			<MoveItemDialog
				showItemMoveDialog={ showItemMoveDialog || false }
				movedItemIndex={ isNaN( movedItemIndex ) ? -1 : movedItemIndex }
				openedPackageId={ openedPackageId }
				targetPackageId={ targetPackageId }
				selected={ selected }
				all={ all }
				unpacked={ unpacked }
				closeItemMove={ labelActions.closeItemMove }
				setTargetPackage={ labelActions.setTargetPackage }
				confirmItemMove={ labelActions.confirmItemMove } />
			<AddItemDialog
				showAddItemDialog={ showAddItemDialog || false }
				movedItemIndex={ movedItemIndex }
				sourcePackageId={ sourcePackageId }
				openedPackageId={ openedPackageId }
				selected={ selected }
				all={ all }
				unpacked={ unpacked }
				closeAddItem={ labelActions.closeAddItem }
				setAddedItem={ labelActions.setAddedItem }
				confirmAddItem={ labelActions.confirmAddItem } />
		</StepContainer>
	);
};

PackagesStep.propTypes = {
	openedPackageId: PropTypes.string,
	showItemMoveDialog: PropTypes.bool,
	selected: PropTypes.object.isRequired,
	all: PropTypes.object.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.object.isRequired,
	expanded: PropTypes.bool,
	movedItemIndex: PropTypes.number,
	targetPackageId: PropTypes.string,
	showAddItemDialog: PropTypes.bool,
	sourcePackageId: PropTypes.string,
};

export default PackagesStep;

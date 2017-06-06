import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';
import Gridicon from 'gridicons';
import Button from 'components/button';

const ItemInfo = ( { item, itemIndex, packageId, showRemove, removeItem, showPackIndividually, openItemMove, moveItem, isIndividualPackage, addPackage } ) => {
	const renderPackIndividually = () => {
		if ( ! showPackIndividually ) {
			return null;
		}

		return (
			<Button className="wcc-package-item__move" compact onClick={ () => moveItem( '', itemIndex, 'individual' ) }>
				{ __( 'Ship Individually' ) }
			</Button>
		);
	};

	const renderAddToPackage = () => {
		if ( ! showPackIndividually ) {
			return null;
		}

		return (
			<Button className="wcc-package-item__move" compact onClick={ () => {
				addPackage( itemIndex );
				//moveItem( '', itemIndex, 'new' );
			} }>
				{ __( 'Add to New Package' ) }
			</Button>
		);
	};

	const renderMoveToPackage = () => {
		return (
			<Button className="wcc-package-item__move" compact onClick={ () => openItemMove( itemIndex ) }>
				{ __( 'Move to Package' ) }
			</Button>
		);
	};

	const renderRemove = () => {
		if ( isIndividualPackage ) {
			return null;
		}

		if ( ! showRemove ) {
			return null;
		}

		return (
			<Button className="wcc-package-item__remove" compact borderless onClick={ () => removeItem( packageId, itemIndex ) }>
				<Gridicon icon="cross" />
			</Button>
		);
	};

	return (
		<div key={ itemIndex } className="wcc-package-item">
			<div className="wcc-package-item__name">
					<span className="wcc-package-item__title">
						{ item.url
							? <a href={ item.url } target="_blank">{ item.name }</a>
							: item.name
						}
					</span>
				{ item.attributes && <p>{ item.attributes }</p> }
			</div>
			<div className="wcc-package-item__actions">
				{ renderPackIndividually() }
				{ renderAddToPackage() }
				{ renderMoveToPackage() }
				{ renderRemove() }
			</div>
		</div>
	);
};

ItemInfo.propTypes = {
	item: PropTypes.object.isRequired,
	itemIndex: PropTypes.number.isRequired,
	showRemove: PropTypes.bool,
	packageId: PropTypes.string.isRequired,
	showPackIndividually: PropTypes.bool,
	openMoveItem: PropTypes.func,
	moveItem: PropTypes.func,
};

export default ItemInfo;

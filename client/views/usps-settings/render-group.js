import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import RenderItem from './render-item';
import SaveButton from 'components/save-button';

const Group = ( { group, form, schema, settings, wooCommerceSettings, updateValue, updateSubSubValue, saveForm } ) => {
	if ( 'fieldset' === group.type ) {
		return (
			<CompactCard>
				<FormSectionHeading>{ group.title }</FormSectionHeading>
				{ group.items.map( item => (
					<RenderItem
						key={ item.key ? item.key : item }
						item={ item }
						schema={ schema }
						settings={ settings }
						wooCommerceSettings={ wooCommerceSettings }
						updateValue={ updateValue }
						updateSubSubValue={ updateSubSubValue }
					/>
				) ) }
			</CompactCard>
		);
	}

	if ( 'actions' === group.type ) {
		return (
			<SaveButton
				form={ form }
				saveForm={ saveForm }
			/>
		);
	}

	return (
		<CompactCard>
			<FormSectionHeading>Unknown</FormSectionHeading>
			<span>{ JSON.stringify( group ) }</span>
		</CompactCard>
	);
};

Group.propTypes = {
	group: PropTypes.shape( {
		title: PropTypes.string,
		items: PropTypes.array,
	} ),
	schema: PropTypes.object.isRequired,
	settings: PropTypes.object.isRequired,
	wooCommerceSettings: PropTypes.object.isRequired,
	updateValue: PropTypes.func.isRequired,
	updateSubSubValue: PropTypes.func.isRequired,
	saveForm: PropTypes.func.isRequired,
};

export default Group;

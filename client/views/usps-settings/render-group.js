import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import RenderItem from './render-item';

const Group = ( { group, schema, settings, wooCommerceSettings, updateValue, updateSubValue, updateSubSubValue } ) => (
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
				updateSubValue={ updateSubValue }
				updateSubSubValue={ updateSubSubValue }
			/>
		) ) }
	</CompactCard>
);

Group.propTypes = {
	group: PropTypes.shape( {
		title: PropTypes.string.isRequired,
		items: PropTypes.array.isRequired,
	} ),
	schema: PropTypes.object.isRequired,
	settings: PropTypes.object.isRequired,
	wooCommerceSettings: PropTypes.object.isRequired,
	updateValue: PropTypes.func.isRequired,
	updateSubValue: PropTypes.func.isRequired,
	updateSubSubValue: PropTypes.func.isRequired,
};

export default Group;

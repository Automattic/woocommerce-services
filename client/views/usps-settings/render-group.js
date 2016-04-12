import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import TextField from '../text-field';

const RenderField = ( { item, schema, settings, updateValue } ) => {
	const id = item.key ? item.key : item;
	return (
		<TextField
			id={ id }
			schema={ schema.properties[id] }
			value={ settings[id] }
			placeholder={ item.placeholder }
			updateValue={ value => updateValue( id, value ) }
		/>
	);
};

const Group = ( { group, schema, settings, updateValue } ) => (
	<CompactCard>
		<FormSectionHeading>{ group.title }</FormSectionHeading>
		{ group.items.map( item => (
			<RenderField
				key={ item.key ? item.key : item }
				item={ item }
				schema={ schema }
				settings={ settings }
				updateValue={ updateValue }
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
	updateValue: PropTypes.func.isRequired,
};

export default Group;

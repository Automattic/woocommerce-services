import React, { PropTypes } from 'react';
import omit from 'lodash/omit';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';
import Button from 'components/button';
import Summary from 'components/summary';
import { translate as __ } from 'lib/mixins/i18n';

const RadioButton = ( props ) => {
	return (
		<FormLabel>
			<FormRadio { ...omit( props, 'children' ) } />
			{ props.children }
		</FormLabel>
	);
};

const Suggestion = ( { acceptSuggestion, formValues, formActions, layout, suggestions, countriesData } ) => {
	return (
		<div>
			<RadioButton
				checked={ ! acceptSuggestion }
				onChange={ () => formActions.setFormProperty( 'acceptSuggestion', false ) } >
				{ layout.suggestion_original_title }
				<Summary
					formValues={ formValues }
					layoutItems={ layout.items }
					summaryTemplate={ layout.summary }
					countriesData={ countriesData } />
				<Button onClick={ formActions.backFromSuggestion } >
					{ __( 'Edit' ) }
				</Button>
			</RadioButton>
			<RadioButton
				checked={ acceptSuggestion }
				onChange={ () => formActions.setFormProperty( 'acceptSuggestion', true ) } >
				{ layout.suggestion_corrected_title }
				<Summary
					formValues={ formValues }
					layoutItems={ layout.items }
					summaryTemplate={ layout.summary }
					overrideFields={ suggestions }
					countriesData={ countriesData } />
			</RadioButton>
		</div>
	);
};

Suggestion.propTypes = {
	layout: PropTypes.shape( {
		summary: PropTypes.string,
		items: PropTypes.array,
		suggestion_original_title: PropTypes.string,
		suggestion_corrected_title: PropTypes.string,
	} ).isRequired,
	acceptSuggestion: PropTypes.bool.isRequired,
	countriesData: PropTypes.object.isRequired,
	formValues: PropTypes.object.isRequired,
	formActions: PropTypes.object.isRequired,
	suggestions: PropTypes.object.isRequired,
};

export default Suggestion;

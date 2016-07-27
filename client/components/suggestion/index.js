import React, { PropTypes } from 'react';
import omit from 'lodash/omit';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';
import Button from 'components/button';
import Summary from 'components/summary';
import { translate as __ } from 'lib/mixins/i18n';
import classNames from 'classnames';

const RadioButton = ( props ) => {
	return (
		<FormLabel className={ classNames( 'suggestion', { 'is-selected': props.checked } ) }>
			<FormRadio { ...omit( props, 'children' ) } />
			{ props.children }
		</FormLabel>
	);
};

const Suggestion = ( { acceptSuggestion, formValues, formActions, layout, suggestions, countriesData } ) => {
	return (
		<div className="suggestion-container">
			<RadioButton
				checked={ ! acceptSuggestion }
				onChange={ () => formActions.setFormProperty( 'acceptSuggestion', false ) } >
				<span className="suggestion-title">{ layout.suggestion_original_title }</span>
				<Summary
					formValues={ formValues }
					layoutItems={ layout.items }
					summaryTemplate={ layout.summary }
					countriesData={ countriesData } />
				<Button
					onClick={ formActions.backFromSuggestion }
					className="suggestion-edit-button">
					{ __( 'Edit' ) }
				</Button>
			</RadioButton>
			<RadioButton
				checked={ acceptSuggestion }
				onChange={ () => formActions.setFormProperty( 'acceptSuggestion', true ) } >
				<span className="suggestion-title">{ layout.suggestion_corrected_title }</span>
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

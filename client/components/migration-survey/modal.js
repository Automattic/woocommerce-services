/**
 * External dependencies
 */
import React, { useState } from 'react';
import { localize } from 'i18n-calypso';

/**
 * Migration Survey Modal Component
 * 
 * For users with WCS&T version >= 3.0.0
 */

const MigrationSurveyModal = ( { isVisible, onClose, translate } ) => {
	const [ primaryAnswer, setPrimaryAnswer ] = useState( '' );
	const [ followupAnswers, setFollowupAnswers ] = useState( {} );
	const [ showFollowup, setShowFollowup ] = useState( false );
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ isSubmitted, setIsSubmitted ] = useState( false );

	const primaryQuestions = [
		{ value: 'missing_features', label: translate( 'The new plugin is missing a feature I rely on.' ) },
		{ value: 'disruption_concerns', label: translate( 'I\'m concerned switching will be difficult or disrupt my current workflow.' ) },
		{ value: 'tried_had_issues', label: translate( 'I tried the new WooCommerce Shipping before and had a problem or found it confusing.' ) },
		{ value: 'unaware', label: translate( 'I wasn\'t aware there was a new WooCommerce Shipping plugin.' ) },
		{ value: 'unsure_benefits', label: translate( 'I\'m not sure what the benefits of switching are.' ) },
		{ value: 'no_time', label: translate( 'I just haven\'t had time to look into it.' ) },
		{ value: 'other', label: translate( 'Other' ) }
	];

	const followupQuestions = {
		missing_features: {
			type: 'textarea',
			label: translate( 'Could you tell us which feature is most important to you? Your feedback directly helps us prioritize what to build next.' )
		},
		disruption_concerns: {
			type: 'textarea', 
			label: translate( 'What is your biggest concern about the switching process? This will help us make sure you can migrate with confidence and ease.' )
		},
		tried_had_issues: {
			type: 'textarea',
			label: translate( 'Could you briefly describe the problem you ran into? This helps us fix bugs and improve the design.' )
		},
		unsure_benefits: {
			type: 'textarea',
			label: translate( 'What\'s the most important thing to you when it comes to shipping? (e.g. saving money, saving time, carrier options)' )
		},
		unaware: {
			type: 'textarea',
			label: translate( 'What\'s the most important thing to you when it comes to shipping? (e.g. saving money, saving time, carrier options)' )
		},
		no_time: {
			type: 'textarea',
			label: translate( 'Knowing the switch takes less than five minutes, what\'s one thing that would convince you to try it?' )
		},
		other: {
			type: 'textarea',
			label: translate( 'We\'d love to hear more. Could you please share your reason with us?' )
		}
	};

	const handlePrimaryChange = ( value ) => {
		setPrimaryAnswer( value );
		setFollowupAnswers( {} );
		setShowFollowup( true ); // Always show followup for any primary answer
	};


	const handleTextChange = ( questionValue, text ) => {
		setFollowupAnswers( prev => ( {
			...prev,
			[ questionValue ]: text
		} ) );
	};

	const trackSurveyDisplay = async () => {
		try {
			await fetch( window.wcsMigrationSurvey.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'wcs_migration_survey_track_display',
					nonce: window.wcsMigrationSurvey.nonce,
				} ),
			} );
		} catch ( error ) {
			// Silently fail tracking
		}
	};

	const handleSurveySubmit = async ( surveyData ) => {
		try {
			await fetch( window.wcsMigrationSurvey.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'wcs_migration_survey_submit',
					nonce: window.wcsMigrationSurvey.nonce,
					survey_data: JSON.stringify( surveyData ),
				} ),
			} );
		} catch ( error ) {
			// Silently fail submission
		}
	};

	// Track when survey is displayed
	React.useEffect( () => {
		if ( isVisible && window.wcsMigrationSurvey ) {
			trackSurveyDisplay();
		}
	}, [ isVisible ] );

	const handleSubmit = async () => {
		if ( ! primaryAnswer ) return;

		setIsSubmitting( true );

		try {
			await handleSurveySubmit( {
				primary: primaryAnswer,
				followup: followupAnswers
			} );
			setIsSubmitted( true );
		} catch ( error ) {
			// Failed to submit survey
			setIsSubmitting( false );
		}
	};

	const handleSkip = async () => {

		try {
			await fetch( window.wcsMigrationSurvey.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'wcs_migration_survey_dismiss',
					nonce: window.wcsMigrationSurvey.nonce,
				} ),
			} );
		} catch ( error ) {
			// Silently fail submission
		}
		onClose();
	};

	if ( ! isVisible ) {
		return null;
	}

	// Show confirmation message after submission
	if ( isSubmitted ) {
		return (
			<div className="migration-survey__modal-backdrop">
				<div className="migration-survey__modal">
					<div className="migration-survey__modal-header">
						<h3>{ translate( 'Thank you!' ) }</h3>
						<button 
							className="migration-survey__modal-close"
							onClick={ onClose }
							aria-label={ translate( 'Close' ) }
						>
							×
						</button>
					</div>
					<div className="migration-survey__modal-content">
						<p>{ translate( 'Your feedback is incredibly valuable. We want to make sure the new WooCommerce Shipping is an excellent replacement for you before we officially retire the shipping functionality in this plugin.' ) }</p>
						<p>{ translate( 'P.S. While we work on improvements, did you know the new WooCommerce Shipping includes discounted UPS rates? You can print USPS, DHL, and now UPS labels right from your dashboard – no separate accounts needed. Want to see how easy it is?' ) } <a href="https://woocommerce.com/document/migrating-from-woocommerce-services-to-woocommerce-shipping/" target="_blank" rel="noopener noreferrer">{ translate( 'Read the step-by-step migration guide.' ) }</a></p>
					</div>
					<div className="migration-survey__modal-footer">
						<button 
							className="migration-survey__button button is-primary"
							onClick={ onClose }
							aria-label={ translate( 'Close after submitting feedback' ) }
						>
							{ translate( 'Close' ) }
						</button>
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="migration-survey__modal-backdrop">
			<div className="migration-survey__modal">
				<div className="migration-survey__modal-header">
					<h3>{ translate( 'A Quick Question About Shipping' ) }</h3>
					<button 
						className="migration-survey__modal-close"
						onClick={ handleSkip }
						type="button" // Prevent form submission as it's being rendered in the order form
						aria-label={ translate( 'Close survey' ) }
					>
						×
					</button>
				</div>

				<div className="migration-survey__modal-content">
					<p>{ translate( 'Thanks for shipping with Woo! We are preparing to transition to the new WooCommerce Shipping plugin as our single officially supported shipping solution. To help us make it a great experience for you, could you answer one quick question?' ) }</p>

					<div className="migration-survey__question">
						<h4>{ translate( 'What\'s the main reason you\'re still using WooCommerce Shipping & Tax as your shipping tool?' ) }</h4>
						{ primaryQuestions.map( question => (
							<label key={ question.value } className="migration-survey__option">
								<input
									className="migration-survey__radio"
									type="radio"
									name="primary_reason"
									value={ question.value }
									checked={ primaryAnswer === question.value }
									onChange={ () => handlePrimaryChange( question.value ) }
								/>
								<span>{ question.label }</span>
							</label>
						) ) }
					</div>

					{ showFollowup && followupQuestions[ primaryAnswer ] && (
						<div className="migration-survey__question migration-survey__followup">
							<div className="migration-survey__option">
								<div>
									<label htmlFor="followup_textarea">{ followupQuestions[ primaryAnswer ].label }</label>
									<textarea
										id="followup_textarea"
										value={ followupAnswers.text || '' }
										onChange={ ( e ) => handleTextChange( 'text', e.target.value ) }
										rows="3"
										className="migration-survey__textarea"
									/>
								</div>
							</div>
						</div>
					) }
				</div>

				<div className="migration-survey__modal-footer">
					<button 
						className="migration-survey__button button is-secondary"
						onClick={ handleSkip }
						disabled={ isSubmitting }
						type="button" // Prevent form submission as it's being rendered in the order form
						aria-label={ translate( 'Skip' ) }
					>
						{ translate( 'Skip' ) }
					</button>
					<button 
						className="migration-survey__button button is-primary"
						onClick={ handleSubmit }
						disabled={ ! primaryAnswer || isSubmitting }
						type="button" // Prevent form submission as it's being rendered in the order form
						aria-label={ isSubmitting ? translate( 'Submitting…' ) : translate( 'Submit Feedback' ) }
					>
						{ isSubmitting ? translate( 'Submitting…' ) : translate( 'Submit Feedback' ) }
					</button>
				</div>
			</div>
		</div>
	);
};

export default localize( MigrationSurveyModal );
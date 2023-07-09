import React from 'react';

import { bindActionCreators } from 'redux';
import { useState, useEffect } from '@wordpress/element';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import { get, truncate } from 'lodash';
import { ExternalLink, RadioControl } from '@wordpress/components';
import FormSelect from 'wcs-client/components/forms/form-select';
import { setHazmatType } from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import UPSHazmatTypes from './hazmat-types';
import {
	getSelectedHazmatType
} from 'wcs-client/extensions/woocommerce/woocommerce-services/state/shipping-label/selectors';

export const UspsHazmat = ({ translate, orderId, setHazmatType, hazmatType }) => {
	const [option, setOption] = useState( 'no' );

	useEffect(() => {
		setOption( hazmatType ? 'yes' : 'no' );
	}, [hazmatType]);
	const changeIsHazmat = (value) => {
		setOption(value);
		if ( value === 'no' && hazmatType ) { // remove hazmatType if user changes option to no
			setHazmatType({
				hazmatType: null,
				orderId,
				siteId
			});
		}
	};

	const hazmatTypeChange = (e) => {
		setHazmatType( e.target.value, orderId );
	};

  return (
    <>
      <hr/>
      <div className="label-purchase-modal__hazmat-section">
        <div className="label-purchase-modal__hazmat-section-content">
          <RadioControl
            className="label-purchase-modal__option-mark-shipment-has-hazmat rates-step__shipping-rate-radio-control"
            label={translate('Are you shipping dangerous goods or hazardous materials?')}
            selected={option}
            options={[
              { label: 'No', value: 'no' },
              { label: 'Yes', value: 'yes' },
            ]}
            onChange={changeIsHazmat}
          />

          {option === 'yes' && <>
            <p className="description">
              {translate(
                `Potentially hazardous material includes items such as batteries, dry ice, flammable liquids, aerosols, ammunition, fireworks, nail polish, perfume, paint, solvents, and more. Determine your product's mailability using the {{hazmatSearchTool/}}. Hazardous items must ship in separate packages.
Learn how to securely package, label, and ship HAZMAT for domestic destinations through USPS{{registeredMark/}} at {{uspsHazmatTutorial/}}.`,
							{
								components: {
									hazmatSearchTool: <ExternalLink href="https://pe.usps.com/HAZMAT/Index">{translate( 'USPS HAZMAT Search Tool' )}</ExternalLink>,
									registeredMark: <span className="registered-mark">&#174;</span>,
									uspsHazmatTutorial: <ExternalLink
										href="https://www.uspsdelivers.com/hazmat-shipping-safety">www.usps.com/hazmat</ExternalLink>,

								}
							}
						)}

            </p>
            <legend className="form-legend">{translate('Select a category')}</legend>
            <FormSelect
              onChange={hazmatTypeChange}
							value={hazmatType}
            >
              <option value={'not_selected'} key={'not_selected'}>
                {translate('Select a hazardous or dangerous material category')}
              </option>
              {Object.entries(UPSHazmatTypes).map(([ key, caption ]) =>
                (<option style={{maxWidth: '300px', width: '300px'}} value={key} key={key}>{truncate(caption, {length: 120, omission: ' ...'})}</option>))
              }

            </FormSelect>
          </>}
        </div>
      </div>
    </>)
}

const mapStateToProps = (state, { orderId, siteId }) => {
	return {
		hazmatType: getSelectedHazmatType(state, { orderId, siteId })
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators({
		setHazmatType
	}, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(localize(UspsHazmat));

/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { configure, mount } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';
import CompactCard from 'components/card/compact';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import * as api from 'woocommerce/woocommerce-services/api';
import { act } from 'react-dom/test-utils';

/**
 * Internal dependencies
 */
import { DynamicCarrierAccountSettingsForm, CheckboxFormFieldSet } from '../dynamic-settings-form.js';

configure( { adapter: new Adapter() } );

jest.mock('components/forms/form-text-input', () => {
    return function DummyFormTextField(props) {
        return (
        <div>
            <input className="test__dummy-form-text-field" onChange={props.onChange}/>
        </div>
        );
    }
});

function createDynamicCarrierAccountSettingsFormWrapper() {
	const props = {
		siteId: 1234,
		translate: ( text ) => text,
        carrierType: 'DhlExpressAccount',
        carrierName: 'DHL Express',
        registrationFields: {
            "account_number": {
                "visibility": "visible",
                "label": "DHL Account Number"
            },
            "country": {
                "visibility": "visible",
                "label": "Account Country Code (2 Letter)"
            },
            "is_reseller": {
                "visibility": "checkbox",
                "label": "Reseller Account? (check if yes)"
            }
        }
	};

	return mount( <DynamicCarrierAccountSettingsForm { ...props } /> );
}

describe( 'Carrier Account Dynamic Registration Form', () => {
    afterEach(() => {
        jest.clearAllMocks();
      });

	describe( 'with the correct sub-components', () => {
        const wrapper = createDynamicCarrierAccountSettingsFormWrapper();
		it( 'renders 3 fields from the provided props', function () {
			expect(wrapper.find( CompactCard )).toHaveLength(3);
        } );

        it( 'renders 2 visible fields as TextField', function () {
			expect(wrapper.find( TextField )).toHaveLength(2);
        } );

        it( 'renders 1 checkbox fields as Checkbox', function () {
			expect(wrapper.find( CheckboxFormFieldSet )).toHaveLength(1);
        } );
    } );

    describe( 'when clicked registration button', () => {
        it ('should submit the form when data are input into the fields', async () => {
            let wrapper;

            const apiSpy = jest.spyOn(api, "post").mockImplementation(() =>
                Promise.resolve({})
            );

            await act(async () => {
                wrapper = createDynamicCarrierAccountSettingsFormWrapper();
            });

            await act(async () => {
                 wrapper.find( 'input.test__dummy-form-text-field' ).first().simulate('change', {target: {
                    value: 'A123456'
                }});
            });
            await act(async () => {
                wrapper.find( 'input.test__dummy-form-text-field' ).at(1).simulate('change', {target: {
                    value: 'US'
                }});
            });
            await act(async () => {
                wrapper.find( 'input#is_reseller' ).simulate('change', {target: {
                    checked: true
                }});
            });
            await act(async () => {
                // Click the register button
                wrapper.find( 'button.is-primary' ).simulate('click');
                expect(apiSpy).toHaveBeenCalledTimes(1);
                expect(apiSpy).toHaveBeenCalledWith(1234, 'connect/shipping/carrier', {
                        type: 'DhlExpressAccount',
                        settings: {
                            account_number: 'A123456',
                            country: 'US',
                            is_reseller: true
                        }
                    }
                );
            });
        });
    } );
} );

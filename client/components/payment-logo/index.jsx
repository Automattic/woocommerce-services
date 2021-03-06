/**
 * External dependencies
 */

import classNames from 'classnames';
import PropTypes from 'prop-types';
import React from 'react';
import { keys } from 'lodash';
import i18n from 'i18n-calypso';

/**
 * Style dependencies
 */
import './style.scss';

/**
 * Image assets
 */
import creditCardAmexImage from './images/cc-amex.svg';
import creditCardDinersImage from './images/cc-diners.svg';
import creditCardDiscoverImage from './images/cc-discover.svg';
import creditCardJCBImage from './images/cc-jcb.svg';
import creditCardMasterCardImage from './images/cc-mastercard.svg';
import creditCardUnionPayImage from './images/cc-unionpay.svg';
import creditCardVisaImage from './images/cc-visa.svg';

const LOGO_PATHS = {
	amex: creditCardAmexImage,
	diners: creditCardDinersImage,
	discover: creditCardDiscoverImage,
	jcb: creditCardJCBImage,
	mastercard: creditCardMasterCardImage,
	unionpay: creditCardUnionPayImage,
	visa: creditCardVisaImage,
};

const ALT_TEXT = {
	alipay: 'Alipay',
	amex: 'American Express',
	'apple-pay': 'Apple Pay',
	bancontact: 'Bancontact',
	'brazil-tef': 'Transferência bancária',
	diners: 'Diners Club',
	discover: 'Discover',
	eps: 'eps',
	giropay: 'Giropay',
	id_wallet: 'OVO',
	ideal: 'iDEAL',
	jcb: 'JCB',
	mastercard: 'Mastercard',
	netbanking: 'Net Banking',
	p24: 'Przelewy24',
	paypal: 'PayPal',
	placeholder: 'Payment logo',
	unionpay: 'UnionPay',
	visa: 'Visa',
	wechat: i18n.translate( 'WeChat Pay', {
		comment: 'Name for WeChat Pay - https://pay.weixin.qq.com/',
	} ),
	sofort: 'Sofort',
};

export const POSSIBLE_TYPES = keys( ALT_TEXT );

class PaymentLogo extends React.Component {
	static propTypes = {
		className: PropTypes.string,
		type: PropTypes.oneOf( POSSIBLE_TYPES ),
		altText: PropTypes.string,
		isCompact: PropTypes.bool,
		disabled: PropTypes.bool,
	};

	render() {
		const { altText, className, isCompact, type, disabled } = this.props;

		const classes = classNames(
			'payment-logo',
			`is-${ type }`,
			{ 'is-compact': isCompact },
			{ disabled },
			className
		);

		// Credit card images have been migrated to Webpack, while the remaining
		// images are still referenced in the stylesheets (they’re still to be migrated)
		const logoPath = LOGO_PATHS[ type ];
		const logoStyle = logoPath ? { backgroundImage: `url(${ logoPath })` } : undefined;

		return (
			<div
				className={ classes }
				style={ logoStyle }
				aria-label={ altText || ALT_TEXT[ type ] || '' }
			/>
		);
	}
}

export default PaymentLogo;

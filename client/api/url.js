const namespace = 'wc/v1/connect/';

export const accountSettings = () => namespace + 'account/settings';

export const packages = () => namespace + 'packages';

export const orderLabels = ( orderId ) => `${ namespace }label/${ orderId }`;

export const getLabelRates = ( orderId ) => `${ namespace }label/${ orderId }/rates`;

export const labelStatus = ( orderId, labelId ) => `${ namespace }label/${ orderId }/${ labelId }`;

export const labelRefund = ( orderId, labelId ) => `${ namespace }label/${ orderId }/${ labelId }/refund`;

export const labelsPrint = () => `${ namespace }label/print`;

export const labelTestPrint = () => `${ namespace }label/preview`;

export const addressNormalization = () => `${ namespace }normalize-address`;

export const shippingServiceSettings = ( methodId, instanceId = false ) => instanceId
	? `${ namespace }services/${ methodId }/${ instanceId }`
	: `${ namespace }services/${ methodId }`;

export const dismissShippingSettingsNuxNotice = () => `${ namespace }services/dismiss_notice`;

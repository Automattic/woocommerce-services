/** @format */
export const accountSettings = 'connect/account/settings';
export const packages = 'connect/packages';
export const orderLabels = ( orderId ) => `connect/label/${ orderId }`;
export const getLabelRates = ( orderId ) => `connect/label/${ orderId }/rates`;
export const labelStatus = ( orderId, labelId ) => `connect/label/${ orderId }/${ labelId }`;
export const labelsStatus = ( orderId, labelIds ) => `connect/label/${ orderId }/${ labelIds.join() }`;
export const labelRefund = ( orderId, labelId ) => `connect/label/${ orderId }/${ labelId }/refund`;
export const labelsPrint = () => 'connect/label/print';
export const labelTestPrint = () => 'connect/label/preview';
export const addressNormalization = () => 'connect/normalize-address';
export const serviceSettings = ( methodId, instanceId = 0 ) => `connect/services/${ methodId }/${ instanceId }`;
export const shippingCarrier = () => 'connect/shipping/carrier';
export const shippingCarriers = () => 'connect/shipping/carriers';
export const subscriptions = () => 'connect/subscriptions';
export const subscriptionActivate = ( subscriptionKey ) => `connect/subscription/${ subscriptionKey }/activate`;
export const shippingCarrierDelete = ( carrier ) => `connect/shipping/carrier/${ carrier }`;
export const shippingCarrierTypes = () => 'connect/shipping/carrier-types';

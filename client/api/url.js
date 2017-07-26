const namespace = 'wc/v1/connect/';

export const accountSettings = () => namespace + 'account/settings';

export const packages = () => namespace + 'packages';

export const purchaseLabel = ( orderId ) => namespace + 'label/' + orderId;

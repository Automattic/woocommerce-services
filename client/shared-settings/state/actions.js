export const SET_PAYMENT_METHOD = 'SET_PAYMENT_METHOD';

export const setPaymentMethod = ( payment_method_id ) => {
	return {
		type: SET_PAYMENT_METHOD,
		payment_method_id,
	};
};

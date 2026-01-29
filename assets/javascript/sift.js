/* global wcServicesSiftConfig */

if ( typeof wcServicesSiftConfig !== 'undefined' && wcServicesSiftConfig.beacon_key && wcServicesSiftConfig.user_id ) {
	const _sift = window._sift || [];
	_sift.push( [ '_setAccount', wcServicesSiftConfig.beacon_key ] );
	_sift.push( [ '_setUserId', wcServicesSiftConfig.user_id ] );
	_sift.push( [ '_trackPageview' ] );
}

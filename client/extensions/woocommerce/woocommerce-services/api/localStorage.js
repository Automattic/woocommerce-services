export const loadSettingFromLocalStorage = ( siteId, name, allowedAge ) => {
	try {
		const serializedState = localStorage.getItem( 'wcs-' + name + '-siteId-' + siteId );
		if (serializedState === null) {
		return undefined;
		}
		const settings = JSON.parse(serializedState);
		const age = Date.now() - settings.localStorageTimestamp;
		if( allowedAge < age ) {
			return undefined;
		}
		delete settings.localStorageTimestamp;
		return settings;
	} catch (err) {
		return undefined;
	}
};

export const storeSettingInLocalStorage = ( siteId, name, settings ) => {
	settings.localStorageTimestamp = Date.now();
	localStorage.setItem( 'wcs-' + name + '-siteId-' + siteId , JSON.stringify( settings ) );
}
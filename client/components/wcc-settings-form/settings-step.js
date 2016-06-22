import React from 'react';
import WCCSettingsGroup from './settings-group';

const WCCSettingsStep = ( props ) => {
	return (
		<div>
			{ props.layout.items.map( ( group, idx ) => (
				<WCCSettingsGroup
					{ ...props }
					group={ group }
					saveForm={ props.formValueActions.submit }
					key={ idx }
				/>
			) ) }
		</div>
	);
};

export default WCCSettingsStep;

import React from 'react';
import Gridicon from 'components/gridicon';
import { translate as __ } from 'lib/mixins/i18n';

const ExpandButton = () => (
	<button className="foldable-card__action foldable-card__expand" type="button">
		<span className="screen-reader-text">{ __( 'Expand' ) }</span>
		<Gridicon icon="chevron-down" size={ 24 } />
	</button>
);

export default ExpandButton;

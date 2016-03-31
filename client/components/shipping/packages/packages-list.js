import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import Gridicon from 'components/gridicon';
import Button from 'components/button';

export default React.createClass( {
	displayName: 'PackagesList',
	render: function() {
		return (
			<FormFieldset>
				<div style={ { height: '26px', borderBottom: '1px solid #e9eff3' } }>
					<FormLegend style={ { float: 'left', width: '10%' } }>Type</FormLegend>
					<FormLegend style={ { float: 'left', width: '65%' } }>Name</FormLegend>
					<FormLegend style={ { float: 'right', width: '25%' } }>Dimensions (L x W x H)</FormLegend>
				</div>
				<div style={ { marginTop: '10px', display: 'flex' } }>
					<div style={ { width: '10%' } }>
						<Gridicon icon="mail" />
					</div>
					<div style={ { width: '65%' } }>
						<a href="#">Large padded envelope</a>
					</div>
					<div style={ { width: '25%' } }>
						<span>14 x 7 x .25 in</span>
						<Button compact borderless style={ { float: 'right' } }>
							<Gridicon icon="cross" size={ 18 } />
						</Button>
					</div>
				</div>
				<div style={ { marginTop: '10px', display: 'flex' } }>
					<div style={ { width: '10%' } }>
						<div class="">
							<Gridicon icon="flip-horizontal" style={ { borderRadius: '50%', backgroundColor: 'lightgray', fillColor: '#fff', padding: '6px' } } />
						</div>
					</div>
					<div style={ { width: '65%' } }>
						<a href="#">Bike box</a>
					</div>
					<div style={ { width: '25%' } }>
						<span>34 x 12 x 9.75 in</span>
						<Button compact borderless style={ { float: 'right' } }>
							<Gridicon icon="cross" size={ 18 } />
						</Button>
					</div>
				</div>
			</FormFieldset>
		);
	}
} );

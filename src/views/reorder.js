/*
	props required to render form:
	- schema
	- formData
	- SchemaField ?

	state required to render form:
	- uiSchema

	scratch:
	- SchemaField component uses extra uiSchema data to determine if/what custom wrapper is needed
	- we need to be able to sort complex structures (an object), or an array
	  - on Form component, uiSchema is a prop, formData is state
	  - object needs to store order in another property, update that in formData, and update property order in uiSchema
	  - arrays can just have the items reordered in formData
*/

import React, { Component } from "react";
import SchemaField from "react-jsonschema-form/lib/components/fields/SchemaField";
import Form from "react-jsonschema-form";

class Settings extends Component {
	
	constructor( props ) {
		super( props );
		this.state = {
			uiSchema: props.initialUiSchema || {},
			formData: props.initialFormData || {}
		};
	}

	render() {
		return(
			<Form schema={this.props.schema} uiSchema={this.state.uiSchema} formData={this.state.formData} />
		);
	}

}

export default Settings;
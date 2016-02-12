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
import { Draggable, Droppable } from "react-drag-and-drop";

const DraggableFieldContainer = ( props ) => {
  const {
    children,
    dragData,
    onDrop
  } = props;
  return (
    <Draggable type="sortable" data={dragData}>
      <Droppable types={["sortable"]} onDrop={onDrop}>
        {children}
      </Droppable>
    </Draggable>
  );
}

class SortableField extends Component {

  handleDrop(data) {
    const {name} = this.props;
    if ("sortable" in data && data["sortable"]) {
      if (data["sortable"] !== name) {
        console.log( "swapping", data["sortable"], name);
      }
    }
  }

  render() {
    const props = this.props; console.log( props );

    if ( props.uiSchema && props.uiSchema.sortable ) {
      return (
        <DraggableFieldContainer
          dragData={props.name}
          onDrop={this.handleDrop.bind(this)}>
          <SchemaField {...props}/>
        </DraggableFieldContainer>
      );
    }

    return <SchemaField {...props} />;

  }

}

class Settings extends Component {
	
	constructor( props ) {
		super( props );
		this.state = {
			uiSchema: props.initialUiSchema || {},
			formData: props.initialFormData || { title: "" }
		};
	}

	render() {
		return(
			<Form
				schema={this.props.schema}
				uiSchema={this.state.uiSchema}
				formData={this.state.formData}
				SchemaField={SortableField}
			/>
		);
	}

}

export default Settings;
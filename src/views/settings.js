import React, { Component } from "react";
import { Draggable, Droppable } from "react-drag-and-drop";
import SchemaField from "react-jsonschema-form/lib/components/fields/SchemaField";
import Form from "react-jsonschema-form";

const schema = {
  title: "Todo Tasks",
  type: "object",
  required: ["title"],
  properties: {
    one: {
      type: "object",
      properties: {
        title: {
          type: "string",
          title: "Title",
          default: "A new task"
        },
        done: {
          type: "boolean",
          title: "Done?",
          default: false
        }
      }
    },
    two: {
      type: "object",
      properties: {
        title: {
          type: "string",
          title: "Title",
          default: "A new task"
        },
        done: {
          type: "boolean",
          title: "Done?",
          default: false
        }
      }
    }
  }
};

const uiSchema = {
	one: {
		sortable: true
	},
  two: {
    sortable: true
  }
};

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
    const {name, swapFields, insertField} = this.props;
    if ("sortable" in data && data["sortable"]) {
      if (data["sortable"] !== name) {
        console.log( "swapping", data["sortable"], name);
      }
    }
  }

  render() {
    const props = this.props;

    if ( props.uiSchema && props.uiSchema.sortable ) {
      return (
        <DraggableFieldContainer
          dragData={props.name}
          onDrop={this.handleDrop.bind(this)}>
          <SchemaField {...props}/>
        </DraggableFieldContainer>
      );
    }

    return <SchemaField {...props}/>;

  }

}

module.exports = React.createClass( {

    render: function() {
        return (
            <Form schema={schema} uiSchema={uiSchema} SchemaField={SortableField} />
        );
    }

} );

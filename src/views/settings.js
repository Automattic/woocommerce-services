import React from "react";
import SchemaField from "react-jsonschema-form/lib/components/fields/SchemaField";
import Form from "react-jsonschema-form";

const schema = {
  title: "Todo Tasks",
  type: "object",
  required: ["title"],
  properties: {
    title: {type: "string", title: "Title", default: "A new task"},
    done: {type: "boolean", title: "Done?", default: false}
  }
};

const uiSchema = {
	done: {
		"ui:widget": "select",
		"arbitrary": "value"
	}
};

const CustomSchemaField = function( props ) {

	if ( props.uiSchema && props.uiSchema.hasOwnProperty( "arbitrary" ) && "value" === props.uiSchema.arbitrary ) {

		return (
			<div className="custom">
				<p>Arbitrary Value found</p>
				<SchemaField {...props} />
			</div>
		);

	}

	return (
		<SchemaField {...props} />
	);
};

module.exports = React.createClass( {

    render: function() {
        return (
            <Form schema={schema} uiSchema={uiSchema} SchemaField={CustomSchemaField} />
        );
    }

} );
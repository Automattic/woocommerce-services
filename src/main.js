import React from "react";
import ReactDOM from "react-dom";
import Settings from "./views/reorder";

const props = {
	schema: {
	  "title": "A list of tasks",
	  "type": "object",
	  "required": [
	    "title"
	  ],
	  "properties": {
	    "title": {
	      "type": "string",
	      "title": "Task list title"
	    },
	    "tasks": {
	      "type": "array",
	      "title": "Tasks",
	      "items": {
	        "type": "object",
	        "required": [
	          "title"
	        ],
	        "properties": {
	          "title": {
	            "type": "string",
	            "title": "Title",
	            "description": "A sample title"
	          },
	          "details": {
	            "type": "string",
	            "title": "Task details",
	            "description": "Enter the task details"
	          },
	          "done": {
	            "type": "boolean",
	            "title": "Done?",
	            "default": false
	          }
	        }
	      }
	    }
	  }
	},
	"initialUiSchema": {
		"tasks": {
			"sortable": true
		}
	}
};

document.addEventListener( "DOMContentLoaded", () => {
    ReactDOM.render(
        <Settings { ...props } />,
        document.getElementById( 'wc-connect-admin-container' )
    );
} );
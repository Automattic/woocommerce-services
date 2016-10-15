# POST /services

## Example Request

### Query Params:

    None

### Headers:

    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en
    Authorization: X-Jetpack API_TOKEN

### Body (JSON):

```javascript
{
  wc_settings: {
    base_city : 'Snohomish',
    base_country : 'US',
    base_postcode : '98290',
    base_state : 'WA',
    currency : 'USD',
    dimension_unit : 'cm',
    jetpack_version : '3.9.1',
    wc_version : '2.5.2',
    weight_unit : 'kg',
    wp_version : '4.5-alpha-36527'
  }
}
```

## Example Response (JSON):

```javascript
{
  services : {
    shipping : [
      {
        id : "usps",
        method_description : "Obtains rates dynamically from the USPS API during cart/checkout.",
        method_title : "USPS (Connect for WooCommerce)",
        service_settings : {
          type : "object",
          required : [
          ],
          properties : {
            enabled : {
              type : "boolean",
              title : "Enable/Disable",
              description : "Enable this shipping method",
              "default" : false
            },
            title : {
              type : "string",
              title : "Method Title",
              description : "This controls the title which the user sees during checkout.",
              "default" : "USPS"
            },
            origin : {
              type : "string",
              title : "Origin Postcode",
              description : "Enter the postcode for the sender.",
              "default" : ""
            },
            boxes : {
              type : "array",
              title : "Box Sizes",
              description : "Items will be packed into these boxes based on item dimensions and volume. Outer dimensions will be passed to USPS, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.",
              items : {
                type : "object",
                title : "Box",
                properties : {
                  name : {
                    type : "string",
                    title : "Name"
                  },
                  outer_length : {
                    type : "number",
                    title : "L (in)"
                  },
                  outer_width : {
                    type : "number",
                    title : "W (in)"
                  },
                  outer_height : {
                    type : "number",
                    title : "H (in)"
                  },
                  inner_length : {
                    type : "number",
                    title : "Inner L (in)"
                  },
                  inner_width : {
                    type : "number",
                    title : "Inner W (in)"
                  },
                  inner_height : {
                    type : "number",
                    title : "Inner H (in)"
                  },
                  box_weight : {
                    type : "number",
                    title : "Weight of Box (lbs)"
                  },
                  max_weight : {
                    type : "number",
                    title : "Max Weight (lbs)"
                  },
                  is_letter : {
                    type : "boolean",
                    title : "Letter"
                  }
                }
              }
            }
          }
        }
      },
      {
        id : "canada_post",
        method_description : "Obtains rates dynamically from the Canada Post API during cart/checkout.",
        method_title : "Canada Post (Connect for WooCommerce)",
        service_settings : {
          type : "object",
          required : [
          ],
          definitions : {
          },
          properties : {
            enabled : {
              type : "boolean",
              title : "Enable/Disable",
              description : "Enable this shipping method",
              "default" : false
            },
            title : {
              type : "string",
              title : "Method Title",
              description : "This controls the title which the user sees during checkout.",
              "default" : "Canada Post"
            }
          }
        }
      }
    ]
  }
}
```

# POST /services/{service}/rates

## Example Request

### Query Params:

    None

### Headers:

    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en
    Authorization: X-Jetpack API_TOKEN

### Body (JSON):

```javascript
{
  "wc_settings": {
    "base_city": "Snohomish",
    "base_country": "US",
    "base_postcode": "98290",
    "base_state": "WA",
    "currency": "USD",
    "dimension_unit": "cm",
    "weight_unit": "kg",
    "jetpack_version": "3.9.1",
    "wc_version": "2.5.2",
    "wp_version": "4.5-alpha-36527"
  },
  "service_settings": {
    "title": "USPS - West Coast Warehouse",
    "origin": "98290",
    "boxes": []
  }
}
```

## Example Response (JSON):

### Success (HTTP 200)

    None

### Failure (HTTP 400)

```javascript
{
  "statusCode": 400,
  "error": "Bad Request",
  "message": "ValidationError: child \"boxes\" fails because [\"boxes\" is required]"
}
```

# POST /shipping/rates

## Example Request

### Query Params:

    None

### Headers:

    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en
    Authorization: X-Jetpack API_TOKEN

### Body (JSON):

```javascript
{
  wc_settings: {
    base_city : 'Snohomish',
    base_country : 'US',
    base_postcode : '98290',
    base_state : 'WA',
    currency : 'USD',
    dimension_unit : 'cm',
    weight_unit : 'kg',
    jetpack_version : '3.9.1',
    wc_version : '2.5.2',
    wp_version : '4.5-alpha-36527'
  },
  services: [
    {
      id : 'usps',
      instance : '0',
      service_settings : {
        title : 'USPS - West Coast Warehouse',
        origin : '98290',
        boxes : []
      }
    },
    {
      id : 'usps',
      instance : '1',
      service_settings : {
        title : 'USPS - East Coast Warehouse',
        origin : '22306',
        boxes : []
      }
    }
  ],
  destination: {
    address : '1003 1st St',
    address_2 : '',
    country : 'US',
    city : 'Snohomish',
    postcode : 98290,
    state : 'WA'
  },
  contents: [
    {
      height : 10,
      length : 10,
      product_id : 403,
      quantity : 1,
      weight : 5,
      width : 10
    },
    {
      height : 15,
      length : 15,
      product_id : 407,
      quantity : 1,
      weight : 10,
      width : 15
    },
    {
      height : 4,
      length : 2,
      quantity : 2,
      product_id : 513,
      weight : 2,
      width : 3
    }
  ]
}
```

## Example Response (JSON):

```javascript
{
  "rates": [
    {
      "id": "usps",
      "instance": 0,
      "rates": [
        {
          "title": "1-Day Shipping",
          "rate": 15.00,
          "packages": [
            {
              "id": "box_2",
              "name": "medium box",
              "length": 1,
              "width": 1,
              "height": 1,
              "items": [
                {
                  "product_id": 513,
                  "quantity": 1
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

# GET /notices

## Example Request

### Query Params:

    None

### Headers:

    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en
    Authorization: X-Jetpack API_TOKEN

### Body (JSON):

```javascript
{
  "settings": {
    "base_city": "Snohomish",
    "base_country": "US",
    "base_postcode": "98290",
    "base_state": "WA",
    "currency": "USD",
    "dimension_unit": "cm",
    "dismissed_notices": [
    	"connect-your-jetpack"
    ],
    "jetpack_version": "3.9.1",
    "wc_version": "2.5.2",
    "weight_unit": "kg",
    "wp_version": "4.5-alpha-36527"
  }
}
```

## Example Response (JSON):

```javascript
{
	"notices": [
		{
			"id": "connect-your-jetpack",
			"location": "global",
			"title": "Connect for WooCommerce Rocks",
			"message": "You should connect your Jetpack and enable Connect for WooCommerce.",
			"button_text": "Connect",
			"button_action": "/wp-admin/admin.php?page=wc-settings&action=connect"
		},
		{
			"id": "enable-usps-shipping",
			"location": "shipping",
			"title": "USPS Now Available",
			"message": "USPS shipping is now available through Connect for WooCommerce.",
			"button_text": "Enable USPS",
			"button_action": "/wp-admin/admin.php?page=wc-settings&action=enable-usps"
		}
	]
}
```
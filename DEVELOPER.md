# GET /services

## Example Request

### Query Params:

    None

### Headers:

    Authorization: Bearer API_TOKEN
    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en

### Body (JSON):

```javascript
{
  jetpack_site_id: 12345678,
  wc_base_location: 'US'
}
```

## Example Response (JSON):

```javascript
{
  "services" : [
    "shipping" : [
      "usps" : {
        "id" : "usps",
        "title" : "USPS",
        "method_title" : "USPS (WooCommerce Connect)",
        "method_description" : "Shipping via USPS, Powered by WooCommerce Connect",
        "fields" : {
          "type" : "object",
          "title" : "USPS",
          "description" : "The USPS extension obtains rates dynamically from the USPS API during cart/checkout.",
          "required" : [
          ],
          "definitions" : {
            "countries" : {
              "type" : "string",
              "enum" : [
                "AU",
                "GB",
                "US"
              ],
              "enumNames" : [
                "Australia",
                "United Kingdom (UK)",
                "United States (US)"
              ]
            }
          },
          "properties" : {
            "enabled" : {
              "type" : "boolean",
              "title" : " Enable/Disable",
              "description" : "Enable this shipping method",
              "default":false
            },
            "title" : {
              "type" : "string",
              "title" : "Method Title",
              "description" : "This controls the title which the user sees during checkout.",
              "default" : ""
            },
            "origin" : {
              "type" : "string",
              "title" : "Origin Postcode",
              "description" : "Enter the postcode for the sender.",
              "default" : ""
            },
            "method_availability" : {
              "type" : "object",
              "title" : "Method Availability",
              "properties" : {
                "filter" : {
                  "type" : "string",
                  "enum" : [
                    "all",
                    "specific",
                    "excluding"
                  ],
                  "enumNames" : [
                    "All Countries",
                    "Specific Countries",
                    "Exclude Specific Countries"
                  ]
                }
              },
              "oneOf" : [
                {
                  "properties" : {
                    "filter" : {
                      "enum" : [
                        "all"
                      ]
                    }
                  }
                },
                {
                  "properties" : {
                    "filter" : {
                      "enum" : [
                        "specific"
                      ]
                    },
                    "countries" : {
                      "title" : "Specific Countries",
                      "$ref" : "#/definitions/countries"
                    }
                  }
                },
                {
                  "properties" : {
                    "filter" : {
                      "enum" : [
                        "excluding"
                      ]
                    },
                    "countries" : {
                      "title" : "Specific Countries",
                      "$ref" : "#/definitions/countries"
                    }
                  }
                }
              ],
              "x-hints" : {
                "form" : {
                  "selector" : "filter"
                }
              }
            }
          }
        ]
      },
      "canada-post" : {
        "id" : "canada-post",
        "title" : "Canada Post",
        "method_title" : "Canada Post (WooCommerce Connect)",
        "method_description" : "Shipping via Canada Post, Powered by WooCommerce Connect",
        "fields" : {
        }
      }
    ]
  ]
}
```

# GET /shipping/rates

## Example Request

### Query Params:

    None

### Headers:

    Authorization: Bearer API_TOKEN
    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en

### Body (JSON):

```javascript
{
  settings: {
    currency: 'USD',
    weight: 'kg',
    distance: 'cm'
  },
  fields: {
    usps: [
      enabled: true,
      'method-availability': 'specific',
      'countries': [
        'US'
      ],
      origin: 98290
    ],
    'canada-post': [
      enabled: false,
    ]
  },
  origin: {
    country: 'US',
    state: 'WA',
    postcode: 98290,
    city: 'Snohomish',
    address: '1003 1st St',
    address_2: ''
  },
  destination: {
    country: 'US',
    state: 'WA',
    postcode: 98290,
    city: 'Snohomish',
    address: '1003 1st St',
    address_2: ''
  },
  contents: [
    {
      quantity: 1,
      weight: 5,
      length: 10,
      width: 10,
      height: 10
    },
    {
      quantity: 1,
      weight: 10,
      length: 15,
      width: 15,
      height: 15
    },
    {
      quantity: 2,
      weight: 2,
      length: 2,
      width: 3,
      height: 4
    }
  ]
}
```

## Example Response (JSON):

```javascript
{
  rates:[
    'usps' : [
      {
        title: 'Priority Mail Express',
        rate: 15.00
      },
      {
        title: 'Priority Mail',
        rate: 8.00
      }
    ]
  ]
}
```

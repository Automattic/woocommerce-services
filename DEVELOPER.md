# GET /services

## Example Request

### Query Params:

    None

### Headers:

    TODO: Version
    TODO: Token
    TODO: Language

### Body (JSON):

    {
      jetpack_site_id: 12345678,
      wc_base_location: 'US'
    }


## Example Response (JSON):

    {
      services:[
        'shipping' : [
          'usps' : {
            'id' : 'usps',
            'title' : 'USPS',
            'method_title' : 'USPS (WooCommerce Connect)',
            'method_description' : 'Shipping via USPS, Powered by WooCommerce Connect'
            'json-schema' : {
            }
          },
          'canada-post' : {
            'id' : 'canada-post',
            'title' : 'Canada Post',
            'method_title' : 'Canada Post (WooCommerce Connect)',
            'method_description' : 'Shipping via Canada Post, Powered by WooCommerce Connect'
            'json-schema' : {
            }
          }
        ]
      ]
    }

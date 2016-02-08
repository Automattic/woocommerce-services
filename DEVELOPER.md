# GET /services

## Example Request

### Query Params:

    None

### Headers:

    Authorization: Bearer API_TOKEN
    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en

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

# GET /shipping/rates

## Example Request

### Query Params:

    None

### Headers:

    Authorization: Bearer API_TOKEN
    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en

### Body (JSON):

    {
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
      units: {
        weight: 'kg',
        distance: 'cm'
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

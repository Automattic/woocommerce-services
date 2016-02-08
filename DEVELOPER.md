# GET /services

## Example Request

### Query Params:

    None

### Headers:

    Authorization: Bearer API_TOKEN
    Accept: application/vnd.woocommerce-connect.v1
    TODO: Token
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
```
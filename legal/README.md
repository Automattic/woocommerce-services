# WooCommerce Connect Client Legal Readme

The Alpha release of the WooCommerce Connect Client (WCC) is for testing purposes, and as such should not be used on production sites. You do so at your own risk.

In order to facilitate the live shipping rate  lookup from the relevant shipping operator (USPS/Canada Post), data must be transferred to their servers from your own, via our intermediary server. An outline of the process is as follows:

* The weight, dimensions, and product IDs of the items in the customer's cart are transferred to the WCC server, along with their complete address.
* Neither the customer's name, or the name of the products are transferred.
* This data is not stored persistently on the WCC server.
* The above data is sent to the shipping operators (along with the postal/zip code of the merchant), who then calculate the shipping cost and provide that in response.
* Other pieces of data are transferred to the WCC server in addition for statistical purposes, but not passed on to the shipping operators. These include the site's Jetpack token; currency, country, and state setting; locale; and postal code.
* TRACKS events are recorded when you add, remove, enable, or disable a shipping service. The info recorded includes your site URL, browser user agent, and IP address.

We rely on the shipping operators to ensure that the information they provide is accurate and up to date. 

The information provided in this notice is subject to change as more features are added.
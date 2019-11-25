/**
 * @format
 */

/**
 * External dependencies
 */
import Sequelize, { Model } from 'sequelize';

/**
 * Internal dependencies
 */

const db = {};

const sequelize = new Sequelize(
	'wordpress-e2e-testing-test',
	'wordpress',
	'password',
	{
		host: '127.0.0.1',
		dialect: 'mysql',
		define: {
			timestamps: false 
		}
	}
);

class Product extends Model {};
class ProductPrice extends Model {};
class Order extends Model {};
class OrderMeta extends Model {};
class OrderLineItem extends Model {};
class OrderLineItemMeta extends Model {};

db.Product = Product.init({
	ID: {
		type: Sequelize.BIGINT( 20 ),
		primaryKey: true
	},
	post_author: {
		type: Sequelize.BIGINT( 20 ),
		allowNull: false,
		defaultValue: 1
	},
	post_date: {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_date_gmt : {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_content  : {
		type: Sequelize.TEXT,
		allowNull: false,
		defaultValue: ''
	},
	post_title: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false
	},
	post_excerpt: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false,
		defaultValue: ''
	},
	post_status: {
		type: Sequelize.STRING( 20 ),
		allowNull: false,
		defaultValue: 'publish'
	},
	comment_status: {
		type: Sequelize.STRING( 20 ),
		allowNull: false,
		defaultValue: 'open'
	},
	ping_status: {
		type: Sequelize.STRING( 20 ),
		allowNull: false,
		defaultValue: 'closed'
	},
	post_password: {
		type: Sequelize.STRING,
		allowNull: false,
		defaultValue: ''
	},
	post_name: {
		type: Sequelize.STRING( 200 ),
		allowNull: false,
		defaultValue: ''
	},
	to_ping: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false,
		defaultValue: ''
	},
	pinged: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false,
		defaultValue: ''
	},
	post_modified : {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_modified_gmt: {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_content_filtered: {
		type: Sequelize.TEXT,
		allowNull: false,
		defaultValue: ''
	},
	post_parent: {
		type: Sequelize.BIGINT( 20 ),
		allowNull: false,
		defaultValue: 0
	},
	guid: {
		type: Sequelize.STRING,
		allowNull: false,
		defaultValue: ''
	},
	menu_order: {
		type: Sequelize.INTEGER,
		allowNull: false,
		defaultValue: 0
	},
	post_type: {
		type: Sequelize.STRING(20),
		allowNull: false,
		defaultValue: 'product'
	},
	post_mime_type: {
		type: Sequelize.STRING(100),
		allowNull: false,
		defaultValue: ''
	},
	comment_count: {
		type: Sequelize.BIGINT( 20 ),
		allowNull: false,
		defaultValue: 0
	}
}, {
  	sequelize,
  	modelName: 'wp_posts'
});

db.ProductPrice = ProductPrice.init({
  	meta_id: {
    	type: Sequelize.BIGINT( 20 ),
    	primaryKey: true
  	},
  	post_id: {
    	type: Sequelize.BIGINT( 20 ),
  	},
  	meta_key: {
    	type: Sequelize.STRING,
    	allowNull: false,
		defaultValue: '_price'
  	},
  	meta_value: {
    	type: Sequelize.STRING
  	}
}, {
  	sequelize,
  	modelName: 'wp_postmeta'
});


db.Order = Order.init({
	ID: {
		type: Sequelize.BIGINT( 20 ),
		primaryKey: true
	},
	post_author: {
		type: Sequelize.BIGINT( 20 ),
		allowNull: false,
		defaultValue: 1
	},
	post_date: {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_date_gmt : {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_content  : {
		type: Sequelize.TEXT,
		allowNull: false,
		defaultValue: ''
	},
	post_title: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false
	},
	post_excerpt: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false,
		defaultValue: ''
	},
	post_status: {
		type: Sequelize.STRING( 20 ),
		allowNull: false,
		defaultValue: 'wc-processing'
	},
	comment_status: {
		type: Sequelize.STRING( 20 ),
		allowNull: false,
		defaultValue: 'closed'
	},
	ping_status: {
		type: Sequelize.STRING( 20 ),
		allowNull: false,
		defaultValue: 'closed'
	},
	post_password: {
		type: Sequelize.STRING,
		allowNull: false,
		defaultValue: ''
	},
	post_name: {
		type: Sequelize.STRING( 200 ),
		allowNull: false,
		defaultValue: ''
	},
	to_ping: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false,
		defaultValue: ''
	},
	pinged: {
		type: Sequelize.TEXT( 'tiny' ),
		allowNull: false,
		defaultValue: ''
	},
	post_modified : {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_modified_gmt: {
		type: Sequelize.DATE,
		allowNull: false,
		defaultValue: Sequelize.NOW
	},
	post_content_filtered: {
		type: Sequelize.TEXT,
		allowNull: false,
		defaultValue: ''
	},
	post_parent: {
		type: Sequelize.BIGINT( 20 ),
		allowNull: false,
		defaultValue: 0
	},
	guid: {
		type: Sequelize.STRING,
		allowNull: false,
		defaultValue: ''
	},
	menu_order: {
		type: Sequelize.INTEGER,
		allowNull: false,
		defaultValue: 0
	},
	post_type: {
		type: Sequelize.STRING(20),
		allowNull: false,
		defaultValue: 'shop_order'
	},
	post_mime_type: {
		type: Sequelize.STRING(100),
		allowNull: false,
		defaultValue: ''
	},
	comment_count: {
		type: Sequelize.BIGINT( 20 ),
		allowNull: false,
		defaultValue: 0
	}
}, {
  	sequelize,
  	modelName: 'wp_posts'
});

db.OrderMeta = OrderMeta.init({
  	meta_id: {
    	type: Sequelize.BIGINT( 20 ),
    	primaryKey: true
  	},
  	post_id: {
    	type: Sequelize.BIGINT( 20 ),
  	},
  	meta_key: {
    	type: Sequelize.STRING,
    	allowNull: false,
  	},
  	meta_value: {
    	type: Sequelize.STRING
  	}
}, {
  	sequelize,
  	modelName: 'wp_postmeta'
});

db.OrderLineItem = OrderLineItem.init({
  	order_item_id: {
    	type: Sequelize.BIGINT( 20 ),
    	primaryKey: true
  	},
  	order_item_name: {
    	type: Sequelize.TEXT( 'tiny' ),
    	allowNull: false
  	},
  	order_item_type: {
    	type: Sequelize.STRING( 200 ),
    	allowNull: false,
    	defaultValue: 'line_item'
  	},
  	order_id: {
    	type: Sequelize.BIGINT( 20 ),
    	allowNull: false,
  	}
}, {
  	sequelize,
  	modelName: 'wp_woocommerce_order_items'
});

db.OrderLineItemMeta = OrderLineItemMeta.init({
  	meta_id: {
    	type: Sequelize.BIGINT( 20 ),
    	primaryKey: true
  	},
  	order_item_id: {
    	type: Sequelize.BIGINT( 20 ),
  	},
  	meta_key: {
    	type: Sequelize.STRING,
    	allowNull: false,
  	},
  	meta_value: {
    	type: Sequelize.TEXT
  	}
}, {
  	sequelize,
  	modelName: 'wp_woocommerce_order_itemmeta'
});

db.ProductPrice.belongsTo(db.Product);
db.Product.hasOne(db.ProductPrice);
db.Product.hasMany(db.Order);
db.Order.belongsTo(db.Product);
db.Order.hasMany(db.OrderLineItem);
db.OrderLineItem.belongsTo(db.Order);
db.OrderLineItem.hasMany(db.OrderLineItemMeta);
db.OrderLineItemMeta.belongsTo(db.OrderLineItem);

db.sequelize = sequelize;

export default db;

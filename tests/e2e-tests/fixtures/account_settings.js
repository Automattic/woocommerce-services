const AccountWithNoCreditCard = {
	"success": true,
	"storeOptions": {
		"currency_symbol": "$",
		"dimension_unit": "cm",
		"weight_unit": "kg",
		"origin_country": "US"
	},
	"formData": {
		"selected_payment_method_id": 0,
		"enabled": true,
		"email_receipts": true,
		"paper_size": "legal"
	},
	"formMeta": {
		"can_manage_payments": true,
		"can_edit_settings": true,
		"master_user_name": "johndoe",
		"master_user_login": "johndoe",
		"master_user_wpcom_login": "johndoe",
		"master_user_email": "john.doe@automattic.com",
		"payment_methods": [],
		"warnings": {
			"payment_methods": false
		}
	},
	"userMeta": {
		"last_box_id": "Box"
	}
}

const VisaCard5959 = {
	"payment_method_id": 9273191,
	"name": "John Doe",
	"card_type": "visa",
	"card_digits": "5959",
	"expiry": "2030-06-10"
}

const Mastercard2862 = {
	"payment_method_id": 6717123,
	"name": "Jane Smith",
	"card_type": "mastercard",
	"card_digits": "2862",
	"expiry": "2025-12-31"
}

const AccountWithOneCreditCard = JSON.parse(JSON.stringify(AccountWithNoCreditCard));  //parse-stringify for deep cloning
AccountWithOneCreditCard.formData.selected_payment_method_id = 9273191;
AccountWithOneCreditCard.formMeta.payment_methods.push(VisaCard5959);

const AccountWithTwoCreditCard = JSON.parse(JSON.stringify(AccountWithOneCreditCard));
AccountWithTwoCreditCard.formData.selected_payment_method_id = 6717123; // set default card
AccountWithTwoCreditCard.formMeta.payment_methods.push(Mastercard2862);

const AccountWithTwoCreditCardAndNoDefault = JSON.parse(JSON.stringify(AccountWithNoCreditCard));
AccountWithTwoCreditCardAndNoDefault.formMeta.payment_methods.push(VisaCard5959, Mastercard2862);

module.exports = {
	AccountWithNoCreditCard,
	AccountWithOneCreditCard,
	AccountWithTwoCreditCard,
	AccountWithTwoCreditCardAndNoDefault
};

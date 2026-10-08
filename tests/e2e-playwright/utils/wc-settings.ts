import { expect, type Page } from '@playwright/test';

// Locale-proof handles for the shared WooCommerce settings chrome. The visible
// text ("Save changes", "Your settings have been saved") is localized, so match
// on the structural markup WC core emits instead: the save button carries
// `.woocommerce-save-button` / `name="save"`. Use these in every settings spec
// rather than getByRole/getByText on the English strings.
export const saveButton = ( page: Page ) =>
	page
		.locator( 'button.woocommerce-save-button, button[name="save"]' )
		.first();

// WC_Admin_Settings::show_messages() emits `<div id="message" class="updated
// inline">` on success and `<div id="message" class="error inline">` on
// failure, so `#message.updated` is both locale-proof and specific to a
// SUCCESSFUL save. Do not widen this to `.notice-success`: wp-admin screens
// routinely carry unrelated success notices (and they render above the
// settings form), so the assertion would pass while the save actually errored.
export const expectSettingsSaved = async ( page: Page ) => {
	await expect( page.locator( '#message.updated' ).first() ).toBeVisible();
};

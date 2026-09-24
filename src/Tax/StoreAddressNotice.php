<?php
/**
 * Shows the result of the store address check to store managers.
 *
 * Reads what StoreAddressVerifier stored for the current store address and shows a notice
 * on WooCommerce admin screens when something needs the merchant's attention. When TaxJar
 * places the store in a different state or ZIP, the merchant can apply that with one click. Nothing changes
 * the store address without that click.
 *
 * Dismissing a notice hides it until the store address changes.
 *
 * @internal Not part of the public API; may change without notice.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\Tax;

defined( 'ABSPATH' ) || exit;

/**
 * Admin notice for the store address check.
 */
final class StoreAddressNotice {

	/**
	 * Admin-post action that applies the suggested state and ZIP.
	 */
	const APPLY_ACTION = 'wc_connect_apply_store_address';

	/**
	 * Admin-post action that dismisses the notice for the current address.
	 */
	const DISMISS_ACTION = 'wc_connect_dismiss_store_address';

	/**
	 * Verifier holding the stored result.
	 *
	 * @var StoreAddressVerifier
	 */
	private $verifier;

	/**
	 * Constructor.
	 *
	 * @param StoreAddressVerifier $verifier Verifier.
	 */
	public function __construct( StoreAddressVerifier $verifier ) {
		$this->verifier = $verifier;
	}

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'render' ) );
		add_action( 'admin_post_' . self::APPLY_ACTION, array( $this, 'handle_apply' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Print the notice, if one is due on this screen.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! $this->is_woocommerce_screen() ) {
			return;
		}

		$result = $this->verifier->get_current_result();

		if ( null === $result || ! empty( $result['dismissed'] ) ) {
			return;
		}

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ),
			esc_html__( 'Check your store address', 'woocommerce-services' )
		);

		switch ( $result['status'] ) {
			case StoreAddressVerifier::STATUS_ZIP_MISSING:
				$this->print_notice(
					'error',
					esc_html__( 'Your store address has no ZIP code. Automated taxes need it to calculate tax.', 'woocommerce-services' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ),
						esc_html__( 'Add a ZIP code', 'woocommerce-services' )
					),
					false
				);
				break;

			case StoreAddressVerifier::STATUS_NOT_FOUND:
				$this->print_notice(
					'error',
					esc_html__( 'We couldn\'t find your store address. Automated taxes use it to calculate tax on every order, so a wrong address means wrong tax.', 'woocommerce-services' ),
					$settings_link,
					true
				);
				break;

			case StoreAddressVerifier::STATUS_AMBIGUOUS:
				$this->print_notice(
					'warning',
					esc_html__( 'We couldn\'t confirm your store address because it matches more than one place. Make sure the state and ZIP code are right, because automated taxes use them to calculate tax.', 'woocommerce-services' ),
					$settings_link,
					true
				);
				break;

			case StoreAddressVerifier::STATUS_SUGGESTION:
				$this->render_suggestion( $result );
				break;
		}
	}

	/**
	 * Print the notice for a suggested state and ZIP.
	 *
	 * A different state is an error: orders from the store's real state are then taxed as
	 * out-of-state, which usually means no tax at all.
	 *
	 * @param array $result Stored result.
	 */
	private function render_suggestion( array $result ) {
		$suggestion = isset( $result['suggestion'] ) && is_array( $result['suggestion'] ) ? $result['suggestion'] : null;

		if ( null === $suggestion ) {
			return;
		}

		if ( empty( $suggestion['state'] ) || empty( $suggestion['postcode'] ) ) {
			return;
		}

		$state_changed = $suggestion['state'] !== $this->verifier->get_store_address()->state();

		$message = $state_changed
			? esc_html__( 'Your store address may have the wrong state. If it does, orders from your own state may be charged no tax.', 'woocommerce-services' )
			: esc_html__( 'Your store ZIP code may be wrong. Automated taxes use it to find your local tax rate.', 'woocommerce-services' );

		$message .= '<br />' . sprintf(
			/* translators: %s: state code and 5-digit ZIP code, for example "CO 80120". */
			esc_html__( 'Your street address is in %s.', 'woocommerce-services' ),
			'<strong>' . esc_html( $suggestion['state'] . ' ' . $suggestion['postcode'] ) . '</strong>'
		);

		if ( $this->can_apply() ) {
			$actions = sprintf(
				'<a class="button button-primary" href="%s">%s</a> <a class="button" href="%s">%s</a>',
				esc_url( $this->action_url( self::APPLY_ACTION ) ),
				$state_changed ? esc_html__( 'Update state and ZIP code', 'woocommerce-services' ) : esc_html__( 'Update ZIP code', 'woocommerce-services' ),
				esc_url( $this->action_url( self::DISMISS_ACTION ) ),
				esc_html__( 'Keep my address', 'woocommerce-services' )
			);
		} else {
			// The address is supplied by code, so updating the settings would not change it.
			$actions = esc_html__( 'Your store address is set by custom code on this site, so update it there.', 'woocommerce-services' );
		}

		$this->print_notice( $state_changed ? 'error' : 'warning', $message, $actions, true );
	}

	/**
	 * Apply the suggested state and ZIP, then return to the page the merchant came from.
	 */
	public function handle_apply() {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( wp_verify_nonce( $nonce, self::APPLY_ACTION ) ) {
			$this->apply_suggestion();
		}

		$this->redirect_back();
	}

	/**
	 * Dismiss the notice, then return to the page the merchant came from.
	 */
	public function handle_dismiss() {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( wp_verify_nonce( $nonce, self::DISMISS_ACTION ) && current_user_can( 'manage_woocommerce' ) ) {
			$this->verifier->dismiss();
		}

		$this->redirect_back();
	}

	/**
	 * Write the suggested state and ZIP to the store settings.
	 *
	 * Only those two change: they are what tax depends on, and the rest of the store address
	 * stays as the merchant wrote it. The nonce is checked by the caller. This checks everything else, and does nothing
	 * unless all of it holds: the user may manage the store, the suggestion is for the
	 * address that is saved now, and the saved settings are what tax is calculated from.
	 *
	 * @return bool True when the address was updated.
	 */
	public function apply_suggestion() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! $this->can_apply() ) {
			return false;
		}

		$result = $this->verifier->get_current_result();

		if ( null === $result || StoreAddressVerifier::STATUS_SUGGESTION !== $result['status'] || ! is_array( $result['suggestion'] ) ) {
			return false;
		}

		$suggestion = $result['suggestion'];

		if ( empty( $suggestion['state'] ) || empty( $suggestion['postcode'] ) ) {
			return false;
		}

		update_option( 'woocommerce_default_country', 'US:' . $suggestion['state'] );
		update_option( 'woocommerce_store_postcode', $suggestion['postcode'] );

		$this->verifier->mark_verified();

		return true;
	}

	/**
	 * Is the saved store address the one tax is calculated from?
	 *
	 * A `taxjar_store_settings` filter can replace it. Then writing the suggestion to the
	 * settings would change nothing, so the notice doesn't offer it.
	 *
	 * @return bool
	 */
	private function can_apply() {
		$country_state = wc_format_country_state_string( (string) get_option( 'woocommerce_default_country', '' ) );

		$saved = Address::from_store_settings(
			array(
				'country'  => $country_state['country'],
				'state'    => $country_state['state'],
				'postcode' => (string) get_option( 'woocommerce_store_postcode', '' ),
				'city'     => (string) get_option( 'woocommerce_store_city', '' ),
				'street'   => (string) get_option( 'woocommerce_store_address', '' ),
			)
		);

		return StoreAddressVerifier::hash_address( $saved ) === StoreAddressVerifier::hash_address( $this->verifier->get_store_address() );
	}

	/**
	 * Is the current admin screen a WooCommerce one?
	 *
	 * @return bool
	 */
	private function is_woocommerce_screen() {
		if ( ! function_exists( 'get_current_screen' ) || ! function_exists( 'wc_get_screen_ids' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return null !== $screen && in_array( $screen->id, wc_get_screen_ids(), true );
	}

	/**
	 * Nonced admin-post URL for an action.
	 *
	 * @param string $action Action name.
	 * @return string
	 */
	private function action_url( $action ) {
		return wp_nonce_url( add_query_arg( 'action', $action, admin_url( 'admin-post.php' ) ), $action );
	}

	/**
	 * Print a notice.
	 *
	 * @param string $type        notice-{type}: error or warning.
	 * @param string $message     Escaped message HTML.
	 * @param string $actions     Escaped HTML shown under the message.
	 * @param bool   $dismissible Whether to show a dismiss link.
	 */
	private function print_notice( $type, $message, $actions, $dismissible ) {
		$allowed_html = array(
			'a'      => array(
				'href'  => array(),
				'class' => array(),
			),
			'strong' => array(),
			'br'     => array(),
		);
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> wc-connect-store-address-notice" style="position: relative;">
			<?php if ( $dismissible ) : ?>
				<a href="<?php echo esc_url( $this->action_url( self::DISMISS_ACTION ) ); ?>" style="text-decoration: none;" class="notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce-services' ); ?>"></a>
			<?php endif; ?>
			<p><?php echo wp_kses( $message, $allowed_html ); ?></p>
			<p><?php echo wp_kses( $actions, $allowed_html ); ?></p>
		</div>
		<?php
	}

	/**
	 * Return to the referring admin page, or the General settings.
	 */
	private function redirect_back() {
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-settings&tab=general' ) );
		exit;
	}
}

/** @format */

/**
 * External dependencies
 */

import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { Button } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import ExtendedHeader from 'woocommerce/components/extended-header';
import FeatureAnnouncement from 'components/migration/feature-announcement';
import './style.scss';

class MigratorSettingsRootView extends Component {
	state = {
		isMigrationNoticeVisible: false,
		isMigrationNoticeDismissed: false,
	};

	componentWillMount() {
		const migrationCookie = window.wpCookies.get( 'wcst-wcshipping-migration-dismissed' );
		this.setState({ isMigrationNoticeDismissed: migrationCookie && parseInt( migrationCookie ) });
	}

	componentWillUnmount() {
		this.props.restorePristineSettings(this.props.siteId);
	}

	addPackage = () => {
		this.props.onChange();
		this.props.addPackage(siteId);
	};

	showMigrationNotice = () => {
		if ( window.wcTracks ) {
			window.wcTracks.recordEvent( 'woocommerceconnect_migration_started_from_settings' );
		}
		window.wpCookies.remove( 'wcst-wcshipping-migration-dismissed' );
		this.setState({ isMigrationNoticeVisible: true });
	}

	onFeatureAnnouncementClosed = () => {
		this.setState({ isMigrationNoticeVisible: false });
	}

	render() {
		const {
			translate,
		} = this.props;

		if ( ! this.state.isMigrationNoticeDismissed ) {
			return null
		}

		return (
			<div className="woocommerce-migrator-settings-root-view">
				<ExtendedHeader
					label={translate('Migration to WooCommerce Shipping')}
					description={translate(
						'You can now migrate to WooCommerce Shipping. This will provide you with a dedicated WooCommerce Shipping extension, which will carry over all your settings and shipping labels when you update.'
					)}
				>
					<Button
						className={classNames('button')}
						onClick={ this.showMigrationNotice }
					>{translate('Start migration process')}</Button>
				</ExtendedHeader>
				{ this.state.isMigrationNoticeVisible && (
					<FeatureAnnouncement onClose={ this.onFeatureAnnouncementClosed }/>
				) }
			</div>
		);
	}
}

MigratorSettingsRootView.propTypes = {};

function mapStateToProps() {
	return {};
}

function mapDispatchToProps( dispatch ) {
	return bindActionCreators( {}, dispatch );
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( MigratorSettingsRootView ) );

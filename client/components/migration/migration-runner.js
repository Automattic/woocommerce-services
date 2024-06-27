/**
 * External dependencies
 */
import { translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { getNonce, getBaseURL } from 'wcs-client/api/request';

// These are used by the "wc/v1/connect/migration-flag" API call. The parameter
// needs to map to the PHP's enum WC_Connect_WCST_To_WCShipping_Migration_State_Enum.
const MIGRATION_STATE_ENUM = {
    NOT_STARTED: 1,
    STARTED: 2,
    ERROR_STARTED: 3,
    INSTALLING: 4,
    ERROR_INSTALLING: 5,
    ACTIVATING: 6,
    ERROR_ACTIVATING: 7,
    DB_MIGRATION: 8,
    ERROR_DB_MIGRATION: 9,
    DEACTIVATING: 10,
    ERROR_DEACTIVATING: 11,
    COMPLETED: 12,
}

// MIGRATION_ENUM_TO_STATE_NAME_MAP maps the PHP's WC_Connect_WCST_To_WCShipping_Migration_State_Enum
// to our state machine's state names. These are one-to-one mapping.
// These state names are the key in the migrationStateTransitions object below.
// Ref: Check state diagram in https://github.com/Automattic/woocommerce-services/pull/2757.
const MIGRATION_ENUM_TO_STATE_NAME_MAP = {
    2: 'stateInit',
    3: 'stateErrorInit',
    4: 'stateInstalling',
    5: 'stateErrorInstalling',
    6: 'stateActivating',
    7: 'stateErrorActivating',
    8: 'stateDB',
    9: 'stateErrorDB',
    10: 'stateDeactivating',
    11: 'stateErrorDeactivating',
    12: 'stateDone'
}

const plugins = 'woocommerce-shipping,woocommerce-tax'; //this needs to be a CSV string.
const headers = {
    "Content-Type": "application/json",
    'X-WP-Nonce': getNonce()
};

const installPluginAPICall = () =>
    fetch( getBaseURL() + 'wc-admin/plugins/install', {
        method: 'POST',
        headers,
        body: JSON.stringify({
            plugins
        })
    } );

const activatePluginAPICall = () =>
    fetch( getBaseURL() + 'wc-admin/plugins/activate', {
        method: 'POST',
        headers,
        body: JSON.stringify({
            plugins
        })
    } );

const deactivateWCSTPluginAPICall = () =>
    fetch( getBaseURL() + 'wp/v2/plugins/woocommerce-services/woocommerce-services', {
        method: 'POST',
        headers,
        body: JSON.stringify({
            status: 'inactive'
        })
    } );

/**
 * Note: this function can not be called under these conditions:
 * 1. WCS&T, Woo Shipping, Woo Tax are all activated. WCS&T will not load the migration-flag controller, returning 404.
 * 2. WCS&T is deactivated. The migration-flag controller is not loaded, this will return 404.
 *
 * @param {string} migrationState The numeric migration number from the PHP side. Check classes/class-wc-connect-wcst-to-wcshipping-migration-state-enum.php.
 * @returns {Promise} Check fetch.
 */
const markMigrationStartedAPICall = ( migrationState ) => () =>
    fetch( getBaseURL() + 'wc/v1/connect/migration-flag', {
        method: 'POST',
        headers,
        body: JSON.stringify({
            migration_state: migrationState
        })
    } );

const stateErrorHandlingAPICall = ( failedMigrationState ) => () => {
    // TODO: The error message doesn't show up anywhere. For now, update the flag in the option table so
    // we know where it failed at.

    return markMigrationStartedAPICall( failedMigrationState )();
};

/**
 * A wrapper function to make API calls and check the API status response.
 * Throw an exception up if the status > 400.
 *
 * @param {function} apiFn The API function that uses fetch.
 * @returns {Object} The API JSON response from fetch.
 */
const fetchAPICall = ( apiFn ) => async () => {
    const apiResponse = await apiFn();
    const apiJSONResponse = await apiResponse.json();
    if ( apiResponse.status >= 400 ) {
        throw new Error( apiJSONResponse.message || translate( "Failed to setup WooCommerce Shipping. Please try again." ) );
    }
    return apiJSONResponse;
};

/**
 * This is the implementation of the state transition diagram. Please check https://github.com/Automattic/woocommerce-services/pull/2757.
 * The key of this object can be found in the MIGRATION_ENUM_TO_STATE_NAME_MAP above.
 *
 * The value is an {object} with these properties:
 *   success: {string|null} This is the state to go to "next". We can only go to this state when callback is successful.
 *   fail: {string|null} This is the state to go to when callback fails. All failed state immediately halt the machine, check diagram.
 *   callback: {function|null} This is the function to run when the machine is on this state. It can make API call.
 */
const migrationStateTransitions = {
    stateInit: {
        success: 'stateInstalling',
        fail: 'stateInit',
        callback: fetchAPICall( markMigrationStartedAPICall( MIGRATION_STATE_ENUM.STARTED ) ),
    },
    stateErrorInit: {
        success: 'stateInit',
        fail: 'stateErrorInit',
        callback: stateErrorHandlingAPICall( MIGRATION_STATE_ENUM.ERROR_STARTED ),
    },
    stateInstalling: {
        success: 'stateActivating',
        fail: 'stateErrorInstalling',
        callback: fetchAPICall( installPluginAPICall ),
    },
    stateErrorInstalling: {
        success: 'stateInstalling',
        fail: 'stateErrorInstalling',
        callback: stateErrorHandlingAPICall( MIGRATION_STATE_ENUM.ERROR_INSTALLING ),
    },
    stateActivating: {
        success: 'stateDeactivating', // TODO: This should be stateDBMigrating to migrate DB.
        fail: 'stateErrorActivating',
        callback: fetchAPICall( activatePluginAPICall ),
    },
    stateErrorActivating: {
        success: 'stateActivating',
        fail: 'stateErrorActivating',
        callback: stateErrorHandlingAPICall( MIGRATION_STATE_ENUM.ERROR_ACTIVATING ),
    },
    stateDB: {
        success: 'stateDeactivating',
        fail: 'stateErrorDB',
        callback: new Promise( ( resolve ) => resolve( { status: 200 } ) ), // TODO: DB migration
    },
    stateErrorDB: {
        success: 'stateDBMigrating',
        fail: 'stateErrorDB',
        callback: stateErrorHandlingAPICall( MIGRATION_STATE_ENUM.ERROR_DB_MIGRATION ),
    },
    stateDeactivating: {
        success: 'stateDone',
        fail: 'stateErrorDeactivating',
        callback: fetchAPICall( deactivateWCSTPluginAPICall ),
    },
    stateErrorDeactivating: {
        success: 'stateDeactivating',
        fail: 'stateErrorDeactivating',
        callback: stateErrorHandlingAPICall( MIGRATION_STATE_ENUM.ERROR_DEACTIVATING ),
    },
    stateDone: { // Done state.
        success: null,
        fail: null,
        callback: null
    }
}

// Any of the state that halts the machine. Including "done" or any errors.
const stopStates = [ 'stateDone', 'stateErrorInstalling', 'stateErrorActivating', 'stateErrorDB', 'stateErrorDeactivating' ];

/**
 * This function runs the entire migration based on what the previousMigrationState is. It will stop
 * when it lands on an "error state" or when the migration is completed.
 *
 * @param {number} previousMigrationState This is the "wcshipping_migration_state" stored in the option table. This is the last state recorded for this migration before it stopped.
 */
const installAndActivatePlugins = async( previousMigrationState ) => {
    const runNext = async ( migrationState ) => {
        if ( !migrationState ) {
            // Nothing to run, do nothing and return.
            return;
        }

        const currentMigrationState = migrationStateTransitions[ migrationState ];
        let nextMigrationStateToRun = '';
        try {
            await currentMigrationState.callback();
            nextMigrationStateToRun = currentMigrationState.success;
        } catch (e) {
            nextMigrationStateToRun = currentMigrationState.fail;
        } finally {
            return nextMigrationStateToRun;
        }
    };

    /**
     * This function checks what the next state to run is. If there is no record of any previous migration run,
     * then we will start from the beginning.
     * If there is a record about where we last stopped at, then we start from that state's next state.
     *
     * @returns {string} The next state to run. The name of the state is the key in this object migrationStateTransitions.
     */
    const getNextStateToRun = () => {
        if ( ! previousMigrationState ) {
            // stateInit
            return MIGRATION_ENUM_TO_STATE_NAME_MAP[ 2 ];
        }

        const currentStateName = MIGRATION_ENUM_TO_STATE_NAME_MAP[ previousMigrationState ];
        return migrationStateTransitions[ currentStateName ].success; // The next state is "success".
    };

    // Run the migration chain from where it last stopped. If there is no record, start from the beginning.
    let nextMigrationStateToRun = getNextStateToRun();
    let runAttemps = 0;
    const maxAttempts = Object.keys( migrationStateTransitions ).length; // The states don't loop, thus it can't be more than its size. Serve as a safe guard in case of infinite loop.
    while ( runAttemps++ < maxAttempts ) {
        nextMigrationStateToRun = await runNext( nextMigrationStateToRun );
        if ( stopStates.includes( nextMigrationStateToRun ) ) {
            // If this is the end of any of the error states, run the callback once more and then quit.
            // This gives the state a chance to run its job before the machine quits.
            await runNext( nextMigrationStateToRun );
            break;
        }
    }

    //Redirect to plugins page regardless of migration success. If there was an error, the best place to check is the plugins page.
    window.location = global.wcsPluginData.adminPluginPath;
};

export {
    installAndActivatePlugins
}
/**
 * External dependencies
 */
import { translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { getNonce, getBaseURL } from 'api/request'; // client/api/request.js

// Check WC_Connect_WCST_To_WCShipping_Migration_State_Enum
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
// Maps the numeric state from PHP to the more descriptive installAndActivatePlugins object we use below.
const MIGRATION_STATE_NAME = {
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
const markMigrationStartedAPICall = (migrationState) => () =>
    fetch( getBaseURL() + 'wc/v1/connect/migration-flag', {
        method: 'POST',
        headers,
        body: JSON.stringify({
            migration_state: migrationState
        })
    } );

const stateErrorHandlingAPICall = (failedMigrationState) => () => {
    // TODO: The error message doesn't show up anywhere. For now, update the flag in the option table so
    // we know where it failed at.

    return markMigrationStartedAPICall(failedMigrationState)();
};

/**
 * A wrapper function to make API calls and check the API status response.
 * Throw an exception up if the status > 400.
 *
 * @param {function} apiFn The API function that uses fetch.
 * @returns {Object} The API JSON response from fetch.
 */
const fetchAPICall = (apiFn) => async () => {
    const apiResponse = await apiFn();
    const apiJSONResponse = await apiResponse.json();
    if (apiResponse.status >= 400) {
        throw new Error(apiJSONResponse.message || translate("Failed to setup WooCommerce Shipping. Please try again."));
    }
    return apiJSONResponse;
};

const migrationStateTransitions = {
    stateInit: {
        success: 'stateInstalling',
        fail: 'stateInit',
        callback: fetchAPICall(markMigrationStartedAPICall(MIGRATION_STATE_ENUM.STARTED)),
    },
    stateErrorInit: {
        success: 'stateInit',
        fail: 'stateErrorInit',
        callback: stateErrorHandlingAPICall(MIGRATION_STATE_ENUM.ERROR_STARTED),
    },
    stateInstalling: {
        success: 'stateActivating',
        fail: 'stateErrorInstalling',
        callback: fetchAPICall(installPluginAPICall),
    },
    stateErrorInstalling: {
        success: 'stateInstalling',
        fail: 'stateErrorInstalling',
        callback: stateErrorHandlingAPICall(MIGRATION_STATE_ENUM.ERROR_INSTALLING),
    },
    stateActivating: {
        success: 'stateDeactivating', // TODO: This should be stateDBMigrating to migrate DB.
        fail: 'stateErrorActivating',
        callback: fetchAPICall(activatePluginAPICall),
    },
    stateErrorActivating: {
        success: 'stateActivating',
        fail: 'stateErrorActivating',
        callback: stateErrorHandlingAPICall(MIGRATION_STATE_ENUM.ERROR_ACTIVATING),
    },
    stateDB: {
        success: 'stateDeactivating',
        fail: 'stateErrorDB',
        callback: new Promise((resolve) => resolve({status: 200})), // TODO: DB migration
    },
    stateErrorDB: {
        success: 'stateDBMigrating',
        fail: 'stateErrorDB',
        callback: stateErrorHandlingAPICall(MIGRATION_STATE_ENUM.ERROR_DB_MIGRATION),
    },
    stateDeactivating: {
        success: 'stateDone',
        fail: 'stateErrorDeactivating',
        callback: fetchAPICall(deactivateWCSTPluginAPICall),
    },
    stateErrorDeactivating: {
        success: 'stateDeactivating',
        fail: 'stateErrorDeactivating',
        callback: stateErrorHandlingAPICall(MIGRATION_STATE_ENUM.ERROR_DEACTIVATING),
    },
    stateDone: { // Done state.
        success: null,
        fail: null,
        callback: null
    }
}

// Any of the state that halts the machine. Including "done" or any errors.
const stopStates = ['stateDone', 'stateErrorInstalling', 'stateErrorActivating', 'stateErrorDB', 'stateErrorDeactivating'];

const installAndActivatePlugins = async(previousMigrationState) => {
    const runNext = async (migrationState) => {
        if (!migrationState) {
            // Nothing to run, do nothing and return.
            return;
        }

        const currentMigrationState = migrationStateTransitions[migrationState];
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
     * This function checks what the next state to run is. If there is no record of any migration run, then we start from the beginning.
     * If there is a record of where it was stuck at, then we start from its next state.
     *
     * @returns {string} The next state to run. The name of the state is the key in this object migrationStateTransitions.
     */
    const getNextStateToRun = () => {
        if ( ! previousMigrationState) {
            // stateInit
            return MIGRATION_STATE_NAME[2];
        }

        const currentStateName = MIGRATION_STATE_NAME[previousMigrationState];
        return migrationStateTransitions[currentStateName].success;
    };

    // Run the migration chain from where it last stopped. If there is no record, start from the beginning.
    let nextMigrationStateToRun = getNextStateToRun();
    let runAttemps = 0;
    const maxAttempts = Object.keys(migrationStateTransitions).length; // The states don't loop, thus it can't be more than its size. Serve as a safe guard in case of infinite loop.
    while ( runAttemps++ < maxAttempts ) {
        nextMigrationStateToRun = await runNext(nextMigrationStateToRun);
        if (stopStates.includes(nextMigrationStateToRun)) {
            // If this is the end of any of the error states, run the callback once more and then quit.
            // This gives the state a chance to run its job before the machine quits.
            await runNext(nextMigrationStateToRun);
            break;
        }
    }

    //Redirect to plugins page regardless of migration success. If there was an error, the best place to check is the plugins page.
    // window.location = global.wcsPluginData.adminPluginPath;
};

export {
    installAndActivatePlugins
}
import type { Page } from '@playwright/test';
import { loginToWpAdmin } from './wp-login';

interface QitGlobal {
	loginAsAdmin?: ( page: Page ) => Promise< void >;
}

const getQitGlobal = (): QitGlobal | undefined => {
	const maybeGlobal = globalThis as unknown as { qit?: QitGlobal };
	return maybeGlobal.qit;
};

export const loginAsAdminWithQitFallback = async ( page: Page ) => {
	const qit = getQitGlobal();

	if ( qit?.loginAsAdmin ) {
		try {
			await qit.loginAsAdmin( page );
			return;
		} catch ( error ) {
			// Fall back to the local login helper, but surface WHY the QIT helper
			// failed - otherwise a later fallback failure ("could not find login
			// form fields") hides the real root cause.
			// eslint-disable-next-line no-console
			console.warn(
				'qit.loginAsAdmin failed; falling back to form login:',
				error
			);
		}
	}

	await loginToWpAdmin( page );
};

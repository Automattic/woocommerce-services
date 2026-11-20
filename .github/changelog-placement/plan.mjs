// Vendored from woocommerce/escargot-dev-environment tools/changelog-sweep/plan.mjs at c8355f6ac5629d8f2d2f62d4a9641085abc411b3.
// Do not edit here; change the source and copy it again (see .github/workflows/changelog-placement.yml).
/**
 * Decides what to write on a PR - commit status, comment, label - from the
 * check's outcome and what is already on the PR.
 *
 * No I/O here on purpose, like detect.mjs: the clear-on-fix and re-notify paths
 * are the parts most likely to rot unnoticed, so they are pure and tested.
 *
 * An outcome is one of:
 *   { kind: 'ok' }                      no entry lands in a released section
 *   { kind: 'amend' }                   only rewords or removes released entries
 *   { kind: 'override' }                misplaced, but the author declared it
 *   { kind: 'misplaced', findings }     findings: [ { file, version, entries } ]
 *   { kind: 'error', message }          the PR could not be checked
 */

export const MARKER = '<!-- escargot:changelog-sweep -->';
// Deliberately not a superstring of MARKER, so the two never match each other.
export const RESOLVED_MARKER = '<!-- escargot:changelog-sweep:resolved -->';
export const LABEL = 'changelog-misplaced';
export const LABEL_COLOR = 'd93f0b';
export const LABEL_DESCRIPTION = 'Changelog entry lands in an already-released section';
export const STATUS_CONTEXT = 'changelog/placement';

// GitHub rejects a commit status description longer than 140 characters.
const clip = ( s ) => ( s.length > 140 ? `${ s.slice( 0, 139 ) }…` : s );

export function statusFor( outcome ) {
	switch ( outcome.kind ) {
		case 'misplaced':
			return {
				state: 'failure',
				description: clip( `Entry lands in released ${ outcome.findings[ 0 ].version }. Move it to a new unreleased section.` ),
			};
		case 'override':
			return { state: 'success', description: 'Amendment to a released section declared (changelog-amend).' };
		case 'amend':
			return { state: 'success', description: 'Only rewords or removes entries in a released section.' };
		case 'ok':
			return { state: 'success', description: 'No changelog entries in released sections.' };
		default:
			return { state: 'error', description: clip( `Could not check: ${ outcome.message || 'unknown error' }` ) };
	}
}

export const isResolved = ( comment ) => Boolean( comment?.body?.includes( RESOLVED_MARKER ) );

export function flaggedBody( findings ) {
	const lines = [
		MARKER,
		'### Changelog entry is in a released section',
		'',
		'A release has since dated the section this PR adds to, so merging would edit a **published** changelog. Nothing else flags this - the PR itself still reads as unreleased, and GitHub reports it mergeable.',
		'',
	];
	for ( const f of findings ) {
		lines.push( `**\`${ f.file }\`** — entries landing in the shipped \`${ f.version }\`:` );
		lines.push( '' );
		for ( const e of f.entries ) lines.push( `> ${ e.trim() }` );
		lines.push( '' );
	}
	lines.push(
		'**To fix:** merge the base branch, then move these entries into a new unreleased section at the top (next patch version, placeholder date). Leave `Version:`, `*_VERSION`, `package.json` and `Stable tag:` alone — woorelease sets those at release time.',
		'',
		'**Deliberately amending a released section?** Add the `changelog-amend` label, or put `[changelog-amend]` in the PR title or body, and this check will stand down.',
		'',
		'<sub>Posted by the Escargot changelog placement check. This comment updates itself when the PR is fixed. Advisory only — it does not block merge.</sub>'
	);
	return lines.join( '\n' );
}

const RESOLVED_REASON = {
	ok: 'no entry from this PR lands in a released section any more',
	amend: 'this PR now only rewords or removes entries in a released section',
	override: 'a deliberate amendment is declared (`changelog-amend`)',
};

export function resolvedBody( previousBody, outcome, headSha ) {
	const original = previousBody.replace( MARKER, '' ).trim();
	return [
		MARKER,
		RESOLVED_MARKER,
		'### Changelog entry is no longer in a released section',
		'',
		`Resolved at \`${ String( headSha ).slice( 0, 7 ) }\`: ${ RESOLVED_REASON[ outcome.kind ] }.`,
		'',
		'<details><summary>Original report</summary>',
		'',
		original,
		'',
		'</details>',
	].join( '\n' );
}

/**
 * @param {object}   args
 * @param {object}   args.outcome  see the header
 * @param {string[]} args.labels   label names currently on the PR
 * @param {Array}    args.comments the PR's comments, oldest first ({ id, body })
 * @param {string}   args.headSha  the PR head the verdict is for
 * @returns {{ status: {state, description}, comment: null|{op:'create'|'update', id?, body}, label: null|'add'|'remove' }}
 */
export function planPr( { outcome, labels = [], comments = [], headSha } ) {
	const status = statusFor( outcome );

	// A PR that could not be checked keeps whatever it had: clearing a flag on
	// an API error would read as "fixed".
	if ( outcome.kind === 'error' ) {
		return { status, comment: null, label: null };
	}

	const flagged = outcome.kind === 'misplaced';
	const hasLabel = labels.includes( LABEL );
	const latest = [ ...comments ].reverse().find( ( c ) => c.body?.includes( MARKER ) ) || null;

	let comment = null;
	if ( flagged ) {
		const body = flaggedBody( outcome.findings );
		// A fresh comment, not an edit, when the PR breaks again after a fix:
		// editing a comment notifies nobody.
		if ( ! latest || isResolved( latest ) ) comment = { op: 'create', body };
		else if ( latest.body !== body ) comment = { op: 'update', id: latest.id, body };
	} else if ( latest && ! isResolved( latest ) ) {
		comment = { op: 'update', id: latest.id, body: resolvedBody( latest.body, outcome, headSha ) };
	}

	let label = null;
	if ( flagged && ! hasLabel ) label = 'add';
	else if ( ! flagged && hasLabel ) label = 'remove';

	return { status, comment, label };
}

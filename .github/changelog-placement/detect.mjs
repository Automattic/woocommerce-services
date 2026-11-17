// Vendored from woocommerce/escargot-dev-environment tools/changelog-sweep/detect.mjs at c8355f6ac5629d8f2d2f62d4a9641085abc411b3.
// Do not edit here; change the source and copy it again (see .github/workflows/changelog-placement.yml).
/**
 * Pure detection logic for misplaced changelog entries.
 *
 * The problem: when woorelease dates the unreleased section, every open PR that
 * put its entry there is now editing a *published* release. Git usually merges
 * that without a conflict, so GitHub reports the PR MERGEABLE/CLEAN either way.
 * Only the merged file reveals it.
 *
 * No I/O here on purpose - everything is a pure function over file contents so
 * it can be tested against real captured fixtures.
 */

/**
 * Section headings used across the Escargot plugins:
 *   "2026-xx-xx - version 4.4.3"      (most plugins)
 *   "= 5.3.6 - 2026-xx-xx ="          (services, shipping, shipstation, box-packer)
 * A trailing "=" is optional in the second form.
 *
 * A section is RELEASED when its date carries no "x" placeholder.
 */
const HEADING_PATTERNS = [
	{
		re: /^(\d{4}|x{4})-(\d{2}|xx)-(\d{2}|xx)\s+-\s+version\s+(\S+)\s*$/i,
		version: ( m ) => m[ 4 ],
		date: ( m ) => `${ m[ 1 ] }-${ m[ 2 ] }-${ m[ 3 ] }`,
	},
	{
		re: /^=\s*(\S+)\s+-\s+(\d{4}|x{4})-(\d{2}|xx)-(\d{2}|xx)\s*=?\s*$/i,
		version: ( m ) => m[ 1 ],
		date: ( m ) => `${ m[ 2 ] }-${ m[ 3 ] }-${ m[ 4 ] }`,
	},
];

export function parseHeading( line ) {
	for ( const p of HEADING_PATTERNS ) {
		const m = line.match( p.re );
		if ( m ) {
			const date = p.date( m );
			return {
				version: p.version( m ),
				date,
				released: ! /x/i.test( date ),
			};
		}
	}
	return null;
}

/** Split a changelog into sections, each carrying the line range it covers. */
export function parseSections( text ) {
	const lines = text.split( '\n' );
	const sections = [];
	lines.forEach( ( line, i ) => {
		const h = parseHeading( line.trim() );
		if ( h ) {
			sections.push( { ...h, heading: line.trim(), start: i, end: lines.length } );
			if ( sections.length > 1 ) {
				sections[ sections.length - 2 ].end = i;
			}
		}
	} );
	return sections;
}

export function sectionAt( sections, lineIndex ) {
	return sections.find( ( s ) => lineIndex > s.start && lineIndex < s.end ) || null;
}

/** Minimal LCS line diff. Changelogs are small; clarity beats cleverness here. */
export function diffLines( aText, bText ) {
	const a = aText.split( '\n' );
	const b = bText.split( '\n' );
	const n = a.length;
	const m = b.length;
	const dp = Array.from( { length: n + 1 }, () => new Uint32Array( m + 1 ) );
	for ( let i = n - 1; i >= 0; i-- ) {
		for ( let j = m - 1; j >= 0; j-- ) {
			dp[ i ][ j ] = a[ i ] === b[ j ] ? dp[ i + 1 ][ j + 1 ] + 1 : Math.max( dp[ i + 1 ][ j ], dp[ i ][ j + 1 ] );
		}
	}
	const added = [];
	const removed = [];
	let i = 0;
	let j = 0;
	while ( i < n && j < m ) {
		if ( a[ i ] === b[ j ] ) {
			i++;
			j++;
		} else if ( dp[ i + 1 ][ j ] >= dp[ i ][ j + 1 ] ) {
			removed.push( { line: i, text: a[ i ] } );
			i++;
		} else {
			added.push( { line: j, text: b[ j ] } );
			j++;
		}
	}
	while ( i < n ) removed.push( { line: i, text: a[ i++ ] } );
	while ( j < m ) added.push( { line: j, text: b[ j++ ] } );
	return { added, removed };
}

const isEntry = ( t ) => /^\s*[*-]\s+\S/.test( t );

/** Token-overlap similarity, used to tell a reworded entry from a brand-new one. */
export function similarity( x, y ) {
	const tok = ( s ) => new Set( s.toLowerCase().replace( /[^a-z0-9 ]+/g, ' ' ).split( /\s+/ ).filter( Boolean ) );
	const A = tok( x );
	const B = tok( y );
	if ( ! A.size || ! B.size ) return 0;
	let inter = 0;
	for ( const t of A ) if ( B.has( t ) ) inter++;
	return inter / ( A.size + B.size - inter );
}

export const OVERRIDE_RE = /\[changelog-amend\]|changelog-amend\s*:/i;

/**
 * Classify what a PR does to a changelog file.
 *
 * @param {string} baseText   changelog on the base branch
 * @param {string} mergedText changelog as it would exist after merging
 * @param {object} [opts]
 * @param {number} [opts.rewordThreshold] similarity above which an add+remove
 *                                        pair in the same released section reads
 *                                        as a deliberate reword rather than a
 *                                        newly misplaced entry.
 * @returns {{verdict:'ok'|'amend'|'misplaced', misplaced:Array, amended:Array}}
 */
export function classify( baseText, mergedText, opts = {} ) {
	const rewordThreshold = opts.rewordThreshold ?? 0.6;
	const baseSections = parseSections( baseText );
	const mergedSections = parseSections( mergedText );
	const { added, removed } = diffLines( baseText, mergedText );

	const removedInReleased = removed
		.map( ( r ) => ( { ...r, section: sectionAt( baseSections, r.line ) } ) )
		.filter( ( r ) => isEntry( r.text ) && r.section?.released );

	const misplaced = [];
	const amended = [];

	for ( const a of added ) {
		if ( ! isEntry( a.text ) ) continue;
		const section = sectionAt( mergedSections, a.line );
		// An entry under an unreleased heading - including a brand new section
		// the PR opened itself - is exactly what we want.
		if ( ! section || ! section.released ) continue;

		const reword = removedInReleased.find(
			( r ) => r.section.version === section.version && similarity( r.text, a.text ) >= rewordThreshold
		);
		if ( reword ) {
			amended.push( { text: a.text, version: section.version, was: reword.text } );
		} else {
			misplaced.push( { text: a.text, version: section.version, heading: section.heading } );
		}
	}

	// Deletions from a shipped section are almost always deliberate (removing a
	// wrong entry, dropping a duplicate). Report them, never fail on them.
	for ( const r of removedInReleased ) {
		if ( amended.some( ( am ) => am.was === r.text ) ) continue;
		amended.push( { text: r.text, version: r.section.version, removedOnly: true } );
	}

	return {
		verdict: misplaced.length ? 'misplaced' : amended.length ? 'amend' : 'ok',
		misplaced,
		amended,
	};
}

/**
 * Classify without needing a merge at all.
 *
 * Asks the more direct question: which version section is this PR adding to,
 * and has that version already shipped on the base branch?
 *
 * This is the primary path because it works on PRs that CONFLICT with the base.
 * GitHub computes no test-merge commit for those - and, importantly, no
 * `refs/pull/N/merge` either, so a per-repo workflow checking out the merge ref
 * is blind to exactly the same PRs. Only the merge base is always available.
 *
 * @param {string} atMergeBase changelog at merge-base(base, head)
 * @param {string} atHead      changelog at the PR head
 * @param {string} currentBase changelog on the base branch as it is now
 */
export function classifyTargeted( atMergeBase, atHead, currentBase, opts = {} ) {
	const rewordThreshold = opts.rewordThreshold ?? 0.6;
	const headSections = parseSections( atHead );
	const mbSections = parseSections( atMergeBase );
	const released = new Map( parseSections( currentBase ).map( ( s ) => [ s.version, s.released ] ) );
	const { added, removed } = diffLines( atMergeBase, atHead );

	// Entries the PR removed, from sections that have since shipped.
	const removedFromReleased = removed
		.map( ( r ) => ( { ...r, section: sectionAt( mbSections, r.line ) } ) )
		.filter( ( r ) => isEntry( r.text ) && r.section && released.get( r.section.version ) === true );

	const misplaced = [];
	const amended = [];

	for ( const a of added ) {
		if ( ! isEntry( a.text ) ) continue;
		const section = sectionAt( headSections, a.line );
		if ( ! section ) continue;
		// A version the base branch does not know about is a new section the PR
		// opened itself - fine by construction.
		if ( released.get( section.version ) !== true ) continue;

		const reword = removedFromReleased.find(
			( r ) => r.section.version === section.version && similarity( r.text, a.text ) >= rewordThreshold
		);
		if ( reword ) amended.push( { text: a.text, version: section.version, was: reword.text } );
		else misplaced.push( { text: a.text, version: section.version, heading: section.heading } );
	}

	for ( const r of removedFromReleased ) {
		if ( amended.some( ( am ) => am.was === r.text ) ) continue;
		amended.push( { text: r.text, version: r.section.version, removedOnly: true } );
	}

	return {
		verdict: misplaced.length ? 'misplaced' : amended.length ? 'amend' : 'ok',
		misplaced,
		amended,
	};
}

/** True when the author has explicitly declared a deliberate amendment. */
export function hasOverride( { title = '', body = '', labels = [] } ) {
	if ( labels.some( ( l ) => String( l ).toLowerCase() === 'changelog-amend' ) ) return true;
	return OVERRIDE_RE.test( title ) || OVERRIDE_RE.test( body );
}

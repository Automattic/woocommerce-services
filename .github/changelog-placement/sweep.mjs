// Vendored from woocommerce/escargot-dev-environment tools/changelog-sweep/sweep.mjs at c8355f6ac5629d8f2d2f62d4a9641085abc411b3.
// Do not edit here; change the source and copy it again (see .github/workflows/changelog-placement.yml).
/**
 * Finds open PRs whose changelog entry would land inside an already-released
 * section, and - with --write - says so on the PR.
 *
 * The defect is introduced by the BASE branch moving, not by the PR: when
 * woorelease dates the unreleased section, every open PR whose entry sits there
 * becomes wrong without anyone pushing to it. So the per-repo workflow runs this
 * twice over: for every open PR when the base branch's changelog changes (the
 * release commit included), and for one PR whenever that PR changes.
 *
 * It needs no merge and no clone. For each PR it reads the changelog at three
 * points - the merge base, the PR head, and the base branch as it is now - and
 * asks which version section the PR adds to and whether that version has since
 * shipped. That works on PRs which CONFLICT with the base too, where no
 * test-merge commit and no refs/pull/N/merge exist.
 *
 * Usage:
 *   node tools/changelog-sweep/sweep.mjs                    # every repo, report only
 *   node tools/changelog-sweep/sweep.mjs --repo o/r         # one repo
 *   node tools/changelog-sweep/sweep.mjs --repo o/r --base trunk   # PRs targeting trunk
 *   node tools/changelog-sweep/sweep.mjs --repo o/r --pr 439       # one PR
 *   ... --write                                             # also status + comment + label
 */
import { readFileSync, appendFileSync, existsSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { classifyTargeted, hasOverride } from './detect.mjs';
import { LABEL, LABEL_COLOR, LABEL_DESCRIPTION, STATUS_CONTEXT, planPr, statusFor } from './plan.mjs';

const HERE = dirname( fileURLToPath( import.meta.url ) );
const ROOT = join( HERE, '..', '..' );
const CHANGELOG_FILES = [ 'changelog.txt', 'readme.txt' ];

/**
 * Fine-grained PATs cannot span organisations, so a fleet-wide run takes one
 * token per owner. GH_TOKEN is the single-token fallback (local `gh`), and
 * GITHUB_TOKEN is what the per-repo workflow has.
 */
function tokenFor( owner ) {
	const key = `GH_TOKEN_${ owner.toUpperCase().replace( /[^A-Z0-9]/g, '_' ) }`;
	const t = process.env[ key ] || process.env.GH_TOKEN || process.env.GITHUB_TOKEN;
	if ( ! t ) throw new Error( `No token for ${ owner }: set ${ key } or GH_TOKEN` );
	return t;
}

async function api( repo, path, init = {} ) {
	const owner = repo.split( '/' )[ 0 ];
	const url = path.startsWith( 'https://' ) ? path : `https://api.github.com/repos/${ repo }${ path }`;
	const res = await fetch( url, {
		...init,
		headers: {
			accept: 'application/vnd.github+json',
			authorization: `Bearer ${ tokenFor( owner ) }`,
			'user-agent': 'escargot-changelog-sweep',
			...( init.headers || {} ),
		},
	} );
	if ( res.status === 404 ) return null;
	if ( ! res.ok ) throw new Error( `${ res.status } ${ res.statusText } for ${ init.method || 'GET' } ${ url }` );
	return res.status === 204 ? true : res.json();
}

const send = ( repo, path, method, body ) => api( repo, path, { method, body: JSON.stringify( body ) } );

/**
 * Follow pagination. `per_page` caps at 100, and stopping there would leave the
 * 101st open PR silently unswept - the same invisible miss this tool exists to
 * catch.
 */
async function apiAll( repo, path ) {
	const out = [];
	const sep = path.includes( '?' ) ? '&' : '?';
	for ( let page = 1; ; page++ ) {
		const batch = await api( repo, `${ path }${ sep }per_page=100&page=${ page }` );
		if ( ! Array.isArray( batch ) || ! batch.length ) break;
		out.push( ...batch );
		if ( batch.length < 100 ) break;
	}
	return out;
}

async function fileAt( repo, path, ref ) {
	const r = await api( repo, `/contents/${ path }?ref=${ encodeURIComponent( ref ) }` );
	if ( ! r?.content ) return null;
	return Buffer.from( r.content, 'base64' ).toString( 'utf8' );
}

/**
 * Merge base is available for every PR, including ones that conflict with the
 * base branch - unlike the test-merge commit, which GitHub does not compute for
 * conflicting PRs (nor does it publish refs/pull/N/merge for them).
 */
async function mergeBaseSha( repo, baseRef, headSha ) {
	const cmp = await api( repo, `/compare/${ encodeURIComponent( baseRef ) }...${ headSha }` );
	return cmp?.merge_base_commit?.sha || null;
}

export function repoList() {
	// dev-setup.sh is the team's canonical list; parse it rather than keeping a
	// second copy that can drift. extra-repos.json covers anything not cloned
	// by the dev environment.
	const repos = new Set();
	const setup = readFileSync( join( ROOT, 'dev-setup.sh' ), 'utf8' );
	for ( const m of setup.matchAll( /"([\w.-]+\/[\w.-]+)"/g ) ) repos.add( m[ 1 ] );
	const extrasPath = join( HERE, 'extra-repos.json' );
	if ( existsSync( extrasPath ) ) {
		for ( const r of JSON.parse( readFileSync( extrasPath, 'utf8' ) ) ) repos.add( r );
	}
	return [ ...repos ].sort();
}

/** Work out the PR's outcome. Reads only; never writes. */
async function checkPr( repo, pr ) {
	const mb = await mergeBaseSha( repo, pr.base.ref, pr.head.sha );
	if ( ! mb ) throw new Error( 'no merge base (fork deleted?)' );

	const findings = [];
	let amended = false;
	for ( const file of CHANGELOG_FILES ) {
		// Three points: the file when the PR branched, the file the PR now has,
		// and the file on the base branch as it is NOW. Comparing the first two
		// gives what the PR contributes; the third says whether the version it
		// targets has since shipped. No merge required.
		const atMergeBase = await fileAt( repo, file, mb );
		if ( atMergeBase === null ) continue;
		const atHead = await fileAt( repo, file, pr.head.sha );
		const currentBase = await fileAt( repo, file, pr.base.ref );
		if ( atHead === null || currentBase === null ) continue;
		const result = classifyTargeted( atMergeBase, atHead, currentBase );
		if ( result.verdict === 'misplaced' ) {
			findings.push( {
				file,
				version: result.misplaced[ 0 ].version,
				entries: result.misplaced.map( ( m ) => m.text ),
			} );
		} else if ( result.verdict === 'amend' ) {
			amended = true;
		}
	}

	if ( findings.length ) {
		const declared = hasOverride( { title: pr.title, body: pr.body || '', labels: ( pr.labels || [] ).map( ( l ) => l.name ) } );
		return declared ? { kind: 'override' } : { kind: 'misplaced', findings };
	}
	return amended ? { kind: 'amend' } : { kind: 'ok' };
}

/** Create the label once per repo, so adding it can never silently do nothing. */
async function ensureLabel( repo, ctx ) {
	if ( ctx.labelReady.has( repo ) ) return;
	if ( ! ( await api( repo, `/labels/${ encodeURIComponent( LABEL ) }` ) ) ) {
		try {
			await send( repo, '/labels', 'POST', { name: LABEL, color: LABEL_COLOR, description: LABEL_DESCRIPTION } );
		} catch ( e ) {
			// 422: another run created it between the lookup and the create.
			if ( ! String( e.message ).startsWith( '422' ) ) throw e;
		}
	}
	ctx.labelReady.add( repo );
}

async function setStatus( repo, sha, status, ctx ) {
	await send( repo, `/statuses/${ sha }`, 'POST', {
		state: status.state,
		context: STATUS_CONTEXT,
		description: status.description,
		...( ctx.runUrl ? { target_url: ctx.runUrl } : {} ),
	} );
}

/** Carry out a plan from planPr(). Returns a short note of what changed. */
async function applyPlan( repo, pr, plan, ctx ) {
	const done = [];
	await setStatus( repo, pr.head.sha, plan.status, ctx );
	if ( plan.comment?.op === 'create' ) {
		await send( repo, `/issues/${ pr.number }/comments`, 'POST', { body: plan.comment.body } );
		done.push( 'comment posted' );
	} else if ( plan.comment?.op === 'update' ) {
		await send( repo, `/issues/comments/${ plan.comment.id }`, 'PATCH', { body: plan.comment.body } );
		done.push( 'comment updated' );
	}
	if ( plan.label === 'add' ) {
		await ensureLabel( repo, ctx );
		await send( repo, `/issues/${ pr.number }/labels`, 'POST', { labels: [ LABEL ] } );
		done.push( 'label added' );
	} else if ( plan.label === 'remove' ) {
		await api( repo, `/issues/${ pr.number }/labels/${ encodeURIComponent( LABEL ) }`, { method: 'DELETE' } );
		done.push( 'label removed' );
	}
	return done;
}

async function sweepPr( repo, pr, ctx ) {
	const label = `${ repo }#${ pr.number }`;

	if ( pr.state !== 'open' ) {
		return { label, status: 'skipped', note: `PR is ${ pr.state }` };
	}
	if ( pr.draft && process.env.SKIP_DRAFTS === '1' ) {
		return { label, status: 'skipped', note: 'draft' };
	}

	let outcome;
	try {
		outcome = await checkPr( repo, pr );
	} catch ( e ) {
		outcome = { kind: 'error', message: e.message };
	}

	const row = { label, status: outcome.kind, url: pr.html_url };
	if ( outcome.kind === 'misplaced' ) row.note = outcome.findings.map( ( f ) => `${ f.file } → ${ f.version }` ).join( ', ' );
	if ( outcome.kind === 'override' ) row.note = 'deliberate amendment declared';
	if ( outcome.kind === 'error' ) row.note = outcome.message;

	if ( ctx.write ) {
		try {
			const comments = outcome.kind === 'error' ? [] : await apiAll( repo, `/issues/${ pr.number }/comments` );
			const plan = planPr( {
				outcome,
				labels: ( pr.labels || [] ).map( ( l ) => l.name ),
				comments,
				headSha: pr.head.sha,
			} );
			const done = await applyPlan( repo, pr, plan, ctx );
			if ( done.length ) row.note = [ row.note, done.join( ', ' ) ].filter( Boolean ).join( '; ' );
		} catch ( e ) {
			// The verdict stands, but the PR does not show it - that is an error.
			row.status = 'error';
			row.note = [ row.note, `write failed: ${ e.message }` ].filter( Boolean ).join( '; ' );
		}
	}
	return row;
}

async function sweepRepo( repo, ctx ) {
	if ( ctx.pr ) {
		const pr = await api( repo, `/pulls/${ ctx.pr }` );
		if ( ! pr ) return [ { label: `${ repo }#${ ctx.pr }`, status: 'error', note: 'PR not found' } ];
		return [ await sweepPr( repo, pr, ctx ) ];
	}

	const query = ctx.base ? `/pulls?state=open&base=${ encodeURIComponent( ctx.base ) }` : '/pulls?state=open';
	const rows = [];
	for ( const stub of await apiAll( repo, query ) ) {
		// One unreadable PR used to abort the repo, discarding the verdicts for
		// every other PR in it. Contain the failure to the PR it belongs to.
		try {
			const pr = await api( repo, `/pulls/${ stub.number }` );
			rows.push( await sweepPr( repo, pr, ctx ) );
		} catch ( e ) {
			rows.push( { label: `${ repo }#${ stub.number }`, status: 'error', note: e.message } );
			if ( ctx.write ) {
				await setStatus( repo, stub.head.sha, statusFor( { kind: 'error', message: e.message } ), ctx ).catch( () => {} );
			}
		}
	}
	return rows;
}

/**
 * @param {object}   opts
 * @param {string[]} opts.repos  owner/name list
 * @param {number}   [opts.pr]   check this one PR (needs exactly one repo)
 * @param {string}   [opts.base] only PRs targeting this branch
 * @param {boolean}  [opts.write]
 * @param {string}   [opts.runUrl] linked from the commit status
 */
export async function run( { repos, pr = null, base = null, write = false, runUrl = null } ) {
	const ctx = { pr, base, write, runUrl, labelReady: new Set() };
	const all = [];
	for ( const repo of repos ) {
		try {
			all.push( ...( await sweepRepo( repo, ctx ) ) );
		} catch ( e ) {
			all.push( { label: repo, status: 'error', note: e.message } );
		}
	}
	return all;
}

export function summarise( all, { write } ) {
	const flagged = all.filter( ( r ) => r.status === 'misplaced' );
	const errored = all.filter( ( r ) => r.status === 'error' );
	return [
		`## Changelog placement — ${ flagged.length } PR(s) with entries in a released section`,
		'',
		...( errored.length
			? [ `**Incomplete run: ${ errored.length } repo(s)/PR(s) could not be checked.** The count above is a floor, not an all-clear.`, '' ]
			: [] ),
		write ? '' : '_Report-only run; no statuses, comments or labels were written._',
		'',
		'| PR | Status | Detail |',
		'| --- | --- | --- |',
		...all
			.filter( ( r ) => r.status !== 'ok' || r.note )
			.map( ( r ) => `| ${ r.url ? `[${ r.label }](${ r.url })` : r.label } | ${ r.status } | ${ r.note || '' } |` ),
		'',
		`${ all.filter( ( r ) => r.status === 'ok' ).length } PR(s) clean.`,
	].join( '\n' );
}

function parseArgs( argv ) {
	const value = ( name ) => ( argv.includes( name ) ? argv[ argv.indexOf( name ) + 1 ] : null );
	const pr = value( '--pr' );
	const repo = value( '--repo' );
	if ( pr !== null && ! /^\d+$/.test( pr ) ) throw new Error( `--pr needs a PR number, got "${ pr }"` );
	if ( pr && ! repo ) throw new Error( '--pr needs --repo' );
	return {
		repos: repo ? [ repo ] : repoList(),
		pr: pr ? Number( pr ) : null,
		base: value( '--base' ) || null,
		write: argv.includes( '--write' ),
	};
}

async function main() {
	const opts = parseArgs( process.argv.slice( 2 ) );
	const { GITHUB_SERVER_URL, GITHUB_REPOSITORY, GITHUB_RUN_ID } = process.env;
	const runUrl = GITHUB_RUN_ID ? `${ GITHUB_SERVER_URL }/${ GITHUB_REPOSITORY }/actions/runs/${ GITHUB_RUN_ID }` : null;

	const all = await run( { ...opts, runUrl } );
	const summary = summarise( all, opts );

	console.log( summary );
	// Append: the step summary file is shared with any other step in the job.
	if ( process.env.GITHUB_STEP_SUMMARY ) appendFileSync( process.env.GITHUB_STEP_SUMMARY, `${ summary }\n` );

	// A missing token or a 401 otherwise reports "0 PR(s) with entries in a released
	// section" and exits green, which reads exactly like a clean run. Findings stay
	// advisory; only a run that could not look fails.
	if ( all.some( ( r ) => r.status === 'error' ) ) process.exit( 1 );
}

if ( process.argv[ 1 ] && resolve( process.argv[ 1 ] ) === fileURLToPath( import.meta.url ) ) {
	main().catch( ( e ) => {
		console.error( e.message );
		process.exit( 1 );
	} );
}

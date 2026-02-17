# Write Changelog Entry Skill

Generate professional changelog entries for WooCommerce Shipping based on the current branch changes.

## When to Use

Use this skill when:
- A feature or fix is complete and ready for release
- You need to document changes for `changelog.txt` and `readme.txt`
- Preparing a PR that requires changelog entries

## Instructions

Follow these steps in order:

### Step 1: Analyze Current Branch Changes

Run the following commands to understand what changed:

```bash
# Get branch name and commits
git log --oneline main..HEAD

# Get detailed diff summary
git diff main --stat

# Get commit messages for context
git log main..HEAD --pretty=format:"%s%n%b"
```

### Step 2: Categorize the Changes

Classify each change into one of these categories:

| Prefix | When to Use | Example |
|--------|-------------|---------|
| `Add` | New features, new functionality | "New Return Label feature" |
| `Fix` | Bug fixes, error corrections | "Prevent rate fetch when address is unverified" |
| `Tweak` | Improvements, enhancements, compatibility | "WooCommerce 10.4 compatibility" |
| `Dev` | Developer-facing changes only | "Update JS dependencies" |

### Step 3: Write User-Facing Entries

Follow these writing guidelines:

#### Tone & Style
- **Professional**: No casual language, emojis, or exclamation marks
- **User-focused**: Describe the benefit, not the implementation
- **Concise**: One sentence, typically 10-20 words
- **Action-oriented**: Start with a verb (Add, Fix, Prevent, Improve, etc.)

#### What to Include
- The user-visible impact of the change
- Feature names users would recognize
- Screen/page names when relevant

#### What to Avoid
- Internal code details (class names, function names, file paths)
- Technical jargon users wouldn't understand
- Security vulnerability details (use generic description)
- Breaking change warnings (handle in upgrade notices)

#### Examples of Good vs Bad Entries

**Good:**
```
* Fix   - Prevent rate fetch API calls when destination address is unverified.
* Add   - New Return Label feature. You can now buy return labels for domestic shipments.
* Tweak - WooCommerce 10.4 compatibility.
```

**Bad:**
```
* Fix   - Fixed bug in LabelRateService.php where $address->isVerified() returned false.
* Add   - Implemented ReturnLabelController and ReturnLabelService classes.
* Tweak - Updated composer.json dependencies and ran npm audit fix.
```

### Step 4: Format the Entry

Use this exact format (note the spacing):

```
* Add   - Description here.
* Fix   - Description here.
* Tweak - Description here.
* Dev   - Description here.
```

- Prefix is left-aligned with 6 characters total (e.g., `Add   `, `Fix   `, `Tweak `)
- Single space after the hyphen
- Sentence ends with a period
- One entry per line

### Step 5: Determine Version Number

Check current version in `readme.txt` (line 10: `Stable tag: X.Y.Z`):

- **Major (X.0.0)**: Breaking changes, major new features
- **Minor (X.Y.0)**: New features, significant improvements
- **Patch (X.Y.Z)**: Bug fixes, small tweaks

### Step 6: Update changelog.txt

Insert the new version block at the **top** of the changelog, after line 2:

```
= X.Y.Z - YYYY-MM-DD =
* Add   - New feature description.
* Fix   - Bug fix description.
* Tweak - Improvement description.

```

Note: Leave one blank line after the entries, before the previous version.

Location: `/Users/samnajian/Dev/woocommerce-shipping/changelog.txt`

### Step 7: Update readme.txt

Insert the same version block in the `== Changelog ==` section (around line 131):

```
= X.Y.Z - YYYY-MM-DD =
* Add   - New feature description.
* Fix   - Bug fix description.
* Tweak - Improvement description.

```

Also update `Stable tag:` on line 10 if this is a release.

Location: `/Users/samnajian/Dev/woocommerce-shipping/readme.txt`

### Step 8: Copy to Clipboard

After generating the entries, copy them to the clipboard for easy pasting into PR descriptions.

## Entry Templates by Change Type

### New Feature
```
* Add   - [Feature name]. [Brief description of what users can now do].
```
Example: `* Add   - New Return Label feature. You can now buy return labels for domestic shipments.`

### Bug Fix
```
* Fix   - [What was fixed] [when/where it occurred].
```
Example: `* Fix   - Prevent rate fetch API calls when destination address is unverified.`

### UI/UX Improvement
```
* Tweak - [What improved] [in which area].
```
Example: `* Tweak - Improve error handling when purchasing shipping labels.`

### Compatibility Update
```
* Tweak - [Platform] [version] compatibility.
```
Example: `* Tweak - WooCommerce 10.4 compatibility.`

### Performance Improvement
```
* Tweak - [What improved] for [benefit].
```
Example: `* Tweak - ScanForm history caching for improved performance.`

### Security Fix
```
* Fix   - [Generic description without revealing vulnerability details].
```
Example: `* Fix   - node-forge vulnerability (CVE-2025-12816)`

### Developer-Only Change
```
* Dev   - [What changed].
```
Example: `* Dev   - Update JS dependencies.`

## Validation Checklist

Before finalizing, verify:
- [ ] Entry starts with correct prefix (`Add`, `Fix`, `Tweak`, `Dev`)
- [ ] Spacing is correct (6 chars for prefix + ` - `)
- [ ] Entry is user-facing (no code details)
- [ ] Entry ends with a period
- [ ] Version number follows semver
- [ ] Date format is `YYYY-MM-DD`
- [ ] Entry added to both `changelog.txt` and `readme.txt`
- [ ] Entries are in order: Add → Fix → Tweak → Dev

## Output Format

Present the changelog entry like this:

```
## Changelog Entry for v{version}

### changelog.txt (insert after line 2)
= {version} - {date} =
* {entries}

### readme.txt (insert after line 131)
= {version} - {date} =
* {entries}

### Stable tag update (readme.txt line 10)
Stable tag: {version}
```

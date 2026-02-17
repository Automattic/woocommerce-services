---
name: create-pr
description: Generate PR description using repository's PR template and current branch changes, copy to clipboard
---

# Create PR

Generate GitHub pull request descriptions using the PR template at `.github/PULL_REQUEST_TEMPLATE.md` and referencing changes in the current branch. Outputs markdown and copies to clipboard.

## When to Use

Activate this skill when the user:
- Uses `/create-pr` command with or without a repo name
- Says "create a PR description", "generate PR body", "make a pull request"
- Wants a PR description for their current branch

## Important

**This skill does NOT:**
- ❌ Commit changes
- ❌ Push to remote
- ❌ Create the actual PR on GitHub

**This skill ONLY:**
- ✅ Generates PR title and description
- ✅ Outputs as markdown
- ✅ Copies to clipboard (platform-specific: `pbcopy` on macOS, `xclip`/`xsel` on Linux, `clip.exe` on Windows)

## Workflow

### Step 1: Identify Repository

**If repo name is provided:**
- Use the specified repository

**If no repo name is provided:**
- Check for `.git` in current workspace
- If multiple git repositories detected in workspace, ask user to specify which one

**Validation:**
- Verify the repository has a remote on GitHub
- Confirm current branch is not `main`, `master`, or the default branch

### Step 2: Gather Branch Information

Run these commands in the repository directory:

```bash
# Get current branch name
git branch --show-current

# Get the base branch (usually main or master)
git symbolic-ref refs/remotes/origin/HEAD | sed 's@^refs/remotes/origin/@@'

# Get commit history since divergence from base
git log {base-branch}..HEAD --oneline

# Get full diff since divergence
git diff {base-branch}...HEAD
```

### Step 3: Find and Read PR Template

Look for PR template in these locations (in order):
1. `.github/PULL_REQUEST_TEMPLATE.md`
2. `.github/pull_request_template.md`
3. `docs/PULL_REQUEST_TEMPLATE.md`
4. `docs/pull_request_template.md`
5. `PULL_REQUEST_TEMPLATE.md`
6. `pull_request_template.md`

**CRITICAL: If template found:**
- **Read the template file** using the Read tool
- **Use the EXACT template structure** - do not restructure or rename sections
- **Preserve HTML comments** (<!-- ... -->) - they provide context for reviewers
- **Keep all checkbox items** exactly as defined in the template
- **Only fill in the content areas** between section headers
- **Do not add sections** that don't exist in the template
- **Do not remove sections** - leave them empty or with "N/A" if not applicable

**If no template found:**
- Use a generic structure:
  ```markdown
  ## Summary
  
  ## Changes
  
  ## Testing
  
  ## Related Issues
  ```

### Step 4: Analyze Changes

Based on the git diff and commit history:

1. **Categorize changes:**
   - New features
   - Bug fixes
   - Refactoring
   - Documentation
   - Tests
   - Chores

2. **Identify key files changed:**
   - List the most significant file changes
   - Note any breaking changes or migrations

3. **Extract commit messages:**
   - Review commit messages for context
   - Identify patterns and themes

### Step 5: Fill PR Template

**Title Generation:**
- Create concise title (50-70 characters max)
- Format: `[Type] Brief description`
- Types: feat, fix, refactor, docs, test, chore

**Body Generation:**

**IMPORTANT: Follow the repository's template structure exactly.**

1. **Copy the entire template** as the starting point
2. **Keep all HTML comments** - they guide reviewers and may be used by automation
3. **Fill in content** below each section header, after any HTML comments
4. **For checkbox sections:**
   - Mark applicable items with `[x]`
   - Leave others as `[ ]`
   - Do NOT remove any checkbox items
5. **For text sections**, fill based on:
   - **Summary/Description:** High-level overview of what changed and why
   - **Changes:** Bulleted list of key changes from the diff
   - **Testing:** How to test/verify the changes (use numbered steps)
   - **Related Issues:** Extract from commit messages or branch names. Use short IDs (e.g., "Fixes WOOSHIP-456") NOT full URLs
   - **Screenshots:** Add if UI changes, otherwise write "N/A" or "N/A - Logic fix, no visual changes"

### Step 6: Generate Final Output

**Format the complete PR content:**

```markdown
## Title

{Generated PR title}

## Description

{Filled PR template body}
```

### Step 7: Copy to Clipboard and Display

1. **Display the generated PR description** to the user in a markdown code block
2. **Copy to clipboard** using the appropriate platform-specific command:
   
   **macOS:**
   ```bash
   echo "{PR title and body}" | pbcopy
   ```
   
   **Linux:**
   ```bash
   # Using xclip (install: apt-get install xclip or yum install xclip)
   echo "{PR title and body}" | xclip -selection clipboard
   
   # Or using xsel (install: apt-get install xsel or yum install xsel)
   echo "{PR title and body}" | xsel --clipboard --input
   ```
   
   **Windows (Git Bash/WSL):**
   ```bash
   echo "{PR title and body}" | clip.exe
   ```
   
   **Cross-platform detection example:**
   ```bash
   # Detect OS and use appropriate clipboard command
   if command -v pbcopy > /dev/null; then
       echo "{PR title and body}" | pbcopy
   elif command -v xclip > /dev/null; then
       echo "{PR title and body}" | xclip -selection clipboard
   elif command -v xsel > /dev/null; then
       echo "{PR title and body}" | xsel --clipboard --input
   elif command -v clip.exe > /dev/null; then
       echo "{PR title and body}" | clip.exe
   else
       echo "⚠️  No clipboard utility found. Please copy manually."
   fi
   ```

3. **Confirm to user:**
   ```
   ✅ PR description generated and copied to clipboard!
   
   Next steps:
   1. Push your branch: git push -u origin HEAD
   2. Create PR on GitHub
   3. Paste from clipboard
   ```

## Repository-Specific Patterns

### **WooCommerce/woocommerce**
- Look for changelog entries
- Check for version bumps
- Verify coding standards compliance

### **Automattic/woocommerce-connect-server**
- Note API changes
- Check for database migrations
- Verify environment variable updates

### **Automattic/ciab-admin**
- Note UI/component changes
- Check for new dependencies
- Verify integration with WC Shipping

### **woocommerce/woocommerce-shipping**
- Check for UDM module changes
- Note carrier integrations
- Verify backward compatibility

## Error Handling

### **No remote configured:**
```
Error: No GitHub remote found. Please configure origin remote first.
Suggestion: git remote add origin https://github.com/{org}/{repo}.git
```

### **On default branch:**
```
Error: Cannot create PR from default branch (main/master).
Suggestion: Create a feature branch first: git checkout -b feature/your-branch-name
```

### **No changes:**
```
Error: No commits found on this branch compared to {base-branch}.
Suggestion: Make some commits first, or check if you're on the right branch.
```

## Examples

### **Example 1: Simple Feature PR**

```
User: /create-pr woocommerce-shipping

[Skill analyzes branch: feature/add-scanform-ui]
[Base branch: main]
[Commits: 3 commits adding ScanForm UI components]
[Template found: .github/PULL_REQUEST_TEMPLATE.md]

Agent outputs:

✅ PR description generated and copied to clipboard!

---

## Title
feat: Add ScanForm UI for USPS bulk label printing

## Description

### Summary
Implements the ScanForm UI to allow merchants to generate USPS ScanForms for bulk label printing.

### Changes
- Added ScanFormModal component with form validation
- Integrated ScanForm API endpoint
- Added ScanForm history table to settings page
- Updated tests for new components

### Testing
1. Enable USPS in WooCommerce Shipping
2. Purchase multiple USPS labels
3. Navigate to Settings > Shipping Labels > ScanForm
4. Generate a new ScanForm
5. Verify history shows in table

### Related Issues
Closes WOOSHIP-1761

---

Next steps:
1. Push your branch: git push -u origin HEAD
2. Create PR on GitHub
3. Paste description from clipboard (Cmd+V)
```

### **Example 2: Multiple Repos Detected**

```
User: /create-pr

[Skill detects 3 git repos in workspace]

Response:
I found multiple repositories in your workspace:
1. woocommerce-shipping
2. ciab-admin
3. woocommerce-connect-server

Which repository do you want to create a PR for?
```

### **Example 3: Bug Fix PR**

```
User: /create-pr

[Skill analyzes branch: fix/null-shipment-ids]
[Single commit: "Fix critical error when opening orders with null shipment IDs"]

Agent outputs:

✅ PR description generated and copied to clipboard!

---

## Title
fix: Handle null shipment item IDs in older orders

## Description

### Summary
Fixes critical error when opening older orders that have null shipment item IDs.

### Changes
- Added null check in shipment data retrieval
- Updated error handling for legacy order data
- Added defensive coding for missing shipment metadata

### Testing
1. Create an order with WooCommerce Shipping 1.x
2. Upgrade to 2.x
3. Open the order in admin
4. Verify no fatal error occurs

### Related Issues
Fixes #1395

---

Content is in your clipboard - paste into GitHub PR form!
```

## Best Practices

1. **Be specific:** Reference actual code changes, not generic descriptions
2. **Include context:** Why was this change needed? What problem does it solve?
3. **Testing instructions:** Make them actionable and specific
4. **Link issues:** Extract Linear issue IDs and GitHub issue numbers from commits
5. **Clean output:** Format as clean markdown suitable for GitHub

## Output Format

Always output in this structure:

```markdown
## Title
{Generated title}

## Description

{Filled template body}
```

Then run:
```bash
echo "{full content}" | pbcopy
```

And tell the user:
```
✅ PR description generated and copied to clipboard!

Next steps:
1. Push your branch: git push -u origin HEAD
2. Create PR on GitHub
3. Paste description from clipboard (Cmd+V)
```

## Notes

- This skill does NOT create the PR - it only generates the description
- User must manually push branch and create PR on GitHub
- **ALWAYS read the repository's PR template first** - do not assume a structure
- **Follow the template EXACTLY** - preserve HTML comments, section headers, and checkbox items
- Some repos use issue templates in `.github/ISSUE_TEMPLATE/` - don't confuse with PR templates
- If commits or branch names reference Linear issues (WOOSHIP-1234, WOO13-143), use the short ID format in "Closes" - NOT the full linear.app URL
- Use `pbcopy` to copy to clipboard (macOS)
- The clipboard content should be the PR body only (not wrapped in code blocks) - ready to paste directly into GitHub

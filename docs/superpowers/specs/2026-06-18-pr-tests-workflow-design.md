# PR Tests Workflow Design

**Date:** 2026-06-18  
**Status:** Implemented  
**Purpose:** Add minimal CI/CD checks to block merging PRs that fail tests or type checking.

## Overview

This workflow runs on all pull requests targeting the `master` branch. It verifies code quality by:
1. Running Vitest tests (`npm run test`)
2. Running TypeScript type checking (`npx astro check`)

If either check fails, the PR cannot be merged.

## Workflow Configuration

**File:** `.github/workflows/pr-tests.yml`

**Trigger:** Pull requests to `master` branch

**Environment:**
- Runner: `ubuntu-latest`
- Node.js: 20 (matches project requirement)
- Dependency caching: npm cache enabled for faster runs

## Steps

1. **Checkout code** — Fetch the PR branch
2. **Set up Node.js** — Install Node 20 with npm cache
3. **Install dependencies** — Run `npm ci` (clean install for CI)
4. **Run tests** — Execute `npm run test` (Vitest)
5. **Type check** — Execute `npx astro check` (Astro TypeScript validation)

## Status Check Configuration

This workflow must be set as a required status check in GitHub repository settings:
1. Go to Settings → Branches → Branch protection rules
2. Edit `master` rule (or create if missing)
3. Under "Require status checks to pass before merging," add: `Tests & Type Checking`

Without this configuration, the workflow runs but won't block merges.

## Current Test Coverage

- **Test file:** `src/layouts/Layout.test.ts`
- **Test count:** 2 passing tests
- **Framework:** Vitest

As the project grows, additional tests should be added to improve coverage.

## Future Enhancements

- Add coverage reporting
- Add linting checks (ESLint)
- Add build verification step
- Configure branch protection to require reviews + passing checks

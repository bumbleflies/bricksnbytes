# GitHub Actions Workflow Redesign

**Date:** 2026-06-18  
**Status:** Design approved  
**Author:** Claude Code (Brainstorming)

## Overview

Restructure GitHub Actions workflows from two monolithic workflows (`pr-tests.yml`, `build-docker.yml`) into a modular, reusable system with:
- **Reusable workflows** for PR tests and Docker build/publish pipeline
- **Trigger workflows** for different branch/event combinations
- **Clear gating:** PR tests must pass before Docker build starts on master pushes

## Goals

1. **PR tests run on any branch** — including direct pushes to master
2. **Build/test/push gated by PR tests** — ensure code quality before Docker operations
3. **Modular architecture** — reusable workflows avoid duplication
4. **Consistent Node version** — Node 24 everywhere

## Current State

**Existing workflows:**
- `pr-tests.yml` — runs on PRs to master only; tests + astro check
- `build-docker.yml` — runs on master push; separate test job + build + healthcheck + push

**Problems:**
- PR tests don't run on pushes to master
- Test logic duplicated between two workflows
- Monolithic structure limits reuse

## Proposed Architecture

### Reusable Workflows

#### `pr-tests.yml` (Reusable)
Single job that runs on any workflow call:
- Checkout code
- Setup Node 24
- Install dependencies (`npm ci`)
- Run tests (`npm run test`)
- Type check (`npx astro check`)

Fails the calling workflow if either test or astro check fails. Used as a gate on master pushes before Docker operations.

#### `build-publish.yml` (Reusable)
Three sequential jobs:

1. **build:** Build Docker image, save as tar, upload artifact
   - Uses docker/setup-buildx-action
   - Generates metadata (tags: latest, semver, sha)
   - Builds with GHA cache
   - Outputs tar to artifact storage (1-day retention)

2. **healthcheck:** Download artifact, load image, test health
   - Waits for `build` to complete
   - Runs container with health check
   - Retries for up to 10 checks (60 seconds total)
   - Fails if container doesn't become healthy

3. **push:** Download artifact, load image, push to Docker Hub
   - Waits for `healthcheck` to complete
   - Authenticates to Docker Hub via `DOCKER_TOKEN` secret
   - Pushes all generated tags (latest, semver, sha)

Chain: `build` → `healthcheck` → `push` via `needs:` dependencies.

### Trigger Workflows

#### `pr.yml`
**Trigger:** Pull request to master  
**Behavior:** Calls `pr-tests.yml`  
**Purpose:** Ensure code quality on PRs

#### `push.yml`
**Trigger:** Push to any branch except master  
**Behavior:** Calls `pr-tests.yml`  
**Purpose:** Run quality checks on all branch pushes

#### `master.yml`
**Trigger:** Push to master (with path filter)  
**Behavior:** 
1. Call `pr-tests.yml` (gate)
2. On success, call `build-publish.yml` (build → healthcheck → push)

**Path filter:** Only run on changes to:
- `src/**`
- `public/**`
- `package.json`, `package-lock.json`
- `astro.config.mjs`, `tsconfig.json`
- `Dockerfile`, `nginx.conf`
- `.github/workflows/master.yml`

**Manual trigger:** `workflow_dispatch` available for manual runs

## Orchestration

| Event | Workflow Chain | Result |
|-------|---|---|
| PR to master | `pr.yml` → `pr-tests.yml` | Feedback on code quality |
| Push to any branch (except master) | `push.yml` → `pr-tests.yml` | Feedback on code quality |
| Push to master | `master.yml` → `pr-tests.yml` → `build-publish.yml` | Test → Build → Healthcheck → Push |

## Implementation Details

### Artifact Passing
- `build` job outputs Docker image tar to `docker-image-bnb` artifact
- `healthcheck` and `push` download the artifact, avoiding rebuild
- Artifact retention: 1 day (sufficient for sequential job completion)

### Permissions
- PR tests: `contents: read` (read-only)
- Build: `contents: read` (read-only)
- Push: `contents: read` + Docker Hub auth via `DOCKER_TOKEN` secret

### Environment Variables
- `REGISTRY: docker.io`
- `IMAGE_NAME: bumblecode/bnb`
- Defined in `build-publish.yml` (scoped to reusable workflow)

### Node Version
- Node 24 across all workflows (pr-tests, build-publish)
- Consistent with existing `build-docker.yml`

## Files to Create/Modify

### Create
- `.github/workflows/pr-tests.yml` — reusable
- `.github/workflows/build-publish.yml` — reusable
- `.github/workflows/pr.yml` — trigger
- `.github/workflows/push.yml` — trigger
- `.github/workflows/master.yml` — trigger

### Delete
- `.github/workflows/pr-tests.yml` (old, replaced by reusable + trigger)
- `.github/workflows/build-docker.yml` (old, replaced by reusable + trigger)

### Note
Old files will be overwritten by new ones with same names but different content/purpose.

## Benefits

1. **No duplication:** Test logic in one place (`pr-tests.yml`)
2. **Consistent CI:** PR tests run on all branches, not just PRs
3. **Quality gate:** Docker build can't start until code passes tests
4. **Reusable:** Both `pr-tests.yml` and `build-publish.yml` can be called independently
5. **Modular:** Docker pipeline remains cohesive (build → test → push is logical)
6. **Maintainable:** Clear separation of concerns; easy to modify one workflow without affecting others

## Success Criteria

- [ ] PR tests trigger on any branch push
- [ ] PR tests trigger on PRs to master
- [ ] Master push runs PR tests first, then build/publish on success
- [ ] Artifact passing works (build output feeds healthcheck/push)
- [ ] All workflows use Node 24
- [ ] Docker image builds, health checks pass, pushes to Docker Hub
- [ ] No workflow errors on test master pushes

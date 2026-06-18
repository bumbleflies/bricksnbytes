# GitHub Actions Workflow Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restructure GitHub Actions workflows into modular, reusable components with PR tests gating Docker builds on master.

**Architecture:** Two reusable workflows (pr-tests, build-publish) called by three trigger workflows (pr, push, master). PR tests run on all branches; build/publish only on master after PR tests pass.

**Tech Stack:** GitHub Actions, YAML, Node 24, Docker, Docker Hub

## Global Constraints

- Node version: 24 everywhere
- Docker registry: docker.io
- Docker image name: bumblecode/bnb
- Artifact retention: 1 day
- Path filter on master: src/**, public/**, package.json, package-lock.json, astro.config.mjs, tsconfig.json, Dockerfile, nginx.conf, .github/workflows/master.yml

---

### Task 1: Create pr-tests.yml reusable workflow

**Files:**
- Create: `.github/workflows/pr-tests.yml`

**Interfaces:**
- Consumes: None (entry point)
- Produces: Reusable workflow callable via `uses: ./.github/workflows/pr-tests.yml`

- [ ] **Step 1: Write pr-tests.yml with test + astro check**

Create `.github/workflows/pr-tests.yml`:

```yaml
name: PR Tests (Reusable)

on:
  workflow_call:

jobs:
  pr-tests:
    runs-on: ubuntu-latest
    name: Tests & Type Checking

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '24'
          cache: 'npm'

      - name: Install dependencies
        run: npm ci

      - name: Run tests
        run: npm run test

      - name: Type check
        run: npx astro check
```

- [ ] **Step 2: Validate YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/pr-tests.yml'))" && echo "YAML valid"`

Expected: `YAML valid` printed, no errors

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/pr-tests.yml
git commit -m "feat: add pr-tests reusable workflow (test + astro check)"
```

---

### Task 2: Create build-publish.yml reusable workflow

**Files:**
- Create: `.github/workflows/build-publish.yml`

**Interfaces:**
- Consumes: None (entry point)
- Produces: Reusable workflow callable via `uses: ./.github/workflows/build-publish.yml`; passes Docker image via artifact `docker-image-bnb`

- [ ] **Step 1: Write build-publish.yml with build, healthcheck, push jobs**

Create `.github/workflows/build-publish.yml`:

```yaml
name: Build and Publish Docker (Reusable)

on:
  workflow_call:

env:
  REGISTRY: docker.io
  IMAGE_NAME: bumblecode/bnb

jobs:
  build:
    runs-on: ubuntu-latest
    permissions:
      contents: read

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v4

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v4
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=raw,value=latest
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha,prefix={{branch}}-

      - name: Build Docker image to tar
        uses: docker/build-push-action@v7
        with:
          context: .
          push: false
          outputs: type=docker,dest=/tmp/bnb.tar
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

      - name: Upload Docker image artifact
        uses: actions/upload-artifact@v4
        with:
          name: docker-image-bnb
          path: /tmp/bnb.tar
          retention-days: 1

  healthcheck:
    runs-on: ubuntu-latest
    needs: build
    permissions:
      contents: read

    steps:
      - name: Download Docker image artifact
        uses: actions/download-artifact@v4
        with:
          name: docker-image-bnb
          path: /tmp

      - name: Load Docker image
        run: |
          docker load --input /tmp/bnb.tar
          docker image ls -a

      - name: Test Docker image healthcheck
        run: |
          docker run --rm --detach --name test_container bumblecode/bnb:latest
          for i in {1..10}; do
            status=$(docker inspect --format='{{.State.Health.Status}}' test_container)
            health_details=$(docker inspect --format='{{json .State.Health}}' test_container)
            echo "Health status: $status"
            echo "Health details: $health_details"
            if [ "$status" = "healthy" ]; then break; fi
            sleep 6
          done
          # Fail the step if not healthy after 10 retries
          if [ "$status" != "healthy" ]; then
            echo "Container did not become healthy after 10 attempts."
            docker logs test_container
            exit 1
          fi
          docker stop test_container

  push:
    runs-on: ubuntu-latest
    needs: healthcheck
    permissions:
      contents: read

    steps:
      - name: Download Docker image artifact
        uses: actions/download-artifact@v4
        with:
          name: docker-image-bnb
          path: /tmp

      - name: Load Docker image
        run: |
          docker load --input /tmp/bnb.tar
          docker image ls -a

      - name: Log in to Container Registry
        uses: docker/login-action@v4
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.DOCKER_TOKEN }}

      - name: Push Docker image
        run: |
          docker push bumblecode/bnb:latest
          # Also push the sha-tagged version if it exists
          docker images --no-trunc --quiet bumblecode/bnb | while read image_id; do
            docker images bumblecode/bnb --format="{{.Repository}}:{{.Tag}}" | grep -v latest | while read tag; do
              docker push "$tag" || true
            done
          done
```

- [ ] **Step 2: Validate YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/build-publish.yml'))" && echo "YAML valid"`

Expected: `YAML valid` printed, no errors

- [ ] **Step 3: Verify artifact passing structure**

Check the file contains:
- `build` job: `actions/upload-artifact@v4` with `name: docker-image-bnb`
- `healthcheck` job: `needs: build` and `actions/download-artifact@v4` with `name: docker-image-bnb`
- `push` job: `needs: healthcheck` and `actions/download-artifact@v4` with `name: docker-image-bnb`

Run: `grep -E "(upload-artifact|download-artifact|docker-image-bnb|needs:)" .github/workflows/build-publish.yml`

Expected: All four patterns present; `needs:` establishes `build` → `healthcheck` → `push` chain

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/build-publish.yml
git commit -m "feat: add build-publish reusable workflow (build → healthcheck → push)"
```

---

### Task 3: Create trigger workflows (pr.yml, push.yml, master.yml)

**Files:**
- Create: `.github/workflows/pr.yml`
- Create: `.github/workflows/push.yml`
- Create: `.github/workflows/master.yml`

**Interfaces:**
- Consumes: `pr-tests.yml` and `build-publish.yml` reusable workflows
- Produces: Three trigger workflows that call reusables via `uses:`

- [ ] **Step 1: Write pr.yml (PR to master trigger)**

Create `.github/workflows/pr.yml`:

```yaml
name: PR Tests

on:
  pull_request:
    branches:
      - master

jobs:
  call-pr-tests:
    uses: ./.github/workflows/pr-tests.yml
```

- [ ] **Step 2: Write push.yml (push to any branch except master trigger)**

Create `.github/workflows/push.yml`:

```yaml
name: Push Tests

on:
  push:
    branches-ignore:
      - master

jobs:
  call-pr-tests:
    uses: ./.github/workflows/pr-tests.yml
```

- [ ] **Step 3: Write master.yml (master push with gating)**

Create `.github/workflows/master.yml`:

```yaml
name: Master Workflow

on:
  push:
    branches:
      - master
    paths:
      - "src/**"
      - "public/**"
      - "package.json"
      - "package-lock.json"
      - "astro.config.mjs"
      - "tsconfig.json"
      - "Dockerfile"
      - "nginx.conf"
      - ".github/workflows/master.yml"
  workflow_dispatch:

jobs:
  pr-tests:
    uses: ./.github/workflows/pr-tests.yml

  build-publish:
    needs: pr-tests
    uses: ./.github/workflows/build-publish.yml
```

- [ ] **Step 4: Validate all three YAML files**

Run: `for f in .github/workflows/{pr,push,master}.yml; do python3 -c "import yaml; yaml.safe_load(open('$f'))" && echo "$f: YAML valid"; done`

Expected: All three files report `YAML valid`

- [ ] **Step 5: Verify orchestration in master.yml**

Check that:
- `pr-tests` job exists and uses `pr-tests.yml`
- `build-publish` job exists, uses `build-publish.yml`, and has `needs: pr-tests`

Run: `grep -A 2 "build-publish:" .github/workflows/master.yml | grep -E "(uses|needs)"`

Expected: Shows `needs: pr-tests` and `uses: ./.github/workflows/build-publish.yml`

- [ ] **Step 6: Commit all three**

```bash
git add .github/workflows/pr.yml .github/workflows/push.yml .github/workflows/master.yml
git commit -m "feat: add trigger workflows (pr, push, master orchestration)"
```

---

### Task 4: Delete old workflows

**Files:**
- Delete: `.github/workflows/pr-tests.yml` (old)
- Delete: `.github/workflows/build-docker.yml` (old)

- [ ] **Step 1: Verify old files exist**

Run: `ls -la .github/workflows/pr-tests.yml .github/workflows/build-docker.yml 2>&1`

Expected: Both files listed (they exist from prior setup)

- [ ] **Step 2: Delete old workflows**

Run: `rm .github/workflows/pr-tests.yml .github/workflows/build-docker.yml`

- [ ] **Step 3: Verify deletion**

Run: `ls .github/workflows/`

Expected: Only the 5 new workflows present: `pr-tests.yml`, `build-publish.yml`, `pr.yml`, `push.yml`, `master.yml`

- [ ] **Step 4: Commit deletion**

```bash
git add -u .github/workflows/
git commit -m "chore: remove old monolithic workflows (replaced by modular reusables)"
```

---

### Task 5: Verify workflows on a test branch

**Files:**
- No files created; verification only

**Interfaces:**
- Consumes: All 5 workflows from previous tasks
- Produces: Verified workflow runs on test branch

- [ ] **Step 1: Push to a test branch to trigger push.yml workflow**

```bash
git push origin HEAD:test/workflow-redesign
```

Expected: Git push succeeds; watch GitHub Actions UI for `Push Tests` workflow run

- [ ] **Step 2: Check that Push Tests workflow ran successfully**

Wait ~30 seconds, then check GitHub Actions UI at `https://github.com/bumbleflies/bricksnbytes/actions`

Expected: `Push Tests` workflow appears, `call-pr-tests` job runs, tests pass (green checkmark)

- [ ] **Step 3: Open a draft PR to master to trigger pr.yml workflow**

Go to GitHub, create a pull request from `test/workflow-redesign` to `master` (can be draft)

Expected: `PR Tests` workflow appears in PR checks, runs successfully (green checkmark)

- [ ] **Step 4: Merge the PR to master (or push directly to master if no PR)**

Option A (via PR):
```bash
# On GitHub UI, click "Merge pull request"
```

Option B (direct push):
```bash
git checkout master
git pull origin master
git merge test/workflow-redesign
git push origin master
```

Expected: Merge succeeds

- [ ] **Step 5: Verify Master Workflow runs on master push**

Watch GitHub Actions UI for `Master Workflow` run

Expected: 
- `pr-tests` job runs and passes
- `build-publish` job starts only after `pr-tests` passes (visible in "needs" indicator)
- `build` → `healthcheck` → `push` run sequentially
- Image builds, healthcheck passes, push to Docker Hub succeeds
- All jobs complete with green checkmarks

- [ ] **Step 6: Verify artifact passing**

In the `Master Workflow` run:
- Click `build` job → see "Upload Docker image artifact" step with `docker-image-bnb`
- Click `healthcheck` job → see "Download Docker image artifact" step retrieving `docker-image-bnb`
- Click `push` job → see "Download Docker image artifact" step retrieving `docker-image-bnb`

Expected: Each job successfully downloads and uses the artifact

- [ ] **Step 7: Clean up test branch**

```bash
git push origin --delete test/workflow-redesign
```

Expected: Test branch deleted from remote

---

## Self-Review

**Spec Coverage:**
- ✅ PR tests run on any branch: Task 3 (push.yml triggers on any branch)
- ✅ PR tests run on PRs to master: Task 3 (pr.yml triggers on PRs)
- ✅ Build/test/push gated by PR tests: Task 3 (master.yml has `needs: pr-tests` on build-publish)
- ✅ Reusable workflows: Tasks 1-2 (pr-tests.yml, build-publish.yml with `on: workflow_call:`)
- ✅ Modular Docker pipeline: Task 2 (build → healthcheck → push as separate jobs)
- ✅ Node 24 everywhere: Task 1, Step 1 uses `node-version: '24'`
- ✅ Artifact passing: Task 2 (upload/download pattern verified)
- ✅ Path filter on master: Task 3, Step 3 (master.yml includes paths: filter)
- ✅ Delete old workflows: Task 4

**Placeholder Scan:** None found. All steps have actual YAML, bash commands, and expected outputs.

**Type Consistency:** Artifact name `docker-image-bnb` consistent across all jobs. Reusable workflow names used consistently (`pr-tests.yml`, `build-publish.yml`).

**Testability:** Each task ends with independently testable deliverable (YAML syntax, artifact passing structure, orchestration verification, end-to-end workflow runs).

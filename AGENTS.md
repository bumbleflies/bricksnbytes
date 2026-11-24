# AGENTS.md

This file guides AI coding agents (and human contributors) working in this repo.
The beginner-friendly onboarding is in `INTRO.md`.

## Project

Astro-based static site rebuild of [bricksnbytes.de](https://bricksnbytes.de/) — programming courses for kids.
Stack: **Astro 7, TypeScript (strict), React 19 (islands only), Vitest, Docker (Nginx)**.
Output is fully static (`dist/`), no SSR. Node 22.12+ required (Astro 7 engine); CI and Docker use Node 26.

## Component guides

This repo holds two independent components with their own stacks. Follow their guides when working there:

- [backoffice/AGENTS.md](backoffice/AGENTS.md) — PHP/MySQL internal backoffice (own README, migrations, tests, Docker image).
- [mailer/AGENTS.md](mailer/AGENTS.md) — Node/nodemailer form-to-mail service behind nginx.

The rest of this file covers the root Astro site; the Safety rules apply repo-wide.

## Commands

```bash
npm install          # install deps
npm run dev          # dev server with hot reload (default http://localhost:4321)
npm run build        # production build -> dist/
npm run preview      # preview the production build locally
npm run test         # run all tests once (vitest run)
npm run test -- --watch                    # watch mode (TDD)
npm run test -- src/layouts/Layout.test.ts # single test file
npx astro check      # TypeScript check (run before committing)
```

No linter configured. No `vitest.config.ts` — defaults from `package.json`.

Port in use? `npm run dev -- --port 3002` (port 3001 is the local mailer target of the `/api/` proxy). Stale styles? Hard-refresh or `rm -rf .astro && npm run dev`.

## Structure

```
src/pages/       # file-based routing: index.astro -> /, kurse.astro -> /kurse, ki-integration.astro -> /ki-integration, …
src/components/  # .astro (default, zero JS) and .tsx (React islands, need client: directive)
src/layouts/     # Layout.astro / SubpageLayout.astro wrap pages (header, footer, fonts, meta)
src/data/        # TypeScript helpers with colocated Vitest tests
src/styles/      # design-system.css (tokens), global.css, layout.css, forms.css
src/content/     # YAML collections: offers/*.yaml, tiles/<page>/*.yaml (schema in src/content.config.ts)
src/scripts/     # browser-side form helpers (e.g. mailerForm.ts)
public/          # static assets served as-is (images/, favicon.svg, site.webmanifest)
dist/            # build output (generated, never edit)
docs/            # additional docs
history/         # completed-feature logs (YYYY-MM-DD_feature-name.md)
backoffice/      # separate PHP backoffice — see backoffice/AGENTS.md
mailer/          # separate Node mailer service — see mailer/AGENTS.md
.design-backups/ # original Claude Design artifacts (reference only, never import)
```

## Conventions

- **Pages** must use `src/layouts/Layout.astro` with `title` (+ optional `description`):
  ```astro
  import Layout from "../layouts/Layout.astro";
  <Layout title="About Us" description="...">...</Layout>
  ```
- **Components**: `.astro` by default. React (`.tsx`) only for interactivity, always with a `client:` directive (`client:load` / `client:visible` / `client:idle`). Props typed via `interface Props`; add `lang?: 'de' | 'en'` (default `'de'`) where user-facing text exists.
- **Styling**: scoped `<style>` blocks in components; global tokens from `src/styles/design-system.css` via `var(--coral)`, `var(--sp-md)`, etc. Never hardcode colors/spacing that already exist as tokens.
- **Content/YAML**: data lives in Astro content collections — `src/content/offers/*.yaml` and `src/content/tiles/<page>/*.yaml`. Load with `getCollection(...)` from `astro:content`, never hardcode listings:
  ```astro
  import { getCollection } from 'astro:content';
  const offers = (await getCollection('offers')).sort((a, b) => a.data.order - b.data.order);
  ```
  Schema is enforced in `src/content.config.ts` (zod). New YAML fields require a schema update; copy an existing file as template.
- **i18n**: German is default. User-facing text uses an optional `lang?: 'de' | 'en'` prop (default `'de'`) with inline `de`/`en` labels, as in `Header.astro`; there are no `de`/`en` YAML field pairs.
- **Static-only**: no server endpoints, no runtime secrets, no `fetch` at request time. Everything pre-renders at build.
- **Tests**: colocated `*.test.ts(x)` under `src/`, Vitest + Testing Library. Test props variants and content schemas, not trivial tautologies.
- **Docs**: significant features get a log in `history/YYYY-MM-DD_feature-name.md` (problem, solution, files changed, verification).

## Gotchas

- The custom YAML loader in `astro.config.mjs` resolves relative YAML imports from the importer dir and absolute ones from repo root. If a YAML import breaks, check extension (`.yaml`/`.yml`) and that file first.
- `dist/`, `.astro/`, `node_modules/` are generated — never edit or commit changes there.
- Dockerfile is multi-stage (Node 26 build → Nginx Alpine serve, with the mailer copied in). `npm run build` must pass locally before touching Docker/CI.
- `.design-backups/` is reference-only: read design intent from it, implement fresh components in `src/`.
- TypeScript is strict (`astro/tsconfigs/strict`) — run `npx astro check` before finishing.

## Safety

- Never commit secrets. Check `git status` / `git diff` before staging; stage only intended files.
- Don't change git config, skip hooks, force-push, or amend others' commits without being asked.
- Infrastructure/Docker changes: test locally (`docker build`, `npm run build`) before pushing; never hotfix production directly.

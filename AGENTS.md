# AGENTS.md

This file guides AI coding agents (and human contributors) working in this repo.
The beginner-friendly onboarding is in `INTRO.md`.

## Project

Astro-based static site rebuild of [bricksnbytes.de](https://bricksnbytes.de/) — programming courses for kids.
Stack: **Astro 7, TypeScript (strict), React 19 (islands only), Vitest, Docker (Nginx)**.
Output is fully static (`dist/`), no SSR. Node 18+ required (CI/dev use Node 24).

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

Port in use? `npm run dev -- --port 3001`. Stale styles? Hard-refresh or `rm -rf .astro && npm run dev`.

## Structure

```
src/pages/       # file-based routing: index.astro -> /, programs/index.astro -> /programs, programs/[slug].astro -> /programs/:slug
src/components/  # .astro (default, zero JS) and .tsx (React islands, need client: directive)
src/layouts/     # Layout.astro wraps every page (header, footer, fonts, meta)
src/styles/      # design-system.css (tokens), global.css, layout.css
src/content/     # YAML data: programs/*.yaml, age-groups/*.yaml (validated by src/content.config.ts)
public/          # static assets served as-is (images/, favicon.ico)
dist/            # build output (generated, never edit)
docs/            # additional docs
history/         # completed-feature logs (YYYY-MM-DD_feature-name.md)
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
- **Content/YAML**: course and age-group data lives in `src/content/*.yaml`. Load via eager glob, never hardcode listings:
  ```astro
  const modules = import.meta.glob('../content/programs/*.yaml', { eager: true });
  const programs = Object.values(modules).map((m: any) => m.default);
  ```
  Schema is enforced in `src/content.config.ts` (zod). New YAML fields require a schema update. YAML files need `name, slug, description, ...` — copy an existing file as template.
- **i18n**: German is default. Keep `de`/`en` variants side by side (`ageGroup`/`ageGroupDe`, `duration`/`durationDe`); follow the `Header.astro` `lang` prop pattern.
- **Static-only**: no server endpoints, no runtime secrets, no `fetch` at request time. Everything pre-renders at build.
- **Tests**: colocated `*.test.ts(x)` under `src/`, Vitest + Testing Library. Test props variants and content schemas, not trivial tautologies.
- **Docs**: significant features get a log in `history/YYYY-MM-DD_feature-name.md` (problem, solution, files changed, verification).

## Gotchas

- The custom YAML loader in `astro.config.mjs` resolves relative YAML imports from the importer dir and absolute ones from repo root. If a YAML import breaks, check extension (`.yaml`/`.yml`) and that file first.
- `dist/`, `.astro/`, `node_modules/` are generated — never edit or commit changes there.
- Dockerfile is multi-stage (Node 24 build → Nginx Alpine serve). `npm run build` must pass locally before touching Docker/CI.
- `.design-backups/` is reference-only: read design intent from it, implement fresh components in `src/`.
- TypeScript is strict (`astro/tsconfigs/strict`) — run `npx astro check` before finishing.

## Safety

- Never commit secrets. Check `git status` / `git diff` before staging; stage only intended files.
- Don't change git config, skip hooks, force-push, or amend others' commits without being asked.
- Infrastructure/Docker changes: test locally (`docker build`, `npm run build`) before pushing; never hotfix production directly.

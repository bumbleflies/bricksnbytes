# 👋 Welcome to BricksnBytes!

Hey! This guide will take you from "I've never seen this repo" to "I can run the site and make my first change" — no prior experience with this project assumed.

If you're also new to programming tools in general, start with **Part 0**. If you already know Git/Node, skip to **Part 1**.

---

## Part 0 — The absolute basics (skip if you know this)

### What you need installed

| Tool | What it is | How to check | How to install |
|------|-----------|--------------|----------------|
| **Git** | Version control — tracks every change, lets you undo | `git --version` | [git-scm.com](https://git-scm.com/downloads) |
| **Node.js** (v18+, we use v24) | Runs JavaScript outside the browser; needed to build this site | `node --version` | [nodejs.org](https://nodejs.org) (pick LTS) |
| **npm** | Comes with Node; installs project libraries | `npm --version` | Comes with Node |
| **A code editor** | Where you read/edit files | — | [VS Code](https://code.visualstudio.com/) (recommended) |
| **A terminal** | A text window where you type commands | — | Built into your OS; VS Code has one too (`` Ctrl+` ``) |

### The 5 terminal commands you'll use 95% of the time

```bash
pwd            # "where am I?" — shows current folder
ls             # "what's here?" — lists files in current folder
cd foldername  # move into a folder (cd .. goes back up)
git status     # "what changed?" — shows modified files
git log --oneline -5   # shows the last 5 changes (commits)
```

Don't memorize more than this for now. You'll pick up the rest as you go.

### Big idea: how teamwork works here (Git in 30 seconds)

1. The project lives on GitHub (the "remote").
2. You **clone** it once to get a local copy on your computer.
3. You edit files locally, then **commit** (save a snapshot) and **push** (upload) so others see it.
4. Important rule for now: **never push directly to `master`**. You'll make your changes on a **branch** (a separate workspace) and open a **Pull Request** (a "please review my changes" ticket). Someone will explain/review this with you the first time — just ask!

---

## Part 1 — What IS this project?

**BricksnBytes** ([bricksnbytes.de](https://bricksnbytes.de/)) offers programming courses for kids (LEGO robots, Minecraft, Scratch, Python...).

This repo is a **rebuild of that website** using modern tools:

| Piece | What it does | Analogy |
|-------|-------------|---------|
| **Astro** | Builds the website from components into plain HTML files | A bakery: ingredients (components) in, finished cakes (web pages) out |
| **TypeScript** | JavaScript with type-checking that catches mistakes early | Spell-check, but for code |
| **React** | Used *only* for interactive bits (e.g. menus, filters) | A power tool you take out only when needed |
| **Vitest** | Runs automated tests ("does the code still work?") | A robot that double-checks your work |
| **Docker + Nginx** | Packages the finished site so it can run on a server | A shipping container for the website |

Key concept: this is a **static site**. Running `npm run build` produces plain HTML/CSS files in `dist/`. There is no database, no login server, no backend — the "server" (Nginx) just hands those files to visitors. That makes it fast, cheap, and hard to break. 🎉

---

## Part 2 — Get it running (do this first!)

Follow these steps **in order**. The goal: see the website on your own computer.

```bash
# 1. Get the code (only needed once)
git clone <repo-url>     # ask your friend for the URL
cd bricksnbytes

# 2. Install libraries (only needed once, takes 1-2 min)
npm install

# 3. Start the development server
npm run dev
```

Now open your browser at **http://localhost:4321** — you should see the BricksnBytes homepage! 🎉

> **Stuck?** See [Part 7 — Troubleshooting](#part-7--when-something-breaks-troubleshooting) before panicking.

### The 4 commands you'll use daily

```bash
npm run dev      # start the site locally (auto-reloads when you save a file!)
npm run build    # build the production version into dist/
npm run preview  # preview that production build locally
npm run test     # run all automated tests once
```

**Workflow:** keep `npm run dev` running in one terminal, edit files in VS Code, watch the browser update automatically. That's the whole loop!

---

## Part 3 — How the project is organized

Think of the project like a house:

```
src/
├── pages/            🏠 Rooms — every file becomes a web page URL
│   ├── index.astro           →  /  (homepage)
│   └── programs/
│       ├── index.astro       →  /programs  (course overview)
│       └── [slug].astro      →  /programs/python-basics, /programs/... (one per course)
├── components/       🧱 LEGO bricks — reusable building blocks (Header, HeroSection, ProgramCard...)
├── layouts/          🖼️ Picture frames — wrap pages (Layout.astro adds header, footer, fonts to every page)
├── styles/           🎨 Paint — colors, fonts, spacing
│   ├── design-system.css     →  THE source of truth: all colors/spacings (var(--coral), var(--sp-md)...)
│   ├── global.css  +  layout.css
├── content/          📝 Content — course & age-group data in YAML files (easy to edit!)
│   ├── programs/*.yaml       →  one file per course (python-basics.yaml, lego-spike.yaml...)
│   └── age-groups/*.yaml     →  vorschule, grundschule, mittelschule, jugendkurse
public/               📦 Static files served as-is (images, favicon.ico)
dist/                 🏭 Factory output — generated by build, NEVER edit by hand
docs/  history/       📚 Notes & logs of past work
.design-backups/      🗄️ Old design drafts — look, don't touch
```

### How a page gets built (example: the homepage)

Open `src/pages/index.astro` — it's short! It says:

1. "Wrap me in `Layout`" (gives the page header/footer/fonts),
2. "Put `HeroSection`, then `AgeGroupSection`, then `WhySection`, then `CTASection` inside."

Each of those sections is a **component** in `src/components/`. Components are just files that describe *one piece* of the page. Pages compose components like LEGO. 🧱

### How course data works (YAML)

Course info (name, price, age group...) is **not** written inside the pages. It lives in small text files like `src/content/programs/python-basics.yaml`:

```yaml
name: Python Programmierung für Anfänger
slug: python-basics
price: 140
ageGroupDe: 10+ Jahre
...
```

Pages load these files automatically (`import.meta.glob(...)` — you'll see this line; it just means "load all YAML files in this folder"). To change a course's price or description, **you only edit the YAML** — no code needed!

> ⚠️ The allowed fields are defined in `src/content.config.ts`. If you add a brand-new field to a YAML file, you must also add it there (copy an existing line as template).

### How styling works

All colors and spacings are **design tokens** (named variables) in `src/styles/design-system.css`:

```css
var(--coral)    /* main brand color */
var(--sp-md)    /* standard spacing (16px) */
var(--r-md)     /* standard rounded corners */
```

Always reuse a token instead of inventing a color/spacing. Components also have their own small `<style>` block for styles that belong only to them.

---

## Part 4 — Your first changes (guided exercises)

Do these in order — each is a small, safe win. Keep `npm run dev` running and watch the browser!

### Exercise 1 — Change a course description (5 min, no code)

1. Open `src/content/programs/python-basics.yaml`.
2. Change the `description:` line, save.
3. Check the browser — the programs page updated! (`http://localhost:4321/programs`)

You just learned: content lives in YAML, the site rebuilds live. ✅

### Exercise 2 — Change a headline (10 min, tiny code)

1. Open `src/pages/index.astro`.
2. Find `headline="Programmieren für Kinder..."` and change the text, save.
3. Browser updates. Revert it back when done (or keep it — your call!).

You just learned: pages are components with props (the `headline="..."` bits are **props** = settings you pass into a component). ✅

### Exercise 3 — Add a new course (15 min)

1. Copy `src/content/programs/python-basics.yaml` to `src/content/programs/mein-kurs.yaml`.
2. Edit `name`, `slug` (lowercase, no spaces — must be unique!), `description`, `price`.
3. Visit `http://localhost:4321/programs/mein-kurs` — your course has its own page automatically!
4. Delete the file when done (or keep experimenting).

You just learned: **file-based routing** — new content files automatically become new pages. ✅

### Exercise 4 — Run the tests (5 min)

```bash
npm run test
```

Green checkmarks = everything works. If you broke something in the exercises above, a test might tell you. Run this before asking for a review!

---

## Part 5 — The rules (please read!)

These keep the project healthy. When in doubt, look at an existing file and copy its pattern.

1. **Every page uses `Layout`.** Start every new page with:
   ```astro
   import Layout from "../layouts/Layout.astro";
   <Layout title="Page Title" description="...">...</Layout>
   ```
2. **`.astro` components by default.** Only use React (`.tsx` files) when something needs to be *interactive* (clicking, typing, filtering). React components need a `client:` directive (e.g. `client:load`) or they won't activate.
3. **Use design tokens**, never hardcoded colors/spacings (`var(--coral)`, not `#ff6b5b`).
4. **German is the default language.** User-facing text in German; keep `de`/`en` pairs together (see `Header.astro`'s `lang` prop).
5. **Never edit `dist/`, `.astro/`, `node_modules/`** — they're auto-generated. Never touch `.design-backups/`.
6. **Before finishing work, run:** `npm run test` and `npx astro check` (type-check). Both must pass.
7. **Commit hygiene:** check `git status` / `git diff` before committing; commit only your intended files; never commit secrets/passwords.

---

## Part 6 — Concepts glossary (jargon buster)

| Word | Plain-English meaning |
|------|----------------------|
| **Component** | A reusable piece of a page (like a LEGO brick). Files in `src/components/`. |
| **Props** | Settings you pass into a component: `<CTASection headline="Hi!" />` — `headline` is a prop. |
| **Layout** | A wrapper giving every page the same frame (header, footer, fonts). |
| **Routing** | Which URL shows which file: `src/pages/programs/index.astro` → `/programs`. |
| **`[slug].astro`** | A *template* page: one file that generates many pages (one per course). `slug` is the course's URL-name. |
| **YAML** | A simple data format (`key: value`) used for course content. Indentation matters! |
| **Schema** (`content.config.ts`) | The rulebook saying which YAML fields are allowed. |
| **Token** | A named design value (`--coral`, `--sp-md`) so colors/spacings stay consistent. |
| **Static site** | Website made of pre-built files — no server code, no database. Fast & simple. |
| **Build** | Turning source files into the finished website (`dist/`). |
| **Test** | An automated check that something works (`*.test.ts` files, run with `npm run test`). |
| **Branch / PR** | A branch is your personal workspace copy; a Pull Request asks others to review & merge it. |
| **SSR** | Server-Side Rendering — we do **NOT** use this; everything is pre-built (static). Ignore it if you see it online. |

---

## Part 7 — When something breaks (troubleshooting)

Don't panic — try these in order. 90% of beginner problems are solved by step 1–2.

| Symptom | Fix |
|---------|-----|
| `npm run dev` says port in use | `npm run dev -- --port 3001` (use a different port) |
| Page looks broken / styles missing | Hard-refresh browser (`Ctrl+Shift+R`), or restart dev: stop (`Ctrl+C`), `rm -rf .astro`, `npm run dev` |
| `npm install` fails | Check Node version: `node --version` (need 18+). Delete `node_modules` + `package-lock.json`? **Ask first** — then `npm install` again |
| TypeScript errors | Run `npx astro check` to see them all; read the file + line it points to |
| YAML changes don't show | Check indentation (spaces, not tabs!) and that the field exists in `src/content.config.ts` |
| Test fails | Read the failure message — it names the file & line. Ask your friend with the message copied. |

**How to ask for help effectively** (your friend will love you for this):
1. What were you trying to do?
2. What command / file?
3. What happened (copy the exact error message)?
4. What did you already try?

---

## Part 8 — Where to go next

- 📖 **Reference docs:** `README.md` (quick commands), `AGENTS.md` (rules for contributors & AI assistants), `CLAUDE.md` (deep development guide).
- 🗂️ **Good files to read:** `src/pages/index.astro` (simplest page) → `src/components/ProgramCard.astro` (simple component) → `src/pages/programs/[slug].astro` (dynamic page) → `src/layouts/Layout.astro`.
- 🧪 **Learn by testing:** `npm run test -- --watch` re-runs tests on every save — great feedback loop.
- 🌐 **Learn the tools:** [Astro tutorial](https://docs.astro.build/en/tutorial/0-introduction/) (best first tutorial), [TypeScript for beginners](https://www.typescriptlang.org/docs/handbook/typescript-from-scratch.html).
- 🚀 **When ready:** add a real small improvement (fix a typo, improve a description, add a course field) and open your first Pull Request — ask your friend to walk you through it once, then it's easy forever.

Happy building! 🧱✨ If anything in this guide is confusing, tell your friend — that means the guide needs fixing, not you.

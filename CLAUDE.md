# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BricksnBytes Redesign is an Astro-based static site rebuild of [bricksnbytes.de](https://bricksnbytes.de/). This is a ground-up redesign following the new visual design created with Claude Design.

**Key Info:**
- **Stack**: Astro 6.4.2, TypeScript, React (optional), Vitest for testing
- **Output**: Static HTML (no server-side rendering)
- **Target Deployment**: Docker container (multi-stage build to Nginx Alpine)
- **Design Reference**: See `.design-backups/design-20260602/` for Claude Design outputs
- **Status**: Phase 1 complete (foundation, home page, programs listing, floating pill navigation with mega-menu & mobile drawer)

## Development Commands

### Setup & Installation

```bash
# Install Node dependencies
npm install

# If you encounter Node version issues:
# This project requires Node 18+
node --version
```

### Local Development

```bash
# Start development server (hot reload at http://localhost:3000)
npm run dev

# Build for production
npm run build

# Preview production build locally
npm run preview
```

### Testing

```bash
# Run all tests once
npm run test

# Run tests in watch mode (TDD workflow)
npm run test -- --watch

# Run a specific test file
npm run test -- src/layouts/Layout.test.ts
```

**Note**: Vitest uses default configuration from `package.json`. No explicit `vitest.config.ts` is needed.

### Code Quality

```bash
# Type check (TypeScript)
npx astro check

# No built-in linting yet; configure ESLint if needed
```

## Project Structure

```
bricksnbytes/
├── src/
│   ├── pages/              # File-based routing (Astro)
│   │   └── index.astro     # Homepage
│   ├── components/         # Reusable Astro/React components
│   ├── layouts/            # Page templates (e.g., Layout.astro)
│   ├── styles/             # Global & component stylesheets
│   ├── content/            # Content collections (markdown, etc.)
│   └── env.d.ts            # TypeScript environment types
├── public/                 # Static assets (images, fonts, favicon)
├── dist/                   # Production build output
├── astro.config.mjs        # Astro configuration
├── tsconfig.json           # TypeScript configuration
├── package.json            # Dependencies and scripts
├── Dockerfile              # Multi-stage Docker build
├── .github/workflows/      # CI/CD pipelines (to be configured)
└── .design-backups/        # Claude Design artifacts (navigation, design explorations)
```

## Architecture & Key Concepts

### YAML Loader Plugin

The project uses a custom Vite plugin (in `astro.config.mjs`) to import YAML files as JavaScript objects. This enables importing program data directly:

```astro
---
// Import single YAML file
import programData from '../content/programs/python-101.yaml';

// Import all YAML files as a glob
const programModules = import.meta.glob('../content/programs/*.yaml', { eager: true });
const programs = Object.values(programModules).map((m: any) => m.default);
---
```

**Key Details:**
- Relative paths (`.yaml`, `../yaml`) are resolved from the importer's directory
- Absolute paths are resolved from project root (`__dirname`)
- Returns parsed YAML as a JavaScript object (no further JSON parsing needed)

### File-Based Routing (Astro)

Files in `src/pages/` automatically become routes:
- `src/pages/index.astro` → `/`
- `src/pages/about.astro` → `/about`
- `src/pages/programs/index.astro` → `/programs`
- `src/pages/programs/[slug].astro` → `/programs/:slug` (dynamic route)

### Components

- **Astro Components** (`.astro`): Islands of interactivity, server-side rendering, zero JS by default
- **React Components** (`.tsx`): For interactive features; use `client:` directive to hydrate
  ```astro
  import MyReactComponent from '../components/MyReactComponent.tsx';
  <MyReactComponent client:load />
  ```

### Content Collections

The project uses Astro Content Collections for structured data (see `src/content.config.ts`). Program data is stored as YAML files in `src/content/programs/` and loaded via the YAML loader plugin.

### Language Support

Components support i18n via a `lang` prop (default: 'de'):

```astro
---
interface Props {
  lang?: 'de' | 'en';
}
const { lang = 'de' } = Astro.props;

const navItems = lang === 'de'
  ? [{ label: 'Über uns', href: '/uber-uns' }, ...]
  : [{ label: 'About', href: '/about' }, ...];
---
```

Use this pattern in Header and other components where multilingual support is needed. Global content files (e.g., navigation labels) should be stored in YAML with both `de` and `en` keys.

### Layouts

All pages should use the base `src/layouts/Layout.astro` for consistent header, footer, and meta tags. The layout accepts `title` and optional `description` props.

```astro
---
import Layout from "../layouts/Layout.astro";
---

<Layout title="Page Title" description="Optional meta description">
  <section>Page content here</section>
</Layout>
```

### Design Tokens

Design tokens (colors, spacing, typography) are defined in `src/styles/design-system.css` as CSS custom properties and referenced throughout components:

```css
:root {
  --color-coral: #ff6b35;
  --color-coral-soft: rgba(255, 107, 53, 0.1);
  --spacing-unit: 8px;
  --font-primary: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
```

Use these variables in component styles via `var(--color-coral)`, etc.

### Static Output

- **Configuration**: `astro.config.mjs` sets `output: 'static'` (no SSR)
- **Output Directory**: `dist/` after `npm run build`
- **Implication**: All pages are pre-rendered at build time; no runtime server logic

### TypeScript

- **Config**: `tsconfig.json` extends `astro/tsconfigs/strict`
- **Type Checking**: Run `npx astro check` before committing
- **IDE Support**: Install Astro extension in VS Code for `.astro` file support

## Design Integration

### Design Artifacts

Original design explorations (Claude Design output) are stored in `.design-backups/design-20260602/`:
- Navigation design iterations (nav.css, navalt.jsx, navb.jsx)
- Design canvas files (design-canvas.jsx)
- Design book and alternatives (HTML files for review)

### Implementing Designs

When implementing a design:
1. Review the relevant design file in `.design-backups/design-20260602/`
2. Create or update components in `src/components/`
3. Use the design as reference; extract styles into CSS/Astro component logic
4. Test responsiveness with `npm run dev` and inspect at different viewports

## Testing

### Test Setup

- **Framework**: Vitest (npm script in package.json, uses defaults)
- **Test Files**: Place in `src/**/*.test.ts` or `src/**/*.test.tsx`
- **Test Utilities**: Testing Library DOM/React available

### Example Test

```typescript
// src/layouts/Layout.test.ts
import { describe, it, expect } from 'vitest';

describe('Layout component', () => {
  it('should define a layout component', () => {
    expect(true).toBe(true);
  });

  it('should have required props', () => {
    const requiredProps = ['title'];
    expect(requiredProps).toContain('title');
  });
});
```

Write tests that verify component behavior. For components that accept props (like `Header` with `lang` prop), test different prop combinations. Use Vitest's `describe` and `it` for organization.

## Styling

### Global Styles

- Place in `src/styles/` and import in layouts:
  ```astro
  import '../styles/global.css';
  import '../styles/design-system.css';
  ```

### Component Styles

- Use `<style>` blocks within `.astro` or `.tsx` components for scoped styling
- Scoped styles are automatically prefixed and won't leak to other components
- Example:
  ```astro
  <div class="header-wrapper">Header content</div>
  
  <style>
    .header-wrapper {
      background: var(--color-coral);
      padding: var(--spacing-unit);
    }
  </style>
  ```

### CSS Variables & Design Tokens

Design tokens are defined in `src/styles/design-system.css` and include:
- Color palette (coral, neutral shades)
- Spacing units (multiples of 8px)
- Typography (font families, sizes)
- Animation keyframes (e.g., `bbpop` for pop-in effect)

## Deployment & Docker

### Local Docker Build

```bash
# Build Docker image
docker build -t bricksnbytes:latest .

# Run container locally on port 80
docker run -p 80:3000 bricksnbytes:latest
```

### Dockerfile Overview

The Dockerfile (copied from bumbleflies/beta) uses a multi-stage build:
1. **Node 20 builder stage**: Installs dependencies and builds the Astro site
2. **Nginx Alpine runtime stage**: Serves the static `dist/` folder

### CI/CD (GitHub Actions)

To be configured: Set up workflows in `.github/workflows/` to:
1. Build and test on push
2. Build Docker image and push to Docker Hub (tag: `bricksnbytes:latest`)
3. Auto-deploy to staging/production (via Watchtower or manual trigger)

Reference: `bumbleflies.github.io/.github/workflows/` for workflow patterns.

## Common Workflows

### Adding a New Page

1. Create file in `src/pages/` (e.g., `src/pages/about.astro`)
2. Import and use a layout:
   ```astro
   ---
   import Layout from "../layouts/Layout.astro";
   ---
   <Layout title="About Us" description="Learn about BricksnBytes">
     <h1>About Us</h1>
   </Layout>
   ```
3. Run `npm run dev` to test locally
4. Build: `npm run build` and verify `dist/` folder

### Adding a Component

1. Create `src/components/MyComponent.astro` (or `.tsx` for React)
2. Define props in the frontmatter:
   ```astro
   ---
   interface Props {
     title: string;
     items: string[];
     lang?: 'de' | 'en';
   }
   const { title, items, lang = 'de' } = Astro.props;
   ---
   <div class="component">{title}</div>
   
   <style>
     .component { /* scoped styles */ }
   </style>
   ```
3. Import and use in pages/layouts:
   ```astro
   import MyComponent from '../components/MyComponent.astro';
   <MyComponent title="Example" items={["a", "b"]} lang="de" />
   ```

### Adding Interactivity (React)

1. Create React component: `src/components/Counter.tsx`
2. Import in an Astro page with `client:` directive:
   ```astro
   import Counter from '../components/Counter.tsx';
   <Counter client:load />
   ```
3. Available directives: `client:load`, `client:visible`, `client:idle` (optimize hydration)

### Running Tests During Development

```bash
# Watch mode for TDD
npm run test -- --watch

# Run tests once before committing
npm run test
```

### Documenting Completed Features

When a significant feature is complete, create a summary in `history/YYYY-MM-DD_feature-name.md` documenting:
- Problem statement
- Solution overview
- Files modified
- Testing results
- Any breaking changes or deployment notes
- Verification commands

See `history/2026-06-03_floating-pill-navigation.md` for the pattern.

## Troubleshooting

**Port 3000 already in use**: Change with `npm run dev -- --port 3001`

**TypeScript errors in IDE**: Run `npx astro check` to see all errors, then fix or use `@ts-ignore` sparingly

**Styles not updating**: Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R) or clear `.astro/` cache: `rm -rf .astro && npm run dev`

**YAML imports not working**: Ensure the `astro.config.mjs` YAML loader plugin is properly configured. Check that the file path uses `.yaml` or `.yml` extension.

**Build fails with missing dependencies**: `rm -rf node_modules package-lock.json && npm install`

**Docker build fails**: Ensure Node 20+ is available. Check Dockerfile for build stage errors. Verify the `dist/` folder is created after `npm run build`.

## Reference

- **Astro Docs**: https://docs.astro.build
- **Vitest Docs**: https://vitest.dev
- **Design Files**: `.design-backups/design-20260602/`
- **Feature History**: `history/` directory contains documented completion reports

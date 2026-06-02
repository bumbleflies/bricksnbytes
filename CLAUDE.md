# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BricksnBytes Redesign is an Astro-based static site rebuild of [bricksnbytes.de](https://bricksnbytes.de/). This is a ground-up redesign following the new visual design created with Claude Design and shares the same architectural approach as `bumbleflies.github.io/beta`.

**Key Info:**
- **Stack**: Astro 6.4.2, TypeScript, React (optional), Vitest for testing
- **Output**: Static HTML (no server-side rendering)
- **Target Deployment**: Docker container (multi-stage build to Nginx Alpine)
- **Design Reference**: See `.design-backups/design-*` for Claude Design outputs (navigation alternatives, design explorations)

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
# Run tests with Vitest
npm run test

# Run tests in watch mode
npm run test -- --watch

# Run a specific test file
npm run test -- src/components/MyComponent.test.ts
```

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

### File-Based Routing (Astro)

Files in `src/pages/` automatically become routes:
- `src/pages/index.astro` → `/`
- `src/pages/about.astro` → `/about`
- `src/pages/services/index.astro` → `/services`
- `src/pages/services/[id].astro` → `/services/:id` (dynamic route)

### Components

- **Astro Components** (`.astro`): Islands of interactivity, server-side rendering, zero JS by default
- **React Components** (`.tsx`): For interactive features; use `client:` directive to hydrate
  ```astro
  import MyReactComponent from '../components/MyReactComponent.tsx';
  <MyReactComponent client:load />
  ```

### Layouts

All pages should use a layout (e.g., `Layout.astro`) for consistent header, footer, and meta tags. The base `src/layouts/Layout.astro` accepts `title` and optional `description` props.

```astro
---
import Layout from "../layouts/Layout.astro";
---

<Layout title="Page Title" description="Optional meta description">
  <section>Page content here</section>
</Layout>
```

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

- **Framework**: Vitest (configured in package.json)
- **Test Files**: Place in `src/**/*.test.ts` or `src/**/*.test.tsx`
- **Test Utilities**: Testing Library DOM/React available

### Example Test

```typescript
// src/components/Button.test.ts
import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/dom';

describe('Button', () => {
  it('renders button with text', () => {
    const html = '<button>Click me</button>';
    const { container } = render(html);
    expect(container.querySelector('button')).toHaveTextContent('Click me');
  });
});
```

## Styling

### Global Styles

- Place in `src/styles/` and import in layouts:
  ```astro
  import '../styles/global.css';
  ```

### Component Styles

- Use `<style>` blocks within `.astro` or `.tsx` components for scoped styling
- Scoped styles are automatically prefixed and won't leak to other components

### CSS Variables & Design Tokens

To be implemented: Create `src/styles/tokens.css` with design tokens (colors, spacing, typography) referenced throughout components.

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

1. Create file in `src/pages/` (e.g., `src/pages/services.astro`)
2. Import and use a layout:
   ```astro
   ---
   import Layout from "../layouts/Layout.astro";
   ---
   <Layout title="Services">
     <h1>Our Services</h1>
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
   }
   const { title, items } = Astro.props;
   ---
   ```
3. Import and use in pages/layouts:
   ```astro
   import MyComponent from '../components/MyComponent.astro';
   <MyComponent title="Example" items={["a", "b"]} />
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

## Troubleshooting

**Port 3000 already in use**: Change with `npm run dev -- --port 3001`

**TypeScript errors in IDE**: Run `npx astro check` to see all errors, then fix or use `@ts-ignore` sparingly

**Styles not updating**: Hard refresh browser or clear `.astro/` cache: `rm -rf .astro`

**Build fails with missing dependencies**: `rm -rf node_modules package-lock.json && npm install`

**Docker build fails**: Ensure Node 20+ is available; check Dockerfile for build stage errors

## Next Steps

- [ ] Configure GitHub Actions workflows (build, test, Docker push)
- [ ] Set up content collections for dynamic pages (if needed)
- [ ] Implement design tokens CSS (colors, spacing, typography)
- [ ] Create navigation and footer components
- [ ] Migrate content from `.design-backups/` design reference
- [ ] Configure Docker deployment and staging/production environments
- [ ] Set up Watchtower auto-deployment (see bumbleflies/beta CLAUDE.md for pattern)

## Reference

- **Astro Docs**: https://docs.astro.build
- **Vitest Docs**: https://vitest.dev
- **Design Files**: `.design-backups/design-20260602/`
- **Sister Project**: `/home/cda/dev/infrastructure/bumbleflies/bumbleflies.github.io/beta` (reference architecture)

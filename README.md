# BricksnBytes Redesign

A modern Astro-based redesign of [bricksnbytes.de](https://bricksnbytes.de/).

## Quick Start

```bash
# Install dependencies
npm install

# Start development server (http://localhost:3000)
npm run dev

# Build for production
npm run build

# Run tests
npm run test
```

## Technology Stack

- **Astro 6.4.2** — Static site generator
- **TypeScript** — Type-safe development
- **React 19** — Optional interactive components
- **Vitest** — Testing framework
- **Docker** — Containerized deployment

## Project Structure

- `src/pages/` — File-based routing
- `src/components/` — Reusable components
- `src/layouts/` — Page templates
- `src/styles/` — Global styles
- `.design-backups/` — Design references and explorations

## Documentation

See [CLAUDE.md](./CLAUDE.md) for detailed development guidance, architecture, and common workflows.

## Design Reference

Original design artifacts are in `.design-backups/design-20260602/` (Claude Design output).

## Deployment

Docker images are built in CI and deployed to staging/production environments. See CLAUDE.md for Docker build and deployment workflow.

## Status

🚧 **Phase 1: Foundation** — Astro project structure, initial components, local development

### Next Phases
- Design system & component library
- Content migration
- CI/CD pipelines
- Docker deployment
- Production launch

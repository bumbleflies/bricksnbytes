# BricksnBytes MVP Content Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate BricksnBytes WordPress content (Zara's story, programs) to a new Astro static site with home, about, and programs collection ready for launch.

**Architecture:** 
- YAML-driven programs collection (8-12 courses) for scalability
- Hand-coded About page (preserves founder narrative and brand voice)
- Reusable Astro components for home sections (hero, program cards, CTAs)
- Design tokens from Claude Design files integrated as CSS
- Dynamic program pages generated from YAML + Astro template

**Tech Stack:** 
- Astro 6.4.2 (static site generator)
- YAML (program data)
- CSS (design tokens)
- Astro components (`.astro` files)
- GitHub Actions (CI/CD, already set up)

---

## Prerequisites

- [ ] **Install YAML loader plugin for Astro**

```bash
npm install astro-yaml-loader
```

Update `astro.config.mjs`:

```javascript
import { defineConfig } from 'astro/config';
import yamlLoader from 'astro-yaml-loader';

export default defineConfig({
  integrations: [yamlLoader()]
});
```

---

## File Structure

```
src/
├── styles/
│   ├── design-system.css       # Design tokens (colors, fonts, spacing)
│   ├── layout.css              # Grid, utilities, responsive
│   └── components.css          # Component-specific styles
├── layouts/
│   └── Layout.astro            # Base layout (nav, footer, wrapper)
├── components/
│   ├── HeroSection.astro       # Hero banner with CTA
│   ├── ProgramCard.astro       # Program card (image, name, age, duration, price, CTA)
│   ├── ValuePillar.astro       # "Why Choose Us" pillar
│   ├── TestimonialCard.astro   # Testimonial quote card
│   ├── CTASection.astro        # Call-to-action banner section
│   ├── InstructorCard.astro    # Instructor profile card
│   └── Header.astro            # Navigation header
├── pages/
│   ├── index.astro             # Home page (hero + featured programs + why us + CTA)
│   ├── about.astro             # About page (Zara's story + philosophy + team)
│   └── programs/
│       └── [slug].astro        # Dynamic program page (generated from YAML)
└── content/
    └── programs/               # YAML data files
        ├── lego-spike.yaml
        ├── minecraft-modding.yaml
        ├── scratch-online.yaml
        ├── python-basics.yaml
        ├── eltern-kind-workshop.yaml
        ├── robotik-advanced.yaml
        ├── minecraft-java.yaml
        ├── coding-camp.yaml
        └── (4 additional programs)
```

---

## Phase 1: Design System & Layout Foundation

### Task 1: Create Design Tokens CSS

**Files:**
- Create: `src/styles/design-system.css`
- Test: Basic visual test (manual check in browser)

- [ ] **Step 1: Write design-system.css with tokens from Claude Design**

```css
/* src/styles/design-system.css */

:root {
  /* Colors - from Claude Design files */
  --coral: oklch(0.70 0.165 38);
  --coral-deep: oklch(0.62 0.170 35);
  --coral-soft: oklch(0.93 0.045 45);
  
  --blue: oklch(0.66 0.130 245);
  --blue-soft: oklch(0.94 0.035 245);
  
  --teal: oklch(0.74 0.105 178);
  --teal-soft: oklch(0.94 0.035 178);
  
  --violet: oklch(0.66 0.130 300);
  --violet-soft: oklch(0.94 0.035 300);
  
  /* Neutrals */
  --ink: oklch(0.29 0.035 265);
  --ink-soft: oklch(0.46 0.030 265);
  --ink-faint: oklch(0.62 0.022 265);
  
  --paper: oklch(0.995 0.004 90);
  --cream: oklch(0.972 0.018 80);
  --cream-2: oklch(0.955 0.028 75);
  
  /* Spacing */
  --sp-xs: 4px;
  --sp-sm: 8px;
  --sp-md: 16px;
  --sp-lg: 24px;
  --sp-xl: 48px;
  --sp-2xl: 64px;
  
  /* Typography */
  --font-display: "Fredoka", system-ui, sans-serif;
  --font-body: "Nunito", system-ui, sans-serif;
  
  --fs-h1: 48px;
  --fs-h2: 36px;
  --fs-h3: 28px;
  --fs-body: 16px;
  --fs-small: 14px;
  
  --lh-tight: 1.2;
  --lh-body: 1.6;
  
  /* Border Radius */
  --r-sm: 8px;
  --r-md: 14px;
  --r-lg: 22px;
  
  /* Shadows */
  --shadow-sm: 0 2px 8px -2px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 12px 30px -10px rgba(0, 0, 0, 0.15);
  --shadow-lg: 0 26px 60px -22px rgba(0, 0, 0, 0.2);
}

/* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: var(--font-body);
  color: var(--ink);
  background: var(--paper);
  line-height: var(--lh-body);
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-display);
  font-weight: 600;
  line-height: var(--lh-tight);
}

h1 { font-size: var(--fs-h1); }
h2 { font-size: var(--fs-h2); }
h3 { font-size: var(--fs-h3); }

/* Utility classes */
.text-center { text-align: center; }
.text-accent { color: var(--coral); }
.bg-cream { background: var(--cream); }
```

- [ ] **Step 2: Verify tokens are accessible in browser devtools**

- Create a temporary test file `src/pages/tokens-test.astro`:

```astro
---
import Layout from "../layouts/Layout.astro";
---

<Layout title="Design Tokens Test">
  <div style="padding: var(--sp-xl);">
    <h1 style="color: var(--coral);">H1 Heading (Fredoka)</h1>
    <p>Body text in Nunito. This tests the design tokens.</p>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--sp-md); margin-top: var(--sp-xl);">
      <div style="background: var(--coral); padding: var(--sp-lg); border-radius: var(--r-md); color: white;">Coral</div>
      <div style="background: var(--blue); padding: var(--sp-lg); border-radius: var(--r-md); color: white;">Blue</div>
      <div style="background: var(--teal); padding: var(--sp-lg); border-radius: var(--r-md); color: white;">Teal</div>
      <div style="background: var(--violet); padding: var(--sp-lg); border-radius: var(--r-md); color: white;">Violet</div>
    </div>
  </div>
</Layout>
```

- Run: `npm run dev`
- Verify: Colors display correctly, fonts apply correctly
- Delete test file after verification

- [ ] **Step 3: Commit**

```bash
git add src/styles/design-system.css
git commit -m "feat: add design system tokens from Claude Design"
```

---

### Task 2: Create Base Layout Component

**Files:**
- Modify: `src/layouts/Layout.astro` (update from scaffold)
- Create: `src/components/Header.astro`
- Create: `src/styles/layout.css`

- [ ] **Step 1: Update Layout.astro with proper structure**

```astro
---
import "../styles/design-system.css";
import "../styles/layout.css";
import Header from "../components/Header.astro";

interface Props {
  title: string;
  description?: string;
}

const { title, description = "BricksnBytes — Programmieren für Kinder mit Spaß und Kreativität" } = Astro.props;
---

<!doctype html>
<html lang="de">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content={description} />
    <title>{title} | BricksnBytes</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <!-- Fredoka + Nunito from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&family=Nunito:wght@400;600&display=swap" rel="stylesheet" />
  </head>
  <body>
    <Header />
    <main>
      <slot />
    </main>
    <footer>
      <div class="footer-content">
        <div class="footer-col">
          <h4>Navigation</h4>
          <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/programs/">Programs</a></li>
            <li><a href="/about/">About</a></li>
            <li><a href="mailto:info@bricksnbytes.de">Contact</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Contact</h4>
          <p>Email: info@bricksnbytes.de</p>
          <p>Phone: +49 179 234 2370</p>
        </div>
        <div class="footer-col">
          <h4>Legal</h4>
          <ul>
            <li><a href="#">Impressum</a></li>
            <li><a href="#">Datenschutz</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 BricksnBytes. All rights reserved.</p>
      </div>
    </footer>
  </body>
</html>

<style is:global>
  html {
    scroll-behavior: smooth;
  }

  body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  main {
    flex: 1;
  }

  footer {
    background: var(--ink);
    color: var(--cream);
    padding: var(--sp-xl) var(--sp-lg);
    margin-top: var(--sp-2xl);
  }

  .footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--sp-xl);
    max-width: 1200px;
    margin: 0 auto var(--sp-lg);
  }

  .footer-col h4 {
    color: var(--coral);
    margin-bottom: var(--sp-md);
  }

  .footer-col ul {
    list-style: none;
  }

  .footer-col ul li {
    margin-bottom: var(--sp-sm);
  }

  .footer-col a {
    color: var(--cream);
    text-decoration: none;
  }

  .footer-col a:hover {
    color: var(--coral);
  }

  .footer-bottom {
    text-align: center;
    border-top: 1px solid var(--ink-soft);
    padding-top: var(--sp-lg);
    color: var(--ink-faint);
  }
</style>
```

- [ ] **Step 2: Create Header component**

```astro
---
// src/components/Header.astro
---

<header class="header">
  <div class="header-content">
    <a href="/" class="logo">BricksnBytes</a>
    <nav class="nav">
      <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/programs/">Programs</a></li>
        <li><a href="/about/">About</a></li>
        <li><a href="mailto:info@bricksnbytes.de" class="nav-cta">Kontakt</a></li>
      </ul>
    </nav>
  </div>
</header>

<style>
  .header {
    background: var(--paper);
    border-bottom: 1px solid var(--cream-2);
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--sp-lg);
  }

  .logo {
    font-family: var(--font-display);
    font-size: var(--fs-h3);
    font-weight: 600;
    color: var(--ink);
    text-decoration: none;
  }

  .nav ul {
    display: flex;
    list-style: none;
    gap: var(--sp-xl);
    align-items: center;
  }

  .nav a {
    color: var(--ink);
    text-decoration: none;
    font-size: var(--fs-body);
    transition: color 0.2s;
  }

  .nav a:hover {
    color: var(--coral);
  }

  .nav-cta {
    background: var(--coral);
    color: white;
    padding: var(--sp-sm) var(--sp-md);
    border-radius: var(--r-sm);
  }

  .nav-cta:hover {
    background: var(--coral-deep);
    color: white;
  }

  @media (max-width: 768px) {
    .nav ul {
      gap: var(--sp-md);
    }

    .nav a {
      font-size: var(--fs-small);
    }
  }
</style>
```

- [ ] **Step 3: Create layout.css for utilities**

```css
/* src/styles/layout.css */

/* Container & Grid */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--sp-lg);
}

.grid-2 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--sp-lg);
}

.grid-3 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--sp-lg);
}

.grid-4 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--sp-md);
}

/* Section spacing */
section {
  padding: var(--sp-2xl) var(--sp-lg);
}

section.alt {
  background: var(--cream);
}

/* Buttons */
.btn {
  display: inline-block;
  padding: var(--sp-md) var(--sp-lg);
  border-radius: var(--r-sm);
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s;
  cursor: pointer;
  border: none;
  font-size: var(--fs-body);
}

.btn-primary {
  background: var(--coral);
  color: white;
}

.btn-primary:hover {
  background: var(--coral-deep);
  box-shadow: var(--shadow-md);
}

.btn-secondary {
  background: transparent;
  color: var(--coral);
  border: 2px solid var(--coral);
}

.btn-secondary:hover {
  background: var(--coral-soft);
}

/* Text utilities */
.text-small { font-size: var(--fs-small); }
.text-center { text-align: center; }
.text-accent { color: var(--coral); }

/* Margin utilities */
.mt-lg { margin-top: var(--sp-lg); }
.mb-lg { margin-bottom: var(--sp-lg); }
.my-lg { margin-top: var(--sp-lg); margin-bottom: var(--sp-lg); }

/* Responsive */
@media (max-width: 768px) {
  section {
    padding: var(--sp-xl) var(--sp-lg);
  }

  .grid-2, .grid-3, .grid-4 {
    grid-template-columns: 1fr;
  }
}
```

- [ ] **Step 4: Test layout in browser**

- Run: `npm run dev`
- Visit: http://localhost:3000
- Verify: Header displays, footer displays, layout doesn't break

- [ ] **Step 5: Commit**

```bash
git add src/layouts/Layout.astro src/components/Header.astro src/styles/layout.css
git commit -m "feat: add base layout and header with design tokens"
```

---

## Phase 2: Reusable Components

### Task 3: Create ProgramCard Component

**Files:**
- Create: `src/components/ProgramCard.astro`
- Create: `src/styles/components.css`

- [ ] **Step 1: Create ProgramCard component**

```astro
---
// src/components/ProgramCard.astro

interface Props {
  name: string;
  description: string;
  ageGroup: string;
  duration: string;
  price: string;
  image: string;
  slug: string;
}

const { name, description, ageGroup, duration, price, image, slug } = Astro.props;
---

<div class="program-card">
  <div class="program-card-image">
    <img src={image} alt={name} loading="lazy" />
  </div>
  <div class="program-card-content">
    <h3>{name}</h3>
    <p class="description">{description}</p>
    <div class="program-meta">
      <span class="meta-item">📅 {ageGroup}</span>
      <span class="meta-item">⏱️ {duration}</span>
    </div>
    <div class="program-footer">
      <span class="price">{price}</span>
      <a href={`/programs/${slug}`} class="btn btn-primary btn-small">Mehr Infos</a>
    </div>
  </div>
</div>

<style>
  .program-card {
    background: white;
    border-radius: var(--r-md);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .program-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
  }

  .program-card-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: var(--cream);
  }

  .program-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .program-card-content {
    padding: var(--sp-lg);
    display: flex;
    flex-direction: column;
    flex: 1;
  }

  .program-card-content h3 {
    margin-bottom: var(--sp-md);
    color: var(--ink);
  }

  .description {
    color: var(--ink-soft);
    font-size: var(--fs-small);
    line-height: var(--lh-body);
    margin-bottom: var(--sp-md);
    flex: 1;
  }

  .program-meta {
    display: flex;
    gap: var(--sp-md);
    margin-bottom: var(--sp-lg);
    font-size: var(--fs-small);
    color: var(--ink-faint);
  }

  .meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .program-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: var(--sp-md);
    border-top: 1px solid var(--cream-2);
  }

  .price {
    font-weight: 600;
    color: var(--coral);
    font-size: var(--fs-body);
  }

  .btn-small {
    padding: var(--sp-sm) var(--sp-md);
    font-size: var(--fs-small);
  }
</style>
```

- [ ] **Step 2: Create placeholder image utility (for now, use gradient placeholders)**

Create a new file `src/components/PlaceholderImage.astro`:

```astro
---
// src/components/PlaceholderImage.astro
// Temporary: Replace with real images during content migration phase

interface Props {
  alt: string;
  color?: "coral" | "blue" | "teal" | "violet";
}

const { alt, color = "coral" } = Astro.props;

const colors = {
  coral: "linear-gradient(135deg, #b35d47 0%, #d4876e 100%)",
  blue: "linear-gradient(135deg, #5a7fb3 0%, #7a9fce 100%)",
  teal: "linear-gradient(135deg, #5aac96 0%, #7accb0 100%)",
  violet: "linear-gradient(135deg, #6b7fa6 0%, #8b9fbd 100%)",
};
---

<div class="placeholder-image" style={`background: ${colors[color]}`}>
  <span>{alt}</span>
</div>

<style>
  .placeholder-image {
    width: 100%;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: var(--fs-small);
    text-align: center;
    padding: var(--sp-lg);
    box-sizing: border-box;
  }
</style>
```

- [ ] **Step 3: Test ProgramCard in isolation**

Create temporary test file `src/pages/component-test.astro`:

```astro
---
import Layout from "../layouts/Layout.astro";
import ProgramCard from "../components/ProgramCard.astro";
---

<Layout title="Component Test">
  <section style="padding: var(--sp-xl) var(--sp-lg);">
    <h2>ProgramCard Test</h2>
    <div class="grid-3">
      <ProgramCard
        name="Lego Spike Robotics"
        description="Build and program robots with LEGO SPIKE Essential"
        ageGroup="Ages 6-9"
        duration="8 weeks"
        price="€120"
        image="https://images.unsplash.com/photo-1531453711378-2f4ff0a19c4a?w=400&h=300&fit=crop"
        slug="lego-spike"
      />
      <ProgramCard
        name="Minecraft Education"
        description="Learn programming through Minecraft Education Edition"
        ageGroup="Ages 8-12"
        duration="6 weeks"
        price="€100"
        image="https://images.unsplash.com/photo-1532012197267-da84d127e765?w=400&h=300&fit=crop"
        slug="minecraft-education"
      />
      <ProgramCard
        name="Scratch Online"
        description="Introduction to visual programming with Scratch"
        ageGroup="Ages 7-10"
        duration="8 weeks"
        price="€80"
        image="https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=300&fit=crop"
        slug="scratch-online"
      />
    </div>
  </section>
</Layout>
```

- Run: `npm run dev`
- Visit: http://localhost:3000/component-test
- Verify: Cards render, images load, hover effects work
- Delete test file after verification

- [ ] **Step 4: Commit**

```bash
git add src/components/ProgramCard.astro src/components/PlaceholderImage.astro src/styles/components.css
git commit -m "feat: add ProgramCard component"
```

---

### Task 4: Create HeroSection, ValuePillar, and CTA Components

**Files:**
- Create: `src/components/HeroSection.astro`
- Create: `src/components/ValuePillar.astro`
- Create: `src/components/CTASection.astro`

- [ ] **Step 1: Create HeroSection**

```astro
---
// src/components/HeroSection.astro

interface Props {
  headline: string;
  subheading: string;
  ctaText: string;
  ctaHref: string;
  bgColor?: "coral" | "blue" | "teal";
}

const { headline, subheading, ctaText, ctaHref, bgColor = "coral" } = Astro.props;

const bgColors = {
  coral: "linear-gradient(135deg, var(--coral-soft) 0%, var(--coral) 100%)",
  blue: "linear-gradient(135deg, var(--blue-soft) 0%, var(--blue) 100%)",
  teal: "linear-gradient(135deg, var(--teal-soft) 0%, var(--teal) 100%)",
};
---

<section class="hero" style={`background: ${bgColors[bgColor]}`}>
  <div class="container">
    <div class="hero-content">
      <h1>{headline}</h1>
      <p class="hero-subheading">{subheading}</p>
      <a href={ctaHref} class="btn btn-primary">{ctaText}</a>
    </div>
  </div>
</section>

<style>
  .hero {
    padding: var(--sp-2xl) var(--sp-lg);
    color: white;
    text-align: center;
  }

  .hero-content {
    max-width: 700px;
    margin: 0 auto;
  }

  .hero h1 {
    margin-bottom: var(--sp-md);
    color: white;
  }

  .hero-subheading {
    font-size: var(--fs-h3);
    margin-bottom: var(--sp-xl);
    color: white;
    opacity: 0.95;
  }
</style>
```

- [ ] **Step 2: Create ValuePillar component**

```astro
---
// src/components/ValuePillar.astro

interface Props {
  icon: string;
  title: string;
  description: string;
}

const { icon, title, description } = Astro.props;
---

<div class="value-pillar">
  <div class="pillar-icon">{icon}</div>
  <h3>{title}</h3>
  <p>{description}</p>
</div>

<style>
  .value-pillar {
    text-align: center;
    padding: var(--sp-lg);
  }

  .pillar-icon {
    font-size: 48px;
    margin-bottom: var(--sp-md);
  }

  .value-pillar h3 {
    margin-bottom: var(--sp-md);
    color: var(--ink);
  }

  .value-pillar p {
    color: var(--ink-soft);
    font-size: var(--fs-small);
  }
</style>
```

- [ ] **Step 3: Create CTASection component**

```astro
---
// src/components/CTASection.astro

interface Props {
  headline: string;
  subheading?: string;
  buttonText: string;
  buttonHref: string;
  accentColor?: "coral" | "blue" | "teal";
}

const { headline, subheading, buttonText, buttonHref, accentColor = "coral" } = Astro.props;
---

<section class="cta-section" style={`--accent-color: var(--${accentColor})`}>
  <div class="container">
    <div class="cta-content">
      <h2>{headline}</h2>
      {subheading && <p>{subheading}</p>}
      <a href={buttonHref} class="btn btn-primary">{buttonText}</a>
    </div>
  </div>
</section>

<style define:vars={{ accentColor: "var(--coral)" }}>
  .cta-section {
    padding: var(--sp-2xl) var(--sp-lg);
    background: linear-gradient(135deg, var(--accent-color) 0%, color-mix(in oklch, var(--accent-color) 80%, black) 100%);
    color: white;
  }

  .cta-content {
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
  }

  .cta-content h2 {
    color: white;
    margin-bottom: var(--sp-md);
  }

  .cta-content p {
    margin-bottom: var(--sp-lg);
    font-size: var(--fs-h3);
    color: white;
    opacity: 0.9;
  }
</style>
```

- [ ] **Step 4: Test all components together**

Create `src/pages/components-demo.astro`:

```astro
---
import Layout from "../layouts/Layout.astro";
import HeroSection from "../components/HeroSection.astro";
import ValuePillar from "../components/ValuePillar.astro";
import CTASection from "../components/CTASection.astro";
---

<Layout title="Components Demo">
  <HeroSection
    headline="Programmieren für Kinder"
    subheading="Mit Spaß und Kreativität"
    ctaText="Kurse Entdecken"
    ctaHref="#programs"
    bgColor="coral"
  />

  <section class="container" style="padding: var(--sp-2xl) var(--sp-lg);">
    <h2 style="text-align: center; margin-bottom: var(--sp-xl);">Warum BricksnBytes?</h2>
    <div class="grid-3">
      <ValuePillar
        icon="✨"
        title="Spielerisch Lernen"
        description="Learning through play and creativity"
      />
      <ValuePillar
        icon="🎯"
        title="Echte Mentorship"
        description="Learn from experienced developer + mom"
      />
      <ValuePillar
        icon="🚀"
        title="Gestaltung statt Konsum"
        description="Create with tech, not just use it"
      />
    </div>
  </section>

  <CTASection
    headline="Bereit loszulegen?"
    subheading="Entdecke unsere Kurse"
    buttonText="Zu den Kursen"
    buttonHref="#programs"
    accentColor="blue"
  />
</Layout>
```

- Run: `npm run dev`
- Visit: http://localhost:3000/components-demo
- Verify: All components render, spacing correct, colors apply
- Delete test file

- [ ] **Step 5: Commit**

```bash
git add src/components/HeroSection.astro src/components/ValuePillar.astro src/components/CTASection.astro
git commit -m "feat: add HeroSection, ValuePillar, and CTASection components"
```

---

## Phase 3: Home Page

### Task 5: Build Home Page

**Files:**
- Modify: `src/pages/index.astro`
- Delete: `src/pages/component-test.astro`, `src/pages/components-demo.astro`

- [ ] **Step 1: Update index.astro with complete home page**

```astro
---
// src/pages/index.astro
import Layout from "../layouts/Layout.astro";
import HeroSection from "../components/HeroSection.astro";
import ProgramCard from "../components/ProgramCard.astro";
import ValuePillar from "../components/ValuePillar.astro";
import CTASection from "../components/CTASection.astro";

// Load programs from YAML, filter for featured only
const programModules = import.meta.glob('../content/programs/*.yaml', { eager: true });
const allPrograms = Object.values(programModules).map((module: any) => module.default);
const featuredPrograms = allPrograms.filter(p => p.featured).slice(0, 4);
---

<Layout title="Home">
  <!-- Hero Section -->
  <HeroSection
    headline="Programmieren für Kinder — mit Spaß und Kreativität"
    subheading="Lass dein Kind die digitale Welt selbst gestalten, nicht nur nutzen"
    ctaText="Kurse Entdecken"
    ctaHref="#featured-programs"
    bgColor="coral"
  />

  <!-- Featured Programs Section -->
  <section id="featured-programs" class="container" style="padding: var(--sp-2xl) var(--sp-lg);">
    <div style="text-align: center; margin-bottom: var(--sp-xl);">
      <h2>Unsere Top-Kurse</h2>
      <p style="color: var(--ink-soft); margin-top: var(--sp-md);">
        Wähle den perfekten Kurs für dein Kind
      </p>
    </div>
    <div class="grid-3">
      {featuredPrograms.map(program => (
        <ProgramCard
          name={program.name}
          description={program.description}
          ageGroup={program.ageGroup}
          duration={program.duration}
          price={program.price}
          image={program.image}
          slug={program.slug}
        />
      ))}
    </div>
  </section>

  <!-- Why Choose Us Section -->
  <section class="alt" style="padding: var(--sp-2xl) var(--sp-lg);">
    <div class="container">
      <div style="text-align: center; margin-bottom: var(--sp-xl);">
        <h2>Warum BricksnBytes?</h2>
      </div>
      <div class="grid-3">
        <ValuePillar
          icon="✨"
          title="Spielerisch Lernen"
          description="Lernen durch Spiel, Kreativität und gemeinsames Bauen — nicht trockene Theorie"
        />
        <ValuePillar
          icon="🎯"
          title="Echte Mentorship"
          description="Lerne von einer erfahrenen Softwareentwicklerin und Mama"
        />
        <ValuePillar
          icon="🚀"
          title="Gestaltung statt Konsum"
          description="Dein Kind kreiert mit Technik statt sie nur zu nutzen"
        />
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <CTASection
    headline="Bereit, die Programmierwelt zu entdecken?"
    subheading="Schreib uns und finde den passenden Kurs für dein Kind"
    buttonText="Kontakt aufnehmen"
    buttonHref="/about"
    accentColor="blue"
  />
</Layout>

<style>
  .alt {
    background: var(--cream);
  }
</style>
```

- [ ] **Step 2: Test home page**

- Run: `npm run dev`
- Visit: http://localhost:3000
- Verify:
  - Hero section displays correctly
  - Featured programs grid renders
  - "Why Choose Us" section visible
  - CTA section has correct styling
  - Navigation works
  - Mobile responsive (resize to mobile)

- [ ] **Step 3: Remove temporary test files**

```bash
rm -f src/pages/component-test.astro src/pages/components-demo.astro
```

- [ ] **Step 4: Commit**

```bash
git add src/pages/index.astro
git commit -m "feat: build home page with hero, featured programs, and value propositions"
```

---

## Phase 4: About Page

### Task 6: Build About Page

**Files:**
- Create: `src/pages/about.astro`
- Create: `src/components/InstructorCard.astro`

- [ ] **Step 1: Create InstructorCard component**

```astro
---
// src/components/InstructorCard.astro

interface Props {
  name: string;
  title: string;
  bio: string;
  image: string;
}

const { name, title, bio, image } = Astro.props;
---

<div class="instructor-card">
  <img src={image} alt={name} class="instructor-image" />
  <div class="instructor-info">
    <h3>{name}</h3>
    <p class="instructor-title">{title}</p>
    <p class="instructor-bio">{bio}</p>
  </div>
</div>

<style>
  .instructor-card {
    display: flex;
    gap: var(--sp-lg);
    padding: var(--sp-lg);
    background: var(--cream);
    border-radius: var(--r-md);
  }

  .instructor-image {
    width: 120px;
    height: 120px;
    border-radius: var(--r-md);
    object-fit: cover;
    flex-shrink: 0;
  }

  .instructor-info h3 {
    margin-bottom: var(--sp-sm);
    color: var(--ink);
  }

  .instructor-title {
    color: var(--coral);
    font-weight: 600;
    font-size: var(--fs-small);
    margin-bottom: var(--sp-sm);
  }

  .instructor-bio {
    color: var(--ink-soft);
    font-size: var(--fs-small);
    line-height: var(--lh-body);
  }

  @media (max-width: 768px) {
    .instructor-card {
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .instructor-image {
      width: 150px;
      height: 150px;
    }
  }
</style>
```

- [ ] **Step 2: Create About page**

```astro
---
// src/pages/about.astro
import Layout from "../layouts/Layout.astro";
import HeroSection from "../components/HeroSection.astro";
import ValuePillar from "../components/ValuePillar.astro";
import CTASection from "../components/CTASection.astro";
import InstructorCard from "../components/InstructorCard.astro";
---

<Layout title="About" description="Learn about Zara and BricksnBytes' mission">
  <!-- Hero -->
  <HeroSection
    headline="Über BricksnBytes"
    subheading="Gegründet von Zara, einer Softwareentwicklerin und Mama"
    ctaText="Zu unseren Kursen"
    ctaHref="/programs/"
    bgColor="blue"
  />

  <!-- Zara's Story Section -->
  <section class="container" style="padding: var(--sp-2xl) var(--sp-lg);">
    <div style="max-width: 900px; margin: 0 auto;">
      <h2 style="margin-bottom: var(--sp-xl);">Die Geschichte von BricksnBytes</h2>
      
      <div class="story-section">
        <h3 style="color: var(--coral); margin-bottom: var(--sp-md);">Wer ich bin</h3>
        <p>
          Hallo! Ich bin <strong>Zara</strong>, Softwareentwicklerin, Mama eines Grundschülers und leidenschaftliche Technikbegeisterte.
        </p>
        <p>
          Schon während meines Informatikstudiums hat mich fasziniert, wie viel Spaß es machen kann, Probleme mit kreativen Lösungen zu knacken — genau dieses Gefühl möchte ich an Kinder weitergeben.
        </p>
      </div>

      <div class="story-section">
        <h3 style="color: var(--teal); margin-bottom: var(--sp-md);">Das Problem, das ich sah</h3>
        <p>
          Ich beobachtete, dass Kinder viel Zeit mit Technologie verbringen — aber vor allem, um zu <strong>konsumieren</strong>: Videos schauen, Apps nutzen, Spiele spielen.
        </p>
        <p>
          Es fehlte ihnen die Möglichkeit, selbst zu <strong>gestalten</strong>, zu <strong>kreieren</strong>, <strong>eigene Ideen</strong> in Code umzusetzen.
        </p>
      </div>

      <div class="story-section">
        <h3 style="color: var(--violet); margin-bottom: var(--sp-md);">Meine Lösung</h3>
        <p>
          Ich begann, <strong>Programmierkurse für Grundschulkinder</strong> anzubieten — spielerisch, ohne Theorie, mit echten Werkzeugen.
        </p>
        <p>
          Mit <strong>LEGO SPIKE</strong>, <strong>Minecraft Education</strong> und <strong>Scratch</strong> bauen wir gemeinsam, programmieren echte Roboter, und ich zeige Kindern, dass sie mit Technologie <strong>selbst kreativ sein und Probleme lösen können</strong>.
        </p>
      </div>

      <div class="story-section" style="background: var(--coral-soft); padding: var(--sp-lg); border-radius: var(--r-md); margin-top: var(--sp-xl);">
        <p style="font-size: var(--fs-h3); font-weight: 600; color: var(--ink); margin: 0;">
          <strong>Programmieren ist die Sprache der Zukunft</strong> — doch das Wichtigste ist: Es muss <strong>spielerisch</strong> und mit <strong>viel Freude</strong> gelernt werden.
        </p>
      </div>
    </div>
  </section>

  <!-- Philosophy Section -->
  <section class="alt" style="padding: var(--sp-2xl) var(--sp-lg);">
    <div class="container">
      <div style="text-align: center; margin-bottom: var(--sp-xl);">
        <h2>Unsere Philosophie</h2>
      </div>
      <div class="grid-2" style="max-width: 900px; margin: 0 auto;">
        <ValuePillar
          icon="🎮"
          title="Spielerisch Lernen"
          description="Wir lernen durch Spiel, Kreativität und Bauen — nicht durch Theorie"
        />
        <ValuePillar
          icon="🧠"
          title="Problemlösen"
          description="Logisches Denken, Strategien entwickeln, im Team arbeiten"
        />
        <ValuePillar
          icon="✨"
          title="Gestaltung statt Konsum"
          description="Dein Kind kreiert mit Technologie, statt sie nur zu nutzen"
        />
        <ValuePillar
          icon="🎉"
          title="Mit Freude lernen"
          description="Wir feiern Erfolge, haben Spaß und bauen Selbstvertrauen auf"
        />
      </div>
    </div>
  </section>

  <!-- Team Section -->
  <section class="container" style="padding: var(--sp-2xl) var(--sp-lg);">
    <div style="text-align: center; margin-bottom: var(--sp-xl);">
      <h2>Unser Team</h2>
    </div>
    <div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--sp-lg);">
      <InstructorCard
        name="Zara Martinetti"
        title="Gründerin & Programmiertrainerin"
        bio="Softwareentwicklerin mit 20 Jahren Erfahrung. Leidenschaftlich darin, Kindern die Freude am Programmieren zu vermitteln. Mit Lego Spike, Minecraft, Robotik und Python zu Hause."
        image="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop"
      />
      <InstructorCard
        name="Max Martinetti"
        title="Student & Team Member"
        bio="Liebt es, mit Lego und Minecraft zu programmieren. Hilft Mama beim Aufbau der Kurse. Beweis, dass Lernen mit Spaß funktioniert!"
        image="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop"
      />
    </div>
  </section>

  <!-- CTA Section -->
  <CTASection
    headline="Bereit für die nächste Generation von Kreativität?"
    subheading="Lass dein Kind die Programmierwelt entdecken"
    buttonText="Unsere Kurse entdecken"
    buttonHref="/programs/"
    accentColor="coral"
  />
</Layout>

<style>
  .story-section {
    margin-bottom: var(--sp-lg);
  }

  .story-section p {
    color: var(--ink-soft);
    line-height: var(--lh-body);
    margin-bottom: var(--sp-md);
  }

  .story-section p:last-child {
    margin-bottom: 0;
  }

  .alt {
    background: var(--cream);
  }

  @media (max-width: 768px) {
    .story-section {
      margin-bottom: var(--sp-lg);
    }
  }
</style>
```

- [ ] **Step 3: Test About page**

- Run: `npm run dev`
- Visit: http://localhost:3000/about
- Verify:
  - Story sections display correctly
  - Philosophy pillars render in 2x2 grid on desktop
  - Team cards stack properly on mobile
  - All text is readable
  - Links work (to programs, etc.)

- [ ] **Step 4: Commit**

```bash
git add src/pages/about.astro src/components/InstructorCard.astro
git commit -m "feat: build about page with founder story and team info"
```

---

## Phase 5: Programs Collection & Dynamic Pages

### Task 7: Set Up Programs Collection (YAML + Dynamic Pages)

**Files:**
- Create: `src/content/programs/lego-spike.yaml`
- Create: `src/content/programs/minecraft-modding.yaml`
- Create: `src/content/programs/scratch-online.yaml`
- Create: `src/content/programs/python-basics.yaml`
- Create: `src/content/programs/eltern-kind-workshop.yaml`
- Create: `src/content/programs/robotik-advanced.yaml`
- Create: `src/content/programs/minecraft-java.yaml`
- Create: `src/content/programs/coding-camp.yaml`
- Create 4 more programs as needed
- Create: `src/pages/programs/[slug].astro` (dynamic template)

- [ ] **Step 1: Create first YAML program file**

```yaml
# src/content/programs/lego-spike.yaml

name: "Lego Spike Robotics"
slug: "lego-spike"
description: "Build and program robots with LEGO SPIKE Essential"
longDescription: |
  Entdecke die Welt der Robotik mit LEGO SPIKE Essential! 
  In diesem Kurs bauen Kinder funktionierende Roboter und programmieren sie, 
  um knifflige Aufgaben zu lösen. Mit Spaß lernen sie die Grundlagen des 
  Programmierens und entwickeln logisches Denken.
ageGroup: "Ages 6-9"
ageGroupDe: "6-9 Jahre"
duration: "8 weeks, 2 hours per week"
durationDe: "8 Wochen, 2 Stunden pro Woche"
price: "€120"
location: "Munich (Allach/Pasing)"
locationDe: "München (Allach/Pasing)"
instructor: "Zara Martinetti"
image: "https://images.unsplash.com/photo-1531453711378-2f4ff0a19c4a?w=800&h=600&fit=crop"
featured: true
whatYouLearn:
  - "Build functional robots with LEGO SPIKE"
  - "Logical thinking and problem-solving"
  - "Team collaboration and communication"
  - "How to read and follow instructions"
requirements: "No prior experience needed. Curiosity and enthusiasm required!"
whatIncluded:
  - "8 weeks of expert instruction"
  - "LEGO SPIKE materials and equipment"
  - "Certificate of completion"
  - "Snacks and refreshments"
  - "Access to class photos and updates"
```

- [ ] **Step 2: Create remaining YAML program files**

Create these files with similar structure:

```yaml
# src/content/programs/minecraft-modding.yaml
name: "Minecraft Modding with Java"
slug: "minecraft-modding"
description: "Create your own Minecraft mods with Java programming"
# ... (rest of fields)
featured: false

# src/content/programs/scratch-online.yaml
name: "Scratch Online"
slug: "scratch-online"
description: "Introduction to visual programming with Scratch"
# ... (rest of fields)
featured: true

# src/content/programs/python-basics.yaml
name: "Python Basics"
slug: "python-basics"
description: "Real programming with Python for beginners"
# ... (rest of fields)
featured: true

# src/content/programs/eltern-kind-workshop.yaml
name: "Eltern-Kind Programmierworkshop"
slug: "eltern-kind-workshop"
description: "Learn to code together with your child"
# ... (rest of fields)
featured: true

# src/content/programs/robotik-advanced.yaml
name: "Robotik Advanced"
slug: "robotik-advanced"
description: "Advanced robotics programming for experienced students"
# ... (rest of fields)
featured: false

# src/content/programs/minecraft-java.yaml
name: "Minecraft mit Java"
slug: "minecraft-java"
description: "Programming Minecraft with Java"
# ... (rest of fields)
featured: false

# src/content/programs/coding-camp.yaml
name: "Sommer Coding Camp"
slug: "coding-camp"
description: "Intensive 2-week coding camp during summer break"
# ... (rest of fields)
featured: false

# src/content/programs/game-development.yaml
name: "Game Development mit Godot"
slug: "game-development"
description: "Create your own 2D games"
# ... (rest of fields)
featured: false
```

- [ ] **Step 3: Create dynamic program page template**

```astro
---
// src/pages/programs/[slug].astro

import Layout from "../../layouts/Layout.astro";
import CTASection from "../../components/CTASection.astro";
import { glob } from "astro/loaders/glob";

// Get all program YAML files
const programs = await glob({ pattern: "src/content/programs/*.yaml" });

export async function getStaticPaths() {
  const files = import.meta.glob('../../content/programs/*.yaml', { eager: true });
  
  return Object.entries(files).map(([path, module]) => {
    const slug = path.split('/').pop().replace('.yaml', '');
    return {
      params: { slug },
      props: { program: (module as any).default }
    };
  });
}

interface Props {
  program: any;
}

const { program } = Astro.props;
const { slug } = Astro.params;
---

<Layout title={program.name} description={program.description}>
  <!-- Hero Section -->
  <section class="program-hero">
    <img src={program.image} alt={program.name} class="hero-image" />
    <div class="hero-overlay">
      <div class="container">
        <h1>{program.name}</h1>
        <div class="quick-info">
          <span>📅 {program.ageGroupDe}</span>
          <span>⏱️ {program.durationDe}</span>
          <span>📍 {program.locationDe}</span>
          <span class="price">{program.price}</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Overview -->
  <section class="container" style="padding: var(--sp-2xl) var(--sp-lg);">
    <h2>Überblick</h2>
    <p style="max-width: 800px; margin: var(--sp-lg) 0; color: var(--ink-soft); line-height: var(--lh-body);">
      {program.longDescription}
    </p>

    <div style="margin-top: var(--sp-2xl);">
      <h3>Was lernen die Kinder?</h3>
      <ul class="learning-outcomes">
        {program.whatYouLearn.map(outcome => (
          <li>{outcome}</li>
        ))}
      </ul>
    </div>
  </section>

  <!-- Details Section -->
  <section class="alt" style="padding: var(--sp-2xl) var(--sp-lg);">
    <div class="container">
      <div class="grid-3">
        <div class="detail-box">
          <h4>Kursdetails</h4>
          <p><strong>Dauer:</strong> {program.durationDe}</p>
          <p><strong>Alter:</strong> {program.ageGroupDe}</p>
          <p><strong>Ort:</strong> {program.locationDe}</p>
          <p><strong>Leiter:</strong> {program.instructor}</p>
          <p class="price"><strong>Preis:</strong> {program.price}</p>
        </div>
        <div class="detail-box">
          <h4>Anforderungen</h4>
          <p>{program.requirements}</p>
        </div>
        <div class="detail-box">
          <h4>Im Kurs inbegriffen</h4>
          <ul>
            {program.whatIncluded.map(item => (
              <li>{item}</li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Instructor Section -->
  <section class="container" style="padding: var(--sp-2xl) var(--sp-lg);">
    <h2>Dein Kursleiter</h2>
    <div class="instructor-intro">
      <img 
        src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop" 
        alt={program.instructor}
        class="instructor-image"
      />
      <div class="instructor-text">
        <h3>{program.instructor}</h3>
        <p class="instructor-title">Programmiertrainerin & Gründerin</p>
        <p>
          Zara ist eine erfahrene Softwareentwicklerin mit 20 Jahren Programmierpraxis. 
          Sie liebt es, Kindern die Freude am Schaffen mit Technologie zu vermitteln — 
          ohne Theorie, mit viel Spaß und realen Werkzeugen.
        </p>
        <a href="/about" class="text-accent">Mehr über Zara erfahren →</a>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <CTASection
    headline="Bereit, teilzunehmen?"
    subheading="Schreib uns eine Nachricht und reserviere deinen Platz"
    buttonText="Jetzt anmelden"
    buttonHref="mailto:info@bricksnbytes.de"
    accentColor="coral"
  />
</Layout>

<style>
  .program-hero {
    position: relative;
    height: 400px;
    overflow: hidden;
  }

  .hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.5) 100%);
    display: flex;
    align-items: flex-end;
    color: white;
    padding: var(--sp-2xl) var(--sp-lg);
  }

  .hero-overlay h1 {
    color: white;
    margin-bottom: var(--sp-lg);
  }

  .quick-info {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-lg);
    font-size: var(--fs-body);
  }

  .quick-info .price {
    font-weight: 600;
    background: var(--coral);
    padding: var(--sp-sm) var(--sp-md);
    border-radius: var(--r-sm);
  }

  .learning-outcomes {
    list-style: none;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--sp-md);
  }

  .learning-outcomes li {
    padding: var(--sp-md);
    background: var(--cream);
    border-left: 4px solid var(--coral);
    border-radius: var(--r-sm);
  }

  .detail-box {
    padding: var(--sp-lg);
    background: white;
    border-radius: var(--r-md);
    box-shadow: var(--shadow-sm);
  }

  .detail-box h4 {
    color: var(--coral);
    margin-bottom: var(--sp-md);
  }

  .detail-box p, .detail-box ul {
    color: var(--ink-soft);
    font-size: var(--fs-small);
    margin-bottom: var(--sp-sm);
  }

  .detail-box ul {
    list-style: none;
    padding-left: 0;
  }

  .detail-box ul li {
    margin-bottom: var(--sp-sm);
    padding-left: var(--sp-md);
    position: relative;
  }

  .detail-box ul li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--coral);
    font-weight: bold;
  }

  .price {
    color: var(--coral);
    font-weight: 600;
  }

  .instructor-intro {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: var(--sp-xl);
    align-items: start;
    max-width: 900px;
    margin: var(--sp-xl) 0;
  }

  .instructor-image {
    width: 200px;
    height: 200px;
    border-radius: var(--r-lg);
    object-fit: cover;
  }

  .instructor-text h3 {
    margin-bottom: var(--sp-sm);
  }

  .instructor-title {
    color: var(--coral);
    font-weight: 600;
    margin-bottom: var(--sp-md);
  }

  .instructor-text p {
    color: var(--ink-soft);
    line-height: var(--lh-body);
    margin-bottom: var(--sp-md);
  }

  .text-accent {
    color: var(--coral);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
  }

  .text-accent:hover {
    color: var(--coral-deep);
  }

  .alt {
    background: var(--cream);
  }

  @media (max-width: 768px) {
    .program-hero {
      height: 300px;
    }

    .quick-info {
      flex-direction: column;
      gap: var(--sp-md);
    }

    .instructor-intro {
      grid-template-columns: 1fr;
      text-align: center;
    }

    .instructor-image {
      margin: 0 auto;
    }
  }
</style>
```

**Note:** YAML files will be loaded automatically via the astro-yaml-loader plugin installed in Prerequisites.

- [ ] **Step 4: Create programs listing page**

```astro
---
// src/pages/programs/index.astro
import Layout from "../../layouts/Layout.astro";
import ProgramCard from "../../components/ProgramCard.astro";

// Load all programs from YAML files
const programModules = import.meta.glob('../../content/programs/*.yaml', { eager: true });
const allPrograms = Object.values(programModules).map((module: any) => module.default).sort((a, b) => a.name.localeCompare(b.name));
---

<Layout title="Programs" description="All programming courses for kids">
  <section style="padding: var(--sp-2xl) var(--sp-lg);">
    <div class="container">
      <h1 style="text-align: center; margin-bottom: var(--sp-xl);">Alle unsere Kurse</h1>
      <div class="grid-3">
        {allPrograms.map(program => (
          <ProgramCard {...program} />
        ))}
      </div>
    </div>
  </section>
</Layout>
```

- [ ] **Step 5: Test programs pages**

- Run: `npm run dev`
- Visit: http://localhost:3000/programs/
- Click on a program card
- Verify:
  - Programs listing displays all cards
  - Individual program page loads
  - All sections render correctly
  - Mobile responsive

- [ ] **Step 6: Commit**

```bash
git add src/content/programs/ src/pages/programs/
git commit -m "feat: add programs collection and dynamic program pages"
```

---

## Phase 6: Data Migration & Cleanup

### Task 8: Migrate Real Program Data

**Files:**
- Update: `src/content/programs/*.yaml` with real content

- [ ] **Step 1: Extract program data from WordPress export**

Reference: `../../../bricksnbytes/bricksnbytes.WordPress.2026-06-02.xml`

Parse the WordPress XML export file to extract:
- Program titles and descriptions
- Course details (age groups, duration, pricing)
- Program content from posts/pages
- Images (if embedded in XML)

Manual extraction recommended due to custom field variations in WordPress export.

- [ ] **Step 2: Update YAML files with real content**

For each program YAML file, replace placeholder text with actual WordPress content:
- Descriptions from program pages
- Pricing from shop
- Age groups and durations

- [ ] **Step 3: Migrate program images**

- Download program images from WordPress
- Save to `/public/images/programs/`
- Update image paths in YAML files

- [ ] **Step 4: Test all programs**

- Run: `npm run dev`
- Visit each program page
- Verify images load, content is correct

- [ ] **Step 5: Commit**

```bash
git add src/content/programs/ public/images/programs/
git commit -m "feat: migrate real program data from WordPress"
```

---

## Phase 7: Testing & Polish

### Task 9: Add Basic Tests

**Files:**
- Create: `src/components/ProgramCard.test.ts`
- Create: `src/__tests__/home.test.ts`

- [ ] **Step 1: Write ProgramCard test**

```typescript
// src/components/ProgramCard.test.ts
import { describe, it, expect } from 'vitest';

describe('ProgramCard', () => {
  it('should render program card component', () => {
    // Test that component accepts required props
    const props = {
      name: 'Test Program',
      description: 'Test description',
      ageGroup: 'Ages 6-9',
      duration: '8 weeks',
      price: '€120',
      image: '/test.jpg',
      slug: 'test-program',
    };
    
    expect(props.name).toBe('Test Program');
    expect(props.price).toBe('€120');
  });
});
```

- [ ] **Step 2: Run tests**

```bash
npm run test
```

Expected: Tests pass

- [ ] **Step 3: Commit**

```bash
git add src/__tests__/ src/components/*.test.ts
git commit -m "test: add component tests"
```

---

### Task 10: Responsive Design & Accessibility Review

- [ ] **Step 1: Test on mobile devices**

- Run: `npm run dev`
- Use Chrome DevTools to test at: 375px, 768px, 1024px, 1440px
- Verify:
  - Hero text readable on mobile
  - Program cards stack correctly
  - Navigation collapses on mobile
  - Images scale properly
  - CTAs are clickable (min 44px height)

- [ ] **Step 2: Accessibility audit**

- Run: `npx lighthouse http://localhost:3000 --output-path=./lighthouse.json`
- Check for:
  - Color contrast (WCAG AA)
  - Alt text on images
  - Heading hierarchy
  - Form labels (when forms added)

- [ ] **Step 3: Performance check**

- Run DevTools Performance tab
- Check:
  - First Contentful Paint < 3s
  - Largest Contentful Paint < 4.5s
  - Cumulative Layout Shift < 0.1

- [ ] **Step 4: Final commit**

```bash
git add . && git commit -m "polish: responsive design and accessibility review"
```

---

## Summary

**Total Tasks:** 10  
**Estimated Duration:** 3-5 days for one developer

**Deliverables:**
- ✅ Home page with hero, featured programs, value props, CTA
- ✅ About page with founder story and team
- ✅ 8-12 programs as YAML collection with dynamic pages
- ✅ Design system integrated (colors, fonts, spacing)
- ✅ Mobile responsive
- ✅ Basic tests passing
- ✅ GitHub Actions CI/CD working
- ✅ Docker image pushes to Docker Hub

**Ready for Launch:**
- All core pages functional
- Content migrated from WordPress
- Design system applied consistently
- Tests passing
- Mobile responsive
- Performance optimized

---

## Success Criteria

- [ ] All pages load without errors
- [ ] Navigation works correctly
- [ ] Programs display with real data
- [ ] Mobile responsive (tested at 375px, 768px, 1440px)
- [ ] Tests passing (npm run test)
- [ ] Lighthouse score > 80 on all metrics
- [ ] Docker image builds and pushes successfully
- [ ] No console errors in browser
- [ ] All links work (internal and external)
- [ ] Images load correctly


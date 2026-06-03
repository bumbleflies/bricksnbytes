# BricksnBytes MVP: Claude Design Validation

**Goal:** Validate that Claude design system is correctly adopted into Astro with a fully production-ready home page, reusable component pattern, and dynamic program pages.

**Scope:** Home page (hero + featured programs + value pillars + CTA), 6 programs extracted from WordPress, dynamic program page template, responsive (375px, 768px, 1440px).

**Timeline:** 2.5–3 hours

---

## Architecture

**Core Pattern:** Design tokens → Global CSS → Layout component → Reusable components → Pages

All styling flows from `design-system.css` (colors, fonts, spacing, shadows). Components are pure Astro with scoped styles. Pages compose components. Programs loaded from YAML files, enabling scalable future collections.

---

## Components

All components scoped to home page needs but designed for reuse on future pages:

1. **Header.astro** — Navigation with logo, menu links, contact CTA button
2. **HeroSection.astro** — Full-width banner (headline, subheading, CTA button) with background color option
3. **ProgramCard.astro** — Program preview card (image, title, age group, duration, price, "More Info" link)
4. **ValuePillar.astro** — Icon + title + description for "Why Choose Us" section
5. **CTASection.astro** — Call-to-action banner with gradient background
6. **Layout.astro** — Base layout wrapper (imports CSS, renders header/footer, provides slots)

Each component has:
- TypeScript props interface
- Scoped CSS using design tokens
- Responsive adjustments via media queries
- No external dependencies beyond Astro

---

## Data & Content

**Programs YAML Structure:**
```yaml
name: "Program Name"
slug: "program-slug"
description: "Short description"
longDescription: "Full description for detail page"
ageGroup: "Ages X-Y"
ageGroupDe: "X-Y Jahre"
duration: "8 weeks, 2 hours/week"
durationDe: "8 Wochen, 2 Stunden/Woche"
price: "€120"
location: "Munich (Location)"
locationDe: "München (Location)"
instructor: "Zara Martinetti"
image: "image-url"
featured: true/false
whatYouLearn:
  - "Learning outcome 1"
  - "Learning outcome 2"
requirements: "Requirements text"
whatIncluded:
  - "Included item 1"
```

**Programs to Extract (6 key programs from WordPress):**
1. Lego Spike Robotics (featured)
2. Minecraft Education (featured)
3. Scratch Online (featured)
4. Python Basics (featured)
5. Eltern-Kind Workshop (featured)
6. Robotik Advanced (not featured)

**Data Loading:**
- Home page: filters programs with `featured: true`, displays 4-5
- Programs listing page: displays all
- Dynamic page: loads individual program by slug

---

## Pages

**Home (index.astro)**
- HeroSection: "Programmieren für Kinder — mit Spaß und Kreativität"
- Featured programs grid (ProgramCard × 4–5)
- "Why Choose Us" section (ValuePillar × 3)
- CTASection: "Bereit, die Programmierwelt zu entdecken?"

**Programs Listing (programs/index.astro)**
- All programs in grid layout

**Program Detail ([slug].astro)**
- Program hero with image overlay
- Overview section
- "What You Learn" list
- Details grid (course info, requirements, included items)
- Instructor section
- CTA to contact

---

## Design System

**Colors (oklch):**
- Coral: primary accent (CTAs, highlights)
- Blue, Teal, Violet: secondary accents for sections
- Ink (dark), Cream (light): text and backgrounds
- Neutrals: soft grays for UI boundaries

**Typography:**
- Display: Fredoka (600 weight for headings)
- Body: Nunito (400/600 weights)
- Sizes: H1 (48px), H2 (36px), H3 (28px), Body (16px), Small (14px)
- Line heights: Tight (1.2) for headings, Body (1.6) for text

**Spacing Scale:**
- xs: 4px, sm: 8px, md: 16px, lg: 24px, xl: 48px, 2xl: 64px

**Component Styles:**
- Border radius: sm (8px), md (14px), lg (22px)
- Shadows: sm, md, lg (progressive depth)
- Buttons: primary (coral bg), secondary (coral border)
- Cards: white bg, shadow, hover lift effect

---

## Responsive Design

**Breakpoints:**
- Mobile: 375px (testing viewport)
- Tablet: 768px (testing viewport)
- Desktop: 1440px (testing viewport)

**Adjustments:**
- Hero text scales down on mobile
- Grid layouts switch to single column below 768px
- Navigation adapts (full nav on desktop, simplified on mobile)
- Spacing reduces on mobile (xl → lg, lg → md)
- Images scale with container

---

## Validation

**Success Criteria:**
- [ ] Home page renders all sections (hero, featured programs, value pillars, CTA)
- [ ] All design tokens applied correctly (colors, fonts, spacing visible)
- [ ] Programs load from YAML (not hardcoded)
- [ ] Program card images display
- [ ] Dynamic program page works for multiple slugs
- [ ] Responsive test passes at 375px, 768px, 1440px
- [ ] Navigation links work (internal and external)
- [ ] No console errors
- [ ] Design system pattern proven for future pages

**Testing Flow:**
1. `npm run dev`
2. Visit home page, verify all sections render
3. Verify colors, fonts, spacing match design
4. Click program card → verify dynamic page loads
5. Resize to 375px, 768px → verify layout adapts
6. Verify program data loads from YAML (not inline)

---

## WordPress Extraction

**Source:** `../../../bricksnbytes/bricksnbytes.WordPress.2026-06-02.xml`

**Manual extraction process:**
1. Parse XML for program posts
2. Extract title, description, pricing, age groups, duration
3. Find images (from post content or media)
4. Create YAML files with extracted content
5. Ensure `featured` flag set appropriately
6. Test that YAML parses correctly in Astro

**Assumption:** WordPress export contains structured program data accessible via post metadata or custom fields.

---

## File Structure

```
src/
├── styles/
│   ├── design-system.css      # All design tokens
│   ├── layout.css             # Grid, utilities, buttons
│   └── components.css         # Component-specific (optional)
├── layouts/
│   └── Layout.astro           # Base wrapper
├── components/
│   ├── Header.astro
│   ├── HeroSection.astro
│   ├── ProgramCard.astro
│   ├── ValuePillar.astro
│   ├── CTASection.astro
│   └── PlaceholderImage.astro
├── pages/
│   ├── index.astro            # Home
│   └── programs/
│       ├── index.astro        # All programs
│       └── [slug].astro       # Program detail
└── content/
    └── programs/              # 6 YAML files
```

---

## Implementation Order

1. Install YAML loader plugin
2. Build design-system.css with all tokens
3. Build Layout + Header
4. Extract programs from WordPress → create YAML files
5. Build components (Hero, ProgramCard, ValuePillar, CTA)
6. Assemble home page
7. Build program detail template + listing page
8. Test responsive + validate design tokens

---

## Known Unknowns

- WordPress export structure (will discover during extraction)
- Astro YAML loader plugin compatibility (standard setup assumed)
- Image hosting (placeholder URLs vs. local paths — will use remote URLs for MVP)

---

## Success Definition

Home page fully styled and functional, proving Claude design system works in Astro. Program pages demonstrate reusable pattern. All design tokens applied consistently. Ready to scale to full site.

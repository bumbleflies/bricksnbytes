# BricksnBytes Content Migration Design
**Date:** 2026-06-03  
**Scope:** MVP Phase 1 — Home, About, Programs  
**Status:** Design Phase

---

## Executive Summary

This design specifies how to migrate BricksnBytes WordPress content (74 pages, 221 images) to a new Astro-based static site. The MVP focuses on three core areas:

1. **Home Page** — Hero + featured programs + value proposition + CTA
2. **About Page** — Zara's founder story + philosophy + team snapshot
3. **Programs** — Dynamic YAML-driven collection of coding courses for kids

**Migration Strategy:** YAML-driven programs (scalable, data-driven) + hand-coded About page (preserves founder narrative and brand voice).

---

## Current WordPress Content Analysis

### Inventory
- **Total items:** 295 (74 published pages + 221 attachments)
- **Page types:**
  - Programs/Courses: 15 pages (Lego Spike, Minecraft, Scratch, etc.)
  - Content/Blog: 23 pages (blog, events, galleries, news)
  - Company Info: 8 pages (team, about, contact, locations)
  - E-commerce: 8 pages (shop, cart, checkout, wishlist)
  - Other: 20 pages (FAQs, pricing, individual team members)

### MVP Content Priority
**Phase 1 (In scope):**
- Home page (consolidated from multiple WordPress home templates)
- About page (Zara's story from "Über mich" + "Über uns" pages)
- Programs: ~8-12 core courses (extracted from program pages)

**Phase 2+ (Out of scope):**
- Blog/news section
- Event listings
- E-commerce shop
- Testimonials (can be added later)
- Full team directory

### Core Brand Narrative
**Founder:** Zara Martinetti  
- Background: Software developer, mother, tech enthusiast
- Mission: Make programming fun and creative for kids (not dry theory)
- Philosophy: "Programming is the language of the future, but must be learned with joy and creativity"
- Approach: Hands-on, playful learning with LEGO SPIKE, Minecraft, Robotics
- Team: Zara + son Max (also featured as a student/team member)

---

## Project Structure

```
bricksnbytes/
├── src/
│   ├── pages/
│   │   ├── index.astro              # Home page
│   │   ├── about.astro              # About page
│   │   └── programs/
│   │       └── [slug].astro         # Dynamic program pages
│   ├── components/
│   │   ├── ProgramCard.astro        # Reusable program card
│   │   ├── TestimonialCard.astro    # Testimonial card
│   │   ├── HeroSection.astro        # Hero section
│   │   ├── CTASection.astro         # Call-to-action
│   │   └── ValuePillar.astro        # Philosophy pillars
│   ├── layouts/
│   │   └── Layout.astro             # Base layout (nav/footer)
│   ├── content/
│   │   └── programs/                # YAML program definitions
│   │       ├── lego-spike.yaml
│   │       ├── minecraft-modding.yaml
│   │       ├── scratch-online.yaml
│   │       └── ... (8-12 programs total)
│   └── styles/
│       ├── design-system.css        # Design tokens (colors, fonts, spacing)
│       ├── layout.css               # Grid/layout utilities
│       └── components.css           # Component styles
├── public/
│   └── images/
│       ├── programs/                # Program images (migrated from WP)
│       └── team/                    # Team photos (Zara, Max)
└── docs/
    └── superpowers/specs/
        └── 2026-06-03-content-migration-design.md
```

---

## Content Structure

### 1. Home Page (`src/pages/index.astro`)

**Purpose:** Convert visitor intent to course enrollment

**Structure:**

#### 1.1 Hero Section
- **Headline:** "Programmieren für Kinder — mit Spaß und Kreativität"
- **Subheading:** Core philosophy from Zara's story ("Learn to create, not just consume")
- **CTA Button:** "Kurse Entdecken" (Discover Courses)
- **Visual:** Modern background using design tokens (subtle shape or gradient)
- **Tone:** Welcoming, playful, energetic

#### 1.2 Featured Programs Section
- **Layout:** 3-4 program cards in a grid
- **Data Source:** Programs marked `featured: true` in YAML
- **Card Content:**
  - Program image
  - Program name
  - Age group (e.g., "Ages 6-9")
  - Duration (e.g., "8 weeks")
  - CTA button: "Mehr Infos" (More Info)
- **Design:** Uses design tokens (coral/blue/teal accents)
- **Responsive:** 1 column mobile, 2-3 columns desktop

#### 1.3 Why Choose BricksnBytes Section
- **Layout:** 3-column grid with icons/accents
- **Content:** Three value pillars from Zara's philosophy
  1. **Spielerisch Lernen** (Playful Learning)
     - Description: Learning through play, creativity, building
  2. **Echte Mentorship** (Real Mentorship)
     - Description: Learn from experienced developer + mom
  3. **Gestaltung statt Konsum** (Create, Don't Consume)
     - Description: Kids create with tech, not just use it
- **Tone:** Inspirational, grounded in founder's values

#### 1.4 Testimonials Section (Optional for MVP)
- **Layout:** 2-3 testimonial cards
- **Content:** Short quotes from parents/students (extracted from WordPress later)
- **Design:** Clean cards with name/role attribution
- **Status:** Can be added in Phase 1b if time permits

#### 1.5 CTA Section
- **Headline:** "Bereit, die Programmierwelt zu entdecken?" (Ready to explore programming?)
- **Body:** Short call to action
- **Button:** "Kontaktiere uns" (Contact Us) → Links to About/Contact
- **Design:** Bold accent color (coral or blue)

---

### 2. About Page (`src/pages/about.astro`)

**Purpose:** Tell Zara's story and build trust with parents/students

**Structure:**

#### 2.1 Page Header
- **Headline:** "Über BricksnBytes — Und warum wir das tun"
- **Subheading:** "Gegründet von Zara, einer Softwareentwicklerin und Mutter"
- **Tone:** Personal, authentic

#### 2.2 Founder Story Section
- **Content:** Full narrative from WordPress "Über mich" pages:
  - Who Zara is (developer, mother, tech enthusiast)
  - The problem she saw (kids consuming, not creating)
  - Her solution (playful coding education)
  - Why it matters ("Programming is the future's language")
  - What drives her passion
- **Visual:** High-quality photo of Zara
- **Design:** Clean text layout with design token accents on key phrases
- **Tone:** Conversational, warm, authentic

#### 2.3 Our Philosophy Section
- **Layout:** 4-column grid (or 2-column on mobile)
- **Content:** Four core values extracted from Zara's story
  1. **Spielerisch Lernen** — Learning through play and creativity
  2. **Echte Probleme lösen** — Logic, strategy, teamwork
  3. **Gestaltung statt Konsum** — Create with tech, not just use it
  4. **Mit Freude lernen** — Joy and celebration in every lesson
- **Design:** Each pillar has icon + headline + short description
- **Accents:** Uses coral/blue/teal from design system

#### 2.4 Team Snapshot
- **Layout:** 2-column card layout
- **Content:**
  - **Zara Martinetti:** Photo + title (Gründerin/Founder) + 2-line bio
  - **Max Martinetti:** Photo + title (Student/Team Member) + 2-line bio + "What I love about learning to code"
- **Purpose:** Show personal, family-focused nature of company
- **Tone:** Friendly, approachable

#### 2.5 CTA Section
- **Headline:** "Bereit loszulegen?" (Ready to get started?)
- **Subheading:** "Schau dir unsere Kurse an oder schreib uns eine Nachricht"
- **Buttons:** 
  - "Kurse Entdecken" (Browse Courses) → Links to programs
  - "Kontakt" (Contact) → Links to contact form (future phase)

---

### 3. Programs Collection (`src/content/programs/*.yaml`)

**Purpose:** Scalable, maintainable course content storage

#### 3.1 YAML Structure (per program)

```yaml
# src/content/programs/lego-spike.yaml
name: "Lego Spike Robotics"
slug: "lego-spike"                    # Used in URL: /programs/lego-spike
description: "Build and program robots with LEGO SPIKE Essential"
longDescription: |                    # Multi-line for rich content
  Learn robotics fundamentals...
ageGroup: "Ages 6-9"
ageGroupDe: "6-9 Jahre"
duration: "8 weeks, 2 hours per week"
durationDe: "8 Wochen, 2 Stunden pro Woche"
price: "€120"
location: "Munich (Allach/Pasing)"
locationDe: "München (Allach/Pasing)"
instructor: "Zara Martinetti"
image: "/images/programs/lego-spike.jpg"
featured: true                        # Show on home page
whatYouLearn:                         # List of learning outcomes
  - "Build and program robots"
  - "Logical thinking"
  - "Problem-solving"
  - "Teamwork"
requirements: "No prior experience needed"
whatIncluded:                         # What's covered
  - "8 weeks of instruction"
  - "LEGO SPIKE materials"
  - "Certificate of completion"
  - "Snacks and refreshments"
```

#### 3.2 Program Page Template (`src/pages/programs/[slug].astro`)

**Route:** `/programs/lego-spike`

**Sections:**
1. Hero: Program image, title, quick-info bar (age, duration, location, price)
2. Overview: Description + learning outcomes
3. Details: 3-column layout (Duration/Schedule, Requirements, What's Included)
4. Instructor: Small card with instructor photo, title, bio (links to About)
5. CTA: "Jetzt anmelden" (Enroll Now) button + contact info

**Responsive:** Stacked on mobile, side-by-side on desktop

#### 3.3 Program Inventory (Phase 1)

Target 8-12 core programs for launch. Based on WordPress export, priority programs:

1. **Lego Spike Robotics** (Ages 6-9)
2. **Minecraft Education** (Ages 8-12)
3. **Scratch Online** (Ages 7-10)
4. **Python Programming** (Ages 10+)
5. **Eltern-Kind Workshop** (Parent+Child, Ages 5-8)
6. **Robotik Advanced** (Ages 10+)
7. **Minecraft Modding with Java** (Ages 12+)
8. **Coding Camp** (Summer, Ages 6-12)

*Additional programs can be added by creating new YAML files.*

---

## Design System Integration

### Colors (from Claude Design files)

**Primary Accents:**
- **Coral:** `oklch(0.70 0.165 38)` — Energy, action, calls-to-action
- **Blue:** `oklch(0.66 0.130 245)` — Trust, learning, stability
- **Teal:** `oklch(0.74 0.105 178)` — Creativity, growth
- **Violet:** `oklch(0.66 0.130 300)` — Imagination, play

**Neutrals:**
- **Ink:** `oklch(0.29 0.035 265)` — Text (dark, readable)
- **Paper:** `oklch(0.995 0.004 90)` — Background (warm white)
- **Cream:** `oklch(0.972 0.018 80)` — Cards, sections

### Typography

- **Display (Headings):** Fredoka
  - H1: 48px, bold
  - H2: 36px, semibold
  - H3: 28px, semibold
- **Body:** Nunito
  - Paragraph: 16px, regular (line-height: 1.6)
  - Small: 14px, regular
- **Hierarchy:** Clear contrast between sections

### Spacing

- **SM:** 8px
- **MD:** 16px
- **LG:** 24px
- **XL:** 48px

### Shadows & Borders

- **Radius:** 8px (small), 14px (medium), 22px (large)
- **Shadows:** Subtle (used on cards, hovers)

---

## Navigation & Layout

### Header/Navigation

**Structure:**
- Logo: "BricksnBytes" (text or icon)
- Menu items: Home | Programs | About | Kontakt
- CTA button: "Kurse Entdecken" (optional, if space)
- Mobile: Hamburger menu toggle

**Design:**
- Clean, minimal
- Coral accent on hover
- Sticky on scroll (optional)
- Responsive: Full nav on desktop, hamburger on mobile

### Footer

**Content:**
- Quick links: Home | Programs | About | Kontakt
- Contact info: Email (info@bricksnbytes.de), Phone (+49 179 234 2370)
- Social links (if applicable)
- Legal: Impressum & Datenschutz (link to WordPress export content)

**Design:**
- Dark background (ink color) with cream text
- 3-column layout (desktop), stacked (mobile)
- Copyright notice

---

## Content Migration Strategy

### Source Data

**Home Page:**
- Text: Synthesized from WordPress home templates + Zara's values
- Images: New hero image (can use stock or commission)

**About Page:**
- Text: Direct from WordPress "Über mich" + "Über uns" pages
- Photos: Zara's headshot (from WordPress), Max's photo
- Structure: Reorganized for narrative flow

**Programs:**
- Program names/descriptions: Extracted from WordPress program pages
- Pricing: From WordPress shop/product pages (or estimate)
- Images: Migrate from WordPress `/wp-content/uploads/`
- Instructor: Zara (all programs for MVP)

### Data Extraction Process

1. **Manual extraction** from WordPress XML export
2. **Clean HTML** (remove Elementor markup, shortcodes)
3. **Organize into YAML** for programs
4. **Create markdown** for About page sections
5. **Migrate images** to `/public/images/programs/` and `/public/images/team/`

### Testimonials (Optional for Phase 1)

If time permits, extract 2-3 testimonials from:
- WordPress blog comments
- Event pages with feedback
- Email reviews (if available)

Format as simple cards:
```yaml
quote: "Was vor Wochen unmöglich schien, ist jetzt Realität!"
author: "Anna (Mutter)"
program: "Minecraft Coding"
```

---

## What's NOT Included (Intentional Scope Limits)

### Phase 1 Exclusions

- ❌ **E-commerce/Shop** — WooCommerce pages skipped for MVP
- ❌ **Blog/News** — Content pages (23 pages) deferred to Phase 2
- ❌ **Event Listings** — Event pages/calendars deferred
- ❌ **Advanced Forms** — Registration forms (contact link only for now)
- ❌ **Testimonials Section** — Can be added Phase 1b if time permits
- ❌ **SEO/Analytics** — Added in Phase 2
- ❌ **Multilingual** — German-only for MVP (English later)
- ❌ **Individual Team Pages** — Team snapshot on About only

### Why These Are Deferred

- **Shop:** Not central to MVP narrative; can be added when ready to sell
- **Blog:** Takes time to migrate/format; not critical for launch
- **Events:** Low traffic content; can be replaced by contact form
- **Forms:** Contact link sufficient for MVP; proper forms Phase 2

---

## Implementation Approach

### Tech Stack

- **Framework:** Astro 6.4.2 (static site generator)
- **Content:** YAML (programs) + Markdown (if needed)
- **Styling:** CSS (with design tokens)
- **Components:** Astro components (`.astro`) + React (`.tsx`) optional
- **Build:** Docker (multi-stage: Node → Nginx Alpine)
- **Deployment:** Docker Hub (`bumblecode/bnb`) auto-pushed from GitHub Actions

### Development Workflow

1. **Create components** (ProgramCard, HeroSection, etc.)
2. **Set up design tokens** in CSS
3. **Build Home page** (fetch featured programs from YAML)
4. **Build About page** (hardcoded, rich content)
5. **Create program template** (`[slug].astro`)
6. **Add YAML programs** (8-12 core courses)
7. **Migrate images** to `/public/images/`
8. **Test responsive design** (mobile, tablet, desktop)
9. **Deploy** via GitHub Actions

### Milestones

- **Milestone 1:** Component library + design system setup
- **Milestone 2:** Home + About pages complete
- **Milestone 3:** Program collection + template working
- **Milestone 4:** Content migration + image optimization
- **Milestone 5:** QA + polish + deploy

---

## Success Criteria

✅ **MVP Launch Success:**
- Home page converts visitors to program interest
- About page tells Zara's story authentically
- All 8-12 core programs are browsable
- Mobile responsive (works on phone/tablet)
- Fast load times (Astro static site)
- Easy to add new programs (YAML-driven)

✅ **Content Quality:**
- No broken images or links
- Text is clean, no HTML artifacts
- Design tokens applied consistently
- Proper German/English (German for MVP)

✅ **Technical:**
- GitHub Actions CI/CD passing
- Docker image builds and deploys
- Tests passing (basic smoke tests)
- No console errors

---

## Open Questions / Deferred Decisions

1. **Testimonials:** Include in Phase 1 or defer to 1b? (Depends on time)
2. **Blog preview:** Show latest blog post on home? (Deferred to Phase 2)
3. **Newsletter signup:** Include on home? (Deferred to Phase 2)
4. **Locations:** Show "Classes in Munich (Allach/Pasing)" or simplify? (Simplify for MVP)
5. **Price display:** Show prices on program cards or "Contact for pricing"? (Show if consistent)

---

## Success Metrics (Post-Launch)

- Bounce rate on home page
- Click-through to program pages
- Contact form submissions
- Mobile traffic % (target: >50%)
- Page load time (target: <3s)

---

## References

- **WordPress Export:** `/home/cda/dev/bricksnbytes/.design-backups/bricksnbytes.WordPress.2026-06-02.xml`
- **Design Tokens:** `/home/cda/dev/bricksnbytes/.design-backups/design-20260602/nav.css`
- **Project Setup:** `bricksnbytes/` repository (GitHub: bumbleflies/bricksnbytes)
- **Astro Reference:** `bumbleflies.github.io/beta/` (sister project)

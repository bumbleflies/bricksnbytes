# Fixed-Width Container Layout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement fixed-width (1200px) content containers with full-width backgrounds across all homepage sections while standardizing spacing tokens.

**Architecture:** Two-layer pattern per section: outer full-width section with decorative elements, inner fixed-width container with centered content. Decorative elements remain absolutely positioned and can overflow container boundaries.

**Tech Stack:** Astro (components), CSS (layout and utilities)

---

## File Structure

**Modified Files:**
- `src/styles/layout.css` — Standardize token names (--spacing-* → --sp-*)
- `src/components/HeroSection.astro` — Wrap grid content in container
- `src/components/AgeGroupSection.astro` — Wrap section content in container
- `src/components/WhySection.astro` — Wrap pillars content in container
- `src/components/CTASection.astro` — Wrap CTA content in container

---

## Task 1: Standardize Spacing Tokens in layout.css

**Files:**
- Modify: `src/styles/layout.css` (lines 1–632)

Replace all `--spacing-*` token references with `--sp-*` throughout the file.

- [ ] **Step 1: Replace container padding tokens**

In `.container` classes (lines 8–28), change:
```css
/* BEFORE */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--spacing-lg);
}

/* AFTER */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--sp-lg);
}
```

Do the same for `.container-sm` and `.container-lg`.

- [ ] **Step 2: Replace grid and gap tokens**

In `.grid` utilities (lines 34–56), change all `gap: var(--spacing-lg)` to `gap: var(--sp-lg)`. Also update `.gap-sm`, `.gap-md`, `.gap-lg`, `.gap-xl` classes.

- [ ] **Step 3: Replace section spacing tokens**

In `.section` utilities (lines 107–121), change:
- `padding: var(--spacing-3xl) 0;` → `padding: var(--sp-3xl) 0;`
- `padding: var(--spacing-xl) 0;` → `padding: var(--sp-xl) 0;`
- etc.

- [ ] **Step 4: Replace button spacing tokens**

In `.btn` and related styles (lines 127–220), change all `var(--spacing-*)` to `var(--sp-*)`:
- `padding: var(--spacing-sm) var(--spacing-lg);` → `padding: var(--sp-sm) var(--sp-lg);`
- etc.

- [ ] **Step 5: Replace margin/padding utility tokens**

In margin utilities (lines 291–380), change all `.mt-*`, `.mb-*`, `.my-*` classes:
```css
/* BEFORE */
.mt-xs { margin-top: var(--spacing-xs); }
.mt-sm { margin-top: var(--spacing-sm); }

/* AFTER */
.mt-xs { margin-top: var(--sp-xs); }
.mt-sm { margin-top: var(--sp-sm); }
```

Repeat for all margin and padding utilities.

- [ ] **Step 6: Replace padding utility tokens**

In padding utilities (lines 386–432), change all `.p-*`, `.px-*`, `.py-*` classes from `--spacing-*` to `--sp-*`.

- [ ] **Step 7: Replace responsive container padding**

In media query (line 527–537), change:
```css
/* BEFORE */
@media (max-width: 768px) {
  .container {
    padding: 0 var(--spacing-md);
  }

/* AFTER */
@media (max-width: 768px) {
  .container {
    padding: 0 var(--sp-md);
  }
```

Repeat for `.container-sm` and `.container-lg`.

- [ ] **Step 8: Verify no remaining --spacing- references**

Run: `grep -n "var(--spacing-" src/styles/layout.css`

Expected: No matches (or only in comments)

- [ ] **Step 9: Commit token standardization**

```bash
git add src/styles/layout.css
git commit -m "fix: standardize spacing tokens from --spacing-* to --sp-* in layout.css"
```

---

## Task 2: Wrap HeroSection Content in Container

**Files:**
- Modify: `src/components/HeroSection.astro` (lines 27–62)

Wrap the `.hero-grid` in a `.container` div. Keep decorative elements outside.

- [ ] **Step 1: Review current structure**

Current structure (lines 27–62):
```astro
<section class="hero">
  <!-- Deco elements -->
  <div class="hero-blob hero-blob-1"></div>
  <div class="deco deco-oval ...></div>
  
  <div class="hero-grid">
    <!-- content -->
  </div>
</section>
```

- [ ] **Step 2: Add container wrapper**

Wrap `.hero-grid` in `.container`:
```astro
<section class="hero">
  <!-- Deco elements stay outside container -->
  <div class="hero-blob hero-blob-1"></div>
  <div class="hero-blob hero-blob-2"></div>
  <div class="deco deco-oval ...></div>
  <div class="deco deco-dot ...></div>
  <div class="deco deco-oval ...></div>
  <div class="deco deco-dash ...></div>

  <!-- Content inside fixed-width container -->
  <div class="container">
    <div class="hero-grid">
      <!-- Left column: text -->
      <div class="hero-left">
        {chips.length > 0 && (
          <div class="chip-row">
            {chips.map((chip) => (
              <span class="chip" style={`background: ${chipColors[chip.color]}`}>
                {chip.label}
              </span>
            ))}
          </div>
        )}
        <h1 class="hero-h1">{headline}</h1>
        <p class="hero-subheading">{subheading}</p>
        <a href={ctaHref} class="cta">{ctaText}</a>
      </div>

      <!-- Right column: image -->
      <div class="hero-right">
        <div class="hero-image">
          <img src={imageSrc} alt="Hero" />
        </div>
      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 3: Update .hero-grid styles**

Remove `max-width: 1200px; margin: 0 auto;` from `.hero-grid` (line 125–126) since the container now provides these:
```css
/* BEFORE */
.hero-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 24px;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
  position: relative;
  z-index: 2;
}

/* AFTER */
.hero-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 24px;
  align-items: center;
  position: relative;
  z-index: 2;
}
```

- [ ] **Step 4: Test responsive behavior**

Run: `npm run dev`

Check at breakpoints:
- Desktop (1440px): Content at 1200px, backgrounds extend full-width
- Tablet (768px): Content constrained, padding reduces to 16px
- Mobile (375px): Grid becomes single column

- [ ] **Step 5: Commit HeroSection changes**

```bash
git add src/components/HeroSection.astro
git commit -m "feat: wrap HeroSection content in fixed-width container"
```

---

## Task 3: Wrap AgeGroupSection Content in Container

**Files:**
- Modify: `src/components/AgeGroupSection.astro` (lines 26–62)

Wrap section content (heading, tag, cards) in `.container` div. Keep decorative elements outside.

- [ ] **Step 1: Review current structure**

Current structure:
```astro
<section class="age-group-section">
  <!-- Deco elements -->
  <div class="deco deco-oval ...></div>
  
  <!-- Content at full width -->
  <div class="section-tag">...</div>
  <h2 class="section-heading">...</h2>
  <div class="card-row">...</div>
  
  <!-- Wave at bottom -->
  <div class="coral-wave"></div>
</section>
```

- [ ] **Step 2: Add container wrapper**

Wrap section-tag, heading, and card-row in `.container`:
```astro
<section class="age-group-section">
  <!-- Deco elements outside container -->
  <div class="deco deco-oval deco-g1" style="..."></div>
  <div class="deco deco-dot deco-g2" style="..."></div>
  <div class="deco deco-oval deco-g3" style="..."></div>
  <div class="deco deco-oval deco-g4" style="..."></div>
  <div class="deco deco-dash deco-g5" style="..."></div>
  <div class="deco deco-dash deco-g6" style="..."></div>

  <!-- Content inside fixed-width container -->
  <div class="container">
    <div class="section-tag">Unsere Kurse</div>
    <h2 class="section-heading">Programmieren nach Altersgruppe</h2>

    <div class="card-row">
      {sortedGroups.map((group, idx) => {
        const color = group.data.color;
        const colors = colorMap[color];
        const radius = blobRadii[idx];
        return (
          <div class="bcard">
            <div class="bcard-blob" style={`background:${colors.blob};border-radius:${radius};`}></div>
            <div class="bcard-photo">
              <img src={group.data.image} alt={group.data.name} />
            </div>
            <div class="bcard-body">
              <div class="bcard-title">{group.data.name}</div>
              <div class="bcard-age" style={`color:${colors.age};`}>{group.data.ageRange}</div>
              <div class="bcard-desc">{group.data.description}</div>
            </div>
            <a href={group.data.href} class="bcard-cta" style={`background:${colors.cta};`}>→</a>
          </div>
        );
      })}
    </div>
  </div>

  <!-- Coral wave outside container, full width -->
  <div class="coral-wave"></div>
</section>
```

- [ ] **Step 3: Keep coral-wave outside container**

The wave is positioned at bottom and should remain full-width, so it stays outside the `.container`.

- [ ] **Step 4: Test responsive behavior**

Run: `npm run dev`

Check:
- Desktop: Cards grid at 1200px width
- Tablet: Cards grid with adjusted padding
- Mobile: Single column cards

- [ ] **Step 5: Commit AgeGroupSection changes**

```bash
git add src/components/AgeGroupSection.astro
git commit -m "feat: wrap AgeGroupSection content in fixed-width container"
```

---

## Task 4: Wrap WhySection Content in Container

**Files:**
- Modify: `src/components/WhySection.astro` (lines 27–59)

Wrap pillars content in `.container` div. Keep background blob outside.

- [ ] **Step 1: Review current structure**

Current structure:
```astro
<section class="why-section">
  <!-- Background blob -->
  <div class="pillars-bg-blob"></div>

  <div class="pillars-inner">
    <div class="inner-grid">
      <!-- Photo + content columns -->
    </div>
  </div>
</section>
```

- [ ] **Step 2: Add container wrapper**

Wrap the grid content in `.container`:
```astro
<section class="why-section">
  <!-- Background blob outside container -->
  <div class="pillars-bg-blob"></div>

  <!-- Content inside fixed-width container -->
  <div class="container">
    <div class="pillars-inner">
      <div class="inner-grid">
        <!-- Photo column -->
        <div class="photo-column">
          <div class="photo-wrap">
            <img src="/images/age-groups/why-photo.png" alt="BricksnBytes children coding" />
            <div class="photo-leaf"></div>
            <div class="photo-dot"></div>
          </div>
        </div>

        <!-- Content column -->
        <div class="content-column">
          <h2 class="section-heading">Was uns besonders macht</h2>
          <div class="pillars-list">
            {pillars.map((pillar, idx) => (
              <div class="pillar-item">
                <div class="pillar-num" style={`background:${colors[idx]};`}>{pillar.num}</div>
                <div class="pillar-text">
                  <h3>{pillar.title}</h3>
                  <p>{pillar.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 3: Test responsive behavior**

Run: `npm run dev`

Check:
- Desktop: Two-column layout at 1200px
- Tablet: Columns adjust width
- Mobile: Single column

- [ ] **Step 4: Commit WhySection changes**

```bash
git add src/components/WhySection.astro
git commit -m "feat: wrap WhySection content in fixed-width container"
```

---

## Task 5: Wrap CTASection Content in Container

**Files:**
- Modify: `src/components/CTASection.astro` (lines 12–28)

Wrap CTA content in `.container` div. Keep blob and decorative elements outside.

- [ ] **Step 1: Review current structure**

Current structure:
```astro
<section class="cta-section">
  <!-- Blob background -->
  <div class="cta-blob"></div>
  
  <!-- Deco elements -->
  <div class="cta-deco ...></div>
  
  <!-- Content -->
  <div class="cta-inner">
    <h2>...</h2>
    <p>...</p>
    <a>...</a>
  </div>
</section>
```

- [ ] **Step 2: Add container wrapper**

Wrap the `.cta-inner` in `.container`:
```astro
<section class="cta-section">
  <!-- Blob background outside container -->
  <div class="cta-blob"></div>

  <!-- Floating deco elements outside container -->
  <div class="cta-deco cta-star-1">✦</div>
  <div class="cta-deco cta-star-2">★</div>
  <div class="cta-deco cta-dot-1"></div>
  <div class="cta-deco cta-dot-2"></div>

  <!-- Content inside fixed-width container -->
  <div class="container">
    <div class="cta-inner">
      <h2>{headline}</h2>
      {subheading && <p>{subheading}</p>}
      <a href={buttonHref} class="cta-btn">{buttonText}</a>
    </div>
  </div>
</section>
```

- [ ] **Step 3: Verify styling**

The `.cta-inner` styles (positioning, text alignment, z-index) should remain unchanged. The container just adds width constraint.

- [ ] **Step 4: Test responsive behavior**

Run: `npm run dev`

Check:
- Desktop: CTA content centered within 1200px
- Tablet: Content reflows with reduced padding
- Mobile: Full-width with 16px padding

- [ ] **Step 5: Commit CTASection changes**

```bash
git add src/components/CTASection.astro
git commit -m "feat: wrap CTASection content in fixed-width container"
```

---

## Task 6: Visual Verification and Testing

**Files:**
- Test: Manual browser testing, no files modified

- [ ] **Step 1: Start dev server**

```bash
npm run dev
```

Expected: Dev server running at http://localhost:3000

- [ ] **Step 2: Test desktop (1440px)**

Open DevTools (F12), set viewport to 1440x900:
- Hero section: Text and image constrained to ~1200px, backgrounds extend full-width
- Age-group cards: Grid of 4 cards centered, backgrounds extend full-width
- Why section: Two-column layout centered at 1200px
- CTA section: Button and text centered at 1200px
- Decorative elements: Blobs and dots overflow container as expected

- [ ] **Step 3: Test tablet (768px)**

Set viewport to 768x1024:
- Container padding reduces from 24px to 16px per side
- Age-group cards: Grid becomes 2 columns
- Hero: Grid becomes 1 column (stacked)
- Why section: Columns stack to single column
- All content still readable and properly constrained

- [ ] **Step 4: Test mobile (375px)**

Set viewport to 375x667:
- Container padding: 16px per side
- Hero: Single column, image below text
- Age-group cards: Single column
- Why section: Single column, photo on top
- CTA: Full-width with padding
- All text readable, no overflow

- [ ] **Step 5: Verify no regressions**

Check that:
- Colors render correctly
- Animations/transitions still work
- Buttons are clickable
- Links navigate correctly
- Images load and scale properly

- [ ] **Step 6: Screenshot desktop view**

For documentation:
```bash
# Take screenshot at 1440px
# Save to project for reference
```

- [ ] **Step 7: Close dev server**

Stop the server with Ctrl+C

---

## Task 7: Final Verification and Commit

**Files:**
- Verify: All modified files

- [ ] **Step 1: Run type check**

```bash
npx astro check
```

Expected: No TypeScript errors

- [ ] **Step 2: Check for any console errors**

From Task 6, dev server logs should have no errors. If any appear, investigate and fix.

- [ ] **Step 3: View git diff summary**

```bash
git status
git log --oneline -7
```

Expected: 5 commits (one for each task)

- [ ] **Step 4: Final verification**

All tasks complete:
- ✓ Spacing tokens standardized (--spacing-* → --sp-*)
- ✓ HeroSection wrapped in container
- ✓ AgeGroupSection wrapped in container
- ✓ WhySection wrapped in container
- ✓ CTASection wrapped in container
- ✓ Visual testing passed at all breakpoints
- ✓ No regressions introduced

---

## Success Criteria

✅ All content constrained to 1200px max-width on desktop  
✅ Backgrounds extend full-width  
✅ Decorative elements overflow container boundaries  
✅ Responsive behavior unchanged (375px, 768px, 1440px)  
✅ Token naming consistent throughout layout.css  
✅ No visual regressions or broken styles  
✅ No TypeScript errors  
✅ All commits follow conventional commit format  

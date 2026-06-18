# Fixed-Width Container Layout Design

**Date**: 2026-06-03  
**Status**: Approved  
**Scope**: Homepage layout restructuring for consistent 1200px fixed-width content with full-width backgrounds

## Problem Statement

The current homepage layout stretches content full-width on desktop, lacking visual constraint and control. A fixed-width container pattern is needed to:
- Improve readability and visual hierarchy
- Maintain consistent horizontal spacing across sections
- Allow decorative elements to overflow for visual interest
- Establish a reusable pattern for future pages

## Design Overview

### Two-Layer Pattern

Each section uses a **two-layer structure**:

1. **Outer Layer (Section)**: Full-width element with background color, decorative elements, and vertical padding
2. **Inner Layer (Container)**: Fixed 1200px max-width, centered, with horizontal padding (var(--sp-lg))

### HTML Structure

```astro
<section class="section" style="background: var(--color)">
  <!-- Decorative elements (positioned absolutely, overflow allowed) -->
  <div class="deco deco-oval"></div>
  
  <!-- Content container (fixed-width) -->
  <div class="container">
    <!-- Text, cards, forms, etc. -->
  </div>
</section>
```

### Container Specification

- **Max-width**: 1200px
- **Horizontal Padding**: var(--sp-lg) (24px)
- **Centering**: margin: 0 auto
- **Responsive**: Media queries adjust padding to var(--sp-md) at ≤768px (existing)

## Implementation Details

### Token Standardization

**Current Issue**: `layout.css` uses `--spacing-*` tokens (undefined) while `design-system.css` defines `--sp-*` tokens.

**Fix**: Update `layout.css` to use `--sp-*` throughout:
- `--spacing-sm` → `--sp-sm` (8px)
- `--spacing-md` → `--sp-md` (16px)
- `--spacing-lg` → `--sp-lg` (24px)
- `--spacing-xl` → `--sp-xl` (32px)
- etc.

### Files to Modify

1. **src/styles/layout.css**
   - Update all `--spacing-*` references to `--sp-*`
   - Keep `.container` rules and media queries unchanged
   - Update `.section`, `.grid`, and utility classes to use `--sp-*`

2. **src/components/HeroSection.astro**
   - Wrap existing content in `<div class="container">`
   - Keep decorative elements outside container (absolute positioning)

3. **src/components/AgeGroupSection.astro**
   - Wrap grid/content in `<div class="container">`
   - Preserve existing section styling

4. **src/components/WhySection.astro**
   - Wrap grid/content in `<div class="container">`
   - Preserve existing section styling

5. **src/components/CTASection.astro**
   - Wrap button/text in `<div class="container">`
   - Preserve existing section styling

### Decorative Elements

Decorative elements (blobs, dots, shapes) positioned absolutely within sections:
- Can overflow the 1200px container
- Positioned relative to the section
- Do not affect content layout

Example:
```astro
<div class="deco deco-dot" style="position: absolute; right: 5%; top: 20px;"></div>
```

## Responsive Behavior

Existing media queries in `layout.css` already handle responsiveness:
- **≤768px**: Container padding reduces to var(--sp-md) (16px)
- **≥1200px**: Full desktop layout with 1200px max-width

No changes needed to breakpoints or responsive logic.

## Visual Consistency

- All homepage sections follow the same pattern
- Consistent whitespace on desktop (24px padding on each side)
- Backgrounds and decorative elements extend full-width for visual impact
- Content remains centered and readable

## Success Criteria

✓ All content constrained to 1200px max-width on desktop  
✓ Backgrounds extend full-width edge-to-edge  
✓ Decorative elements can overflow container  
✓ Responsive behavior unchanged (375px, 768px, 1440px breakpoints)  
✓ Token naming consistent (`--sp-*` throughout)  
✓ No visual regressions  
✓ All sections follow same pattern  

## Testing Plan

1. Visual testing at breakpoints: 375px, 768px, 1200px, 1440px
2. Verify background colors extend edge-to-edge
3. Verify decorative elements overflow properly
4. Verify responsive behavior works (padding changes at 768px)
5. Browser DevTools inspection of container widths

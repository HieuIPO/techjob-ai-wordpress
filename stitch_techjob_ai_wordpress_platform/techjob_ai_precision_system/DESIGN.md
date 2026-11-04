---
name: TechJob AI Precision System
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#3e4a3d'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#6e7b6c'
  outline-variant: '#bdcaba'
  surface-tint: '#006e2d'
  primary: '#006b2c'
  on-primary: '#ffffff'
  primary-container: '#00873a'
  on-primary-container: '#f7fff2'
  inverse-primary: '#62df7d'
  secondary: '#0051d5'
  on-secondary: '#ffffff'
  secondary-container: '#316bf3'
  on-secondary-container: '#fefcff'
  tertiary: '#712ae2'
  on-tertiary: '#ffffff'
  tertiary-container: '#8a4cfc'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#7ffc97'
  primary-fixed-dim: '#62df7d'
  on-primary-fixed: '#002109'
  on-primary-fixed-variant: '#005320'
  secondary-fixed: '#dbe1ff'
  secondary-fixed-dim: '#b4c5ff'
  on-secondary-fixed: '#00174b'
  on-secondary-fixed-variant: '#003ea8'
  tertiary-fixed: '#eaddff'
  tertiary-fixed-dim: '#d2bbff'
  on-tertiary-fixed: '#25005a'
  on-tertiary-fixed-variant: '#5a00c6'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
typography:
  display:
    fontFamily: Be Vietnam Pro
    fontSize: 3rem
    fontWeight: '700'
    lineHeight: '1.15'
    letterSpacing: -0.025em
  headline-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 2rem
    fontWeight: '700'
    lineHeight: '1.25'
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Be Vietnam Pro
    fontSize: 1.625rem
    fontWeight: '700'
    lineHeight: '1.3'
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Be Vietnam Pro
    fontSize: 1.5rem
    fontWeight: '600'
    lineHeight: '1.3'
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Be Vietnam Pro
    fontSize: 1.25rem
    fontWeight: '600'
    lineHeight: '1.35'
    letterSpacing: -0.01em
  title:
    fontFamily: Be Vietnam Pro
    fontSize: 1.125rem
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: -0.005em
  body-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 1rem
    fontWeight: '400'
    lineHeight: '1.6'
    letterSpacing: 0em
  body-md:
    fontFamily: Be Vietnam Pro
    fontSize: 0.875rem
    fontWeight: '400'
    lineHeight: '1.55'
    letterSpacing: 0em
  body-sm:
    fontFamily: Be Vietnam Pro
    fontSize: 0.75rem
    fontWeight: '400'
    lineHeight: '1.5'
    letterSpacing: 0.01em
  label-md:
    fontFamily: Inter
    fontSize: 0.875rem
    fontWeight: '500'
    lineHeight: '1.4'
    letterSpacing: 0.005em
  label-sm:
    fontFamily: Inter
    fontSize: 0.75rem
    fontWeight: '600'
    lineHeight: '1.35'
    letterSpacing: 0.02em
  numeric-data:
    fontFamily: Inter
    fontSize: 0.875rem
    fontWeight: '600'
    lineHeight: '1.25'
    letterSpacing: -0.01em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 0.75rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
---

## Brand & Style

This design system blends **Modern SaaS Precision** with **Minimalist High-Density Utility**, drawing direct cues from developer-centric tools like Linear, while retaining the familiar, high-conversion recruitment UX of TopCV and ITviec.

The system serves two distinct user personas through a strictly bifurcated visual architecture:
1. **Candidate & Public Workspace**: Fresh, encouraging, and growth-oriented. Driven by Emerald Green accents, luminous slate surfaces, and high-readability typography to reduce interview anxiety and showcase job listings crisply.
2. **Enterprise & Admin/Recruiter Workspace**: Analytical, dense, and objective. Driven by Royal Blue accents, structural data grids, and high-legibility tabular representations tailored for heavy ATS workflows and AI interview evaluations.

The aesthetic prioritizes high data density, micro-interactions with razor-sharp tolerances, 1px micro-borders (`#E2E8F0`), and crisp visual contrast adhering to WCAG 2.1 AA accessibility standards across all interactive surfaces.

## Colors

The color palette enforces explicit spatial boundaries between candidate and recruiter contexts, while retaining a unified semantic feedback system.

### Workspace Role Distinction
- **Candidate Workspace Primary**: `#16A34A` (Emerald 600) | Hover: `#15803D` (Emerald 700) | Active/Pressed: `#166534` (Emerald 800) | Subtle Fill: `#F0FDF4` (Emerald 50).
- **Admin & ATS Workspace Primary**: `#2563EB` (Royal Blue 600) | Hover: `#1D4ED8` (Royal Blue 700) | Active/Pressed: `#1E40AF` (Royal Blue 800) | Subtle Fill: `#EFF6FF` (Blue 50).

### Surface & Neutral Architecture
- **Canvas / App Background**: `#F8FAFC` (Slate 50).
- **Card / Raised Surfaces**: `#FFFFFF` (Pure White).
- **Subtle Surface / Inset Well**: `#F1F5F9` (Slate 100).
- **Border Default**: `#E2E8F0` (Slate 200).
- **Border Strong / Input Rule**: `#CBD5E1` (Slate 300).
- **Text Primary**: `#0F172A` (Slate 900 / Navy Black) — 4.5:1+ contrast against all light surfaces.
- **Text Secondary**: `#475569` (Slate 600).
- **Text Muted / Placeholder**: `#94A3B8` (Slate 400).

### Semantic & Recruitment Status Tokens
- **Submitted**: Text `#1D4ED8`, Background `#EFF6FF`, Border `#BFDBFE` (Royal Blue).
- **Reviewing**: Text `#B45309`, Background `#FFFBEB`, Border `#FDE68A` (Amber).
- **Interview / AI Assessing**: Text `#6D28D9`, Background `#F5F3FF`, Border `#DDD6FE` (Purple).
- **Passed / Qualified**: Text `#15803D`, Background `#F0FDF4`, Border `#BBF7D0` (Emerald).
- **Rejected / Disqualified**: Text `#B91C1C`, Background `#FEF2F2`, Border `#FECACA` (Rose Red).
- **Global Focus Ring**: 3px outline in `#60A5FA` (Blue 400) with a 1px offset in `#FFFFFF`.

## Typography

Typography prioritizes bilingual clarity (Vietnamese tone marks and technical English jargon) coupled with tabular alignment for compensation ranges, ATS pipeline metrics, and AI matching score percentages.

- **Editorial & Content (`Be Vietnam Pro`)**: Utilized for headlines, body copy, and job narrative text. It provides native, natural diacritic rendering for Vietnamese recruitment while retaining geometric precision.
- **Data, Navigation & System UI (`Inter`)**: Assigned to interactive components, data grids, status badges, buttons, form inputs, and ATS dashboards.
- **Tabular Numerics Rule**: For all compensation values (e.g., `$2,500 - $3,800`, `45.000.000 ₫`), candidate test scores (`98/100`), AI match confidence ratios, and table columns, apply CSS `font-variant-numeric: tabular-nums; font-feature-settings: "tnum" 1;`. This guarantees vertical alignment across multi-row candidate evaluation sheets.

## Layout & Spacing

The layout model is split based on task context:

- **Candidate Portal**: Fixed-fluid centered grid (max-width `1280px` on desktop) with a 12-column layout, 24px (`1.5rem`) gutters, and dynamic margins. Focuses on breathing room, readability of job descriptions, and structured stepper forms for AI interview prep.
- **Admin ATS Workspace**: Full-width fluid responsive canvas (max-width `1600px` or full screen width) with a persistent 260px collapsible sidebar, 16px horizontal gutters, and strict tabular density.

### Breakpoints & Fluid Reflow
- **Mobile (< 640px)**: 4-column layout, `1rem` outer canvas padding. ATS candidate rows transform into card-based swipeable summaries; tabular filters convert to bottom sheets.
- **Tablet (641px - 1024px)**: 8-column layout, `1.25rem` outer canvas padding. Admin navigation switches to an icon-rail bar (64px width).
- **Desktop (1025px+)**: 12-column layout, `2rem` outer margins. Multi-column interview boards (Kanban) and split-screen AI transcript analysis panels active.

## Elevation & Depth

Visual hierarchy is achieved through a combination of **low-contrast micro-outlines (`1px solid #E2E8F0`)** and **ambient, diffused drop shadows**. Sharp 3D effects are omitted in favor of clean, flat, high-efficiency plane layering.

### Surface Hierarchy
1. **Level 0 (Base Canvas)**: `#F8FAFC`. Completely flat. Housing root layouts and inactive table background tracks.
2. **Level 1 (Card / Resting Item)**: `#FFFFFF` with a 1px border in `#E2E8F0`. Shadow: `0 1px 2px 0 rgba(15, 23, 42, 0.04)`.
3. **Level 2 (Interactive Hover / Active Table Row)**: `#FFFFFF` with a 1px border in `#CBD5E1`. Shadow: `0 4px 6px -1px rgba(15, 23, 42, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.04)`.
4. **Level 3 (Flyouts, Popovers, Dropdowns)**: `#FFFFFF` with border `#E2E8F0`. Shadow: `0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.03)`.
5. **Level 4 (Modals, AI Live Mock Interview Overlays)**: `#FFFFFF` resting over a backdrop tint of `rgba(15, 23, 42, 0.5)` with `backdrop-filter: blur(4px)`. Shadow: `0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.04)`.

## Shapes

The geometric signature enforces distinct radiuses for container surfaces versus interactive controls, giving elements a clear, tactile hierarchy:

- **Structural Cards & Panels**: Fixed at `12px` (`0.75rem`) for standard cards and `16px` (`1rem`) for prominent candidate profiles, AI session stages, and dashboard metric widgets.
- **Interactive Controls (Buttons, Inputs, Selects)**: Tight, precise curvature fixed at `8px` (`0.5rem`) to `10px` (`0.625rem`). This prevents buttons from appearing toy-like while avoiding severe, sharp edges.
- **Status Badges, Chips & Meta Tags**: Full pill curvature (`9999px`) or compact `6px` (`0.375rem`) depending on context:
  - Recruitment status badges (`Submitted`, `Interview`, etc.) utilize soft-rounded tags at `6px` with compact padding.
  - Skill taxonomy tags (`React`, `Go`, `Kubernetes`) use `9999px` pill contours for visual scanning.

## Components

### Buttons
- **Candidate Primary**: Background `#16A34A`, text `#FFFFFF`, border none, radius `8px` to `10px`, height 40px (desktop) / 36px (compact). Hover: `#15803D`.
- **Admin Primary**: Background `#2563EB`, text `#FFFFFF`, border none, radius `8px`. Hover: `#1D4ED8`.
- **Secondary / Outline**: Background `#FFFFFF`, text `#0F172A`, 1px border `#CBD5E1`. Hover: `#F8FAFC`, border `#94A3B8`.
- **Ghost**: Background transparent, text `#475569`. Hover: background `#F1F5F9`, text `#0F172A`.
- **Focus State**: `box-shadow: 0 0 0 3px #60A5FA; outline: none; border-color: transparent;`.

### Status Badges & Chips
- Formatted with 1px solid border matching the semantic color family, font family `Inter`, font weight `600`, size `12px` (`0.75rem`), height `24px`, horizontal padding `8px`:
  - **Submitted**: BG `#EFF6FF`, Border `#BFDBFE`, Text `#1D4ED8`.
  - **Reviewing**: BG `#FFFBEB`, Border `#FDE68A`, Text `#B45309`.
  - **Interview**: BG `#F5F3FF`, Border `#DDD6FE`, Text `#6D28D9`.
  - **Passed**: BG `#F0FDF4`, Border `#BBF7D0`, Text `#15803D`.
  - **Rejected**: BG `#FEF2F2`, Border `#FECACA`, Text `#B91C1C`.

### Form Input Fields & Selects
- Background `#FFFFFF`, border 1px `#CBD5E1`, border radius `8px`, height `40px`, padding `0 12px`.
- Text `#0F172A`, placeholder `#94A3B8`.
- Focus ring: `outline: none; border-color: #2563EB; box-shadow: 0 0 0 3px #60A5FA;`.
- Error state: Border `#DC2626`, focus ring `#F87171`.

### Cards & Job Listings
- Base: Pure white `#FFFFFF`, 1px border `#E2E8F0`, radius `12px` or `16px`, padding `20px` to `24px`.
- Hover state on listings: 1px border `#CBD5E1`, subtle lift shadow `0 4px 6px -1px rgba(15, 23, 42, 0.05)`.
- Left-border accent (Optional for featured roles): 3px solid `#16A34A` along the left bounding box.

### Checkboxes & Radio Buttons
- Checkbox: `18px × 18px`, border radius `4px`, border `1.5px solid #CBD5E1`.
- Checked state Candidate: BG `#16A34A`, border `#16A34A`, white check icon.
- Checked state Admin: BG `#2563EB`, border `#2563EB`, white check icon.
- Focus: 3px halo `#60A5FA`.

### Technical & AI Interview Specific Components
- **AI Score Gauge / Metric Pill**: Background `#F8FAFC`, border `#E2E8F0`, displaying a circular progress or linear bar with color thresholds (<60 Rose, 60-79 Amber, 80+ Emerald), featuring tabular numeric formatting (`font-feature-settings: 'tnum'`).
- **Code & Transcript Block**: Background `#0F172A`, text `#F8FAFC`, border radius `10px`, font family monospaced, padding `16px`. Provides syntax highlighting and synchronized timestamps for mock interview playback.
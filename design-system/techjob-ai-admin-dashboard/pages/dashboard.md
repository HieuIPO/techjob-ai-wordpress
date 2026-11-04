# Dashboard Page Overrides

## Purpose

Design an administrative workbench for quickly reviewing applications and preparing interviews. Keep the first viewport operational and compact; do not treat it as a marketing landing page.

## Layout

- Main content uses a two-column work area: application review queue first, upcoming interviews second.
- Place four compact operational counts above the work area.
- Put the hiring pipeline below the queue as a compact status summary.
- At mobile widths, stack the sections and turn application rows into readable cards.
- Keep navigation labels and workflow status names aligned with the WordPress application.

## Visual direction

- Use the Master palette's warm neutral background and calm teal action color.
- Keep panels opaque and flat with a fine border; avoid glass, blur, and oversized shadows.
- Use tight but comfortable spacing. Preserve 44px targets for controls and visible keyboard focus.
- Use text labels with status colors. Never use color alone to communicate a status.
- Use inline SVG line icons; no emoji or external icon package.
- Use system font fallbacks and no remote font dependency in the standalone prototype.

## Data and behavior

- The prototype is standalone HTML, CSS, and JavaScript and must say that its data is illustrative.
- Use the real application states: submitted, reviewing, interview, passed, rejected.
- Search and local prototype controls may update the demo state only; do not call WordPress or imply that changes were saved.
- Include an empty state for filters with no matches.

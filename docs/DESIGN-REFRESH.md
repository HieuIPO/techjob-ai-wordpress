# TechJob AI: public interface refresh

Scope: homepage, public job list/detail controls, authentication and candidate surfaces. Existing admin layout retained. WordPress/PHP and native CSS; no frontend framework or new dependencies.

Audit: original green ATS branding, system sans, dense stacked search/filter/list, rounded panels. Preserve brand, navigation labels, route slugs, form field names, consent and backend handlers. New visual direction: restrained green career platform, light/dark semantic tokens, asymmetric photographic hero, clear search labels, softer surface hierarchy. Dials: DESIGN_VARIANCE 6, MOTION_INTENSITY 3, VISUAL_DENSITY 4.

Implementation: career.css scoped to public pages; hero.php for search; practice-banner.php opens the existing chat. Image generated for TechJob AI and stored locally under assets/images. Controls use 10px radii, surfaces 20px; hero image has a distinct asymmetric crop. Theme follows system preference with a persisted manual choice. No dummy jobs, companies or statistics added after data cleanup.

Verification: public homepage, English homepage, jobs, login and local assets return 200 across the checked ports. Browser inspected light/dark desktop, 390px mobile width, mobile menu and login page, and practice CTA opening the existing chat. PHP lint and JavaScript syntax pass. Policy tests run without ZIP extension: 10 checks pass. No full recruitment suite run because its fixtures depend on the demo accounts/data the user explicitly deleted. No Lighthouse or measured Web Vitals report was generated; no performance scores claimed.

Screenshot: progress/redesign-desktop.png.

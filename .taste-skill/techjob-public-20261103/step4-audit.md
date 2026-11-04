# Step 4 - Softly implementation audit

## Result

The Softly art direction is applied to the public TechJob AI experience. It uses the requested warm canvas, sage and lavender surfaces, coral actions, Outfit, Reenie Beanie, subtle grain, blurred background shapes, rounded controls, and slow reveals. The current TechJob AI wordmark and all product flows remain recognizable.

The final inspector run at 1440, 768, and 390 px reports 0 FAIL. The allowed rules are shadow, button-fx, pill-nav, and palette-cream because the new brief directly requests them. Two WARN remain: no-footage and mixed-font-headline. No suitable video was found for this product. The mixed type is limited to the AI portion of the wordmark, where the brief asks for Reenie Beanie. No broken images, horizontal overflow, or browser console errors were reported.

The three live feature tabs provide the product preview. I did not add phone screenshots, testimonials, an email waitlist, or FAQ content that the project does not currently support. The signup CTA uses the existing registration route. The company and jobs sections keep their real empty states.

## Pre-flight

- The existing H1 remains Giỏi công nghệ. Chọn đúng cơ hội. It is two lines at all three inspected widths.
- Supporting copy is 10 words. The search and registration actions are visible on the first screen.
- The navigation is 76 px tall on the desktop check. Its primary labels remain on one line.
- The grain layer has 0.35 opacity and does not intercept pointer input.
- Background shapes float over a 6 second loop with 10 px movement. Reveal motion uses 800 ms and the existing reduced-motion behavior.
- The job search, CV, and interview tabs switch correctly. The mobile menu opens and closes and updates aria-expanded.
- The browser inspector reports 4 real photos. The no-footage warning is defended because available clips did not represent this audience or product.
- The new public stylesheet and JavaScript pass tasteskill lint. PHP syntax checks pass for jobs.php and view.php.
- A broader static scan run earlier against shared legacy stylesheets reported 61 FAIL and 43 WARN from existing patterns. Those shared stylesheets were not redesigned. The landing page runtime is checked separately and passes with the user-requested effects allowed. Do not treat the broader legacy stylesheet scan as clean.

## Em-dash audit

No U+2014 appears in public templates, includes, or assets. The two legacy CSS file-header comments use hyphens.

## Preservation audit

Changed URLs, route registrations, primary navigation labels, form fields, headings, and anchors: none.

- Routes and permalink behavior are unchanged. The current URL remains /?lang=vi.
- Primary navigation labels remain Trang chủ, Việc làm IT, Công ty, English, Đăng ký, and Đăng nhập.
- Search fields remain lang, q, and location. Search filters remain lang, q, skill, category, location, job_type, min_salary, and max_salary.
- The H1 and existing section headings are unchanged.
- Anchors and IDs remain #main-content, #tj-chat, tj-tab-jobs, tj-tab-cv, tj-tab-interview, tj-panel-jobs, tj-panel-cv, and tj-panel-interview.
- Title and metadata are unchanged. The observed local baseline has duplicate title elements and noindex,nofollow robots metadata. No ranking pages were assumed.
- New hero actions point to the existing search panel and registration route.

## Brand fidelity audit

- The new art direction specifies the canvas #FDFCF8, sage #E8EFE8, lavender #EFEDF4, coral #FFB7B2, and dark text #292524.
- Muted text is darkened to #6B625D to pass the contrast check.
- TechJob green #006B2C remains on brand links and the second line of the hero heading. The CTA uses coral as specified by the new brief.
- Outfit is the primary typeface. Reenie Beanie is used for AI in the wordmark. Existing fallbacks remain.
- The TechJob AI wordmark and its </> code mark are preserved.

## Data and feature fidelity

The local page currently reports 0 job openings and no featured companies. No counts, match scores, company logos, testimonials, or AI CV analysis were invented. The CV tab continues to open profile and CV management. Interview copy describes the real five-question flow and its Gemini or demo mode. The four photo captions and source license links remain.

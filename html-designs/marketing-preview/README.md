# AssistMyHR marketing design preview

Open `index.html` directly in a browser. No installation or build needed.

- `index.html`: page content and structure
- `styles.css`: base styling, forms and shared layout rules
- `typography.css`: shared type scale across every preview page; 12px section labels, 16px desktop body text, 14px buttons and consistent responsive headings. Adjust these variables to change typography throughout the preview. Text inside screenshots and decorative miniature UI artwork retains its original scale.
- `header.css`: wider shared floating header (up to 1600px), centered desktop navigation with shared 16px menu text, and a responsive mobile menu
- `footer.css`: shared blue footer with a demo call to action, platform and account navigation, the existing contact email, and responsive columns
- `demo.css` and `request-demo.html`: dedicated modern walkthrough request page
- `contact.css` and `contact.html`: general enquiries page with email/phone cards, a guided-demo link and an accessible responsive form
- `auth-shell.css`: shared website chrome and responsive layouts around the real Laravel login and registration forms
- `hero.css`: modern homepage hero with blue typography, a subtle grid backdrop, a dashboard screenshot rotated toward the left on its vertical axis and responsive layouts. The screenshot has no diagonal rotation. Its background spans the full viewport width and continues behind the homepage header; the content remains centered.
- `how-hero.css` and `how-hero.html`: How it works hero with the original applications screenshot, a four-step journey graphic, a demo action and a link to the detailed hiring steps. The layout stacks on smaller screens.
- `pricing.html`, `pricing.css` and `pricing.js`: one all-features plan at $75 per user/month or $600 per user/year ($50/month equivalent, saving $300/year), advertising a 30-day free trial with no credit card required. Native radio buttons compare billing options. The build generates the real `/pricing` view from this markup. Set the live trial length to 30 days in Super admin → Settings to match the offer; signup and subscription handling remain server-managed.
- `preview.js`: preview navigation and local form feedback
- `platform-section.html`: reusable markup for the new illustrated hiring platform section (also included in `index.html`)
- `platform-section.css`: blue theme and responsive layouts for that section
- `platform-section.js`: accessible tabs for Recruit & manage, AI Candidate Search and Analytics & Reports
- `why-choose-us.html`: dedicated page content with all 12 published modules, four communication tools, dashboard, AI CV search, hiring workflow and services/support
- `why-choose-us.css`: responsive layouts for the dedicated Why choose us page

Includes Home, Why choose us?, How it works, Pricing, AI screening, Contact us, Request demo, sign-in and an account placeholder. The shared header has centered navigation and three actions on the right: a plain Log in link, an outlined Start a free trial button and a filled blue Request a demo button. Log in opens the local sign-in view; the trial button opens the existing account placeholder. Navigation collapses below 1340px to make room for Pricing; below 720px both buttons move into the mobile menu, while Log in stays visible. Contact us is also available in the mobile menu. The menu supports Escape and closes after navigation or an outside click. Forms do not send, store or authenticate data.

The hero uses the supplied dashboard screenshot (`assets/dashboard-screenshot.png`). The second homepage section uses the supplied job applications screenshot (`assets/applications-screenshot.png`), AI Candidate Search screenshot (`assets/ai-candidate-search-screenshot.png`) and Analytics Report screenshot (`assets/analytics-report-screenshot.png`) in its three tabs. All four images are unchanged copies of the user-provided PNGs, displayed at their original proportions without cropping. Click an image or its "View full size" link to open the full-resolution capture in a new tab. Other product examples and candidate names are illustrative.

Uses the Plus Jakarta Sans font with `ui-sans-serif, system-ui, sans-serif` fallbacks. Google Fonts supplies the font, so an internet connection is needed for the custom font. The header, footer, sign-in page and other branding placements use the supplied `assets/assistmyhr-logo.webp`, copied unchanged from the uploaded logo. A white background keeps its original dark lettering readable in dark sections. The shared header and footer appear on every preview page.

The footer links use existing local views. Its Back to top button scrolls the current page without changing the selected view and respects reduced-motion preferences. The email link uses the contact address already present in the preview. Request a demo opens a dedicated local page. The production build connects that page's form to the existing enquiry endpoint with the Live demo topic. The build also generates Laravel header/footer/style includes for the real login and registration pages, whose authentication, recovery and signup forms remain server-handled.

This folder remains the standalone editing preview; its forms do not send data. The production homepage is generated from it into `resources/frontend/index.html` and `public/marketing/` using `node scripts/build-marketing-site.mjs`. The hosted copy links to the real login and registration routes and submits enquiries to the existing contact service. Commit the generated files along with design changes, then deploy through the repository's normal Git workflow. Architectural illustrations replace the photographic backgrounds in the original reference.

The hiring platform section on the homepage takes visual inspiration from the large headings, illustrated product views and feature cards on https://www.workable.com/. Its copy and interface examples are original to this preview; no Workable customer claims, statistics or integrations are attributed to AssistMyHR. This section is available at `#platform-overview`. Tabs support clicking, touch, arrow keys, Home and End. The request-demo links open the existing local enquiry preview.

"Why choose us?" opens a dedicated local page at `#why-choose-us`. Its features and services were reviewed directly on https://assistmyhr.com/ on 24 October 2026. The page includes Jobs, ATS Overview, Job Applications, Candidate Database, Candidate Marketing, AI Search, Interview Schedule, Team, Internal Messages, Temp Staffing, Client Candidate Reviews and ToDos. Communication coverage includes candidate calling, inbound/outbound email, two-way SMS and client candidate reviews. Dashboard capabilities, AI CV indexing/extraction/matching, the four-stage hiring process, free demos, setup/onboarding help, custom requirements and the published email/phone are included. Performance statistics were not copied. Section links support direct navigation and retain the active Why choose us menu state.

To reuse the section later, copy `platform-section.html` into the target page, load its CSS and JavaScript, and retain the three referenced screenshot files from `assets/`. It inherits the page's Plus Jakarta Sans font and uses the `.button` style from `styles.css`. Its navigation links expect this preview's section anchors.

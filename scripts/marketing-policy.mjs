// Format the policy body and contact links; source header metadata is not displayed.
const escapeHtml = value => value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');

const listBoundaries = [
  ['The Personal Information Protection and Electronic Documents Act (PIPEDA).', 'Other applicable federal and provincial privacy and employment legislation.'],
  ['Visitors accessing the Assist My HR website.', 'Authorized third-party integrations connected to the Platform.'],
  ['Full name and preferred name.', 'Other information voluntarily provided by candidates or authorized customers.'],
  ['Organization name and business address.', 'Account usage and administrative activity.'],
  ['Internet Protocol (IP) address.', 'Cookie information and related identifiers.'],
  ['Job boards and recruitment platforms.', 'Other authorized business applications.'],
  ['Creation and management of candidate profiles.', 'Collaboration between authorized recruitment team members.'],
  ['Create and maintain customer accounts.', 'Detect suspicious or unauthorized activity.'],
  ['Comply with applicable legal obligations.', 'Enforce applicable contractual terms.'],
  ['Resume parsing and extraction of structured candidate information.', 'Recruitment analytics and reporting.'],
  ['Determining lawful recruitment purposes.', 'Complying with applicable recruitment, employment, and privacy legislation.'],
  ['Cloud infrastructure and hosting.', 'Authorized AI or resume processing.'],
  ['Encryption of information in transit.', 'Appropriate third-party service provider oversight.'],
  ['Request access to personal information.', 'Exercise applicable data portability rights.'],
  ['User authentication.', 'Analytics, where implemented and authorized.'],
  ['Changes to our Services.', 'Operational or contractual requirements.'],
  ['Maintaining privacy policies and procedures.', 'Reviewing material changes to information-processing practices.'],
  ['Office of the Privacy Commissioner of Canada.', "Commission d'accès à l'information du Québec."],
];

export function renderPrivacyPolicy(source, homeHref = '/') {
  const lines = source.replace(/^\uFEFF/, '').split(/\r?\n/).map(line => line.trim()).filter(Boolean);
  if (lines[0] !== 'PRIVACY POLICY' || lines[1] !== 'ASSIST MY HR') throw new Error('Unexpected policy title.');
  const bodyStart = lines.findIndex(line => /^1\. /.test(line));
  if (bodyStart < 2) throw new Error('Policy introduction not found.');
  const listEnds = new Map(listBoundaries.map(([first, last]) => {
    const start = lines.indexOf(first), end = lines.indexOf(last, start);
    if (start < 0 || end < start) throw new Error('Policy list not found: ' + first);
    return [start, end];
  }));
  const contents = [], blocks = [];
  let sectionOpen = false;
  let sectionNumber;
  for (let index = bodyStart; index < lines.length; index++) {
    const line = lines[index], heading = line.match(/^(\d+)\. (.+)$/);
    if (heading) {
      if (sectionOpen) blocks.push('      </section>');
      const number = heading[1];
      sectionNumber = number;
      contents.push(`<li><a href="#privacy-section-${number}"><span>${number.padStart(2, '0')}</span>${escapeHtml(heading[2])}</a></li>`);
      blocks.push(`      <section id="privacy-section-${number}" aria-labelledby="privacy-heading-${number}">\n        <h2 id="privacy-heading-${number}">${escapeHtml(line)}</h2>`);
      sectionOpen = true;
    } else if (/^\d+\.\d+ /.test(line)) {
      blocks.push(`        <h3>${escapeHtml(line)}</h3>`);
    } else if (listEnds.has(index)) {
      const end = listEnds.get(index);
      blocks.push('        <ul>\n' + lines.slice(index, end + 1).map(item => `          <li>${escapeHtml(item)}</li>`).join('\n') + '\n        </ul>');
      index = end;
    } else {
      const email = sectionNumber === '21' && line.match(/^Email: ([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})$/);
      const phone = sectionNumber === '21' && line.match(/^Phone: (\+[\d ()-]+)$/);
      if (email) {
        blocks.push(`        <p class="privacy-contact-line">Email: <a href="mailto:${escapeHtml(email[1])}">${escapeHtml(email[1])}</a></p>`);
        continue;
      }
      if (phone) {
        blocks.push(`        <p class="privacy-contact-line">Phone: <a href="tel:${phone[1].replace(/[^\d+]/g, '')}">${escapeHtml(phone[1])}</a></p>`);
        continue;
      }
      const className = line.startsWith('Implementation note:') ? ' class="privacy-editorial-note"' : line.includes('[To Be Confirmed]') ? ' class="privacy-placeholder"' : '';
      blocks.push(`        <p${className}>${escapeHtml(line)}</p>`);
    }
  }
  if (sectionOpen) blocks.push('      </section>');
  if (contents.length !== 21) throw new Error('Expected all 21 privacy policy sections.');
  return `<div class="privacy-page" aria-labelledby="privacy-title">
  <div class="privacy-hero"><div class="privacy-inner">
    <nav class="privacy-breadcrumb" aria-label="Breadcrumb"><a href="${escapeHtml(homeHref)}">Home</a><span aria-hidden="true">/</span><span aria-current="page">Privacy policy</span></nav>
    <span class="privacy-eyebrow">${escapeHtml(lines[1])}</span>
    <h1 id="privacy-title">${escapeHtml(lines[0])}</h1>
    <a class="privacy-read-link" href="#privacy-content">Read the policy <span aria-hidden="true">↓</span></a>
  </div></div>
  <div class="privacy-layout privacy-inner">
    <aside class="privacy-contents"><details open><summary>On this page <span aria-hidden="true">⌄</span></summary><nav aria-label="Privacy policy contents"><ol>${contents.join('\n')}</ol></nav></details></aside>
    <article class="privacy-document" id="privacy-content" tabindex="-1" aria-label="Privacy policy text">
${blocks.join('\n')}
    </article>
  </div>
</div>`;
}

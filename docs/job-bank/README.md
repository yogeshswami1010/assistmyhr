# AssistMyHR sample incoming feed for Job Bank

Share [sample.xml](sample.xml) with Job Bank as a **technical review sample**. It contains fictional events and reserved example URLs. Ask Job Bank for its supplementary import business rules and a test import before supplying a production URL.

The 2025 incoming-feed guide specifies lowercase field names, CDATA values, a NOC code and title, an action for adding, updating or deleting a job, and an HTTP endpoint that accepts a timestamp. It illustrates an add with `A` and shows the query format `?from=YYYYMMDDhhmmss`.

The sample proposes the XML structure, field names apart from `action`, `U` and `D` action values, and a minimal delete record. Confirm these with Job Bank, along with required fields, field formats, supported characters, timestamp timezone and boundary behavior, and how Job Bank will access and test the feed.

## ATS preview feed

Set `APP_URL` to the public careers host and apply the database migration. For SaaS installations, also run the tenant migrations. Create or edit a job and select **Include this job in the Job Bank feed** under **Job boards**. Confirm employer consent and eligibility, and enter the NOC code, NOC title, Canadian city and province or territory.

- `GET /job-feeds/job-bank.xml` returns currently eligible jobs as add records.
- `GET /job-feeds/job-bank.xml?from=20261118090000` returns recorded changes since that UTC timestamp, inclusive. Consumers should allow for replay at the timestamp boundary.

The feed compares eligible postings against its last observed state on each request. It records an add for a new listing, an update when exported fields change, and a delete when a previously observed listing is closed, expired, deselected, deleted, or otherwise ineligible. Multiple job changes between requests may be combined into one observed change.

The preview uses the provisional XML contract above. Confirm the schema, NOC mapping requirements and duplicate-posting rules with Job Bank before supplying the live URL.

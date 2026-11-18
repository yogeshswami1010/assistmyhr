# AssistMyHR sample incoming feed for Job Bank

Share [sample.xml](sample.xml) with Job Bank as a **technical review sample**. It contains fictional events and reserved example URLs. Ask Job Bank for its supplementary import business rules and a test import before supplying a production URL.

The 2025 incoming-feed guide specifies lowercase field names, CDATA values, a NOC code and title, an action for adding, updating or deleting a job, and an HTTP endpoint that accepts a timestamp. It illustrates an add with `A` and shows the query format `?from=YYYYMMDDhhmmss`.

The sample proposes the XML structure, field names apart from `action`, `U` and `D` action values, and a minimal delete record. Confirm these with Job Bank, along with required fields, field formats, supported characters, timestamp timezone and boundary behavior, and how Job Bank will access and test the feed.

For production, add a dedicated route alongside the existing ATS feeds and keep durable changes per posting. A posting that closes, expires or loses publication eligibility must still produce a removal after it leaves the careers site. Export only eligible jobs with verified employer consent, a public employer name, a validated NOC mapping and a public posting link.

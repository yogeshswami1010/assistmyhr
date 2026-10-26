<?php

// Older cached pages use this URL. Preserve their POST body while moving to
// the validated, CSRF-protected Laravel enquiry endpoint.
header('Cache-Control: no-store');
header('Location: /website/enquiries', true, 307);
exit;

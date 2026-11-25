<?php

return [
    'location_ids' => json_decode((string) env('JOBILLICO_LOCATION_IDS', '{}'), true),
];

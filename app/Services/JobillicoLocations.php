<?php

namespace App\Services;

use App\Job;

class JobillicoLocations
{
    public function forJob(Job $job): ?int
    {
        $location = $job->jobLocation->first() ?? $job->location;
        if ($location === null) {
            return null;
        }

        $ids = [];
        foreach ((array) config('jobillico.location_ids') as $name => $id) {
            $ids[$this->normalize((string) $name)] = $id;
        }

        $cityAndProvince = filled($job->location_city) && filled($job->location_state)
            ? $job->location_city.','.$job->location_state
            : '';
        foreach ([$location->location, $cityAndProvince] as $name) {
            $name = $this->normalize((string) $name);
            $id = $name === '' ? null : ($ids[$name] ?? null);
            if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
                return (int) $id;
            }
        }

        return null;
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(implode(',', array_map('trim', explode(',', $name))));
    }
}

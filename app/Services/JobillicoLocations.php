<?php

namespace App\Services;

use App\Job;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use SimpleXMLElement;

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

        $locations = $this->accountLocations();
        $street = $this->normalize((string) $job->location_street_address);
        $fullLocation = $this->normalize((string) $location->location);
        $cityAndProvince = $this->normalize($cityAndProvince);
        $matches = [];
        foreach ($locations as $candidate) {
            [$id, $address, $city, $province] = $candidate;
            $provinces = [
                'ab' => 'alberta', 'bc' => 'british columbia', 'mb' => 'manitoba',
                'nb' => 'new brunswick', 'nl' => 'newfoundland and labrador',
                'ns' => 'nova scotia', 'nt' => 'northwest territories', 'nu' => 'nunavut',
                'on' => 'ontario', 'pe' => 'prince edward island', 'qc' => 'quebec',
                'sk' => 'saskatchewan', 'yt' => 'yukon',
            ];
            if ($city === '' || $province === '') {
                continue;
            }
            $provinceCode = array_search($province, $provinces, true);
            $regions = [$city.','.$province, $city.','.($provinces[$province] ?? ($provinceCode ?: $province))];
            $jobLocations = [$cityAndProvince, $fullLocation, preg_replace('/,canada$/', '', $fullLocation)];
            $locationNames = [...$regions, $address.','.$regions[0], $address.','.$regions[1]];
            if (array_intersect($jobLocations, $locationNames) === []) {
                continue;
            }
            if ($street !== '' && $street !== $address) {
                continue;
            }
            $matches[$id] = $id;
        }

        return count($matches) === 1 ? reset($matches) : null;
    }

    private function accountLocations(): array
    {
        $email = config('jobillico.email');
        $password = config('jobillico.password');
        $production = config('jobillico.production');
        if (blank($email) || blank($password)) {
            return [];
        }

        return Cache::remember('jobillico:locations:'.hash('sha256', $email.'|'.(int) $production), 300, function () use ($email, $password, $production) {
            $request = new SimpleXMLElement('<request/>');
            $request->addChild('bsandboxoff', $production ? '1' : '0');
            $oauth = $request->addChild('oauth');
            $oauth->addChild('sparam', htmlspecialchars($email, ENT_XML1, 'UTF-8'));
            $oauth->addChild('sparam', htmlspecialchars($password, ENT_XML1, 'UTF-8'));
            $request->addChild('locations');

            try {
                $response = Http::timeout(5)->withoutRedirecting()->withBody($request->asXML(), 'application/xml')
                    ->post('https://www.jobillico.com/api/info');
            } catch (ConnectionException) {
                return [];
            }
            if (!$response->successful()) {
                return [];
            }

            $xml = simplexml_load_string($response->body(), SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $locations = [];
            foreach ($xml ? $xml->xpath('/results/locations/*[ilocation]') : [] as $location) {
                $id = filter_var((string) $location->ilocation, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id !== false) {
                    $locations[] = [$id, $this->normalize((string) $location->saddress),
                        $this->normalize((string) $location->scity), $this->normalize((string) $location->estate)];
                }
            }

            return $locations;
        });
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(implode(',', array_map('trim', explode(',', $name))));
    }
}

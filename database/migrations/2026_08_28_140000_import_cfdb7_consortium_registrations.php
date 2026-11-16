<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Staging workspaces use synthetic candidates.
        if (app()->environment('staging')) {
            return;
        }

        $path = database_path('imports/cfdb7-2026-08-28.csv');
        if (!is_file($path)) {
            throw new RuntimeException('Consortium registration import CSV was not found: '.$path);
        }

        $handle = fopen($path, 'rb');
        $headers = fgetcsv($handle);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $imported = 0;

        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) !== count($headers)) continue;
            $row = array_combine($headers, $values);
            $submittedAt = Carbon::createFromFormat('d/m/Y H:i', trim($row['Date']));
            $dobRaw = trim($row['Date Of Birth']);
            try {
                $dob = str_contains($dobRaw, '/')
                    ? Carbon::createFromFormat('d/m/Y', $dobRaw)->format('Y-m-d')
                    : Carbon::createFromFormat('Y-m-d', $dobRaw)->format('Y-m-d');
            } catch (Throwable) {
                $dob = null;
            }

            $experienceText = trim($row['Experience']);
            $experience = null;
            if (is_numeric($experienceText)) {
                $experience = (float) $experienceText;
            } elseif (preg_match('/(\d+(?:\.\d+)?)/', $experienceText, $match)) {
                $experience = (float) $match[1];
                if (preg_match('/month/i', $experienceText)) $experience = round($experience / 12, 1);
            }

            $yes = fn ($value) => in_array(strtolower(trim((string) $value)), ['1', 'yes', 'true', 'on'], true);
            $payload = [
                'first_name' => trim($row['First Name']),
                'last_name' => trim($row['Last Name']),
                'email' => strtolower(trim($row['Email'])),
                'phone' => trim($row['Phone Number']),
                'gender' => trim($row['Gender']) ?: null,
                'date_of_birth' => $dob,
                'street_address' => trim($row['Street Address']) ?: null,
                'city' => trim($row['City']),
                'eligible_to_work_canada' => $yes($row['Legally Eligible']),
                'status_in_canada' => trim($row['Status In Canada']) ?: null,
                'preferred_job_type' => trim($row['Preferred Job Type']) ?: null,
                'commute_mode' => trim($row['Mode Commute']) ?: null,
                'years_experience' => $experience,
                'legacy_experience_text' => $experienceText ?: null,
                'industry_expertise' => trim($row['Field Of Expertise']) ?: null,
                'available_weekends' => $yes($row['Work Weekends']),
                'available_night_shifts' => $yes($row['Work Night Shifts']),
                'referral_source' => trim($row['Hear About Us']) ?: null,
                'additional_information' => trim($row['Additional Information']) ?: null,
                'information_certified' => $yes($row['Acceptance 195']),
                'agreement_accepted' => $yes($row['Terms']),
                'sms_consent' => $yes($row['Acceptance 196']),
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ];

            DB::table('consortium_registrations')->updateOrInsert(
                ['legacy_source' => 'cfdb7', 'legacy_id' => trim($row['Id'])],
                $payload
            );
            $imported++;
        }
        fclose($handle);

        if ($imported !== 242) {
            throw new RuntimeException('Expected 242 Consortium registrations but imported '.$imported.'.');
        }
    }

    public function down(): void
    {
        if (app()->environment('staging')) {
            return;
        }

        DB::table('consortium_registrations')->where('legacy_source', 'cfdb7')->delete();
    }
};

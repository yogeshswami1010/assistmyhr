<?php

namespace App\Services;

use App\JobApplication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ApplicantResumeParser
{
    public function parse(int $applicationId): void
    {
        $application = JobApplication::find($applicationId);
        if (!$application || trim((string) $application->cv_text) === '') return;

        try {
            $data = $this->parseStructured((string) $application->cv_text);
            $years = (float) data_get($data, 'total_experience.years', 0)
                + ((float) data_get($data, 'total_experience.months', 0) / 12);
            $location = array_filter([
                data_get($data, 'personal.location.city'),
                data_get($data, 'personal.location.province'),
                data_get($data, 'personal.location.country'),
            ]);

            $application->forceFill([
                'parsed_cv_data' => json_encode($data),
                'cv_experience_years' => round($years, 1),
                'cv_job_titles' => implode(', ', (array) ($data['job_titles'] ?? [])),
                'cv_skills_text' => implode(', ', (array) ($data['skills'] ?? [])),
                'cv_location_text' => implode(', ', $location),
                'cv_indexed_at' => now(),
                'cv_index_failed' => false,
            ])->save();
        } catch (\Throwable $e) {
            // Keep this record eligible for the existing Candidate Database
            // retry parser instead of permanently skipping it.
            $application->forceFill([
                'cv_indexed_at' => now(),
                'cv_index_failed' => false,
            ])->save();
            Log::warning('Automatic applicant CV parsing failed.', [
                'job_application_id' => $applicationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function parseStructured(string $cvText): array
    {
        $apiKey = trim((string) config('services.deepseek.key'));
        if ($apiKey === '') throw new RuntimeException('Configure an active DeepSeek key in AI Settings.');

        $schema = '{"personal":{"name":"","email":"","phone":"","location":{"city":"","province":"","country":""}},"headline":"","total_experience":{"years":0,"months":0},"job_titles":[],"skills":[],"certifications":[],"education":[{"degree":"","field":"","school":""}],"employment":[{"company":"","title":"","start":"","end":"","duration_years":0}],"languages":[],"availability":{"notice_period":""},"resume_summary":""}';
        $prompt = "You are a CV parser API. Return only one valid JSON object with this exact schema:\n{$schema}\n"
            ."Use empty values when unknown. Extract every job title and skill, calculate total experience, and do not include markdown.\n\nCV TEXT:\n"
            .mb_substr($cvText, 0, 12000);

        $response = Http::timeout(45)->withToken($apiKey)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => config('services.deepseek.model', 'deepseek-chat'),
                'temperature' => 0.1,
                'max_tokens' => 4000,
                'messages' => [
                    ['role' => 'system', 'content' => 'You extract structured CV data and output valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('DeepSeek CV parser returned HTTP '.$response->status().'.');
        }

        $content = trim((string) $response->json('choices.0.message.content'));
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```\s*$/', '', $content) ?? $content;
        $data = json_decode(trim($content), true);
        if (!is_array($data) && preg_match('/\{.*\}/s', $content, $match)) {
            $data = json_decode($match[0], true);
        }
        if (!is_array($data) || !is_array($data['personal'] ?? null)) {
            throw new RuntimeException('The AI returned invalid structured CV data.');
        }

        foreach (['job_titles', 'skills', 'certifications', 'education', 'employment', 'languages'] as $key) {
            $data[$key] = (array) ($data[$key] ?? []);
        }
        return $data;
    }
}

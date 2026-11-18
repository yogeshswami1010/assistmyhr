<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;

class JobBankFeed
{
    public function records(?string $from, Closure $publishedJobs): array
    {
        return DB::transaction(function () use ($from, $publishedJobs) {
            // Serialize feed refreshes so overlapping requests cannot log the same change twice.
            DB::table('job_bank_feed_state')->where('reference', '')->lockForUpdate()->firstOrFail();
            $previous = DB::table('job_bank_feed_state')->where('reference', '<>', '')->pluck('payload', 'reference');
            $recordedAt = now('UTC')->format('YmdHis');
            $snapshot = [];

            foreach ($publishedJobs() as $reference => $fields) {
                $payload = json_encode($fields, JSON_THROW_ON_ERROR);
                $oldPayload = $previous->pull($reference);
                $snapshot[$reference] = $this->record('A', $reference, $recordedAt, $fields);

                if ($oldPayload !== $payload) {
                    DB::table('job_bank_feed_state')->updateOrInsert(['reference' => $reference], ['payload' => $payload]);
                    $this->append($this->record($oldPayload === null ? 'A' : 'U', $reference, $recordedAt, $fields));
                }
            }

            foreach ($previous->keys() as $reference) {
                $this->append($this->record('D', $reference, $recordedAt));
                DB::table('job_bank_feed_state')->where('reference', $reference)->delete();
            }

            if ($from === null) {
                return array_values($snapshot);
            }

            return DB::table('job_bank_feed_events')->where('recorded_at', '>=', $from)
                ->orderBy('id')->pluck('payload')
                ->map(fn ($payload) => json_decode($payload, true, 512, JSON_THROW_ON_ERROR))
                ->filter(fn ($record) => $record['action'] === 'D' || isset($snapshot[$record['id']]))
                ->all();
        });
    }

    private function record(string $action, string $reference, string $timestamp, array $fields = []): array
    {
        return ['action' => $action, 'id' => $reference, 'updated_at' => $timestamp] + $fields;
    }

    private function append(array $record): void
    {
        DB::table('job_bank_feed_events')->insert([
            'recorded_at' => $record['updated_at'],
            'payload' => json_encode($record, JSON_THROW_ON_ERROR),
        ]);
    }
}

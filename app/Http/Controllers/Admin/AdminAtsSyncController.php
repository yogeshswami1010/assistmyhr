<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAtsSyncController extends Controller
{
    /** Keep the signed-in team member marked online and return current presence. */
    public function heartbeat(): JsonResponse
    {
        $userId = (int) auth()->id();
        User::query()->whereKey($userId)->update(['last_seen_at' => now()]);

        $onlineUserIds = User::query()
            ->where('last_seen_at', '>=', now()->subSeconds(75))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $authUser = auth()->user();
        $latestInternalNotification = $authUser->unreadNotifications()
            ->where('type', \App\Notifications\InternalMessageReceived::class)
            ->latest()
            ->first();

        return response()->json([
            'online_user_ids' => $onlineUserIds,
            'server_time' => now()->timestamp,
            'unread_notification_count' => $authUser->unreadNotifications()->count(),
            'unread_internal_message_count' => \App\InternalMessage::where('recipient_id', $authUser->id)->whereNull('read_at')->count(),
            'latest_notification_id' => $authUser->notifications()
                ->where('type', '!=', \App\Notifications\InternalMessageReceived::class)
                ->reorder('created_at', 'desc')
                ->orderByDesc('id')
                ->value('id'),
            'latest_incoming_message_id' => (int) \App\InternalMessage::where('recipient_id', $userId)->max('id'),
            'latest_internal_notification' => $latestInternalNotification ? [
                'id' => $latestInternalNotification->id,
                'sender_id' => $latestInternalNotification->data['sender_id'] ?? null,
                'sender_name' => $latestInternalNotification->data['sender_name'] ?? 'A team member',
                'message_text' => $latestInternalNotification->data['message_text'] ?? '',
                'time' => $latestInternalNotification->created_at->diffForHumans(),
                'created_at_timestamp' => $latestInternalNotification->created_at->timestamp,
            ] : null,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
    /**
     * Return a small fingerprint of shared ATS data. Browsers compare this value
     * to detect changes made by another signed-in team member.
     */
    public function state(): JsonResponse
    {
        $tables = [
            'jobs',
            'job_applications',
            'application_status',
            'applicant_notes',
            'job_client_notes',
            'applicant_sms_messages',
            'interview_schedules',
            'job_application_answers',
            'job_application_status_history',
            'companies',
            'job_locations',
        ];

        // Schema lookups are cached because this endpoint is intentionally polled.
        $metadata = Cache::remember('ats_sync_table_metadata_v1', 3600, function () use ($tables) {
            return collect($tables)->mapWithKeys(function ($table) {
                if (!Schema::hasTable($table)) {
                    return [];
                }

                return [$table => [
                    'updated_at' => Schema::hasColumn($table, 'updated_at'),
                    'deleted_at' => Schema::hasColumn($table, 'deleted_at'),
                ]];
            })->all();
        });

        $state = [];
        foreach ($metadata as $table => $columns) {
            $select = ['COUNT(*) AS row_count'];
            if ($columns['updated_at']) $select[] = 'MAX(updated_at) AS last_updated_at';
            if ($columns['deleted_at']) $select[] = 'MAX(deleted_at) AS last_deleted_at';

            $state[$table] = (array) DB::table($table)->selectRaw(implode(', ', $select))->first();
        }

        return response()->json([
            'signature' => hash('sha256', json_encode($state)),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
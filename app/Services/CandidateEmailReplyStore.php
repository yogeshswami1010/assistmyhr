<?php

namespace App\Services;

use App\CandidateEmailMessage;
use App\JobApplication;

/** Shared thread matching and idempotent storage for IMAP and Microsoft replies. */
class CandidateEmailReplyStore
{
    private array $sentBySender = [];

    public function store(array $reply, callable $body): ?array
    {
        $from = strtolower(trim($reply['from_address']));
        $subject = $reply['subject'];
        $inReplyTo = $reply['in_reply_to'] ?? '';
        $references = $reply['references'] ?? '';
        $messageId = $reply['message_id'];
        $sent = $this->sentBySender[$from] ??= CandidateEmailMessage::where('direction', 'outbound')
            ->whereRaw('LOWER(TRIM(to_address)) = ?', [$from])->latest('id')
            ->get(['job_application_id', 'subject', 'message_id', 'created_at'])->toArray();
        $sent = array_values(array_filter($sent, static fn ($message) => strtotime($message['created_at']) <= strtotime($reply['received_at'])));
        $parentIds = array_merge(CandidateEmailThread::ids($inReplyTo), CandidateEmailThread::ids($references));
        $variants = array_merge($parentIds, array_map(static fn ($id) => '<'.$id.'>', $parentIds));
        $threadSent = $variants ? CandidateEmailMessage::where('direction', 'outbound')
            ->whereIn('message_id', $variants)->get(['job_application_id', 'subject', 'message_id'])->toArray() : [];
        $applicationId = CandidateEmailThread::match($threadSent, $inReplyTo, $references, null)
            ?? CandidateEmailThread::match($sent, $inReplyTo, $references, $subject);
        if (!$applicationId) {
            $ids = JobApplication::whereRaw('LOWER(TRIM(email)) = ?', [$from])->limit(2)->pluck('id');
            if ($ids->count() === 1) $applicationId = (int) $ids->first();
        }
        if (!$applicationId) return null;

        $existing = CandidateEmailMessage::where('direction', 'inbound')
            ->whereIn('message_id', [$messageId, '<'.$messageId.'>'])->first();
        $data = [
            'job_application_id' => $applicationId, 'direction' => 'inbound',
            'from_address' => $from, 'to_address' => $reply['to_address'],
            'subject' => mb_substr($subject, 0, 191), 'body' => $body(),
            'message_id' => $messageId,
            'in_reply_to' => CandidateEmailThread::ids($inReplyTo)[0] ?? (CandidateEmailThread::ids($references)[0] ?? null),
            'received_at' => $reply['received_at'],
        ];
        $status = 'unchanged';
        if ($existing) {
            if ((int) $existing->job_application_id !== $applicationId) $data['read_at'] = null;
            $existing->fill($data);
            if ($existing->isDirty()) { $existing->save(); $status = 'repaired'; }
        } else {
            CandidateEmailMessage::create($data);
            $status = 'imported';
        }
        return ['application_id' => $applicationId, 'status' => $status];
    }
}

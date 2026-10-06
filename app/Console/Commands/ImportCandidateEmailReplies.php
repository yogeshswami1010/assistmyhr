<?php
namespace App\Console\Commands;

use App\CandidateEmailMessage;
use App\JobApplication;
use App\Services\CandidateEmailThread;
use App\Services\CandidateEmailContent;
use Illuminate\Console\Command;

class ImportCandidateEmailReplies extends Command
{
    protected $signature = 'candidate-emails:import-replies {--debug : Show matching results} {--sender= : Check one sender address}';
    protected $description = 'Import candidate replies from the configured IMAP mailbox';

    public function handle(): int
    {
        if (config('saas.enabled') && app(\App\Saas\TenantContext::class)->current()?->slug !== 'main' && !config('services.candidate_email_imap.host')) {
            return self::SUCCESS;
        }
        if (!function_exists('imap_open')) {
            $this->error('PHP IMAP extension is not installed.');
            return self::FAILURE;
        }
        $settings = config('services.candidate_email_imap');
        $smtp = config('mail.ai_search_smtp');
        $host = $settings['host'] ?? 'imappro.zoho.in';
        $mailbox = sprintf('{%s:%s/imap/ssl}INBOX', $host, $settings['port'] ?? 993);
        imap_timeout(IMAP_OPENTIMEOUT, 15);
        imap_timeout(IMAP_READTIMEOUT, 15);
        $inbox = @imap_open($mailbox, $smtp['username'] ?? '', $smtp['password'] ?? '', OP_READONLY);
        if (!$inbox) {
            $error = imap_last_error();
            imap_errors(); imap_alerts();
            $this->error($error ?: 'Could not connect to the mailbox.');
            return self::FAILURE;
        }
        $count = 0; $repaired = 0; $failed = 0; $senderCache = [];
        try {
            $mailboxStatus = imap_status($inbox, $mailbox, SA_UIDVALIDITY);
            foreach (imap_search($inbox, 'ALL') ?: [] as $number) {
                try {
                    $header = imap_headerinfo($inbox, $number);
                    if (!$header) continue;
                    $from = strtolower(trim(($header->from[0]->mailbox ?? '').'@'.($header->from[0]->host ?? '')));
                    if ($this->option('sender') && $from !== strtolower(trim($this->option('sender')))) continue;
                    $subject = $this->decodeHeader((string) ($header->subject ?? ''));
                    $messageId = CandidateEmailThread::ids($header->message_id ?? '')[0] ??
                        'imap-'.hash('sha256', $mailbox.'|'.($smtp['username'] ?? '').'|'.($mailboxStatus->uidvalidity ?? '').'|'.imap_uid($inbox, $number));
                    $inReplyTo = (string) ($header->in_reply_to ?? '');
                    $references = (string) ($header->references ?? '');
                    // Use sent records for this sender, rather than the first of
                    // potentially several applications sharing the email address.
                    if (!isset($senderCache[$from])) {
                        $senderCache[$from] = CandidateEmailMessage::where('direction', 'outbound')
                            ->whereRaw('LOWER(TRIM(to_address)) = ?', [$from])->latest('id')
                            ->get(['job_application_id','subject','message_id','created_at'])->toArray();
                    }
                    $receivedAt = isset($header->udate) ? date('Y-m-d H:i:s', $header->udate) : now()->format('Y-m-d H:i:s');
                    $sent = array_values(array_filter($senderCache[$from], static fn ($message) => strtotime($message['created_at']) <= strtotime($receivedAt)));
                    $parentIds = array_merge(CandidateEmailThread::ids($inReplyTo), CandidateEmailThread::ids($references));
                    $parentVariants = array_merge($parentIds, array_map(static fn ($id) => '<'.$id.'>', $parentIds));
                    $threadSent = $parentVariants ? CandidateEmailMessage::where('direction', 'outbound')
                        ->whereIn('message_id', $parentVariants)->get(['job_application_id','subject','message_id'])->toArray() : [];
                    // Exact thread IDs also handle replies from a candidate's
                    // alternate/forwarded address. Subject fallback stays sender-scoped.
                    $applicationId = CandidateEmailThread::match($threadSent, $inReplyTo, $references, null)
                        ?? CandidateEmailThread::match($sent, $inReplyTo, $references, $subject);
                    if (!$applicationId) {
                        $ids = JobApplication::whereRaw('LOWER(TRIM(email)) = ?', [$from])->limit(2)->pluck('id');
                        if ($ids->count() === 1) $applicationId = (int) $ids->first();
                    }
                    if (!$applicationId) {
                        if ($this->option('debug')) $this->warn('Unmatched or ambiguous: '.$from.' | '.$subject);
                        continue;
                    }
                    $existing = CandidateEmailMessage::where('direction', 'inbound')
                        ->whereIn('message_id', [$messageId, '<'.$messageId.'>'])->first();
                    $structure = imap_fetchstructure($inbox, $number);
                    if (!$structure) throw new \RuntimeException('Unable to read MIME structure.');
                    $body = CandidateEmailContent::body($structure, fn ($part) => $part === ''
                        ? imap_body($inbox, $number, FT_PEEK) : imap_fetchbody($inbox, $number, $part, FT_PEEK));
                    $data = ['job_application_id' => $applicationId, 'direction' => 'inbound', 'from_address' => $from,
                        'to_address' => $smtp['from']['address'] ?? $smtp['username'], 'subject' => mb_substr($subject, 0, 191),
                        'body' => $body, 'message_id' => $messageId,
                        'in_reply_to' => CandidateEmailThread::ids($inReplyTo)[0] ?? (CandidateEmailThread::ids($references)[0] ?? null),
                        'received_at' => $receivedAt];
                    if ($existing) {
                        if ((int) $existing->job_application_id !== $applicationId) $data['read_at'] = null;
                        $existing->fill($data);
                        if ($existing->isDirty()) { $existing->save(); $repaired++; }
                        if ($this->option('debug')) $this->line('Stored on profile #'.$applicationId.': '.$from.' | '.$subject);
                    } else {
                        CandidateEmailMessage::create($data); $count++;
                        if ($this->option('debug')) $this->info('Imported to profile #'.$applicationId.': '.$from.' | '.$subject);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn('Message '.$number.' failed: '.$e->getMessage());
                }
            }
        } finally {
            imap_close($inbox);
            imap_errors(); imap_alerts();
        }
        $this->info("Imported {$count} candidate email replies; repaired {$repaired} stored replies; failed {$failed}.");
        if ($failed) return self::FAILURE;
        return self::SUCCESS;
    }

    private function decodeHeader(string $value): string
    {
        $decoded = '';
        foreach (imap_mime_header_decode($value) as $part) {
            $decoded .= CandidateEmailContent::decode($part->text, 0, $part->charset === 'default' ? 'UTF-8' : $part->charset);
        }
        return $decoded;
    }
}

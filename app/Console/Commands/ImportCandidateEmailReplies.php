<?php
namespace App\Console\Commands;

use App\CandidateReplyMailbox;
use App\Services\CandidateEmailReplyStore;
use App\Services\CandidateEmailThread;
use App\Services\CandidateEmailContent;
use App\Services\MicrosoftReplyInbox;
use App\Services\MicrosoftInboxException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ImportCandidateEmailReplies extends Command
{
    protected $signature = 'candidate-emails:import-replies {--debug : Show matching results} {--sender= : Check one sender address}';
    protected $description = 'Import candidate replies from the connected Microsoft or IMAP mailbox';

    public function handle(): int
    {
        if (config('saas.enabled')) {
            if (!app(\App\Saas\TenantContext::class)->current()) {
                $this->error('Use saas:maintenance minute to import replies in client workspaces.');
                return self::FAILURE;
            }
            if (Schema::hasTable('candidate_reply_mailboxes') && ($outlook = CandidateReplyMailbox::find(1))) {
                return $this->importOutlook($outlook);
            }
            if (!config('services.candidate_email_imap.host')) {
                $this->warn('No reply inbox configured. Ask the platform administrator to set the client IMAP host.');
                return self::SUCCESS;
            }
        }
        $settings = config('services.candidate_email_imap');
        $smtp = config('mail.ai_search_smtp');
        $host = $settings['host'] ?? null;
        if (in_array(strtolower((string) $host), ['outlook.office365.com', 'imap-mail.outlook.com'], true)) {
            $this->error('Outlook requires Microsoft sign-in. Use Super Admin > Client > Outlook Inbox > Connect Outlook; the SMTP password cannot receive Outlook replies.');
            return self::FAILURE;
        }
        if (!function_exists('imap_open')) {
            $this->error('PHP IMAP extension is not installed.');
            return self::FAILURE;
        }
        if (!$host || empty($smtp['username']) || empty($smtp['password'])) {
            $this->warn('Configure the reply inbox and SMTP credentials before importing replies.');
            return self::SUCCESS;
        }
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
        $count = 0; $repaired = 0; $failed = 0;
        $store = new CandidateEmailReplyStore;
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
                    $receivedAt = isset($header->udate) ? date('Y-m-d H:i:s', $header->udate) : now()->format('Y-m-d H:i:s');
                    $result = $store->store([
                        'from_address' => $from, 'to_address' => $smtp['from']['address'] ?? $smtp['username'],
                        'subject' => $subject, 'message_id' => $messageId, 'in_reply_to' => $inReplyTo,
                        'references' => $references, 'received_at' => $receivedAt,
                    ], function () use ($inbox, $number) {
                        $structure = imap_fetchstructure($inbox, $number);
                        if (!$structure) throw new \RuntimeException('Unable to read MIME structure.');
                        return CandidateEmailContent::body($structure, fn ($part) => $part === ''
                            ? imap_body($inbox, $number, FT_PEEK) : imap_fetchbody($inbox, $number, $part, FT_PEEK));
                    });
                    if (!$result) {
                        if ($this->option('debug')) $this->warn('Unmatched or ambiguous: '.$from.' | '.$subject);
                        continue;
                    }
                    if ($result['status'] === 'imported') $count++;
                    elseif ($result['status'] === 'repaired') $repaired++;
                    if ($this->option('debug')) $this->line(ucfirst($result['status']).' on profile #'.$result['application_id'].': '.$from.' | '.$subject);
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

    private function importOutlook(CandidateReplyMailbox $mailbox): int
    {
        try {
            $count = app(MicrosoftReplyInbox::class)->sync($mailbox);
            $this->info("Imported {$count} Outlook candidate email replies.");
            return self::SUCCESS;
        } catch (MicrosoftInboxException $e) {
            $mailbox->update(['sync_error' => mb_substr($e->getMessage(), 0, 255)]);
            $this->error($e->getMessage());
            return self::FAILURE;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Outlook reply import failed.', ['exception_type' => $e::class]);
            $mailbox->update(['sync_error' => 'Outlook replies could not be saved. Contact the platform administrator.']);
            $this->error('Outlook replies could not be saved. See application logs.');
            return self::FAILURE;
        }
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

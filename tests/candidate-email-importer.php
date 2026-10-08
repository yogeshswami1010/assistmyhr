<?php
// Exercise the actual command against a fake mailbox and an in-memory model
// store, so this regression can run even without Composer/PHP IMAP installed.
namespace Illuminate\Console {
    class Command {
        const SUCCESS = 0; const FAILURE = 1;
        public array $output = [];
        public function option($key) { return $key === 'debug' ? true : null; }
        public function info($text) { $this->output[] = $text; }
        public function line($text) { $this->output[] = $text; }
        public function warn($text) { $this->output[] = $text; }
        public function error($text) { $this->output[] = $text; }
    }
}
namespace ReplyImporterTest {
    class Values {
        public function __construct(private array $values) {}
        public function toArray() { return $this->values; }
        public function count() { return count($this->values); }
        public function first() { return $this->values[0] ?? null; }
    }
    class Query {
        private array $predicates = []; private ?int $limit = null; private ?string $order = null;
        public function __construct(private string $model) {}
        public function where($field, $value) { $this->predicates[] = fn ($row) => ($row[$field] ?? null) === $value; return $this; }
        public function whereIn($field, $values) { $this->predicates[] = fn ($row) => in_array($row[$field] ?? null, $values, true); return $this; }
        public function whereRaw($expression, $bindings) {
            preg_match('/TRIM\((\w+)\)/', $expression, $matches);
            $field = $matches[1]; $value = $bindings[0];
            $this->predicates[] = fn ($row) => strtolower(trim($row[$field] ?? '')) === $value; return $this;
        }
        public function latest($field) { $this->order = $field; return $this; }
        public function limit($limit) { $this->limit = $limit; return $this; }
        private function rows() {
            $rows = array_values(array_filter($this->model::$rows, function ($row) {
                foreach ($this->predicates as $predicate) if (!$predicate($row)) return false;
                return true;
            }));
            if ($this->order) usort($rows, fn ($a, $b) => $b[$this->order] <=> $a[$this->order]);
            return $this->limit === null ? $rows : array_slice($rows, 0, $this->limit);
        }
        public function get($fields) { return new Values(array_map(fn ($row) => array_intersect_key($row, array_flip($fields)), $this->rows())); }
        public function pluck($field) { return new Values(array_column($this->rows(), $field)); }
        public function first() { $rows = $this->rows(); return $rows ? new $this->model($rows[0]) : null; }
    }
}
namespace App {
    class CandidateEmailMessage {
        public static array $rows = [];
        private array $original;
        public function __construct(private array $data) { $this->original = $data; }
        public static function where($field, $value) { return (new \ReplyImporterTest\Query(static::class))->where($field, $value); }
        public static function create($data) { $data['id'] = count(self::$rows) + 100; self::$rows[] = $data; }
        public function __get($field) { return $this->data[$field] ?? null; }
        public function fill($data) { $this->data = array_merge($this->data, $data); }
        public function isDirty() { return $this->data !== $this->original; }
        public function save() { foreach (self::$rows as &$row) if ($row['id'] === $this->data['id']) $row = $this->data; }
    }
    class JobApplication {
        public static array $rows = [];
        public static function whereRaw($expression, $bindings) { return (new \ReplyImporterTest\Query(static::class))->whereRaw($expression, $bindings); }
    }
}
namespace App\Console\Commands {
    function config($key) {
        if ($key === 'saas.enabled') return false;
        return $key === 'services.candidate_email_imap' ? ['host' => 'example.invalid','port' => 993] :
            ['username' => 'hr@example.invalid','password' => 'fake','from' => ['address' => 'hr@example.invalid']];
    }
    function imap_timeout(...$args) {}
    function imap_open($mailbox, $user, $password, $flags) { $GLOBALS['imapOpenFlags'] = $flags; return new \stdClass(); }
    function imap_status(...$args) { return (object) ['uidvalidity' => 10]; }
    function imap_search(...$args) { return [1, 2, 3]; }
    function imap_headerinfo($inbox, $number) {
        return (object) [
            'from' => [(object) ['mailbox' => $number === 2 ? 'alternate' : 'yogeshswami1010', 'host' => 'gmail.com']],
            'message_id' => '<reply'.$number.'@gmail.test>',
            'in_reply_to' => $number === 2 ? '<ats-future@example.invalid>' : '',
            'references' => $number === 2 ? '<root@somewhere.test> <ats-future@example.invalid>' : '',
            'subject' => $number === 3 ? 'Re: Ambiguous subject' : 'Re: Test template subjest 2',
            'udate' => strtotime('2026-10-02 17:56:00 UTC'),
        ];
    }
    function imap_fetchstructure(...$args) { return (object) ['type' => 0, 'subtype' => 'PLAIN', 'encoding' => 3]; }
    function imap_body($inbox, $number, $flags) { $GLOBALS['imapBodyFlags'][] = $flags; return base64_encode('just for test'); }
    function imap_close(...$args) {}
    function imap_errors() { return []; }
    function imap_alerts() { return []; }
    function imap_mime_header_decode($value) { return [(object) ['text' => $value, 'charset' => 'default']]; }
}
namespace {
    if (!function_exists('imap_open')) { function imap_open() {} }
    foreach (['IMAP_OPENTIMEOUT' => 1, 'IMAP_READTIMEOUT' => 2, 'OP_READONLY' => 2, 'SA_UIDVALIDITY' => 16, 'FT_PEEK' => 2] as $key => $value) if (!defined($key)) define($key, $value);
    require __DIR__.'/../app/Services/CandidateEmailThread.php';
    require __DIR__.'/../app/Services/CandidateEmailContent.php';
    require __DIR__.'/../app/Console/Commands/ImportCandidateEmailReplies.php';
    function checkImport($condition, $description) { if (!$condition) throw new \RuntimeException($description); }
    \App\JobApplication::$rows = [['id' => 1772,'email' => 'yogeshswami1010@gmail.com'], ['id' => 3280,'email' => 'yogeshswami1010@gmail.com']];
    \App\CandidateEmailMessage::$rows = [
        ['id' => 1, 'direction' => 'outbound', 'to_address' => 'yogeshswami1010@gmail.com', 'job_application_id' => 3280, 'subject' => 'Test template subjest 2', 'message_id' => null, 'created_at' => '2026-10-02 16:00:00'],
        ['id' => 2, 'direction' => 'outbound', 'to_address' => 'yogeshswami1010@gmail.com', 'job_application_id' => 1772, 'subject' => 'Future thread', 'message_id' => 'ats-future@example.invalid', 'created_at' => '2026-10-02 16:00:00'],
        ['id' => 3, 'direction' => 'inbound', 'job_application_id' => 1772, 'subject' => 'Re: Test template subjest 2', 'message_id' => '<reply1@gmail.test>', 'body' => 'an encoded body', 'read_at' => '2026-10-02 17:00:00'],
        ['id' => 4, 'direction' => 'outbound', 'to_address' => 'yogeshswami1010@gmail.com', 'job_application_id' => 1772, 'subject' => 'Ambiguous subject', 'message_id' => null, 'created_at' => '2026-10-02 16:00:00'],
        ['id' => 5, 'direction' => 'outbound', 'to_address' => 'yogeshswami1010@gmail.com', 'job_application_id' => 3280, 'subject' => 'Ambiguous subject', 'message_id' => null, 'created_at' => '2026-10-02 16:00:00'],
    ];
    $command = new \App\Console\Commands\ImportCandidateEmailReplies();
    checkImport($command->handle() === 0, 'Importer should succeed');
    $reply = \App\CandidateEmailMessage::$rows[2];
    checkImport($reply['job_application_id'] === 3280, 'Already imported reply must move from wrong profile to the sent conversation');
    checkImport($reply['body'] === 'just for test', 'Old MIME body must be repaired');
    checkImport($reply['read_at'] === null, 'Moved reply must become unread on correct profile');
    checkImport(count(\App\CandidateEmailMessage::$rows) === 6, 'Only the exact alternate-address thread should be newly imported');
    checkImport(\App\CandidateEmailMessage::$rows[5]['job_application_id'] === 1772, 'Exact parent links alternate reply address correctly');
    checkImport(str_contains(end($command->output), 'repaired 1'), 'Summary must report repairs separately from imports');
    checkImport($GLOBALS['imapOpenFlags'] === OP_READONLY && !array_diff($GLOBALS['imapBodyFlags'], [FT_PEEK]), 'Do not mark mailbox messages read');
    $again = new \App\Console\Commands\ImportCandidateEmailReplies();
    checkImport($again->handle() === 0 && count(\App\CandidateEmailMessage::$rows) === 6, 'Re-running must not duplicate replies');
    checkImport(str_contains(end($again->output), 'repaired 0'), 'Repeated import should leave repaired records unchanged');
    echo "PASS: actual importer repairs wrong-profile replies, decodes MIME, handles alternate senders, preserves mailbox read state, and is idempotent\n";
}

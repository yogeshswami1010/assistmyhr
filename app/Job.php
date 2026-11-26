<?php

namespace App;

use Carbon\Carbon;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use Sluggable;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    // Allowed status values (single source of truth)
    public const STATUS_OPEN    = 'open';
    public const STATUS_DRAFT   = 'draft';
    public const STATUS_CLOSED  = 'closed';
    public const STATUS_FILLED  = 'filled';
    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUSES = [
        self::STATUS_OPEN    => 'Open',
        self::STATUS_DRAFT   => 'Draft',
        self::STATUS_CLOSED  => 'Closed',
        self::STATUS_FILLED  => 'Filled',
        self::STATUS_ON_HOLD => 'On Hold',
    ];

    public function getJobDescriptionAttribute($value) { return tenant_html($value); }
    public function getJobRequirementAttribute($value) { return tenant_html($value); }

    protected $casts = [
        'work_experience_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'hiring_status' => 'string',
        'required_columns' => 'array',
        'meta_details' => 'array',
        'section_visibility' => 'array',
        'show_on_consortium'   => 'boolean',
        'show_on_assistmyday'  => 'boolean',
        // NEW: the three flags are now set automatically from the form
        'show_job_type'        => 'boolean',
        'show_work_experience' => 'boolean',
        'show_salary'          => 'boolean',
        'publish_on_jooble'    => 'boolean',
        'publish_on_assistmyhr' => 'boolean',
        'publish_on_postjobfree' => 'boolean',
        'publish_on_jobillico' => 'boolean',
        'publish_on_talent' => 'boolean',
        'publish_on_whatjobs' => 'boolean',
        'whatjobs_settings' => 'array',
        'whatjobs_sync' => 'array',
    ];

    protected $appends = [
        'active',
    ];

    public function applications()
    {
        return $this->belongsToMany(JobApplication::class);
    }

    public function category()
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function skills()
    {
        return $this->hasMany(JobSkill::class, 'job_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // CHANGED: only "open" jobs show on the website
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    // CHANGED: no start_date check. Date is auto-set on creation, so "open" is enough.
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeJooblePublished($query)
    {
        return $query->publicJobPage()->where('publish_on_jooble', true);
    }

    public function scopeWithinPublicationWindow($query)
    {
        $today = now()->toDateString();

        return $query->where(function ($query) use ($today) {
            $query->whereDate('start_date', '<=', $today)
                ->where('start_date', 'not like', '0000-00-00%')
                ->orWhere(fn ($query) => $query->where(fn ($query) => $query->whereNull('start_date')->orWhere('start_date', 'like', '0000-00-00%'))
                    ->whereDate('created_at', '<=', $today));
        })->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', 'like', '0000-00-00%')->orWhereDate('end_date', '>=', $today));
    }

    public function publicationDate()
    {
        return str_starts_with((string) $this->getRawOriginal('start_date'), '0000-00-00')
            ? $this->created_at : ($this->start_date ?? $this->created_at);
    }

    public function publicationEndDate()
    {
        return str_starts_with((string) $this->getRawOriginal('end_date'), '0000-00-00') ? null : $this->end_date;
    }

    public function setPostingStatus(string $status): void
    {
        $this->status = $status;

        if ($status === self::STATUS_OPEN && $this->publicationEndDate()?->lt(today())) {
            $this->end_date = null;
        }
    }

    public function joobleRegion(): string
    {
        return $this->jobLocation->pluck('location')->map(fn ($location) => trim((string) $location))->filter()->unique()->implode(', ')
            ?: trim((string) $this->company_location);
    }

    public function publicLocationLabel(): string
    {
        $label = $this->jobLocation->pluck('location')->implode(', ');
        if (trim($label) !== '') {
            return $label;
        }

        return trim((string) $this->location?->location) ?: trim((string) $this->company_location);
    }

    public function publicSalaryLabel(): ?string
    {
        if (!$this->show_salary) {
            return null;
        }

        $amounts = match ($this->pay_type) {
            'Range' => [$this->starting_salary, $this->maximum_salary],
            'Maximum' => [$this->maximum_salary ?? $this->starting_salary],
            default => [$this->starting_salary],
        };
        $amounts = array_filter($amounts, 'is_numeric');
        if ($amounts === []) {
            return null;
        }

        $symbol = $this->currency?->currency_symbol ?? '';
        $formatted = array_map(static function ($amount) use ($symbol) {
            $amount = (float) $amount;

            return $symbol.number_format($amount, $amount === floor($amount) ? 0 : 2);
        }, $amounts);

        return implode(' - ', $formatted).($this->pay_according ? ' / '.$this->pay_according : '');
    }

    public function supportsTalentPublishing(): bool
    {
        return $this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'publish_on_talent');
    }

    public function talentLocationFields(): array
    {
        $location = $this->jobLocation->first() ?? $this->location;
        $address = trim((string) ($location?->location ?: $this->company_location));
        $parts = preg_split('/\s*,\s*/', $address, -1, PREG_SPLIT_NO_EMPTY);
        $country = trim((string) $location?->country?->country_name);
        $suffix = (string) end($parts);

        if (count($parts) >= 2 && $suffix !== '') {
            $countryCode = $location?->country?->country_code;
            if ($country !== '' && (strcasecmp($suffix, $country) === 0 || strcasecmp($suffix, (string) $countryCode) === 0)) {
                array_pop($parts);
            } elseif ($country === '' && (strlen($suffix) > 2 || count($parts) >= 3)) {
                $country = (string) Country::on($this->getConnectionName())
                    ->where(fn ($query) => $query->whereRaw('LOWER(country_name) = ?', [mb_strtolower($suffix)])
                        ->orWhere('country_code', strtoupper($suffix)))
                    ->value('country_name');
                if ($country !== '') {
                    array_pop($parts);
                }
            }
        }

        $postalCode = '(?:[A-Z]\d[A-Z]\s?\d[A-Z]\d|\d{5}(?:-\d{4})?)';
        if (preg_match('/^'.$postalCode.'$/i', (string) end($parts))) {
            array_pop($parts);
        }
        $state = count($parts) >= 2 ? preg_replace('/\s+'.$postalCode.'$/i', '', array_pop($parts)) : '';
        $city = $state !== '' || (count($parts) === 1 && filled($this->location_state)) ? (string) end($parts) : '';

        if ($country === '') {
            $postalText = trim((string) $this->location_postal_code) ?: $address;
            if (preg_match('/\b[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z]\s?\d[ABCEGHJ-NPRSTV-Z]\d\b/i', $postalText)) {
                $country = 'Canada';
            }
        }

        return [
            'city' => trim((string) $this->location_city) ?: $city,
            'state' => trim((string) $this->location_state) ?: $state,
            'country' => $country,
        ];
    }

    public function talentFeedIssue(string $companyName, ?array $location = null): ?string
    {
        $location ??= $this->talentLocationFields();
        $description = trim(html_entity_decode(strip_tags($this->job_description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return match (true) {
            blank($this->slug) || blank($this->title) => 'The job needs a title and a public job link.',
            blank($companyName) => 'Add a company name in Business Settings.',
            in_array('', $location, true) =>
                'Add the city, state or province, and country to the job location.',
            mb_strlen($description) < 50 => 'Add a job description with at least 50 characters.',
            default => null,
        };
    }

    public function joobleContentIssue(): ?string
    {
        if (blank($this->slug) || blank($this->title)) {
            return 'The job needs a title and a public job link.';
        }
        if (blank($this->joobleRegion())) {
            return 'Add a job location.';
        }
        $description = html_entity_decode(strip_tags($this->job_description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!preg_match('/[^\s\p{Z}]/u', $description)) {
            return 'Add a job description.';
        }

        return null;
    }

    public function postJobFreeCompanyIssue(?string $companyName = null): ?string
    {
        $companyName ??= CompanySetting::on($this->getConnectionName())->value('company_name');
        if (blank($companyName)) {
            return 'PostJobFree requires a company name in Business Settings.';
        }

        return null;
    }

    public function scopePublicJobPage($query)
    {
        return $query->open()->withinPublicationWindow()
            ->whereHas('company', fn ($query) => $query->where('status', 'active'));
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['title', 'location.location'],
            ],
        ];
    }

    // CHANGED: uses "open" instead of "active"
    public static function activeJobs()
    {
        return Job::open()->get();
    }

    public static function activeJobsCount()
    {
        return Job::open()->count();
    }

    // CHANGED: "active" now means status is open
    public function getActiveAttribute()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'job_questions');
    }

    public function workExperience()
    {
        return $this->belongsTo(WorkExperience::class, 'work_experience_id');
    }

    public function getWorkExperienceLabelAttribute()
    {
        $ids = $this->work_experience_ids ?: array_filter([$this->work_experience_id]);

        return WorkExperience::on($this->getConnectionName())->whereIn('id', $ids)->pluck('work_experience')->implode(', ');
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class, 'job_type_id');
    }

    public function jobLocation()
    {
        return $this->hasManyThrough(
            JobLocation::class,
            JobJobLocation::class,
            'job_id',
            'id',
            'id',
            'location_id');
    }

    public function location()
    {
        return $this->belongsTo(JobLocation::class, 'location_id');
    }

    public function experience()
    {
        return $this->belongsTo(\App\WorkExperience::class, 'work_experience_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function statuses()
    {
        return $this->hasMany(ApplicationStatus::class, 'job_id')->orderBy('position');
    }
}

<?php

namespace App\Support;

use App\Job;

class CandidateJobLabel
{
    public static function html(?Job $job): string
    {
        $title = e($job ? ucfirst($job->title) : '—');
        if (!$job?->trashed()) { return $title; }

        return $title.' <span style="display:inline-block;margin-left:6px;padding:3px 8px;border-radius:20px;background:#fff1f2;color:#be123c;font-size:10px;font-weight:600;white-space:nowrap">Deleted job</span>';
    }
}

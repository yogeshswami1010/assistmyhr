<?php

namespace App\Saas;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotaService
{
    public function assertCanCreate(string $table): void
    {
        $tenant = app(TenantContext::class)->current();
        if (!$tenant) { return; }
        $field = ['users' => 'max_users', 'jobs' => 'max_jobs', 'job_applications' => 'max_candidates'][$table] ?? null;
        $limit = $field ? $tenant->subscription?->plan?->$field : null;
        if ($limit !== null && DB::table($table)->count() >= $limit) {
            throw ValidationException::withMessages(['subscription' => 'Your plan has reached its '.str_replace('_', ' ', $field).' limit. Contact the administrator to upgrade.']);
        }
    }

    public function usage(): array
    {
        return [
            'users' => DB::table('users')->count(), 'jobs' => DB::table('jobs')->count(),
            'candidates' => DB::table('job_applications')->count(), 'storage_bytes' => $this->storageBytes(),
        ];
    }

    public function storageBytes(): int
    {
        $root = app(TenantContext::class)->root();
        $total = 0;
        foreach (['uploads', 'private/candidate-calls'] as $directory) {
            if (!is_dir($root.'/'.$directory)) { continue; }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/'.$directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($directory === 'uploads' && str_starts_with(str_replace('\\', '/', $file->getPathname()), str_replace('\\', '/', $root).'/uploads/temp/')) { continue; }
                if ($file->isFile() && !$file->isLink()) { $total += $file->getSize(); }
            }
        }
        return $total;
    }

    public function assertUploadFits(int $bytes): void
    {
        $tenant = app(TenantContext::class)->current();
        $limit = $tenant?->subscription?->plan?->storage_mb;
        if ($limit !== null && $this->storageBytes() + $bytes > $limit * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Your plan storage limit has been reached.']);
        }
    }
}

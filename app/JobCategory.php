<?php

namespace App;

use App\Services\WhatJobsJobDetails;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model
{
    public static function addDefaults(ConnectionInterface $connection): void
    {
        $categories = $connection->table('job_categories');
        $existing = $categories->pluck('name')->map(fn ($name) => mb_strtolower(trim($name)))->all();
        $now = now();
        $missing = [];
        foreach (WhatJobsJobDetails::SECTORS as $name) {
            if (!in_array(mb_strtolower($name), $existing, true)) {
                $missing[] = ['name' => $name, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        if ($missing !== []) {
            $categories->insert($missing);
        }
    }
    protected $guarded = ['id'];

    public function skills(){
        return $this->hasMany(Skill::class, 'category_id');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'category_id');
    }
}

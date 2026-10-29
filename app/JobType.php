<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobType extends Model
{
    public const DEFAULT_TYPES = ['Full time', 'Part time', 'Contract', 'Internship', 'Trainee'];

    public static function forJobForm()
    {
        $types = static::all();
        $normalize = static function ($name) {
            $name = strtolower(trim(preg_replace('/[\s-]+/', ' ', $name)));

            return $name === 'on contract' ? 'contract' : $name;
        };
        $existing = $types->map(fn ($type) => $normalize($type->job_type))->all();

        foreach (static::DEFAULT_TYPES as $name) {
            if (in_array($normalize($name), $existing, true)) {
                continue;
            }

            $type = new static;
            $type->job_type = $name;
            $type->save();
            $types->push($type);
        }

        return $types;
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TodoItem extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'tag',
        'due_date',
        'position',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }

    public function scopeVisibleTo($query, int $userId)
    {
        return $query->where(fn ($query) => $query->where('user_id', $userId)->orWhere('assigned_to', $userId));
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}

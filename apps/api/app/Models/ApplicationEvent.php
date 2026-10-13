<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ApplicationEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'event_name',
        'occurred_at',
        'request_id',
        'workflow_id',
        'assessment_id',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'properties' => 'array',
        ];
    }
}

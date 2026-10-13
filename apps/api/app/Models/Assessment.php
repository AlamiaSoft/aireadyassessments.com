<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_email',
        'job_title',
        'job_description',
        'source_file_path',
        'source_file_name',
        'source_file_sha256',
        'public_token_hash',
        'request_id',
        'status',
        'report',
        'failure_code',
        'processing_started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'report' => 'array',
            'processing_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}

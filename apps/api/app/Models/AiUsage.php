<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_id',
        'assessment_id',
        'task',
        'provider',
        'model',
        'status',
        'http_status',
        'latency_ms',
        'input_tokens',
        'output_tokens',
        'estimated_cost_usd',
        'fallback_step',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'latency_ms' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'estimated_cost_usd' => 'decimal:8',
        ];
    }
}

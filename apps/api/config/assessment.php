<?php

return [
    'private_disk' => env('ASSESSMENT_PRIVATE_DISK', 'local'),
    'max_upload_kb' => (int) env('ASSESSMENT_MAX_UPLOAD_KB', 10240),
    'max_text_chars' => (int) env('ASSESSMENT_MAX_TEXT_CHARS', 30000),
    'min_job_description_chars' => (int) env('ASSESSMENT_MIN_JOB_DESCRIPTION_CHARS', 80),
    'status_token_ttl_days' => (int) env('ASSESSMENT_STATUS_TOKEN_TTL_DAYS', 7),
    'document_retention_days' => (int) env('DOCUMENT_RETENTION_DAYS', 30),
    'ai' => [
        'chat_completions_url' => env('AI_CHAT_COMPLETIONS_URL'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL'),
        'provider' => env('AI_PROVIDER_LABEL', 'configured-provider'),
        'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 60),
        'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 1800),
        'input_price_per_million_usd' => env('AI_INPUT_PRICE_PER_MILLION_USD') !== null && env('AI_INPUT_PRICE_PER_MILLION_USD') !== '' ? (float) env('AI_INPUT_PRICE_PER_MILLION_USD') : null,
        'output_price_per_million_usd' => env('AI_OUTPUT_PRICE_PER_MILLION_USD') !== null && env('AI_OUTPUT_PRICE_PER_MILLION_USD') !== '' ? (float) env('AI_OUTPUT_PRICE_PER_MILLION_USD') : null,
        'daily_spend_limit_usd' => (float) env('AI_DAILY_SPEND_LIMIT_USD', 5.0),
        'enforce_spend_limit' => (bool) env('AI_ENFORCE_SPEND_LIMIT', true),
    ],
];

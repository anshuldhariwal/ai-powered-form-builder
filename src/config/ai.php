<?php

return [
    'provider' => env('LLM_PROVIDER', 'local'),
    'model' => env('LLM_MODEL', 'deterministic-fallback'),
    'api_key' => env('LLM_API_KEY'),
    'base_url' => rtrim(env('LLM_BASE_URL', 'https://api.openai.com/v1'), '/'),
    'timeout' => (int) env('AI_REQUEST_TIMEOUT_SECONDS', 60),
];

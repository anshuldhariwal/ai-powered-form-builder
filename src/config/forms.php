<?php

return [
    'schema_limits' => [
        'max_bytes' => (int) env('FORM_MAX_SCHEMA_BYTES', 1_048_576),
        'max_steps' => (int) env('FORM_MAX_STEPS', 20),
        'max_sections_per_step' => (int) env('FORM_MAX_SECTIONS_PER_STEP', 30),
        'max_fields' => (int) env('FORM_MAX_FIELDS', 150),
        'max_options_per_field' => (int) env('FORM_MAX_OPTIONS_PER_FIELD', 100),
        'max_conditions' => (int) env('FORM_MAX_CONDITIONS', 300),
    ],
];

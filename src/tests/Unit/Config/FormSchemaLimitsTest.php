<?php

it('provides the approved form schema limit defaults', function () {
    expect(config('forms.schema_limits'))->toBe([
        'max_bytes' => 1_048_576,
        'max_steps' => 20,
        'max_sections_per_step' => 30,
        'max_fields' => 150,
        'max_options_per_field' => 100,
        'max_conditions' => 300,
    ]);
});

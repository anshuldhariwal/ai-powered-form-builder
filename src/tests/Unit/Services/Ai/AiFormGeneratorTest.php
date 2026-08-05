<?php

use App\Services\Ai\AiFormGenerator;
use App\Services\Forms\FormSchemaValidator;

uses(Tests\TestCase::class);

test('the fallback generator produces a contract-valid editable schema', function () {
    $schema = app(AiFormGenerator::class)->generate('Create an internship application with required phone');

    app(FormSchemaValidator::class)->validate($schema);
    expect($schema['form']['title'])->toContain('Internship')
        ->and(collect($schema['steps'][0]['sections'][0]['fields'])->pluck('key'))->toContain('phone');
});

test('AI editing preserves existing stable IDs while applying a bounded change', function () {
    $generator = app(AiFormGenerator::class);
    $schema = $generator->generate('Create a contact form with phone');
    $fieldId = $schema['steps'][0]['sections'][0]['fields'][0]['id'];

    $edited = $generator->generate('Make phone required', $schema);

    expect($edited['steps'][0]['sections'][0]['fields'][0]['id'])->toBe($fieldId)
        ->and(collect($edited['steps'][0]['sections'][0]['fields'])->firstWhere('type', 'phone')['required'])->toBeTrue();
});

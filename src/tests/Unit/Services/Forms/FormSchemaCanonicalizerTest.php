<?php

use App\Services\Forms\FormSchemaCanonicalizer;

it('ignores object key order when canonicalizing a schema', function () {
    $canonicalizer = new FormSchemaCanonicalizer;

    $first = [
        'schema_version' => '1.0',
        'form' => ['title' => 'Café / Feedback', 'description' => null],
        'steps' => [['title' => 'First', 'id' => 'step_first']],
    ];
    $second = [
        'steps' => [['id' => 'step_first', 'title' => 'First']],
        'form' => ['description' => null, 'title' => 'Café / Feedback'],
        'schema_version' => '1.0',
    ];

    expect($canonicalizer->canonicalize($first))
        ->toBe($canonicalizer->canonicalize($second))
        ->toContain('Café / Feedback')
        ->and($canonicalizer->checksum($first))
        ->toBe($canonicalizer->checksum($second))
        ->toMatch('/^[a-f0-9]{64}$/');
});

it('preserves list order because it controls form layout', function () {
    $canonicalizer = new FormSchemaCanonicalizer;
    $first = ['steps' => [['id' => 'step_one'], ['id' => 'step_two']]];
    $reordered = ['steps' => [['id' => 'step_two'], ['id' => 'step_one']]];

    expect($canonicalizer->checksum($first))
        ->not->toBe($canonicalizer->checksum($reordered));
});

it('preserves fractional zeroes', function () {
    $canonicalizer = new FormSchemaCanonicalizer;

    expect($canonicalizer->canonicalize(['value' => 1.0]))
        ->toBe('{"value":1.0}')
        ->not->toBe($canonicalizer->canonicalize(['value' => 1]));
});

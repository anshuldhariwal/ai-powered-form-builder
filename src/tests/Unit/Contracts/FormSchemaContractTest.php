<?php

use Opis\JsonSchema\Validator;
use Tests\TestCase;

uses(TestCase::class);

function contractJson(string $relativePath): object
{
    $contents = file_get_contents(base_path('../contracts/'.$relativePath));

    if ($contents === false) {
        throw new RuntimeException('Unable to read contract fixture: '.$relativePath);
    }

    return json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
}

it('accepts every valid shared form-schema fixture', function () {
    $validator = new Validator;
    $schema = contractJson('form-schema.v1.json');
    $fixtures = glob(base_path('../contracts/fixtures/valid/*.json')) ?: [];

    expect($fixtures)->not->toBeEmpty();

    foreach ($fixtures as $fixture) {
        $data = json_decode(file_get_contents($fixture) ?: '', false, 512, JSON_THROW_ON_ERROR);

        expect($validator->validate($data, $schema)->isValid())
            ->toBeTrue(basename($fixture).' should be valid.');
    }
});

it('accepts every reviewer-facing contract example', function () {
    $validator = new Validator;
    $schema = contractJson('form-schema.v1.json');
    $examples = glob(base_path('../contracts/examples/*.json')) ?: [];

    expect($examples)->toHaveCount(2);

    foreach ($examples as $example) {
        $data = json_decode(file_get_contents($example) ?: '', false, 512, JSON_THROW_ON_ERROR);

        expect($validator->validate($data, $schema)->isValid())
            ->toBeTrue(basename($example).' should be valid.');
    }
});

it('rejects every invalid shared form-schema fixture', function () {
    $validator = new Validator;
    $schema = contractJson('form-schema.v1.json');
    $fixtures = glob(base_path('../contracts/fixtures/invalid/*.json')) ?: [];

    expect($fixtures)->not->toBeEmpty();

    foreach ($fixtures as $fixture) {
        $data = json_decode(file_get_contents($fixture) ?: '', false, 512, JSON_THROW_ON_ERROR);

        expect($validator->validate($data, $schema)->isValid())
            ->toBeFalse(basename($fixture).' should be invalid.');
    }
});

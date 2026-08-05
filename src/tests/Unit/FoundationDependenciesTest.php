<?php

use Opis\JsonSchema\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpWord\PhpWord;

test('PHPWord can construct a document', function () {
    $document = new PhpWord;
    $text = $document->addSection()->addText('FormForge AI');

    expect($text->getText())->toBe('FormForge AI');
});

test('PhpSpreadsheet can construct a workbook', function () {
    $workbook = new Spreadsheet;
    $workbook->getActiveSheet()->setCellValue('A1', 'field_key');

    expect($workbook->getActiveSheet()->getCell('A1')->getValue())->toBe('field_key');
});

test('Opis validates JSON data against a schema', function () {
    $schema = json_decode('{"type":"object","required":["name"],"properties":{"name":{"type":"string"}}}');
    $data = json_decode('{"name":"Application form"}');

    expect((new Validator)->validate($data, $schema)->isValid())->toBeTrue();
});

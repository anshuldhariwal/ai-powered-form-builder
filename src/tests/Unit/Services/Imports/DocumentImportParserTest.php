<?php

use App\Services\Forms\FormSchemaValidator;
use App\Services\Imports\DocumentImportParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(Tests\TestCase::class);

test('a header-row workbook becomes a valid editable form candidate', function () {
    $path = tempnam(sys_get_temp_dir(), 'formforge-import-').'.xlsx';
    $sheet = (new Spreadsheet)->getActiveSheet();
    $sheet->fromArray([['Full name', 'Email address', 'Department']]);
    (new Xlsx($sheet->getParent()))->save($path);

    $result = app(DocumentImportParser::class)->parse($path, 'employee-onboarding.xlsx');
    app(FormSchemaValidator::class)->validate($result['schema']);

    expect($result['schema']['steps'][0]['sections'][0]['fields'])->toHaveCount(3)
        ->and($result['warnings'])->not->toBeEmpty();
    unlink($path);
});

test('a field-definition workbook uses the label column', function () {
    $path = tempnam(sys_get_temp_dir(), 'formforge-import-').'.xlsx';
    $sheet = (new Spreadsheet)->getActiveSheet();
    $sheet->fromArray([['key', 'label', 'type'], ['name', 'Candidate name', 'text'], ['email', 'Candidate email', 'email']]);
    (new Xlsx($sheet->getParent()))->save($path);

    $result = app(DocumentImportParser::class)->parse($path, 'fields.xlsx');

    expect(collect($result['schema']['steps'][0]['sections'][0]['fields'])->pluck('label')->all())->toBe(['Candidate name', 'Candidate email']);
    unlink($path);
});

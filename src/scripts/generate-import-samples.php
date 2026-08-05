<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

$directory = dirname(__DIR__, 2).'/samples/imports';

$definitions = new Spreadsheet;
$definitions->getActiveSheet()->fromArray([
    ['key', 'label', 'type'],
    ['full_name', 'Full name', 'text'],
    ['email', 'Email address', 'email'],
    ['department', 'Preferred department', 'select'],
]);
(new Xlsx($definitions))->save($directory.'/field-definition-layout.xlsx');

$headers = new Spreadsheet;
$headers->getActiveSheet()->fromArray([['Employee name', 'Work email', 'Start date', 'Manager']]);
(new Xlsx($headers))->save($directory.'/plain-header-row.xlsx');

$word = new PhpWord;
$section = $word->addSection();
$section->addTitle('Internship application', 1);
$section->addText('Full name?');
$section->addText('Email address?');
$section->addText('Phone number?');
$section->addText('Why do you want this internship?');
IOFactory::createWriter($word, 'Word2007')->save($directory.'/internship-application.docx');

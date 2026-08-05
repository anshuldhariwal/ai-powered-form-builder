<?php

namespace App\Services\Imports;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetFactory;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use RuntimeException;

class DocumentImportParser
{
    /** @return array{schema: array<string, mixed>, warnings: list<string>} */
    public function parse(string $path, string $name): array
    {
        $extension = Str::lower(pathinfo($name, PATHINFO_EXTENSION));
        $labels = $extension === 'docx' ? $this->docx($path) : $this->xlsx($path);
        if ($labels === []) {
            throw new RuntimeException('No importable questions were found.');
        }
        $fields = [];
        foreach (array_values(array_unique($labels)) as $index => $label) {
            $key = Str::snake(Str::ascii($label)) ?: 'field_'.($index + 1);
            $fields[] = $this->field($key, $label, str_contains(Str::lower($label), 'email') ? 'email' : 'text');
        }
        $title = Str::of(pathinfo($name, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->title()->value();

        return ['schema' => ['schema_version' => '1.0', 'form' => ['title' => $title, 'description' => 'Imported document; review all inferred fields before publishing.', 'submit_label' => 'Submit', 'success_message' => 'Thank you for your response.'], 'steps' => [['id' => 'step_imported', 'title' => 'Imported questions', 'description' => null, 'sections' => [['id' => 'section_imported', 'title' => 'Questions', 'description' => null, 'fields' => $fields]]]], 'conditions' => [], 'settings' => ['show_progress' => true, 'allow_multiple_submissions' => true]], 'warnings' => ['Field types were inferred deterministically. Review labels, types, required flags, and options before publishing.']];
    }

    /** @return list<string> */
    private function docx(string $path): array
    {
        $document = WordFactory::load($path);
        $labels = [];
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text = trim((string) $element->getText());
                    if ($text !== '') {
                        $labels[] = rtrim($text, " ?: \t\n\r\0\x0B");
                    }
                }
            }
        }

        return $labels;
    }

    /** @return list<string> */
    private function xlsx(string $path): array
    {
        $rows = SpreadsheetFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        if ($rows === []) {
            return [];
        }
        $headers = array_map(fn ($value) => trim((string) $value), array_shift($rows));
        $labelColumn = array_search('label', array_map('strtolower', $headers), true);
        if ($labelColumn !== false) {
            return array_values(array_filter(array_map(fn ($row) => trim((string) ($row[$labelColumn] ?? '')), $rows)));
        }

        return array_values(array_filter($headers));
    }

    /** @return array<string, mixed> */
    private function field(string $key, string $label, string $type): array
    {
        return ['id' => 'field_'.Str::lower((string) Str::ulid()), 'type' => $type, 'key' => Str::limit($key, 100, ''), 'label' => Str::limit($label, 255, ''), 'placeholder' => null, 'help_text' => null, 'default' => null, 'required' => false, 'options' => [], 'validation' => ['min_length' => null, 'max_length' => null, 'min' => null, 'max' => null, 'email' => false, 'url' => false, 'numeric' => false, 'regex' => null, 'allowed_file_types' => [], 'max_file_size_kb' => null]];
    }
}

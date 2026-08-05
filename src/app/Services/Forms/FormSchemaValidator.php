<?php

namespace App\Services\Forms;

use Illuminate\Validation\ValidationException;
use Opis\JsonSchema\Validator;

class FormSchemaValidator
{
    /** @param array<string, mixed> $schema */
    public function validate(array $schema): void
    {
        $contract = $this->decodeFile(base_path('../contracts/form-schema.v1.json'));
        $data = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        if (! (new Validator)->validate($data, $contract)->isValid()) {
            throw ValidationException::withMessages(['schema' => 'The form does not match schema version 1.0.']);
        }

        $errors = $this->semanticErrors($schema);

        if ($errors !== []) {
            throw ValidationException::withMessages(['schema' => $errors]);
        }
    }

    /** @return array<int, string> */
    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    private function semanticErrors(array $schema): array
    {
        $limits = config('forms.schema_limits');
        $errors = [];
        $ids = [];
        $keys = [];
        $fieldCount = 0;

        $bytes = strlen(json_encode($schema, JSON_THROW_ON_ERROR));
        if ($bytes > $limits['max_bytes']) {
            $errors[] = 'The form schema is too large.';
        }
        if (count($schema['steps']) > $limits['max_steps']) {
            $errors[] = 'The form has too many steps.';
        }
        if (count($schema['conditions']) > $limits['max_conditions']) {
            $errors[] = 'The form has too many conditions.';
        }

        foreach ($schema['steps'] as $step) {
            $this->collectUnique($step['id'], $ids, 'structural ID', $errors);
            if (count($step['sections']) > $limits['max_sections_per_step']) {
                $errors[] = 'A step has too many sections.';
            }

            foreach ($step['sections'] as $section) {
                $this->collectUnique($section['id'], $ids, 'structural ID', $errors);
                foreach ($section['fields'] as $field) {
                    $fieldCount++;
                    $this->collectUnique($field['id'], $ids, 'structural ID', $errors);
                    $this->collectUnique($field['key'], $keys, 'field key', $errors);
                    if (count($field['options']) > $limits['max_options_per_field']) {
                        $errors[] = "Field {$field['key']} has too many options.";
                    }
                    $optionValues = array_column($field['options'], 'value');
                    if (count($optionValues) !== count(array_unique($optionValues))) {
                        $errors[] = "Field {$field['key']} has duplicate option values.";
                    }
                    $optionTypes = ['select', 'radio', 'checkbox'];
                    if (! in_array($field['type'], $optionTypes, true) && $field['options'] !== []) {
                        $errors[] = "Field {$field['key']} cannot have options.";
                    }
                    if (in_array($field['type'], ['select', 'radio'], true) && $field['options'] === []) {
                        $errors[] = "Field {$field['key']} requires options.";
                    }
                    if ($field['type'] === 'heading' && ($field['required'] || $field['default'] !== null)) {
                        $errors[] = "Heading {$field['key']} cannot be required or have a default.";
                    }
                    $validation = $field['validation'];
                    if ($validation['min_length'] !== null && $validation['max_length'] !== null && $validation['min_length'] > $validation['max_length']) {
                        $errors[] = "Field {$field['key']} has invalid length bounds.";
                    }
                    if ($validation['min'] !== null && $validation['max'] !== null && $validation['min'] > $validation['max']) {
                        $errors[] = "Field {$field['key']} has invalid numeric bounds.";
                    }
                    if ($validation['regex'] !== null && @preg_match($validation['regex'], '') === false) {
                        $errors[] = "Field {$field['key']} has an invalid regular expression.";
                    }
                }
            }
        }

        if ($fieldCount > $limits['max_fields']) {
            $errors[] = 'The form has too many fields.';
        }

        foreach ($schema['conditions'] as $condition) {
            $this->collectUnique($condition['id'], $ids, 'structural ID', $errors);
            if (! isset($keys[$condition['source_field_key']])) {
                $errors[] = "Condition {$condition['id']} has an unknown source field.";
            }
            if (! isset($ids[$condition['target_id']])) {
                $errors[] = "Condition {$condition['id']} has an unknown target.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<string, true>  $seen
     * @param  array<int, string>  $errors
     */
    private function collectUnique(string $value, array &$seen, string $label, array &$errors): void
    {
        if (isset($seen[$value])) {
            $errors[] = "Duplicate {$label}: {$value}.";
        }
        $seen[$value] = true;
    }

    private function decodeFile(string $path): object
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('Shared form contract is unavailable.');
        }

        return json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
    }
}

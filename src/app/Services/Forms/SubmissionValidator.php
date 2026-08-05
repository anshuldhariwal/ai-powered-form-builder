<?php

namespace App\Services\Forms;

use Illuminate\Validation\ValidationException;

class SubmissionValidator
{
    /** @param array<string, mixed> $schema
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function validate(array $schema, array $answers): array
    {
        $errors = [];
        $allowed = [];
        $states = $this->fieldStates($schema, $answers);

        foreach ($schema['steps'] as $step) {
            foreach ($step['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    if ($field['type'] === 'heading') {
                        continue;
                    }

                    $key = $field['key'];
                    $allowed[$key] = true;
                    $value = $answers[$key] ?? null;
                    $empty = $value === null || $value === '' || $value === [];
                    $state = $states[$field['id']] ?? ['visible' => true, 'required' => $field['required']];
                    if (! $state['visible']) {
                        if (! $empty) {
                            $errors[$key] = "{$field['label']} is hidden and must not be submitted.";
                        }

                        continue;
                    }
                    if ($state['required'] && $empty) {
                        $errors[$key] = "{$field['label']} is required.";

                        continue;
                    }
                    if ($empty) {
                        continue;
                    }

                    $message = $this->invalidReason($field, $value);
                    if ($message !== null) {
                        $errors[$key] = "{$field['label']} {$message}";
                    }
                }
            }
        }

        foreach (array_keys($answers) as $key) {
            if (! isset($allowed[$key])) {
                $errors[$key] = 'Unknown field.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return array_intersect_key($answers, $allowed);
    }

    /** @param array<string, mixed> $schema
     * @param  array<string, mixed>  $answers
     * @return array<string, array{visible: bool, required: bool}>
     */
    private function fieldStates(array $schema, array $answers): array
    {
        $states = [];
        $targets = [];
        foreach ($schema['steps'] as $step) {
            $stepFields = [];
            foreach ($step['sections'] as $section) {
                $sectionFields = [];
                foreach ($section['fields'] as $field) {
                    $sectionFields[] = $field['id'];
                    $stepFields[] = $field['id'];
                    $states[$field['id']] = ['visible' => true, 'required' => $field['required']];
                    $targets[$field['id']] = [$field['id']];
                }
                $targets[$section['id']] = $sectionFields;
            }
            $targets[$step['id']] = $stepFields;
        }
        foreach ($schema['conditions'] as $condition) {
            if ($condition['action'] === 'show') {
                foreach ($targets[$condition['target_id']] ?? [] as $fieldId) {
                    $states[$fieldId]['visible'] = false;
                }
            }
        }
        foreach ($schema['conditions'] as $condition) {
            if (! $this->conditionMatches($condition, $answers[$condition['source_field_key']] ?? null)) {
                continue;
            }
            foreach ($targets[$condition['target_id']] ?? [] as $fieldId) {
                match ($condition['action']) {
                    'show' => $states[$fieldId]['visible'] = true,
                    'hide' => $states[$fieldId]['visible'] = false,
                    'require' => $states[$fieldId]['required'] = true,
                    'optional' => $states[$fieldId]['required'] = false,
                    default => null,
                };
            }
        }

        return $states;
    }

    /** @param array<string, mixed> $condition */
    private function conditionMatches(array $condition, mixed $actual): bool
    {
        return match ($condition['operator']) {
            'equals' => $actual == $condition['value'],
            'not_equals' => $actual != $condition['value'],
            'contains' => str_contains((string) $actual, (string) $condition['value']),
            'greater_than' => is_numeric($actual) && $actual > $condition['value'],
            'less_than' => is_numeric($actual) && $actual < $condition['value'],
            'is_empty' => $actual === null || $actual === '' || $actual === [],
            'is_not_empty' => $actual !== null && $actual !== '' && $actual !== [],
            default => false,
        };
    }

    /** @param array<string, mixed> $field */
    private function invalidReason(array $field, mixed $value): ?string
    {
        $type = $field['type'];
        $rules = $field['validation'];
        if (in_array($type, ['text', 'textarea', 'email', 'phone', 'date'], true) && ! is_string($value)) {
            return 'must be text.';
        }
        if (($type === 'number' || $type === 'rating' || $rules['numeric']) && ! is_numeric($value)) {
            return 'must be a number.';
        }
        if (($type === 'email' || $rules['email']) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return 'must be a valid email address.';
        }
        if ($rules['url'] && filter_var($value, FILTER_VALIDATE_URL) === false) {
            return 'must be a valid URL.';
        }
        if ($type === 'date' && date_create_from_format('Y-m-d', (string) $value) === false) {
            return 'must be a valid date.';
        }
        if ($type === 'phone' && preg_match('/^[+()0-9 .-]{7,30}$/', (string) $value) !== 1) {
            return 'must be a valid phone number.';
        }
        if ($type === 'checkbox' && ! in_array($value, [true, false, 0, 1, '0', '1'], true)) {
            return 'must be checked or unchecked.';
        }
        if (in_array($type, ['select', 'radio'], true) && ! in_array($value, array_column($field['options'], 'value'), true)) {
            return 'has an invalid selection.';
        }

        $length = is_string($value) ? mb_strlen($value) : null;
        if ($rules['min_length'] !== null && $length !== null && $length < $rules['min_length']) {
            return "must contain at least {$rules['min_length']} characters.";
        }
        if ($rules['max_length'] !== null && $length !== null && $length > $rules['max_length']) {
            return "may not contain more than {$rules['max_length']} characters.";
        }
        if ($rules['min'] !== null && is_numeric($value) && (float) $value < $rules['min']) {
            return "must be at least {$rules['min']}.";
        }
        if ($rules['max'] !== null && is_numeric($value) && (float) $value > $rules['max']) {
            return "may not be greater than {$rules['max']}.";
        }
        if ($rules['regex'] !== null && preg_match($rules['regex'], (string) $value) !== 1) {
            return 'has an invalid format.';
        }

        return null;
    }
}

<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class AiFormGenerator
{
    /** @param array<string, mixed>|null $existing
     * @return array<string, mixed>
     */
    public function generate(string $prompt, ?array $existing = null): array
    {
        if ($existing !== null) {
            return $this->edit($existing, $prompt);
        }

        $title = Str::of($prompt)->replaceMatches('/^(create|generate|build|make)\s+(a\s+)?/i', '')->before('.')->limit(80, '')->trim()->title()->value() ?: 'Generated Form';
        $fields = [];
        foreach ([['full_name', 'Full name', 'text'], ['email', 'Email address', 'email']] as [$key, $label, $type]) {
            $fields[] = $this->field($key, $label, $type, true);
        }
        if (Str::contains(Str::lower($prompt), ['phone', 'contact', 'internship', 'application'])) {
            $fields[] = $this->field('phone', 'Phone number', 'phone', Str::contains(Str::lower($prompt), 'required'));
        }
        if (Str::contains(Str::lower($prompt), ['feedback', 'survey', 'rating'])) {
            $fields[] = $this->field('rating', 'Overall rating', 'rating', true, ['min' => 1, 'max' => 5]);
            $fields[] = $this->field('comments', 'Comments', 'textarea', false);
        }

        return ['schema_version' => '1.0', 'form' => ['title' => $title, 'description' => "Generated from: {$prompt}", 'submit_label' => 'Submit', 'success_message' => 'Thank you for your response.'], 'steps' => [['id' => 'step_generated', 'title' => 'Questions', 'description' => null, 'sections' => [['id' => 'section_generated', 'title' => 'Your details', 'description' => null, 'fields' => $fields]]]], 'conditions' => [], 'settings' => ['show_progress' => true, 'allow_multiple_submissions' => true]];
    }

    /** @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function edit(array $schema, string $prompt): array
    {
        $lower = Str::lower($prompt);
        if (Str::contains($lower, 'emergency contact')) {
            $schema['steps'][0]['sections'][0]['fields'][] = $this->field('emergency_contact', 'Emergency contact', 'phone', true);
        }
        foreach ($schema['steps'] as &$step) {
            foreach ($step['sections'] as &$section) {
                foreach ($section['fields'] as &$field) {
                    if (Str::contains($lower, 'phone') && Str::contains($lower, 'required') && $field['type'] === 'phone') {
                        $field['required'] = true;
                    }
                }
            }
        }
        unset($step, $section, $field);

        return $schema;
    }

    /** @param array<string, int|float|null> $validation
     * @return array<string, mixed>
     */
    private function field(string $key, string $label, string $type, bool $required, array $validation = []): array
    {
        return ['id' => 'field_'.Str::lower((string) Str::ulid()), 'type' => $type, 'key' => $key, 'label' => $label, 'placeholder' => null, 'help_text' => null, 'default' => null, 'required' => $required, 'options' => [], 'validation' => array_merge(['min_length' => null, 'max_length' => null, 'min' => null, 'max' => null, 'email' => false, 'url' => false, 'numeric' => false, 'regex' => null, 'allowed_file_types' => [], 'max_file_size_kb' => null], $validation)];
    }
}

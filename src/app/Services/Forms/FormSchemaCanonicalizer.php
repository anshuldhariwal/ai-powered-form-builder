<?php

namespace App\Services\Forms;

use JsonException;

class FormSchemaCanonicalizer
{
    /**
     * @param  array<string, mixed>  $schema
     *
     * @throws JsonException
     */
    public function canonicalize(array $schema): string
    {
        return json_encode(
            $this->sortObjectKeys($schema),
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     *
     * @throws JsonException
     */
    public function checksum(array $schema): string
    {
        return hash('sha256', $this->canonicalize($schema));
    }

    private function sortObjectKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->sortObjectKeys(...), $value);
        }

        ksort($value, SORT_STRING);

        return array_map($this->sortObjectKeys(...), $value);
    }
}

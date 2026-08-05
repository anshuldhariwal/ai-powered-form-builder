<?php

namespace App\Services\Ai;

class AiRequestSigner
{
    /**
     * @return array{X-FormForge-Timestamp: string, X-FormForge-Signature: string}
     */
    public function headers(
        string $method,
        string $path,
        string $body,
        int $timestamp,
        string $secret,
    ): array {
        $canonical = implode("\n", [
            (string) $timestamp,
            strtoupper($method),
            $path,
            hash('sha256', $body),
        ]);

        return [
            'X-FormForge-Timestamp' => (string) $timestamp,
            'X-FormForge-Signature' => hash_hmac('sha256', $canonical, $secret),
        ];
    }
}

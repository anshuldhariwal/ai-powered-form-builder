<?php

use App\Services\Ai\AiRequestSigner;

test('the signer matches the shared HMAC test vector', function () {
    $headers = (new AiRequestSigner)->headers(
        method: 'post',
        path: '/v1/forms/generate',
        body: 'hello',
        timestamp: 1_700_000_000,
        secret: 'test-secret',
    );

    expect($headers)
        ->toHaveKey('X-FormForge-Timestamp', '1700000000')
        ->toHaveKey(
            'X-FormForge-Signature',
            '0a3261878e7c7e82ba8197b3c18129598e47cb7bcafa92a91d785844463c0fbc',
        );
});

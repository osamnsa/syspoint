<?php
declare(strict_types=1);

/**
 * Minimal Paystack REST client — just the three calls checkout needs:
 * start a transaction, verify one server-side, and check a webhook's
 * signature. No SDK dependency, just cURL + the secret key from config().
 */

function paystack_secret_key(): string
{
    return config()['paystack']['secret_key'];
}

function paystack_public_key(): string
{
    return config()['paystack']['public_key'];
}

/**
 * @return array{ok: bool, authorization_url: ?string, access_code: ?string, reference: ?string, error: ?string}
 */
function paystack_initialize(string $email, float $amountNaira, string $reference, string $callbackUrl, array $metadata = []): array
{
    $payload = [
        'email' => $email,
        'amount' => (int) round($amountNaira * 100), // Paystack wants kobo
        'reference' => $reference,
        'callback_url' => $callbackUrl,
        'metadata' => $metadata,
    ];

    $result = paystack_request('POST', '/transaction/initialize', $payload);

    if (!$result['ok']) {
        return ['ok' => false, 'authorization_url' => null, 'access_code' => null, 'reference' => null, 'error' => $result['error']];
    }

    $data = $result['body']['data'] ?? [];

    return [
        'ok' => true,
        'authorization_url' => $data['authorization_url'] ?? null,
        'access_code' => $data['access_code'] ?? null,
        'reference' => $data['reference'] ?? $reference,
        'error' => null,
    ];
}

/**
 * @return array{ok: bool, status: ?string, reference: ?string, amount_naira: ?float, error: ?string}
 */
function paystack_verify(string $reference): array
{
    $result = paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));

    if (!$result['ok']) {
        return ['ok' => false, 'status' => null, 'reference' => null, 'amount_naira' => null, 'error' => $result['error']];
    }

    $data = $result['body']['data'] ?? [];
    $status = $data['status'] ?? null; // 'success' | 'failed' | 'abandoned' | ...

    return [
        'ok' => true,
        'status' => $status,
        'reference' => $data['reference'] ?? $reference,
        'amount_naira' => isset($data['amount']) ? ((int) $data['amount']) / 100 : null,
        'error' => $status === 'success' ? null : ('Payment not successful (status: ' . $status . ')'),
    ];
}

/**
 * Paystack signs webhook bodies with HMAC-SHA512 of the raw request body,
 * keyed by the secret key, sent in the X-Paystack-Signature header. This
 * must be checked against the *raw* body before it's json_decode()'d —
 * re-encoding the parsed array can produce different bytes and always
 * fails the check.
 */
function paystack_verify_webhook_signature(string $rawBody, ?string $signatureHeader): bool
{
    if (!$signatureHeader) {
        return false;
    }

    $expected = hash_hmac('sha512', $rawBody, paystack_secret_key());

    return hash_equals($expected, $signatureHeader);
}

/**
 * @return array{ok: bool, body: ?array, error: ?string}
 */
function paystack_request(string $method, string $path, ?array $payload = null): array
{
    $secretKey = paystack_secret_key();
    if ($secretKey === '') {
        return ['ok' => false, 'body' => null, 'error' => 'Paystack is not configured (missing secret key).'];
    }

    $ch = curl_init('https://api.paystack.co' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'body' => null, 'error' => 'Paystack request failed: ' . $curlError];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'body' => null, 'error' => 'Paystack returned an unreadable response.'];
    }

    if ($httpCode >= 400 || empty($decoded['status'])) {
        return ['ok' => false, 'body' => $decoded, 'error' => $decoded['message'] ?? 'Paystack request failed.'];
    }

    return ['ok' => true, 'body' => $decoded, 'error' => null];
}

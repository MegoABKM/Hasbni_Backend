<?php

declare(strict_types=1);

namespace App\Saas\Services;

use RuntimeException;

final class AppleJwsVerifier
{
    /**
     * @return array<string, mixed>
     */
    public function verify(string $jws): array
    {
        $parts = explode('.', $jws);

        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed Apple JWS.');
        }

        $header = $this->decode($parts[0]);
        $payload = $this->decode($parts[1]);
        $chain = $header['x5c'] ?? [];

        if (($header['alg'] ?? null) !== 'ES256' || ! is_array($chain) || $chain === []) {
            throw new RuntimeException('Apple JWS has an invalid signing header.');
        }

        $root = (string) config('services.apple.root_certificate');

        if ($root === '' || ! is_file($root)) {
            throw new RuntimeException('Apple root certificate is not configured.');
        }

        $certificates = array_map(fn (mixed $certificate): string => $this->certificate((string) $certificate), $chain);
        $this->verifyCertificateChain($certificates, $root);
        $leafPem = $certificates[0];

        $publicKey = openssl_pkey_get_public($leafPem);
        $signature = $this->rawEcdsaToDer($this->base64UrlDecode($parts[2]));
        $verified = openssl_verify("{$parts[0]}.{$parts[1]}", $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            throw new RuntimeException('Apple JWS signature verification failed.');
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function decode(string $value): array
    {
        $decoded = json_decode($this->base64UrlDecode($value), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('Apple JWS contains invalid JSON.');
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $normalized = strtr($value, '-_', '+/');
        $normalized .= str_repeat('=', (4 - strlen($normalized) % 4) % 4);
        $decoded = base64_decode($normalized, true);

        if ($decoded === false) {
            throw new RuntimeException('Apple JWS contains invalid base64.');
        }

        return $decoded;
    }

    private function certificate(string $certificate): string
    {
        return "-----BEGIN CERTIFICATE-----\n".chunk_split($certificate, 64, "\n")."-----END CERTIFICATE-----\n";
    }

    /**
     * @param  array<int, string>  $certificates
     */
    private function verifyCertificateChain(array $certificates, string $rootPath): void
    {
        $rootContents = file_get_contents($rootPath);

        if ($rootContents === false) {
            throw new RuntimeException('Apple root certificate cannot be read.');
        }

        $rootPem = str_contains($rootContents, 'BEGIN CERTIFICATE')
            ? $rootContents
            : $this->certificate(base64_encode($rootContents));
        $now = time();

        foreach ($certificates as $index => $certificatePem) {
            $certificate = openssl_x509_read($certificatePem);
            $details = $certificate === false ? false : openssl_x509_parse($certificate);

            if ($certificate === false || ! is_array($details)) {
                throw new RuntimeException('Apple JWS contains an invalid certificate.');
            }

            if (($details['validFrom_time_t'] ?? PHP_INT_MAX) > $now || ($details['validTo_time_t'] ?? 0) < $now) {
                throw new RuntimeException('Apple JWS certificate is outside its validity period.');
            }

            $issuerPem = $certificates[$index + 1] ?? $rootPem;
            $issuerKey = openssl_pkey_get_public($issuerPem);

            if ($issuerKey === false || openssl_x509_verify($certificate, $issuerKey) !== 1) {
                throw new RuntimeException('Apple JWS certificate chain is not trusted.');
            }
        }
    }

    private function rawEcdsaToDer(string $signature): string
    {
        if (strlen($signature) !== 64) {
            throw new RuntimeException('Apple JWS contains an invalid ES256 signature.');
        }

        $encode = static function (string $integer): string {
            $integer = ltrim($integer, "\x00");
            $integer = $integer === '' ? "\x00" : $integer;

            if ((ord($integer[0]) & 0x80) !== 0) {
                $integer = "\x00".$integer;
            }

            return "\x02".chr(strlen($integer)).$integer;
        };

        $sequence = $encode(substr($signature, 0, 32)).$encode(substr($signature, 32));

        return "\x30".chr(strlen($sequence)).$sequence;
    }
}

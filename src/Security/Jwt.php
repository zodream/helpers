<?php
declare(strict_types=1);
namespace Zodream\Helpers\Security;

/**

 * Implements JWT encoding and decoding as per http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06

 * Encoding algorithm based on http://code.google.com/p/google-api-php-client

 * Decoding algorithm based on https://github.com/luciferous/jwt

 * @author Francis Chuang <francis.chuang@gmail.com>

 */
use Exception;
use DomainException;
use UnexpectedValueException;

class Jwt extends BaseSecurity {
    
    protected string $algo = 'HS256';
    
    protected string $key;

    public function setAlgo(string $algo): static {
        $this->algo = strtoupper($algo);
        return $this;
    }

    public function getAlgo(): string {
        return $this->algo;
    }

    public function setKey(string $key): static {
        $this->key = $key;
        return $this;
    }

    public function getKey(): string {
        return $this->key;
    }
    
    public function encrypt($data): string {
        $header = array('typ' => 'JWT', 'alg' => $this->algo); 
        $segments = array(
            $this->urlsafeB64Encode(json_encode($header)), 
            $this->urlsafeB64Encode(json_encode($data))
        );
        $signing_input = implode('.', $segments);
        $signature = $this->sign($signing_input, $this->key, $this->algo);
        $segments[] = $this->urlsafeB64Encode($signature);
        return implode('.', $segments);
    }

    public function decrypt(string $data) {
        $tks = explode('.', $data);
        if (count($tks) != 3) {
            throw new Exception(
                __('Wrong number of segments')
            );
        }
        list($headB64, $payloadB64, $cryptoB64) = $tks;
        if (null === ($header = json_decode($this->urlsafeB64Decode($headB64)))) {
            throw new Exception(
                __('Invalid segment encoding')
            );
        }
        if (null === $payload = json_decode($this->urlsafeB64Decode($payloadB64))) {
            throw new Exception(
                __('Invalid segment encoding')
            );
        }
        $sig = $this->urlsafeB64Decode($cryptoB64);
        if (isset($this->key)) {
            if (empty($header->alg)) {
                throw new DomainException(
                    __('Empty algorithm')
                );
            }
            if (!$this->verifySignature($sig, sprintf('%s.%s', $headB64, $payloadB64), $this->key, $this->algo)) {
                throw new UnexpectedValueException(
                    __('Signature verification failed')
                );
            }
        }
        return $payload;
    }

    private function verifySignature(string $signature, string $input, $key, string $algo): bool {
        return match ($algo) {
            'HS256', 'HS384', 'HS512' => $this->sign($input, $key, $algo) === $signature,
            'RS256' => (boolean)openssl_verify($input, $signature, $key, OPENSSL_ALGO_SHA256),
            'RS384' => (boolean)openssl_verify($input, $signature, $key, OPENSSL_ALGO_SHA384),
            'RS512' => (boolean)openssl_verify($input, $signature, $key, OPENSSL_ALGO_SHA512),
            default => throw new Exception(
                __('Unsupported or invalid signing algorithm.')
            ),
        };
    }

    private function sign(string $input, $key, string $algo): string {
        return match ($algo) {
            'HS256' => hash_hmac('sha256', $input, $key, true),
            'HS384' => hash_hmac('sha384', $input, $key, true),
            'HS512' => hash_hmac('sha512', $input, $key, true),
            'RS256' => $this->generateRSASignature($input, $key, OPENSSL_ALGO_SHA256),
            'RS384' => $this->generateRSASignature($input, $key, OPENSSL_ALGO_SHA384),
            'RS512' => $this->generateRSASignature($input, $key, OPENSSL_ALGO_SHA512),
            default => throw new Exception(
                __('Unsupported or invalid signing algorithm.')
            ),
        };
    }

    private function generateRSASignature(string $input, $key, int|string $algo): string {
        if (!openssl_sign($input, $signature, $key, $algo)) { 
            throw new Exception(
                __('Unable to sign data.')
            );
        } 
        return $signature; 
    } 
    
    private function urlSafeB64Encode(string $data): string {
        $b64 = base64_encode($data);
        return str_replace(array('+', '/', '\r', '\n', '='),
            array('-', '_'),
            $b64
        );
    }
 
    private function urlSafeB64Decode(string $b64): string {
        $b64 = str_replace(array('-', '_'), 
            array('+', '/'), 
            $b64
        );
        return base64_decode($b64); 
    }
}
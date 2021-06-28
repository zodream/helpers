<?php
declare(strict_types=1);
namespace Zodream\Helpers\Security;


use OpenSSLAsymmetricKey;
use Zodream\Disk\File;

class Rsa extends BaseSecurity {
    protected OpenSSLAsymmetricKey|false $privateKey;

    protected OpenSSLAsymmetricKey|false $publicKey;

    protected int $padding = OPENSSL_PKCS1_PADDING;

    /**
     * @param int $padding
     * @return Rsa
     */
    public function setPadding(int $padding) {
        $this->padding = $padding;
        return $this;
    }

    public function setPublicKey($key) {
        if ($key instanceof File) {
            $key = $key->read();
        }
        if (!str_contains($key, 'PUBLIC KEY')) {
            $key = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($key, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }
        $this->publicKey = openssl_pkey_get_public($key);
        return $this;
    }

    public function setPrivateKey($key, $password = '') {
        if ($key instanceof File) {
            $key = $key->read();
        }
        if (!str_contains($key, 'PRIVATE KEY')) {
            $key = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($key, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }
        $this->privateKey = openssl_pkey_get_private($key, $password);
        return $this;
    }

    public function getPublicKey(): OpenSSLAsymmetricKey|bool
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): OpenSSLAsymmetricKey|bool
    {
        return $this->privateKey;
    }

    /**
     * 私钥加密
     * @param string $data 要加密的数据
     * @return string 加密后的字符串
     */
    public function privateKeyEncrypt(string $data) {
        if (empty($this->privateKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_private_encrypt($data, $encrypted, $this->privateKey, $this->padding)) {
            return false;
        }
        return base64_encode($encrypted);
    }

    /**
     * 公钥加密
     * @param string $data 要加密的数据
     * @return string 加密后的字符串
     */
    public function publicKeyEncrypt(string $data) {
        if (empty($this->publicKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_public_encrypt($data, $encrypted, $this->publicKey, $this->padding)) {
            return false;
        }
        return base64_encode($encrypted);
    }

    /**
     * 用公钥解密私钥加密内容
     * @param string $data 要解密的数据
     * @return string 解密后的字符串
     */
    public function decryptPrivateEncrypt(string $data) {
        if (empty($this->privateKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_public_decrypt(base64_decode($data), $encrypted, $this->publicKey, $this->padding)) {
            return false;
        }
        return $encrypted;
    }
    /**
     * 用私钥解密公钥加密内容
     * @param string $data  要解密的数据
     * @return string 解密后的字符串
     */
    public function decryptPublicEncrypt(string $data) {
        if (empty($this->privateKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_private_decrypt(base64_decode($data), $encrypted, $this->privateKey, $this->padding)) {
            return false;
        }
        return $encrypted;
    }

    /**
     * ENCRYPT STRING
     * @param string $data
     * @return string
     */
    public function encrypt($data): string {
        return $this->publicKeyEncrypt($data);
    }

    /**
     * DECRYPT STRING
     * @param string $data
     * @return string
     */
    public function decrypt($data) {
        return $this->decryptPublicEncrypt($data);
    }

    public function __destruct() {
        if (is_resource($this->publicKey)) {
            openssl_free_key($this->publicKey);
        }
        if (is_resource($this->privateKey)) {
            openssl_free_key($this->privateKey);
        }
    }
}
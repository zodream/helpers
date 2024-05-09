<?php
declare(strict_types=1);
namespace Zodream\Helpers\Security;
/**
 * 基于时间的双向加解密方案，方便 js 加解密
 */
class Encryptor extends BaseSecurity {
    public function __construct(
        string|int $timestamp = 0,
    ) {
        $this->keyItems = $this->createKeys($timestamp);
    }

    private array $keyItems = [];

    public function encrypt($data): string {
        return $this->map(base64_encode($data), function (string $code, int $i) {
            return $this->dictionaryCode(ord($code) - $this->keyItems[$i % count($this->keyItems)]);
        });
    }

    public function decrypt(string $data) {
        $i = 0;
        $j = 0;
        $items = [];
        while ($i < strlen($data)) {
            $step = 1;
            if (!$this->isDictionaryCode(substr($data, $i, $step))) {
                $step ++;
            }
            $items[] = chr($this->dictionaryKey(substr($data, $i, $step))
                + $this->keyItems[$j % count($this->keyItems)]);
            $i += $step;
            $j ++;
        }
        return base64_decode(implode('', $items));
    }

    protected function dictionaryLength(): int {
        return 51;
    }

    protected function dictionaryCode(int $code): string {
        $code -= 24;
        $rate = $code % $this->dictionaryLength();
        $prefix = $code >= $this->dictionaryLength() ? '0' : '';
        if ($rate < 9) {
            return $prefix. chr($rate + 49);
        }
        if ($rate < 35) {
            return $prefix.chr($rate + 88);
        }
        return $prefix.chr($rate + 30);
    }

    protected function isDictionaryCode(string $code): bool {
        return $code !== '0';
    }

    protected function dictionaryKey(string $code): int {
        $base = 24;
        if (strlen($code) > 1) {
            $base += $this->dictionaryLength();
            $code = substr($code, 1);
        }
        $ord = ord($code);
        if ($ord <= 57) {
            return $base + $ord - 49;
        }
        if ($ord <= 90) {
            return $base + $ord - 30;
        }
        return $base + $ord - 88;
    }

    private function each(string $text, callable $cb): void {
        for ($i = 0; $i < strlen($text); $i ++) {
            call_user_func($cb, substr($text, $i, 1), $i);
        }
    }

    private function map(string $text, callable $cb): string {
        return implode('', $this->mapToArray($text, $cb));
    }

    private function mapToArray(string $text, callable $cb): array {
        $items = [];
        $this->each($text, function (string $code, int $i) use (&$items, $cb) {
            $items[] = call_user_func($cb, $code, $i);
        });
        return $items;
    }

    private function createKeys(string|int $timestamp): array {
        $key = is_numeric($timestamp) ? intval($timestamp) : strtotime($timestamp);
        if (empty($key)) {
            $key = time();
        }
        return $this->mapToArray((string)$key, function (string $i) {
            return intval($i);
        });
    }
}
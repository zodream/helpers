<?php
declare(strict_types=1);
namespace Zodream\Helpers;

use Exception;
use OutOfBoundsException;
use Stringable;

class BinaryReader implements Stringable {

    protected int $position = -1;
    protected string $current = '';
    protected readonly int $length;

    public function __construct(
        protected readonly string $content
    ) {
        $this->length = strlen($this->content);
    }

    public function length(): int {
        return $this->length;
    }


    /**
     * 当前位置
     * @return int
     */
    public function position(): int {
        return $this->position;
    }

    public function canNext(): bool {
        return $this->position < $this->length() - 1;
    }

    public function canBack(): bool {
        return $this->position > 0;
    }

    public function seek(int $i): void {
        if ($this->position === $i) {
            return;
        }
        $this->position = $i;
        $this->current = $this->readChar($i);
    }

    /**
     * 截取字符串
     * @param int $begin 开始位置， 如果 $end 传则为结束为位置， 从当前位置开始
     * @param int $end 结束位置
     * @return string
     */
    public function substr(int $begin, int $end = -1): string {
        if ($end < $begin) {
            return substr($this->content, $this->position, $begin - $this->position);
        }
        return substr($this->content, $begin, $end - $begin);
    }

    /**
     * 从当前位置偏移
     * @param int $offset
     * @return void
     */
    public function seekOffset(int $offset): void {
        $this->seek($this->position + $offset);
    }


    public function next(): string {
        $this->current = $this->nextChar();
        return $this->current;
    }

    public function back(): void {
        $this->seekOffset(-1);
    }

    protected function nextChar(): string {
        if (!$this->canNext()) {
            return '';
        }
        $this->position ++;
        return $this->content[$this->position];
    }

    protected function isValidPosition(int $i): bool {
        return $i >= 0 && $i < $this->length;
    }

    public function readChar(int $p): string {
        if (!$this->isValidPosition($p)) {
            return '';
        }
        return $this->content[$p];
    }

    public function read(int $length): string {
        if ($this->length() < $this->position + $length) {
            throw new OutOfBoundsException(sprintf('Trying to read too many bytes[%d+%d>%d]',
                $this->position, $length, $this->length()));
        }
        $buffer = substr($this->content, $this->position + 1, $length);
        $this->position += $length;
        return $buffer;
    }

    public function readUnpack(int $length, string $format): int {
        $bytes = $this->read($length);
        $unpacked = unpack($format, $bytes);
        assert(is_array($unpacked));
        return current($unpacked);
    }

    public function readUint8(): int {
        $byte = $this->read(1);
        return ord($byte);
    }

    public function readUint16(): int {
        $bytes = $this->read(2);
        $unpacked = unpack('n', $bytes);
        assert(is_array($unpacked));
        return current($unpacked);
    }

    public function readUint32(): int {
        $bytes = $this->read(4);
        $unpacked = unpack('N', $bytes);
        assert(is_array($unpacked));
        return current($unpacked);
    }

    public function readUint64(): int {
        $bytes = $this->read(8);
        $unpacked = unpack('J', $bytes);
        assert(is_array($unpacked));
        return current($unpacked);
    }

    public function readFloat(): int {
        $bytes = $this->read(4);
        $unpacked = unpack('G', $bytes);
        assert(is_array($unpacked));
        return current($unpacked);
    }

    public function readDouble(): int {
        $bytes = $this->read(8);
        $unpacked = unpack('E', $bytes);
        assert(is_array($unpacked));
        return current($unpacked);
    }

    /**
     * 获取剩下的部分
     * @return string
     */
    public function readRemaining(): string {
        return substr($this->content, $this->position + 1);
    }

    public function current(): string {
        return $this->current;
    }

    /**
     * 在i之前是否还可以移动
     * @param int $i
     * @return bool
     */
    public function canNextUntil(int $i): bool {
        return $this->position < $i - 1 && $this->position < $this->length() - 1;
    }

    public function reset(): void {
        $this->position = -1;
    }


    /**
     * 查找字符串
     * @param string $s
     * @param int $offset
     * @param int $end 限制范围
     * @return int
     */
    public function indexOf(string $s, int $offset = 0, int $end = -1): int {
        $i = strpos($this->content, $s, max($this->position + $offset, 0));
        return $i === false || ($end >= 0 && $i > $end) ? -1 : $i;
    }

    /**
     * 判断从当前开始的字符串是否是字符串
     * @param string $s
     * @return bool
     */
    public function is(string $s): bool {
        if ($s === '') {
            return false;
        }
        if ($this->current !== $s[0]) {
            return false;
        }
        if (strlen($s) === 1) {
            return true;
        }
        return substr($this->content, $this->position, strlen($s)) === $s;
    }

    /**
     * 判断接下来是哪一个字符串
     * @param string ...$items
     * @return int
     */
    public function nextIs(string|int ...$items): int {
        if (!$this->canNext()) {
            return -1;
        }
        foreach ($items as $i => $item)  {
            if ($item === '') {
                continue;
            }
            if (is_int($item)) {
                if (ord($this->readChar($this->position + 1)) === $item) {
                    return $i;
                }
            } elseif (substr($this->content, $this->position + 1, strlen($item)) === $item) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * 获取字符串的最小位置和是哪个字符串
     * @param string ...$items
     * @return array{int, int} [position, items_index]
     */
    public function minIndex(string ...$items): array {
        $index = -1;
        $min = -1;
        for ($i = count($items) - 1; $i >= 0; $i--) {
            $j = $this->indexOf($items[$i]);
            if ($j >= 0 && ($min < 0 || $j <= $min)) {
                $index = $i;
                $min = $j;
            }
        }
        return [$min, $index];
    }

    /**
     * 反向遍历，不移动当前位置
     * @param callable $cb
     * @param int $offset 默认从前一个位置开始
     * @return void
     */
    public function reverse(callable $cb, int $offset = -1): void {
        $i = $this->position + $offset;
        while ($i >= 0) {
            if (call_user_func($cb, $this->content[$i], $i)) {
                break;
            }
            $i --;
        }
    }

    /**
     * 字符是否是上一个字符，并计算连续出现的次数
     * @param string $code
     * @return int
     */
    public function reverseCount(string $code): int {
        $count = 0;
        $this->reverse(function ($i) use ($code, &$count) {
            if ($i != $code) {
                return false;
            }
            $count++;
            return null;
        });
        return $count;
    }

    public function unwrap(): string {
        return $this->content;
    }

    public function equals(BinaryReader $other): bool {
        return $this->content === $other->content;
    }

    public function __toString(): string {
        return $this->unwrap();
    }

    public static function fromBase64(string $base64): BinaryReader {
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new Exception('Invalid base64 string');
        }
        return new static($binary);
    }

    public static function fromBuffer(array $buffer): BinaryReader {
        return new BinaryReader(implode('', array_map('chr', $buffer)));
    }

    public static function fromHex(string $hex): BinaryReader {
        $binary = hex2bin($hex);
        if ($binary === false) {
            throw new Exception('Invalid hex string');
        }
        return new BinaryReader($binary);
    }
}
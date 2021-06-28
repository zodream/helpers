<?php
declare(strict_types=1);
namespace Zodream\Helpers\Security;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/9
 * Time: 13:13
 */
class Encrypt extends BaseSecurity {
    protected string $key;

    public function setKey(string $key) {
        $this->key = md5($key);
        return $this;
    }

    public function getKey(): string {
        return $this->key;
    }

    public function encrypt(string $data): string {
        $data = substr(md5($data.$this->key), 0, 8).$data;
        return str_replace('=','',base64_encode($this->make($data)));
    }

    public function decrypt(string $data): string {
        $data = base64_decode($data);
        $arg = $this->make($data);
        if(substr($arg, 0, 8) == substr(md5(substr($arg, 8).$this->key), 0, 8)){
            return substr($arg, 8);
        }
        return '';
    }

    protected function make(string $data): string {
        $keyLength = strlen($this->key);
        $stringLength = strlen($data);
        $randomKey = $box = array();
        $result = '';
        for($i = 0; $i <= 255; $i++){
            $randomKey[$i] = ord($this->key[$i % $keyLength]);
            $box[$i] = $i;
        }
        for($j = $i = 0; $i < 256; $i++){
            $j = ($j + $box[$i] + $randomKey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for($a = $j = $i = 0; $i < $stringLength; $i++){
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($data[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        return $result;
    }
}
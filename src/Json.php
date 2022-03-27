<?php
declare(strict_types=1);
namespace Zodream\Helpers;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/12
 * Time: 17:01
 */
class Json {
    public static function decode(mixed $json, bool $isArray = true) {
        if (!is_string($json)) {
            return $json;
        }
        if ($isArray === false) {
            return json_decode($json);
        }
        return json_decode($json, true);
    }

    /**
     * @param array|object $args
     * @param int $option 默认不编码成 多字节 Unicode \u XXX
     * @return string
     */
    public static function encode(mixed $args, int $option = JSON_UNESCAPED_UNICODE) {
        if (is_object($args) && method_exists($args, 'toJson')) {
            return $args->toJson();
        }
        return json_encode($args, $option);
    }
}
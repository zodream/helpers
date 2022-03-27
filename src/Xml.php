<?php
declare(strict_types=1);
namespace Zodream\Helpers;

use Zodream\Helpers\Xml\ArrayToXml;
use Zodream\Helpers\Xml\XmlToArray;

class Xml {
    /**
     * @param string $xml
     * @param bool $isArray
     * @return array|object|mixed
     * @throws \Exception
     */
    public static function decode(mixed $xml, bool $isArray = true) {
        if (!is_string($xml)) {
            return $xml;
        }
        if ($isArray === false) {
            return simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return XmlToArray::createArray($xml);
    }

    public static function specialDecode(string $xml) {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)),TRUE);
    }

    /**
     * @param array $args
     * @param string $root
     * @return string
     */
    public static function encode(array $args, string $root = 'root') {
        return ArrayToXml::createXML($root, $args)->saveXML();
    }

    /**
     * 特殊的xml 编码 主要用于微信回复
     * @param array $args
     * @param string $root
     * @return string
     */
    public static function specialEncode(array $args, string $root = 'xml') {
        return ArrayToXml::createXML($root, static::toSpecialArray($args))->saveXML();
    }

    /**
     *
     * 转化成标准Xml数组
     * @param string|mixed $data
     * @return array|integer
     */
    protected static function toSpecialArray(mixed $data) {
        if (is_integer($data)
            || is_bool($data)
            || is_float($data)
            || is_double($data)) {
            return $data;
        }
        if (is_object($data)) {
            $data = (array)$data;
        }
        if (!is_array($data)) {
            return [
                '@cdata' => $data
            ];
        }
        foreach ($data as $key => &$item) {
            if (is_null($item)) {
                // 去掉 null 的值
                unset($item, $data[$key]);
                continue;
            }
            if (str_starts_with($key, '@')) {
                continue;
            }
            $item = static::toSpecialArray($item);
        }
        return $data;
    }
}
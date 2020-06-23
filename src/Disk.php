<?php
namespace Zodream\Helpers;
/**
 * Created by PhpStorm.
 * User: ZoDream
 * Date: 2016/12/1
 * Time: 22:13
 */
class Disk {
    /**
     * 格式化容量
     * @param $size
     * @return string
     */
    public static function size($size) {
        $sizes = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
        if ($size == 0) {
            return('n/a');
        }
        return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]);
    }

    /**
     * 获取两个路径的相对路径
     * @param string $a1
     * @param string $b1
     * @return string
     */
    public static function getRelationPath($a1, $b1) {
        $a1 = explode('/', ltrim($a1, '/'));
        $b1 = explode('/', ltrim($b1, '/'));
        for($i = 0; isset($b1[$i], $a1[$i]); $i++){
            if($a1[$i] == $b1[$i]) $a1[$i] = '..';
            else break;
        }
        return implode('/', $a1);
    }

    public static function getAbsolutePath($path) {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}
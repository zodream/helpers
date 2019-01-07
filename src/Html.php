<?php
namespace Zodream\Helpers;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 12:22
 */
class Html {

    /**
     * 压缩html，
     * @param string $arg
     * @param bool $hasJs 如果包含js请用false
     * @return string
     */
    public static function compress($arg, $hasJs = true) {
        $search = $hasJs ? [
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ] : [
            '/> *([^ ]*) *</',
            '//',
            '#/\*[^*]*\*/#',
            "/\r\n/",
            "/\n/",
            "/\t/",
            '/>[ ]+</'
        ];
        $replace = $hasJs ? ['>', '<', '\\1', ''] : [
            '>\\1<',
            '',
            '',
            '',
            '',
            '',
            '><'
        ];
        return trim(preg_replace($search, $replace, $arg));
    }

    /**
     * 过滤html元素
     * @param string $content
     * @return string
     */
    public static function filterHtml($content) {
        return preg_replace('/<(.*?)>/', '', htmlspecialchars_decode($content));
    }

    public static function shortString($content, $length = 100) {
        $content = preg_replace('/(\<.+?\>)|(\&nbsp;)+/', '', htmlspecialchars_decode($content));
        return Str::substr($content, 0, $length);
    }
    
    /**
     * 将一个URL转换为完整URL
     * PHP将相对路径URL转换为绝对路径URL
     * @param string $srcUrl
     * @param string $baseUrl
     * @return string
     */
    public static function formatUrl($srcUrl, $baseUrl) {
        $srcInfo = parse_url($srcUrl);
        if(isset($srcInfo['scheme'])) {
            return $srcUrl;
        }
        $baseInfo = parse_url($baseUrl);
        $url = $baseInfo['scheme'].'://'.$baseInfo['host'];
        if(substr($srcInfo['path'], 0, 1) == '/') {
            $path = $srcInfo['path'];
        }else{
            $filename=  basename($baseInfo['path']);
            //兼容基础url是列表
            if(strpos($filename,".")===false){
                $path = dirname($baseInfo['path']).'/'.$filename.'/'.$srcInfo['path'];
            }else{
                $path = dirname($baseInfo['path']).'/'.$srcInfo['path'];
            }

        }
        $rst = array();
        $path_array = explode('/', $path);
        if(!$path_array[0]) {
            $rst[] = '';
        }
        foreach ($path_array AS $key => $dir) {
            if ($dir == '..') {
                if (end($rst) == '..') {
                    $rst[] = '..';
                }elseif(!array_pop($rst)) {
                    $rst[] = '..';
                }
            }elseif($dir && $dir != '.') {
                $rst[] = $dir;
            }
        }
        if(!end($path_array)) {
            $rst[] = '';
        }
        $url .= implode('/', $rst);
        return str_replace('\\', '/', $url);
    }

    public static function toText($str) {
        $str = preg_replace("/<style .*?<\\/style>/is", "", $str);
        $str = preg_replace("/<script .*?<\\/script>/is", "", $str);
        $str = preg_replace("/<br \\s*\\/>/i", ">>>>", $str);
        $str = preg_replace("/<\\/?p>/i", ">>>>", $str);
        $str = preg_replace("/<\\/?td>/i", "", $str);
        $str = preg_replace("/<\\/?div>/i", ">>>>", $str);
        $str = preg_replace("/<\\/?blockquote>/i", "", $str);
        $str = preg_replace("/<\\/?li>/i", ">>>>", $str);
        $str = preg_replace("/ /i", " ", $str);
        $str = preg_replace("/ /i", " ", $str);
        $str = preg_replace("/&/i", "&", $str);
        $str = preg_replace("/&/i", "&", $str);
        $str = preg_replace("/</i", "<", $str);
        $str = preg_replace("/</i", "<", $str);
        $str = preg_replace("/“/i", '"', $str);
        $str = preg_replace("/&ldquo/i", '"', $str);
        $str = preg_replace("/‘/i", "'", $str);
        $str = preg_replace("/&lsquo/i", "'", $str);
        $str = preg_replace("/'/i", "'", $str);
        $str = preg_replace("/&rsquo/i", "'", $str);
        $str = preg_replace("/>/i", ">", $str);
        $str = preg_replace("/>/i", ">", $str);
        $str = preg_replace("/”/i", '"', $str);
        $str = preg_replace("/&rdquo/i", '"', $str);
        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
        return preg_replace("/&#.*?;/i", "", $str);
    }
}
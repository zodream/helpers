<?php
declare(strict_types=1);
namespace Zodream\Helpers;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 12:22
 */
class Html {

    /**
     * 压缩html，请事先对一些不要压缩的标签进行替换
     * @param string $arg
     * @param bool $hasJs 如果包含js请用false
     * @return string
     */
    public static function compress(string $arg, bool $hasJs = true): string {
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
    public static function filterHtml(string $content): string {
        return preg_replace('/<(.*?)>/', '', htmlspecialchars_decode($content));
    }

    public static function shortString(string $content, int $length = 100): string {
        $content = preg_replace('/(\<.+?\>)|(\&nbsp;)+/', '', htmlspecialchars_decode($content));
        return Str::substr($content, 0, $length);
    }

    /**
     * 编码html
     * @param string|null $html
     * @param int $length 大于0则需要截取
     * @return string
     */
    public static function text(string|null $html, int $length = 0): string {
        if (empty($html)) {
            return '';
        }
        $text = htmlspecialchars($html);
        if ($length > 0) {
            return Str::substr($text, 0, $length, true);
        }
        return $text;
    }

    /**
     * 将内容简单的转化为 html
     * @param string $content
     * @param bool $lineSpace 每行子前是否自动添加四个空格
     * @return string
     */
    public static function fromText(mixed $content, bool $lineSpace = true): string {
        return implode('', array_map(function ($line) use ($lineSpace) {
            if (empty($line)) {
                return '<p></p>';
            }
            return sprintf('<p>%s%s</p>', $lineSpace ?
                '&nbsp;&nbsp;&nbsp;&nbsp;' : '', $line);
        }, explode("\n", (string)$content)));
    }

    /**
     * 转换html为文本
     * @param string $str
     * @return string
     */
    public static function toText(string $str): string {
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
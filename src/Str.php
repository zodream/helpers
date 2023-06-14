<?php
declare(strict_types=1);
namespace Zodream\Helpers;

/**
* string 的扩展
* 
* @author Jason
*/

class Str {
    /**
     * 获取值
     * @param string $value
     * @return mixed
     */
	public static function value(mixed $value): mixed {
		return is_callable($value) ? call_user_func($value) : $value;
	}

    public static function toBool(mixed $value): bool {
        return ((is_numeric($value) && $value > 0) || (is_bool($value) && $value)
                || (is_string($value) && strtolower($value) === 'true')) && !empty($value);
    }

    /**
     * 字符串转方法执行
     * @param mixed $str
     * @param array $params
     * @param mixed $default
     * @return mixed|null
     */
	public static function call(mixed $str, array $params = [], mixed $default = null): mixed {
	    if (empty($str)) {
	        return $default;
        }
	    if (is_callable($str)) {
	        return call_user_func_array($str, $params);
        }
	    if (!is_string($str)) {
            return $default;
        }
	    if (!str_contains($str, '@')) {
            return $default;
        }
	    list($cls, $method) = explode('@', $str, 2);
	    if (!class_exists($cls)) {
	        return $default;
        }
	    $instance = new $cls();
	    if (!method_exists($instance, $method)) {
	        return $default;
        }
	    return $instance->$method(...$params);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function contains(string $haystack, string|array $needles): bool {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

	/**
	 * 拓展str_repeat 重复字符并用字符连接
	 * @param string $str
	 * @param integer $count
	 * @param string $line
	 * @return string
	 */
	public static function repeat(string $str, int $count, string $line = ','): string {
		return substr(str_repeat($str.$line, $count), 0, - strlen($line));
	}

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param string|array $pattern
     * @param string $value
     * @return bool
     */
    public static function is(string|array $pattern, string $value): bool {
        $patterns = is_array($pattern) ? $pattern : (array) $pattern;

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            // If the given value is an exact match we can of course return true right
            // from the beginning. Otherwise, we will translate asterisks and do an
            // actual pattern match against the two strings to see if they match.
            if ($pattern == $value) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');

            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }

	/**
	 * 生成更加真实的随机字符串
	 *
	 * @param  int  $length
	 * @return string
	 */
	public static function random(int $length = 16): string {
		if (function_exists('str_random')) {
			return str_random($length);
		}
		$string = '';
		while (($len = strlen($string)) < $length) {
			$size = $length - $len;
			$bytes = static::randomBytes($size);
			$string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
		}
		return $string;
	}

    /**
     * @throws \Exception
     */
    public static function randomInt(int $min, int $max): int {
        return random_int($min, $max);
    }

	/**
	 * 生成随机数字字符串
	 * @param int $length
	 * @return string
	 */
	public static function randomNumber(int $length = 6): string {
		return sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
	}

    /**
     * 生成更加真实的随机字节
     *
     * @param integer $length
     * @return string
     * @throws \ErrorException
     */
	public static function randomBytes(int $length = 16): string {
		if (PHP_MAJOR_VERSION >= 7 || defined('RANDOM_COMPAT_READ_BUFFER')) {
            return random_bytes($length);
		} elseif (function_exists('openssl_random_pseudo_bytes')) {
			$bytes = openssl_random_pseudo_bytes($length, $strong);
			if ($bytes === false || $strong === false) {
				throw new \InvalidArgumentException('Unable to generate random string.');
			}
			return $bytes;
		}
		throw new \ErrorException('OpenSSL extension or paragonie/random_compat is required for PHP 5 users.');
	}

	/**
	 * 替换url中的参数
	 *
	 * @param string $url
	 * @param string|array $key
	 * @param null|string $value
	 * @return string 合并后的值
	 */
	public static function urlBindValue(string $url, string|array $key, ?string $value = null): string {
		$arr = explode('?', $url, 2);
		$arr = str_replace('&amp;', '&', $arr);      //解决 & 被转义
		$data = array();
		if (count($arr) > 1) {
			parse_str($arr[1], $data);
		}
		if (!is_null($value)) {
			$data[$key] = $value;
            return $arr[0].'?'.http_build_query($data);
		}
        if (is_array($key)) {
            foreach ($key as $k => $val) {
                if (!is_integer($k)) {
                    $data[$k] = $val;
                    continue;
                }
                $temps = self::explode($val, '=', 2);
                $data[$temps[0]] = $temps[1];
            }
        } else if (is_string($key)) {
            $keys = array();
            parse_str($key, $keys);
            $data = array_merge($data, $keys);
        }
		return $arr[0].'?'.http_build_query($data);
	}
	/**
	 * 生成简单的随机字符串
	 * @param  int  $length
	 * @return string
	 */
	public static function quickRandom(int $length = 16): string {
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
	}

    /**
     * @param int $length 总长度
     * @param int $arg 数字转字符串
     * @param string $pool 随机字符串参考
     * @return string
     */
    public static function randomByNumber(
        int $length = 6,
        mixed $arg = 0,
        string $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ) {
        $arg = intval($arg);
        $str = '';
        $len = 0;
        $max = strlen($pool);
        while ($arg > 0) {
            $index = $arg % $max;
            $str = $pool[$index].$str;
            $len ++;
            $arg = floor($arg / $max);
        }
        return substr($str.str_shuffle(str_repeat($pool, $length - $len)), 0, $length);
    }

	/**
	 * 字节长度
	 * @param string $string
	 * @return integer
	 */
	public static function byteLength(string $string) {
		return mb_strlen($string, '8bit');
	}

    /**
     * 截取字符串为数组，补充explode函数
     * @param string $str
     * @param string $link
     * @param int $num
     * @param array|string $default 不存在时使用
     * @return array
     */
	public static function explode(string $str, string $link = ' ', int $num = 1, mixed $default = null): array {
		$args = explode($link, $str, $num);
		if (count($args) >= $num) {
			return $args;
		}
		if (!is_array($default)) {
			return array_pad($args, $num, $default);
		}
		for ($i = $num - 1; $i >= 0 ; $i --) {
			if (!array_key_exists($i, $args)) {
				return $args;
			}
			$args[$i] = $default[$i];
		}
		return $args;
	}

	/**
	 * EXPLODE STRING BY ARRAY
	 * @param array $delimiters
	 * @param $string
	 * @return array
	 */
	public static function multiExplode(array $delimiters, $string) {
		$ready = str_replace($delimiters, $delimiters[0], $string);
		return explode($delimiters[0], $ready);
	}

	/**
	 * 判断字符串是否以$needles开头
	 * @param string $haystack
	 * @param string|array $needles 要寻找的字符串
	 * @return bool
	 */
	public static function startsWith(string $haystack, string|array $needles): bool {
		foreach ((array) $needles as $needle) {
			if ($needle !== '' && str_starts_with($haystack, $needle)) {
				return true;
			}
		}
		return false;
	}

    /**
     * 是否以。。。结尾
     * @param string $arg
     * @param string|array $search
     * @return bool
     */
	public static function endWith(string $arg, string|array $search): bool {
        foreach ((array) $search as $needle) {
            if ($needle !== '' && str_ends_with($arg, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 首字符替换
     * @param string $arg
     * @param string $search
     * @param array|string $replace
     * @return string
     */
	public static function firstReplace(string $arg, string $search, array|string $replace = ''): string {
		return preg_replace('/^'.$search.'/', $replace, $arg, 1);
	}

	public static function lastReplace(string $arg, string $search, array|string $replace = ''): string {
		return preg_replace('/'.$search.'$/', $replace, $arg, 1);
	}

    public static function parseCallback(string $callback, mixed $default = null): array {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    public static function studly(string $value): string {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    /**
     * 驼峰转下划线
     * @param string $camelCaps
     * @param string $separator
     * @return string
     */
    public static function unStudly(string $camelCaps, string $separator = '_'): string {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

	/**
	 * UTF8字符串的长度
	 * @param string $str
	 * @return int
	 */
	public static function absLength(string $str): int {
		if (empty($str)) {
			return 0;
		}
		if (function_exists('mb_strlen')) {
			return mb_strlen($str,'utf-8');
		}
        preg_match_all("/./u", $str, $ar);
        return count($ar[0]);
	}

    /**
     * 中文截取，支持gb2312,gbk,utf-8,big5
     *
     * @param string $str 要截取的字串
     * @param int $start 截取起始位置
     * @param int $length 截取长度
     * @param bool $suffix 是否加尾缀
     * @return string
     */
	public static function substr(string $str, int $start, int $length, bool $suffix = false): string {
        if (mb_strlen($str, 'utf-8') <= $length) {
            return $str;
        }
        $slice = mb_substr($str, $start, $length, 'utf-8');
		if ($suffix) {
            return $slice.'…';
        }
		return $slice;
	}
	
}
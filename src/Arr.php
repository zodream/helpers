<?php
declare(strict_types=1);
namespace Zodream\Helpers;

/**
* array 的扩展
* 
* @author Jason
*/

use ArrayAccess;
use JsonSerializable;
use Traversable;
use Zodream\Infrastructure\Support\Collection;

class Arr {

    /**
     * 深度格式化成多维数组
     * @param array $data
     * @return array
     */
    public static function format(array $data): array {
        return array_map(function ($item) {
            $args = static::toArrOrNone($item);
            if (false !== $args) {
                return $args;
            }
            return $item;
        }, $data);
    }

    protected static function toArrOrNone(mixed $item): array|false {
        if (is_array($item)) {
            return static::format($item);
        }
        if (is_object($item)) {
            if (method_exists($item, 'toArray')) {
                return $item->toArray();
            }
            if (method_exists($item, 'all')) {
                return $item->all();
            }
            if (method_exists($item, 'toJson')) {
                return json_decode($item->toJson(), true);
            }
        }
        if ($item instanceof JsonSerializable) {
            return $item->jsonSerialize();
        }
        if ($item instanceof Traversable) {
            return iterator_to_array($item);
        }
        return false;
    }

    public static function toArray(mixed $data): array {
        $args = static::toArrOrNone($data);
        if (false !== $args) {
            return $args;
        }
        return (array) $data;
    }
	
	/**
	 * 寻找第一个符合的
	 * @param array $array
	 * @param callable $callback
	 * @param null $default
	 * @return mixed
	 */
	public static function first(array $array, callable $callback, mixed $default = null): mixed {
		foreach ($array as $key => $value) {
			if (call_user_func($callback, $key, $value)) {
				return $value;
			}
		}
		return Str::value($default);
	}

    /*** 合并前缀  把 key 作为前缀 例如 返回一个文件夹下的多个文件路径
     * array('a'=>array(
    * 'b.txt',
    * 'c.txt'
    * ))
     * @param array $args 初始
     * @param string $link 连接符
     * @param string $pre 前缀
     * @return array
     */
	public static function toFile(mixed $args, string $link = '', string $pre = ''): array {
		$list = [];
		if (is_array($args)) {
			foreach ($args as $key => $value) {
				if (is_int($key)) {
					if (is_array($value)) {
						$list = array_merge($list, self::toFile($value, $link, $pre));
					} elseif(is_object($value)) {
						$list[] = $value;
					} else {
						$list[] = $pre.$value;
					}
				} else {
					if (is_array($value)) {
						$list = array_merge($list, self::toFile($value, $link, $key.$link));
					} else {
						$list[] = $pre.$key.$link.$value;
					}
				}
			}
		} else {
			$list[] = $pre.$args;
		}
		return $list;
	}

    /** 把多维数组转换成字符串
     * @param array $args 数组
     * @param string $link 连接符
     * @return string
     */
	public static function toString(mixed $args, string $link  = ''): string {
		$str = '';
		if (is_array($args)) {
			foreach ($args as $value) {
				$str .= self::toString($value, $link);
			}
		} else {
			$str .= $args.$link;
		}
		return $str;
	}

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse(array $array): array {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function except(array $array, array|string $keys): array {
        static::forget($array, $keys);
        return $array;
    }


    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forget(array &$array, array|string $keys) {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function last($array, callable|null $callback = null, $default = null) {
        if (is_null($callback)) {
            return empty($array) ? Str::value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    public static function flatten($array, $depth = INF) {
        return array_reduce($array, function ($result, $item) use ($depth) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (! is_array($item)) {
                return array_merge($result, [$item]);
            } elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            } else {
                return array_merge($result, static::flatten($item, $depth - 1));
            }
        }, []);
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    public static function pluck($array, mixed $value, mixed $key = null): array {
        $results = [];
        list($value, $key) = static::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = static::dataGet($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = static::dataGet($item, $key);

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function dataGet(mixed $target, mixed $key, mixed $default = null): mixed {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
                    return Str::value($default);
                }

                $result = Arr::pluck($target, $key);

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return Str::value($default);
            }
        }

        return $target;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only($array, $keys) {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key) {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend($array, $value, $key = null) {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null) {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }


    /** 根据字符串获取数组值，取多维数组
     * @param string $name 要取得键
     * @param array $args
     * @param null $default
     * @param string $link
     * @return array|string
     */
	public static function getValues($name, array $args, $default = null, $link = ',') {
		$names = explode($link, $name);
        if (!str_contains($name, $link)) {
            list($newKey, $arg, $oldKey) = self::_getValueByKeyWithDefault($name, $args, $default);
            if ($newKey == $oldKey) {
                return $arg;
            }
            return array(
                $newKey => $arg
            );
        }
		$returnValue = array();
		foreach ($names as $value) {
			list($newKey, $arg) = self::_getValueByKeyWithDefault($value, $args, $default);
            $returnValue[$newKey] = $arg;
		}
		return $returnValue;
	}

    /** 根据 "oldKey:newKey default" 获取值
     * @param string $key
     * @param array $args
     * @param null $default
     * @return array (newKey, value, oldKey)
     */
    private static function _getValueByKeyWithDefault($key,array $args, $default = null) {
        //使用方法
        list($temp, $def) = Str::explode($key, ' ', 2, $default);
        $temps  = explode(':', $temp, 2);
        $oldKey = $temps[0];
        $newKey = end( $temps );
        return array(
            $newKey,
            array_key_exists($oldKey, $args) ? $args[$oldKey] : $def,
            $oldKey
        );
    }

	public static function get($array, $key, $default = null) {
		if (is_null($key)) {
			return $array;
		}

		if (isset($array[$key])) {
			return $array[$key];
		}

		foreach (explode('.', $key) as $segment) {
			if ((! is_array($array) || ! array_key_exists($segment, $array)) &&
				(! $array instanceof \ArrayAccess || ! $array->offsetExists($segment))) {
				return Str::value($default);
			}

			$array = $array[$segment];
		}

		return $array;
	}

    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    public static function has($array, $keys)
    {
        $keys = (array) $keys;

        if (! $array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /** 根据字符串取一个值，采用递进的方法取值
     * @param string $keys 关键字
     * @param array $values 值
     * @param null $default 默认
     * @param string $link 关键字的连接符
     * @return string|array
     */
	public static function getChild($keys, array $values, $default = null, $link = '.') {
		return self::getChildByArray(explode($link, $keys), $values, $default);
	}
	
	/**
	 * 根据关键字数组取值
	 * @param array $keys
	 * @param array $values
	 * @param null $default
	 * @return array|string
	 */
	public static function getChildByArray(array $keys, array $values, $default = null) {
        return match (count($keys)) {
            0 => $values,
            1 => array_key_exists($keys[0], $values) ? $values[$keys[0]] : $default,
            2 => $values[$keys[0]][$keys[1]] ?? $default,
            3 => $values[$keys[0]][$keys[1]][$keys[2]] ?? $default,
            4 => $values[$keys[0]][$keys[1]][$keys[2]][$keys[3]] ?? $default,
            default => isset($values[$keys[0]]) ? self::getChildByArray(array_slice($keys, 1), $values[$keys[0]], $default) : $default,
        };
	}

	/**
	 * REMOVE KEY IN ARRAY AND RETURN VALUE OR DEFAULT
	 * @param array $array
	 * @param string $key
	 * @param null $default
	 * @return mixed|null
	 */
	public static function remove(&$array, $key, $default = null) {
		if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
			$value = $array[$key];
			unset($array[$key]);

			return $value;
		}

		return $default;
	}
	
	/**
	 * 根据关键字数组取值(其中包含特殊关键字*)
	 * @param string $keys 关键字
     * @param array $values 值
     * @param null $default 默认
     * @param string $link 关键字的连接符
     * @return string|array
	 */
	public static function getChildWithStar($keys, array $values, $default = null, $link = '.') {
		$keys = explode($link, $keys, 2);
		$results = null;
		if ($keys[0] === '*') {
			$results = $values;
		} else {
			$results = array_key_exists($keys[0], $values) ? $values[$keys[0]] : $default;
		}
		if (count($keys) == 1) {
			return $results;
		}
		return self::getChildWithStar($keys[1], $results, $default, $link);
	}

    /**
	 * 扩展 array_combine 能够用于不同数目
     * @param array $keys
     * @param array $values
     * @param bool $complete
     * @return array
     */
	public static function combine(array $keys, array $values, $complete = TRUE) {
		$arr = array();
		if (!self::isAssoc($values) ) {
            for ($i = 0; $i < count($keys); $i++) {
                $arr[$keys[$i]] = isset($values[$i]) ? $values[$i] : null;
            }
            return $arr;
        }
        foreach ($keys as $key) {
        	if (isset($values[$key])) {
        		$arr[$key] = $values[$key];
        	} else if ($complete) {
        		$arr[$key] = null;
        	}
        }
		return $arr;
	}

    /** 判断是否是关联数组
     * @param array $args
     * @return bool
     */
	public static function isAssoc(mixed $args): bool {
		return is_array($args) && array_keys($args) !== range(0, count($args) - 1);
	}

    /**
     * 判断是否时多维数组
     * @param $args
     * @return bool
     */
	public static function isMultidimensional(mixed $args) {
        if (!is_array($args)) {
            return false;
        }
        return (bool)count(array_filter($args, 'is_array'));
    }

	/**
	 * 取关联数组的第 n 个的键值
	 * @param array $args
	 * @param int $index
	 * @return array
	 */
	public static function split(array $args, $index = 0) {
		if (count($args) <= $index) {
			return [null, null];
		}
		$i = 0;
		foreach ($args as $key => $item) {
			if ($i == $index) {
				return [$key, $item];
			}
			$i ++ ;
		}
        return [null, null];
	}

	/**
	 * 把数组的值的首字母大写
	 * @param array $arguments
	 * @return array
	 */
	public static function ucFirst(array $arguments) {
		return array_map('ucfirst', $arguments);
	}

    /**
     * GET KEY BY VALUE IN ARRAY
     * @param array $args
     * @param mixed $value
     * @return mixed
     */
	public static function getKey(array $args, $value) {
	    return array_search($value, $args);
    }

    /**
     *
     * EXAMPLE:
     *  $args = [
     *      [
     *          'a' => 12,
     *          'b' => 12323
     *      ]
     * ];
     * if $column = 'a', $indexKey = null
     * return [0 => 12],
     * else $indexKey = 'b',
     * return = [12323 => 12];
     *
     * @param array $args
     * @param string $column
     * @param string $indexKey
     * @return array
     */
    public static function getColumn(array $args, $column, $indexKey = null) {
        return array_column($args, $column, $indexKey);
    }
	
	/**
	 * 合并多个二维数组 如果键名相同后面的数组会覆盖前面的数组
	 * @param array $arr
	 * @param array ...
	 * @return array
	 */
	public static function merge2D(array $arr) {
		$args = func_get_args();
		if (func_num_args() < 1) {
		    return [];
        }
		$results = call_user_func_array('array_merge', $args);
		foreach ($results as $key => $value) {
			$temps = [];
			$isArr = true;
			foreach ($args as $val) {
			    if (!array_key_exists($key, $val)) {
			        continue;
                }
			    if (!is_array($val[$key])) {
			        $isArr = false;
                }
				$temps[] = $val[$key];
			}
			if (empty($temps)) {
			    continue;
            }
			$results[$key] = !$isArr ? end($temps) :
                call_user_func_array('array_merge', $temps);
		}
		return $results;
	}

    /**
     * 从二维数组中移除
     * @param array $data
     * @param array|string $keys
     * @return array
     */
	public static function unset2D(array $data, $keys) {
	    foreach ((array)$keys as $key => $item) {
	        if (!is_array($item)) {
	            unset($data[$item]);
	            continue;
            }
            if (!isset($data[$key]) && !array_key_exists($key, $data)) {
	            continue;
            }
            if (!is_array($data[$key])) {
                unset($data[$key]);
                continue;
            }
            foreach ($item as $value) {
	            unset($data[$key][$value]);
            }
        }
        return $data;
    }

	/**
	 * 判断是否在二维数组中 if no return false; or return $key
	 * @param string $needle
	 * @param array $args
	 * @return bool|int|string
	 */
	public static function inArray($needle, array $args) {
		foreach ($args as $key => $value) {
			if (in_array($needle, (array)$value)) {
				return $key;
			}
		}
		return false;
	}

	/**
	 * 根据关键字排序，不是在关键字上往后移
	 *
	 *
	 * @param array $args 要排序的数组.
	 * @param array $keys 关键字数组.
	 * @return array 返回排序的数组,
	 */
	public static function sortByKey(array $args, array $keys) {
		$keyArray = $noArray = array();
		foreach ($keys as $value) {
			if (isset( $args[$value] )) {
				$keyArray[$value] = $args[$value];
			}
		}
		foreach ($args as $key => $value) {
			if (!in_array($key, $keys)) {
				$noArray[$key] = $value;
			}
		}
		return array_merge($keyArray, $noArray);
	}

	public static function keyAndValue(array $args) {
	    return [
	        key($args),
            current($args)
        ];
    }

    /**
     * 转换成真实的类型
     * @param array $data
     * @param array $maps
     * @return array
     */
    public static function toRealArr(array $data, array $maps) {
	    foreach ($data as $key => $item) {
	        if (!isset($maps[$key])) {
	            continue;
            }
            if (in_array($maps[$key], ['int', 'integer'])) {
	            $data[$key] = intval($item);
	            continue;
            }
            if ($maps[$key] == 'float') {
                $data[$key] = floatval($item);
                continue;
            }
            if ($maps[$key] == 'double') {
                $data[$key] = doubleval($item);
                continue;
            }
            if (in_array($maps[$key], ['bool', 'boolean'])) {
                $data[$key] = Str::toBool($item);
                continue;
            }
            if ($maps[$key] == 'datetime') {
                $data[$key] = Time::format($item);
                continue;
            }
            if ($maps[$key] == 'ago') {
                $data[$key] = Time::ago($item);
                continue;
            }
            if ($maps[$key] == 'array') {
                $data[$key] = Json::decode($item);
            }
        }
        return $data;
    }

    /**
     * 获取真实的类型
     * @param string $class
     * @return bool|array
     * @throws \Exception
     */
    public static function getRealType($class) {
        $callback = function () use ($class) {
            $instance = new \ReflectionClass($class);
            $doc = $instance->getDocComment();
            unset($instance);
            if (!is_string($doc)) {
                return [];
            }
            preg_match_all('/\@property\s+([a-z]+)\s+\$([a-z\d_]+)/i', $doc, $matches, PREG_SET_ORDER);
            return array_column($matches, 1, 2);
        };
        if (app()->isDebug()) {
            return $callback();
        }
        return cache()->getOrSet('class_doc_type:'.$class, $callback, 86400);
    }
}
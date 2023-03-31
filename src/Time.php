<?php
declare(strict_types=1);
namespace Zodream\Helpers;

use Exception;

/**
* time 的扩展
* 
* @author Jason
*/

class Time {

    /**
     * 将时间转换成字符串格式
     * @param int|string $time
     * @param string $format
     * @return string
     */
	public static function format(int|string $time = '', string $format = 'Y-m-d H:i:s'): string
    {
		if (!empty($time) && !is_numeric($time)) {
			$format = $time;
			$time = time();
		}
		if (is_null($time)) {
			$time = time();
		}
		return date($format, is_string($time) ? intval($time) : $time);
	}

    /**
     * 获取mysql 时间戳
     * @param null $time
     * @return string
     */
	public static function timestamp($time = null): string
    {
        if (is_null($time)) {
            $time = time();
        }
	    return date('Y-m-d H:i:s', $time);
    }

    /**
     * 获取时间是多久以前
     * @param $time
     * @param int $maxSecond
     * @param string $maxFormat
     * @return string
     * @throws Exception
     */
	public static function isTimeAgo(int $time, int $maxSecond = 0, string $maxFormat = 'Y-m-d'): string
    {
		if (empty($time)) {
			return '';
		}
		$differ = time() - $time;
		if ($maxSecond > 0 && $differ > $maxSecond) {
		    return static::format($time, $maxFormat);
        }
		if ($differ < 1) {
			$differ = 1;
		}
		$tokens = array (
				31536000 => '{time} year ago',
				2592000  => '{time} month ago',
				604800   => '{time} week ago',
				86400    => '{time} day ago',
				3600     => '{time} hour ago',
				60       => '{time} minute ago',
				1        => '{time} second ago'
		);
	
		foreach ($tokens as $unit => $text) {
			if ($differ < $unit) continue;
			$numberOfUnits = floor($differ / $unit);
			return __($text, [
			    'time' => $numberOfUnits
            ]);
		}
		return self::format($time);
	}

    /**
     * 格式化时间，同步其他端显示
     * @param int $time
     * @return string
     */
	public static function ago(int $time): string {
        if (empty($time)) {
            return '--';
        }
        $now = time();
        $diff = floor($now - $time);
        if ($diff < 1) {
            return __('now');
        }
        if ($diff < 60) {
            return __('{time} second ago', [
                'time' => $diff
            ]);
        }
        if ($diff < 3600) {
            return __('{time} minute ago', [
                'time' => floor($diff / 60)
            ]);
        }
        if ($diff < 86400) {
            return __('{time} hour ago', [
                'time' => floor($diff / 3600)
            ]);
        }
        if ($diff < 2592000) {
            return __('{time} day ago', [
                'time' => floor($diff / 86400)
            ]);
        }
        $year = date('Y', $time);
        if ($year === date('Y', $now)) {
            return __('{month}-{day}', [
                'month' => sprintf('%02d', date('m', $time)),
                'day' => sprintf('%02d', date('d', $time))
            ]);
        }
        return __('{year}-{month}', [
            'year' => $year,
            'month' => sprintf('%02d', date('m', $time))
        ]);
    }

    /**
     * 获取当前时间精确到微秒 以秒为单位
     * @return float
     */
	public static function millisecond(): float {
        return microtime(true);
    }

    /**
     * 获取过去的时间，以毫秒为单位
     * @param float $start 以秒为单位 millisecond() 获取到的值
     * @return float
     */
    public static function elapsedTime(float $start): float
    {
        return round((self::millisecond() - $start) * 1000, 2);
    }

    /**
     * 根据起止日期生成连续日期
     * @param string $start
     * @param string $end
     * @param string $format
     * @return array
     */
    public static function rangeDate(string $start, string $end, string $format = 'Y-m-d'): array
    {
        $day = 86400;
        $start = strtotime($start);
        $end = strtotime($end) + $day;
        $days = [];
        for (; $start < $end; $start += $day) {
            $days[] = static::format($start, $format);
        }
        return $days;
    }

    /**
     * 获取月份的开始结束日期
     * @param integer $time
     * @param string|bool $format
     * @return array
     */
    public static function month(int $time, string|bool $format = 'Y-m-d'): array
    {
        $start_at = date('Y-m-01', $time);
        $end_at = date('Y-m-t', $time);
        if ($format === 'Y-m-d') {
            return [$start_at, $end_at];
        }
        if ($format === 'Y-m-d H:i:s') {
            return [$start_at.' 00:00:00', $end_at.' 23:59:59'];
        }
        $start_at = strtotime($start_at.' 00:00:00');
        $end_at = strtotime($end_at.' 23:59:59');
        if (!is_string($format)) {
            return [$start_at, $end_at];
        }
        return [static::format($start_at, $format), static::format($end_at, $format)];
    }

    /**
     * 获取周的起止日期
     * @param integer $now
     * @param string|bool $format
     * @return array
     */
    public static function week(int $now, string|bool $format = 'Y-m-d'): array
    {
        $time = ('1' == date('w', $now)) ? strtotime('Monday', $now)
            : strtotime('last Monday', $now);
        $end = strtotime('Sunday', $now) + 86399;
        if (!is_string($format)) {
            return [$time, $end];
        }
        return [static::format($time, $format), static::format($end, $format)];
    }

    /**
     * 转化为星期几或周几
     * @param integer|string $time
     * @param string $prefix
     * @return string
     */
    public static function weekFormat(int|string $time, string $prefix = '星期'): string {
        if (!is_integer($time)) {
            $time = strtotime($time);
        }
        $maps = ['日', '一', '二', '三', '四', '五', '六'];
        return $prefix.$maps[date('w', $time)];
    }

    /**
     * 格式化时间
     * @param int $time
     * @return string
     */
    public static function hoursFormat(int $time): string {
        return sprintf('%s:%s:%s',
            str_pad((string)floor($time / 3600), 2, '0', STR_PAD_LEFT),
            str_pad((string)floor($time % 3600 / 60), 2, '0', STR_PAD_LEFT),
            str_pad((string)floor($time % 60), 2, '0', STR_PAD_LEFT)
        );
    }

    /**
     * 格式化时间间隔
     * @param float $seconds
     * @return string
     */
    public static function formatDuration(float $seconds): string {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 0.1) {
            return round($seconds * 1000, 2) . 'ms';
        } elseif ($seconds < 1) {
            return round($seconds * 1000) . 'ms';
        }
        return round($seconds, 2) . 's';
    }

}
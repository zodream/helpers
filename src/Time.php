<?php 
namespace Zodream\Helpers;

/**
* time 的扩展
* 
* @author Jason
*/

class Time {

	/**
	 * 将时间转换成字符串格式
	 * @param null $time
	 * @param string $format
	 * @return string
	 */
	public static function format($time = null, $format = 'Y-m-d H:i:s') {
		if (!empty($time) && !is_numeric($time)) {
			$format = $time;
			$time = time();
		}
		if (is_null($time)) {
			$time = time();
		}
		return date($format, $time);
	}

    /**
     * 获取mysql 时间戳
     * @param null $time
     * @return string
     */
	public static function timestamp($time = null) {
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
     * @return int|string
     * @throws \Exception
     */
	public static function isTimeAgo($time, $maxSecond = 0, $maxFormat = 'Y-m-d'){
		if (empty($time)) {
			return null;
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
     * 获取当前时间精确到微秒 以秒为单位
     * @return float
     */
	public static function millisecond() {
        return microtime(true);
    }

    /**
     * 获取过去的时间，以毫秒为单位
     * @param float $start 以秒为单位 millisecond() 获取到的值
     * @return float
     */
    public static function elapsedTime($start) {
        return round((self::millisecond() - $start) * 1000, 2);
    }

    /**
     * 根据起止日期生成连续日期
     * @param string $start
     * @param string $end
     * @param string $format
     * @return array
     */
    public static function rangeDate($start, $end, $format = 'Y-m-d') {
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
     * @param string $format
     * @return array
     */
    public static function month($time, $format = 'Y-m-d') {
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
     * @param string $format
     * @return array
     */
    public static function week($now, $format = 'Y-m-d') {
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
    public static function weekFormat($time, $prefix = '星期') {
        if (!is_integer($time)) {
            $time = strtotime($time);
        }
        $maps = ['日', '一', '二', '三', '四', '五', '六'];
        return $prefix.$maps[date('w', $time)];
    }

}
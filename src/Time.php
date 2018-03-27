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
	 * @return int|string
	 */
	public static function isTimeAgo($time){
		if (empty($time)) {
			return null;
		}
		$differ = time() - $time;
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
			return str_replace('{time}', $numberOfUnits, $text);
		}
		return self::format($time);
	}

    /**
     * GET NOW WITH microtime
     * @return float
     */
	public static function millisecond() {
        list($tmp1, $tmp2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
    }
}
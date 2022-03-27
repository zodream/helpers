<?php
declare(strict_types=1);
namespace Zodream\Helpers;
/**
 * 无限树结构
 *
 * @author Jason
 */
class Tree {

    /**
     * 获取子孙id
     * @param array $data
     * @param string|int $parent_id
     * @param string $key
     * @param string $id_key
     * @return array
     */
	public static function getTreeChild(array $data, string|int $parent_id, string $key = 'parent_id', string $id_key = 'id'): array {
		$result = [];
		$args   = [$parent_id];
		do {
			$kids = [];
			$flag = false;
			foreach ($args as $fid) {
				for ($i = count($data) - 1; $i >=0 ; $i --) {
					$node = $data[$i];
					if ($node[$key] == $fid) {
						array_splice($data, $i , 1);
						$result[] = $node[$id_key];
						$kids[]   = $node[$id_key];
						$flag     = true;
					}
				}
			}
			$args = $kids;
		} while($flag === true);
		return $result;
	}

    /**
     * 获取父级id
     * @param array $data
     * @param string|int $id
     * @param string $key
     * @param string $id_key
     * @return array
     */
	public static function getTreeParent(array $data, string|int $id, string $key = 'parent_id', string $id_key = 'id'): array {
		$result = [];
		$obj    = [];
		foreach ($data as $node) {
			$obj[$node[$id_key]] = $node[$key];
		}
		while ($id) {
			if (!isset($obj[$id]) || $obj[$id] <= 0) {
			    break;
            }
            if (in_array($obj[$id], $result)) {
			    // 数组有问题，陷入死循环，提前退出
			    break;
            }
            $result[] = $obj[$id];
            $id = $obj[$id];
		}
		unset($obj);
		return array_reverse($result);
	}

}
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
     * @return int[] 不包含自身id
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

    public static function pathToTree(array $items): array {
        $root = [];
        for ($i = 0; $i < count($items); $i++){
            $chain = explode('/', $items[$i]);
            $currentHierarchy = &$root;
            $last = count($chain) - 1;
            for ($j = 0; $j <= $last; $j++){
                $wantedNode = $chain[$j];
                if ($wantedNode === ''){
                  continue;
                }
                $success = false;
                // 遍历root是否已有该层级
                for($k = 0; $k < count($currentHierarchy); $k++){
                    if($currentHierarchy[$k]['name'] === $wantedNode){
                        if (!isset($currentHierarchy[$k]['children'])) {
                            $currentHierarchy[$k]['children'] = [];
                        }
                        $currentHierarchy = &$currentHierarchy[$k]['children'];
                        $success = true;
                        break;
                    }
                }

                if ($success) {
                    continue;
                }
                if($j === $last){
                    $key = $items[$i];
                } else {
                    $key = implode('/', array_slice($chain, 0, $j + 1)) . '/';
                }
                $newNode = [
                    'key' => $key,
                    'name' => $wantedNode,
                    'children' => []
                ];
                // 文件，最后一个字符不是"/“符号
                if ($j === $last){
                    unset($newNode['children']);
                }
                $currentHierarchy[] = $newNode;
                if ($j === $last) {
                    break;
                }
                $currentHierarchy = &$currentHierarchy[count($currentHierarchy) - 1]['children'];
            }
            unset($currentHierarchy);
        }
        return $root;
    }
}
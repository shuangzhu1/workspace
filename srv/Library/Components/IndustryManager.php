<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-4-8
 * Time: 下午3:05
 */

namespace Components;

use Models\Industries;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\User\Plugin;

class IndustryManager extends Plugin
{

    /**
     * @var BackendInterface
     */
    private $cache = null;

    /**
     * @var IndustryManager
     */
    private static $instance = null;

    private function __construct()
    {
        $this->cache = $this->di->get('memcached');
    }

    public static function instance()
    {
        if (!self::$instance instanceof IndustryManager) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getTreeData($host_key, $refresh = false)
    {
        $cacheKey = "industries_list_for_tree_" . $host_key;
        $data = $this->cache->get($cacheKey);
        if (!$data || $refresh) {
            $topMenus = Industries::find(" pid='0' AND host_key='{$host_key}'")->toArray();
            $data = array();
            if (count($topMenus) > 0) {
                foreach ($topMenus as $item) {
                    $subMenus = Industries::find(" pid='{$item['id']}' AND host_key='{$host_key}'")->toArray();
                    $children = [];
                    if ($subMenus) {
                        foreach ($subMenus as $child) {
                            $children[] = array(
                                'pid' => $item['id'],
                                'id' => $child['id'],
                                'text' => $child['name'],
                                'desc' => $child['desc']
                            );
                        }
                    }
                    unset($subMenus);
                    $data[] = array(
                        'pid' => 0,
                        'id' => $item['id'],
                        'text' => $item['name'],
                        'desc' => $item['desc'],
                        'children' => $children
                    );
                }
                unset($topMenus);
            }
            $this->cache->save($cacheKey, $data);
        }
        return $data;
    }

    public function getIndustryById($cid)
    {
        return [];
    }
}
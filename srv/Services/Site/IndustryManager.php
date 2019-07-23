<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 9:24
 */

namespace Services\Site;


use Models\Site\SiteIndustries;
use Phalcon\Mvc\User\Plugin;

class IndustryManager extends Plugin
{
    private $cache = null;

    /**
     * @var IndustryManager
     */
    private static $instance = null;

    private function __construct()
    {
        $this->cache = new CacheSetting('redis');
    }

    public static function instance()
    {
        if (!self::$instance instanceof IndustryManager) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**获取行业列表
     * @param bool $refresh
     * @return array|\Phalcon\Mvc\ResultsetInterface
     */
    public function industries($refresh = false)
    {
        $data = $this->cache->get(CacheSetting::PREFIX_INDUSTRY); /*缓存数据读取*/
        if (!$data || $refresh) {
            $data = SiteIndustries::getByColumnKeyList(['status=1', 'columns' => 'id,name', 'order' => 'created desc'], 'id');
            $this->cache->set(CacheSetting::PREFIX_INDUSTRY, '', $data);
        }
        return $data;
    }

    /*获取行业数*/
    public function getTreeData()
    {
        $topMenus = SiteIndustries::findList(["parent_id='0'"]);

        $data = array();
        if (count($topMenus) > 0) {
            foreach ($topMenus as $item) {
                $subMenus = SiteIndustries::findList("parent_id='{$item['id']}'");

                $children = [];
                if ($subMenus) {
                    foreach ($subMenus as $child) {
                        $children[] = array(
                            'parent_id' => $item['id'],
                            'id' => $child['id'],
                            'name' => $child['name'],
                            'desc' => $child['desc'],
                            'created' => $child['created'],
                            'type' => 'item',
                        );
                    }
                }
                unset($subMenus);
                $data[] = array(
                    'parent_id' => 0,
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'desc' => $item['desc'],
                    'children' => $children,
                    'created' => $item['created'],
                    'type' => 'folder'
                );
            }
            unset($topMenus);
        }
        return $data;
    }

    /**获取具体的行业信息
     * @param $industry_id
     * @return array
     */
    public function getIndustriesById($industry_id)
    {
        $data = self::industries();
        return $data && $data[$industry_id] ? $data[$industry_id] : [];
    }
}
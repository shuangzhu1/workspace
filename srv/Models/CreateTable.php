<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/14
 * Time: 14:32
 */

namespace Models;


use Models\Viewer\ViewerTb;
use Phalcon\Mvc\User\Plugin;

class CreateTable extends Plugin
{
    //创建店铺浏览记录表
    public function createShopViewTable($shop_id)
    {
        $table_name = "shop_viewer_$shop_id";
        $res = $this->di->get('db_viewer')->execute("CREATE TABLE IF NOT EXISTS `$table_name` (
   `id` int(11) unsigned NOT NULL  AUTO_INCREMENT COMMENT  '主键ID',
   `user_id` INT(11) unsigned NOT NULL  COMMENT  '用户ID',
   `ymd` INT(8) unsigned  NOT NULL DEFAULT 0 COMMENT  'Ymd',
   `count` mediumint(8) NOT NULL DEFAULT 0 COMMENT  '当天调用次数',
   `times` varchar(2000) NOT NULL DEFAULT '' COMMENT  '时间点,多个时间以英文逗号分割',
   `f_time` INT(11) NOT NULL  COMMENT  '当天第一次请求时间',
   `l_time` INT(11) NOT NULL  COMMENT  '当天最后一次请求时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id_ymd` (`user_id`,`ymd`),
    KEY `user` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        if ($res) {
            ViewerTb::insertOne(['table_name'=>$table_name]);
            return $table_name;
        } else {
            return false;
        }
    }

    //创建店铺浏览记录表
    public function createGoodViewTable($good_id)
    {
        $table_name = "good_viewer_$good_id";
        $res = $this->di->get('db_viewer')->execute("CREATE TABLE IF NOT EXISTS `$table_name` (
   `id` int(11) unsigned NOT NULL  AUTO_INCREMENT COMMENT  '主键ID',
   `user_id` INT(11) unsigned NOT NULL  COMMENT  '用户ID',
   `ymd` INT(8) unsigned  NOT NULL DEFAULT '0' COMMENT  'Ymd',
   `count` mediumint(8) NOT NULL  COMMENT  '当天调用次数',
   `times` varchar(2000) NOT NULL DEFAULT '' COMMENT  '时间点,多个时间以英文逗号分割',
   `f_time` INT(11) NOT NULL COMMENT  '当天第一次请求时间',
   `l_time` INT(11) NOT NULL COMMENT  '当天最后一次请求时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id_ymd` (`user_id`,`ymd`),
    KEY `user` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        if ($res) {
            ViewerTb::insertOne(['table_name'=>$table_name]);
            return $table_name;
        } else {
            return false;
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/14
 * Time: 14:16
 */

namespace Models\Viewer;


use Models\BaseModel;

class GoodViewer extends BaseModel
{
    public function setSource($table_name)
    {
        parent::setSource($table_name);
        return $this;
    }

    public function initialize()
    {
        $this->setConnectionService("db_viewer");
    }

//    public function createTable($table_name)
//    {
//        return $this->getReadConnection()->execute("CREATE TABLE IF NOT EXISTS `$table_name` (
//   `id` int(11) unsigned NOT NULL  AUTO_INCREMENT COMMENT  '主键ID',
//   `user_id` INT(11) unsigned NOT NULL  COMMENT  '用户ID',
//   `ymd` INT(8) unsigned  NOT NULL DEFAULT '0' COMMENT  'Ymd',
//   `count` mediumint(8) NOT NULL  COMMENT  '当天调用次数',
//   `times` varchar(2000) NOT NULL DEFAULT '' COMMENT  '时间点,多个时间以英文逗号分割',
//   `f_time` INT(11) NOT NULL COMMENT  '当天第一次请求时间',
//   `l_time` INT(11) NOT NULL COMMENT  '当天最后一次请求时间',
//    PRIMARY KEY (`id`),
//    KEY `user` (`user_id`)
//) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
//
//    }
}
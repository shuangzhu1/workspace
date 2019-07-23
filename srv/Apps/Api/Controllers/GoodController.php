<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/16
 * Time: 15:14
 */

namespace Multiple\Api\Controllers;


use Services\Shop\GoodManager;
use Util\Ajax;
use Util\Debug;

class GoodController extends ControllerBase
{
    //添加商品 //编辑商品
    public function editAction()
    {
        $name = $this->request->get("name", 'green');//商品名称
        $images = $this->request->get("images", 'string', "");//商品图片-多图以英文逗号分隔
        $price = $this->request->get("price", 'int', 0);//价格-分
        $brief = $this->request->get("brief", "green");//商品描述
        $url = $this->request->get("url", "string", "");//商品外部链接
        $unit = $this->request->get("unit", "string", "");//计价单位【例如斤/小时/平方米】
        $sale = $this->request->get("sale", "int", 0);//商品状态【2-不上架 1-立即上架 3-删除】
        $good_id = $this->request->get("good_id", "int", 0);//商品id
        $shop_id = $this->request->get("shop_id", "int", 0);//店铺id

        $uid = $this->uid;
        //编辑商品
        if ($good_id) {
            if (!$name && !$images && !$price && !$brief && !$url && !$unit && !$sale) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            $res = GoodManager::init()->edit($uid, $good_id, $name, $price, $brief, $images, $url, $unit, $sale);
        } //添加商品
        else {
            if (!$uid || !$name || !$shop_id || !$price || !$unit || !$images || !$sale) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            $res = GoodManager::init()->add($uid, $shop_id, $name, $price, $brief, $images, $url, $unit, $sale);
        }

        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_SUBMIT);
        } else {
            $this->ajax->outRight("提交成功", Ajax::SUCCESS_SUBMIT);
        }
    }

    //商品详情
    public function detailAction()
    {
        $uid=$this->uid;
        $good_id = $this->request->get("good_id", 'int', 0);
        if (!$good_id || !$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        GoodManager::init()->detail($uid,$good_id);
    }

    //商品列表
    public function listAction()
    {
        $sale = $this->request->get("sale", "int", 1); //1-在售 2-仓库中
        $shop_id = $this->request->get("shop_id", 'int', 1);//商店id

        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", "int", 20);
        if (!$shop_id || !$sale || !in_array($sale, [1, 2])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        GoodManager::init()->list($shop_id, $this->uid, $sale, $page, $limit);
    }
}
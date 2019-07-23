<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/15
 * Time: 10:02
 */
class GoodsTask extends \Phalcon\Cli\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

    //商品红包统计 一个商品红包哪些用户抢了
    public function packagePickAction($args)
    {
        set_time_limit(0);
        $now = time();//当前时间
        //--3天前    红包3天后过期
        $date = @$args[0];
        $date = $date ? $date : date('Y-m-d', $now);
        $end_date = strtotime($date) * 1000;
        $start_time = (strtotime($date) - 86400 * 3 * 1000);
        //  $list = \Models\Social\SocialDiscuss::getByColumnKeyList(['package_id>0 and created>=' . $start_time . ' and created<=' . strtotime($date) . ' and share_original_type="good"', 'columns' => 'share_original_item_id as goods_id,package_id,package_info,created'], 'package_id');
        $list = \Models\Square\RedPackage::getByColumnKeyList(['package_id>0 and created>=' . $start_time . ' and created<' . $end_date . ' and type="' . \Services\User\SquareManager::TYPE_GOODS . '"', 'columns' => 'item_id as goods_id,package_id,package_info,created'], 'package_id');
        if ($list) {

            $goods_ids = array_unique(array_column($list, 'goods_id')); //--需要统计的所有商品id
            $package_ids = array_keys($list); //红包id集合
            $exist_package = \Models\Statistics\GoodsPackagePickStat::getColumn(['package_id in (' . implode(',', $package_ids) . ')', 'columns' => 'package_id'], 'package_id');
            $goods_info = \Models\Shop\ShopGoods::getByColumnKeyList(['id in(' . implode(',', $goods_ids) . ')', 'columns' => 'id as goods_id,shop_id,user_id'], 'goods_id');

            foreach ($list as $k => $item) {
                if (!isset($goods_info[$item['goods_id']])) {
                    continue;
                }

                $detail = \Services\MiddleWare\Sl\Request::getPost(\Services\MiddleWare\Sl\Base::PACKAGE_DETAIL, ['uid' => 13, 'redid' => $k]);
                if ($detail && $detail['curl_is_success']) {
                    $content = json_decode($detail['data'], true);
                    $pickers = $content['data']['result'];
                } else {
                    \Util\Debug::log("红包详情请求失败:" . var_export($detail), 'error');
                    continue;
                }
//                $suffix = $k % 20;
//                //--表名
//                $table_name = "redbag_result" . ($suffix > 0 ? $suffix : '');
//                //--红包领取记录
//                $pickers = $this->di->get("db_package_pick")->query("select uid,money,random,created from " . $table_name . " where rid='" . $k . "'")->fetchAll(PDO::FETCH_ASSOC);

                //--有人抢
                if ($pickers) {
                    $pick_info = [];
                    foreach ($pickers as $p) {
                        $pick_info[$p['uid']] = ['m' => $p['money'], 't' => $p['created']];
                    }
                    $pick_info = json_encode($pick_info);


                    //添加过记录的 更新
                    if (in_array($k, $exist_package)) {
                        $data = [
                            'created' => $now,
                            'picker_info' => $pick_info
                        ];
                        \Models\Statistics\GoodsPackagePickStat::updateOne($data, 'package_id="' . $k . '"');
                    } //没添加过记录 插入
                    else {
                        $package_info = json_decode($item['package_info'], true);
                        $package_info['created'] = $item['created'];
                        $package_info = json_encode($package_info);
                        $data = [
                            'created' => $now,
                            'picker_info' => $pick_info,
                            'shop_id' => $goods_info[$item['goods_id']]['shop_id'],
                            'goods_id' => $item['goods_id'],
                            'package_id' => $k,
                            'shop_user_id' => $goods_info[$item['goods_id']]['user_id'],
                            'package_info' => $package_info,
                        ];
                        \Models\Statistics\GoodsPackagePickStat::insertOne($data);
                    }
                }

            }
        }
    }

    //商品抢红包用户统计 -一个用户抢了商品哪些红包
    public function pickPackageAction($args)
    {
        set_time_limit(0);
        $now = time();//当前时间
        //--3天前    红包3天后过期
        $date = @$args[0];
        $date = $date ? $date : date('Y-m-d', $now);
        $end_date = strtotime($date) * 1000;
        $start_time = (strtotime($date) - 86400 * 3 * 1000);

        // $list = \Models\Social\SocialDiscuss::getByColumnKeyList(['package_id>0 and created>=' . $start_time . ' and created<=' . strtotime($date) . ' and share_original_type="good"', 'columns' => 'share_original_item_id as goods_id,package_id,package_info,created'], 'package_id');
        $list = \Models\Square\RedPackage::getByColumnKeyList(['package_id>0 and created>=' . $start_time . ' and created<' . $end_date . ' and type="' . \Services\User\SquareManager::TYPE_GOODS . '"', 'columns' => 'item_id as goods_id,package_id,package_info,created'], 'package_id');
        if ($list) {

            $goods_ids = array_unique(array_column($list, 'goods_id')); //--需要统计的所有商品id
            $goods_info = \Models\Shop\ShopGoods::getByColumnKeyList(['id in(' . implode(',', $goods_ids) . ')', 'columns' => 'id as goods_id,shop_id,user_id'], 'goods_id');
            foreach ($list as $k => $item) {
                //过滤之前删除的商品
                if (!isset($goods_info[$item['goods_id']])) {
                    continue;
                }
//                $suffix = $k % 20;
//                //--表名
//                $table_name = "redbag_result" . ($suffix > 0 ? $suffix : '');
//                //--红包领取记录
//                $pickers = $this->di->get("db_package_pick")->query("select uid,money,random,created from " . $table_name . " where rid='" . $k . "'")->fetchAll(PDO::FETCH_ASSOC);

                $detail = \Services\MiddleWare\Sl\Request::getPost(\Services\MiddleWare\Sl\Base::PACKAGE_DETAIL, ['uid' => 13, 'redid' => $k]);
                if ($detail && $detail['curl_is_success']) {
                    $content = json_decode($detail['data'], true);
                    $pickers = $content['data']['result'];
                } else {
                    \Util\Debug::log("红包详情请求失败:" . var_export($detail), 'error');
                    continue;
                }

                $discuss_package_info = json_decode($item['package_info'], true);

                if ($pickers) {
                    $exist_uids = \Models\Statistics\GoodsPickPackageStat::getByColumnKeyList(['user_id in (' . implode(',', array_column($pickers, 'uid')) . ') and goods_id=' . $item['goods_id'], 'columns' => 'package_info,user_id as uid,id'], 'uid');
                    foreach ($pickers as $p) {
                        //之前抢过该商品的红包 更新
                        if (key_exists($p['uid'], $exist_uids)) {
                            $package_info = json_decode($exist_uids[$p['uid']]['package_info'], true);
                            $package_info[$k] = ['m' => $p['money'], 'tm' => $discuss_package_info['money'], 't' => $item['created']];
                            \Models\Statistics\GoodsPickPackageStat::updateOne([
                                'created' => $now,
                                'package_info' => json_encode($package_info)
                            ], 'id=' . $exist_uids[$p['uid']]['id']);
                        } //之前没抢过该商品的红包 插入
                        else {
                            $package_info = [$k => ['m' => $p['money'], 'tm' => $discuss_package_info['money'], 't' => $item['created']]];
                            $data = [
                                'created' => $now,
                                'package_info' => json_encode($package_info),
                                'user_id' => $p['uid'],
                                'shop_id' => $goods_info[$item['goods_id']]['shop_id'],
                                'goods_id' => $item['goods_id'],
                                'shop_user_id' => $goods_info[$item['goods_id']]['user_id'],
                            ];
                            \Models\Statistics\GoodsPickPackageStat::insertOne($data);
                        }
                    }
                }
            }
        }
    }
}
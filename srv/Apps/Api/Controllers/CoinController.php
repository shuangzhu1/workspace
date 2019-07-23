<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/20
 * Time: 18:19
 */

namespace Multiple\Api\Controllers;


use Components\Rules\Coin\PointRule;
use Models\Site\SiteMaterial;
use Models\User\UserCoinRules;
use Models\User\UserDragonCoin;
use Services\Site\MaterialManager;
use Services\Site\SiteKeyValManager;
use Services\User\DragonCoin;
use Util\Ajax;
use Util\Debug;

class CoinController extends ControllerBase
{
    //充值
    public function chargeAction()
    {
        $uid = $this->uid;
        $money = $this->request->get('money', 'int', 0);//充值金额
        $coins = $this->request->get('coins', 'int', 0);//充值龙豆数
        $platform = $this->request->get('platform', 'int', 1);//1-苹果 2-支付宝 3-微信 4-余额
        $pay_id = $this->request->get('pay_id', 'string', '');//支付流水号
        $pay_time = $this->request->get('pay_time', 'int', 0);//支付时间
        if (!$uid || !$money || !$coins || !$platform || !$pay_id || !$pay_time || !key_exists($platform, PointRule::$charge_type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = PointRule::init()->chargeCoin($uid, $coins, ['money' => $money, 'platform' => $platform, 'pay_id' => $pay_id, 'pay_time' => $pay_time]);
        if ($res === false) {
            $this->ajax->outError(Ajax::FAIL_CHARGE);
        }
        $this->ajax->outRight(['donate' => $res]);
    }


    //龙豆记录
    public function recordsAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $type = $this->request->get("type", 'int', 0); //0-全部 1-收入 2-支出
        $this->ajax->outRight(PointRule::init()->getRecords($uid, $type, $page, $limit));
    }

    //龙豆规则
    public function ruleAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $point_rule = UserCoinRules::findOne(['behavior=' . PointRule::BEHAVIOR_CHARGE]);
        $rule = $point_rule['params'];
        $rule = json_decode($rule, true);
        $this->ajax->outRight(array_values($rule));
    }

    //我的龙币
    public function myDragonCoinAction()
    {
        $type = $this->request->get("type", 'int', 0);
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = [];
        //配置及我的龙币
        if ($type == 0) {
            $res = ['config' => ['change_type' => '', 'diamond_rate' => 0], 'info' => ['history_count' => 0, 'available_count' => 0, 'changed_money' => 0, 'changed_beans' => 0, 'changed_diamond' => 0]];
        } //仅配置
        elseif ($type == 1) {
            $res = ['config' => ['change_type' => '', 'diamond_rate' => 0]];

        } //我的龙币信息
        elseif ($type == 2) {
            $res = ['info' => ['history_count' => 0, 'available_count' => 0, 'changed_money' => 0, 'changed_beans' => 0, 'changed_diamond' => 0]];
        }

        if ($type == 0 || $type == 2) {
            $dragon_coin = UserDragonCoin::findOne(['user_id=' . $uid, 'columns' => 'history_count,available_count,changed_money,changed_beans,changed_diamond']);
            if ($dragon_coin) {
                $res['info']['history_count'] = intval($dragon_coin['history_count']);
                $res['info']['available_count'] = intval($dragon_coin['available_count']);
                $res['info']['changed_money'] = intval($dragon_coin['changed_money']);
                $res['info']['changed_beans'] = intval($dragon_coin['changed_beans']);
                $res['info']['changed_diamond'] = intval($dragon_coin['changed_diamond']);
            }
        }
        if ($type == 0 || $type == 1) {
            $setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'coin_setting');
            if ($setting) {
                if ($setting['change_type']) {
                    $res['config']['change_type'] = implode(',', $setting['change_type']);
                }
                if ($setting['diamond_rate']) {
                    $res['config']['diamond_rate'] = floatval($setting['diamond_rate']);
                }
            }
        }
        $this->ajax->outRight($res);
    }

    //龙币记录
    public function dragonRecordsAction()
    {
        $uid = $this->uid;
        $last_id = $this->request->get("last_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);
        $res = DragonCoin::getInstance()->getRecords($uid, $last_id, $limit);
        $this->ajax->outRight($res);
    }

    //兑换龙币
    public function changeDragonCoinAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("type", 'int', 2); //2-现金 3-龙钻 4-龙豆
        $coins = $this->request->get("coins", 'int', 0);//龙币数
        if (!$uid || !$coins || !in_array($type, [DragonCoin::TYPE_CHANGE_CASH, DragonCoin::TYPE_CHANGE_DIAMOND, DragonCoin::TYPE_CHANGE_DRAGON_BEANS])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $dragon = DragonCoin::getInstance();
        $res = $dragon->setType($type)
            ->setUid($uid)
            ->setVal($coins)
            ->setInOut(DragonCoin::IN_OUT_OUT)
            ->execute();
        if (!$res) {
            $code = $dragon->getErrorCode();
            if ($code == 1) {
                $this->ajax->outError(Ajax::ERROR_DRAGON_COIN_NOT_ENOUGH);
            } else {
                $this->ajax->outError(Ajax::FAIL_SUBMIT);
            }
        }
        $this->ajax->outRight("请求成功", Ajax::SUCCESS_SUBMIT);
    }

    /**
     * 用户收益公告
     * 每次进入相关页面，从该接口获取数据，接口返回数据则显示，否则不显示，显示与否由后台配置
     */
    public function getNoticeAction()
    {
        $type = $this->request->get('type','int',0);
        if( !in_array($type,[3,4]) )//3:现金收益公告 4：礼物收益公告
        {
            $this->ajax->outRight([]);
        }else
        {
            $res = SiteMaterial::findOne(['enable = 1 and type = ' . $type,'columns' => 'title,link','order' => 'created desc']);
            if( $res )
            {
                $res['link'] = MaterialManager::$urlPrefix . $res['link'];
                $this->ajax->outRight([$res]);
            }
            $this->ajax->outRight([]);

        }
    }
}
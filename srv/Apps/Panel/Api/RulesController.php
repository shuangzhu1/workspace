<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/19
 * Time: 18:07
 */

namespace Multiple\Panel\Api;


use Components\Rules\Coin\PointRule;
use Models\User\UserCoinRules;
use Models\User\UserPointRules;
use Services\Site\SiteKeyValManager;
use Util\Ajax;

class RulesController extends ApiBase
{
    /*--设置龙豆规则--*/
    public function setCoinPointAction()
    {
        // params
        $data = $this->request->get('data');
        if (!($data)) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // each do
        $this->db->begin();
        try {
            foreach ($data as $row) {
                $id = isset($row['id']) ? $row['id'] : null;
                unset($row['id']); // for update it
                $row['points'] = $row['quantity'];
                unset($row['quantity']);
                $row['limit_count'] = $row['limit'];
                unset($row['limit']);

                if (!$id) {
                    $rule = new UserCoinRules();
                    $row['created'] = time();
                    if (!$rule->insertOne($row)) {
                        $ms = '';
                        foreach ($rule->getMessages() as $m) {
                            $ms .= (string)$m;
                        }
                        throw new \Phalcon\Exception($ms);
                    }
                } else {
                    if (!UserCoinRules::updateOne($row, ['id' => $id])) {
                        $ms = '';
                        throw new \Phalcon\Exception($ms);
                    }
                }
            }
            $this->db->commit();
        } catch (\Phalcon\Exception $e) {
            $this->db->rollback();
            $this->di->get('errorLogger')->error("save user point rules failed." . $e->getMessage());
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, $e->getMessage());
        }

        // log
        $this->ajax->outRight('操作成功');
    }



    /*--设置经验值规则--*/
    public function setPointAction()
    {
        // params
        $data = $this->request->get('data');
        if (!($data)) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // each do
        $this->db->begin();
        try {
            foreach ($data as $row) {
                $id = isset($row['id']) ? $row['id'] : null;
                unset($row['id']); // for update it
                $row['points'] = $row['quantity'];
                unset($row['quantity']);
                $row['limit_count'] = $row['limit'];
                unset($row['limit']);
                if (!$id) {
                    $rule = new UserPointRules();
                    $row['created'] = time();
                    if (!$rule->insertOne($row)) {
                        $ms = '';
                        foreach ($rule->getMessages() as $m) {
                            $ms .= (string)$m;
                        }
                        throw new \Phalcon\Exception($ms);
                    }
                } else {
                    if (!UserPointRules::updateOne($row, ['id' => $id])) {
                        $ms = '';
                        throw new \Phalcon\Exception($ms);
                    }
                }
            }
            $this->db->commit();
        } catch (\Phalcon\Exception $e) {
            $this->db->rollback();
            $this->di->get('errorLogger')->error("save user point rules failed." . $e->getMessage());
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, $e->getMessage());
        }

        // log
        $this->ajax->outRight('操作成功');
    }

    //增加龙豆规则
    public function addCoinRuleAction()
    {
        $key = $this->request->getPost("key", 'string', '');
        $coin = $this->request->getPost("coin", 'int', 0);
        $money = $this->request->getPost("money", 'float', 0);
        $donate = $this->request->getPost("donate", 'int', 0);
        $point_rule = UserCoinRules::findOne(['behavior=' . PointRule::BEHAVIOR_CHARGE]);
        if (!$coin || !$money || $coin < 0 || $money < 0 || $donate < 0) {
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        }
        if ($point_rule) {
            $rule = $point_rule['params'];
            if ($rule) {
                $rule = json_decode($rule, true);
                $money_arr = array_column($rule, 'money');
                if (in_array($money, $money_arr)) {
                    Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "该条规则已经添加过,请勿重复添加");
                }
            }
            $rule[$money] = ["coin" => $coin, 'money' => $money, 'donate' => $donate, 'key' => $key];
            ksort($rule);
            UserCoinRules::updateOne(['params' => json_encode($rule)], 'id=' . $point_rule['id']);
        } else {
            $rule = [$money => ["coin" => $coin, 'money' => $money, 'donate' => $donate, 'key' => $key]];
            $data = [
                'behavior' => PointRule::BEHAVIOR_CHARGE,
                'action' => PointRule::ACTION_UP,
                'term' => PointRule::TERM_EVERY_BEHAVIOR,
                'created' => time(),
                'params' => json_encode($rule)
            ];
            UserCoinRules::insertOne($data);
        }
        Ajax::init()->outRight();
    }

    //编辑规则
    public function saveCoinRuleAction()
    {
        $data = $this->request->getPost('data');
        if (!($data && is_array($data))) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        $point_rule = UserCoinRules::findOne(['behavior=' . PointRule::BEHAVIOR_CHARGE]);
        if (!$point_rule) {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "请先添加规则");
        }
        $rule = $point_rule['params'];
        if (!$rule) {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "请先添加规则");
        }
        $rule = json_decode($rule, true);
        foreach ($data as $row) {
            if (!$row['coin'] || !$row['money']) {
                continue;
            }
            unset($rule[$row['id']]);
            $rule[$row['money']] = [
                "coin" => $row['coin'], 'money' => $row['money'], 'donate' => $row['donate'], 'key' => $row['key']
            ];
            ksort($rule);
        }
        UserCoinRules::updateOne(['params' => json_encode($rule)], 'id=' . $point_rule['id']);
        Ajax::init()->outRight();
    }

    //删除规则
    public function delCoinRuleAction()
    {
        $id = $this->request->getPost("id", 'int', 0);
        $point_rule = UserCoinRules::findOne(['behavior=' . PointRule::BEHAVIOR_CHARGE]);
        if (!$point_rule) {
            Ajax::init()->outRight();
        }
        $rule = $point_rule['params'];
        if (!$rule) {
            Ajax::init()->outRight();
        }
        $rule = json_decode($rule, true);
        if (isset($rule[$id])) {
            unset($rule[$id]);
            UserCoinRules::updateOne(['params' => json_encode($rule)], 'behavior=' . PointRule::BEHAVIOR_CHARGE);
        }
        Ajax::init()->outRight();
    }
}
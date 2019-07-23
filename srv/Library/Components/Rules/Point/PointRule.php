<?php
/**
 * Created by PhpStorm.
 * User: ykuang
 * Date: 16-12-05
 * Time: 上午10:21
 */

namespace Components\Rules\Point;

use Components\User\UserManager;
use Models\User\UserPointLog;
use Models\User\UserPointRules;
use Models\User\Users;
use Util\Ajax;
use Util\Debug;


class PointRule extends AbstractRule
{
    static $instance = null;
    /**
     * behaviors list
     */
    const BEHAVIOR_NEW_DISCUSS = 1000;//发布动态
    const BEHAVIOR_ADD_COMMENT = 1001;//发布评论
    const BEHAVIOR_ADD_LIKE = 1002;//点赞
    const BEHAVIOR_ADD_SHARE = 1003;//分享
    const BEHAVIOR_FINISH_INFO_AREA = 1004;//完善个人资料之地区设置
    const BEHAVIOR_FINISH_INFO_SIGNATURE = 1005;//完善个人资料之个性签名
    const BEHAVIOR_AUTH = 1006;//实名认证
    const BEHAVIOR_FORWARD = 1007;//转发
    const BEHAVIOR_BIND_PHONE = 1008;//绑定手机
    const BEHAVIOR_USER_PHOTOS = 1009;//完善个人资料之照片墙
    const BEHAVIOR_USER_VOICE = 1010;//完善个人资料之语音介绍
    const BEHAVIOR_USER_BIRTHDAY = 1011;//完善个人资料之生日
    const BEHAVIOR_ADD_CONTACT = 1012;//添加好友
    const BEHAVIOR_REPORT = 1013;//举报成功
    const BEHAVIOR_COLLECT = 1014;//收藏


    public static $behaviorNameMap = array(
        self::BEHAVIOR_NEW_DISCUSS => "发布动态",
        self::BEHAVIOR_ADD_COMMENT => '评论',
        self::BEHAVIOR_ADD_LIKE => "点赞",
        self::BEHAVIOR_ADD_SHARE => "分享",
        self::BEHAVIOR_FORWARD => "转发",
        self::BEHAVIOR_FINISH_INFO_AREA => "完善个人资料之地区信息",
        self::BEHAVIOR_FINISH_INFO_SIGNATURE => "完善个人资料之个性签名",
        self::BEHAVIOR_USER_PHOTOS => "完善个人资料之照片墙",
        self::BEHAVIOR_USER_VOICE => "完善个人资料之语音介绍",
        self::BEHAVIOR_USER_BIRTHDAY => "完善个人资料之生日",
        self::BEHAVIOR_AUTH => "实名认证",
        self::BEHAVIOR_BIND_PHONE => "绑定手机",
        self::BEHAVIOR_ADD_CONTACT => "添加好友",
        self::BEHAVIOR_REPORT => "举报成功",
        self::BEHAVIOR_COLLECT => "收藏",

    );


    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function executeRule($user_id, $behavior)
    {
        $rule = $this->getRule($behavior);
        return $this->execute($user_id, $rule);
    }

    public function execute($user_id, RuleStructure $rule)
    {

        if (!$rule->isValid()) {
            return false;
        }
        $userModel = Users::findOne(['id = ' . $user_id, 'columns' => 'points']);
        if (!($userModel && $this->checkLogUnique($user_id, $rule))) {
            return false;
        }
        $userPoints = $userModel['points'];
        $userPointsAvailable = $userPoints;//$userModel['points_available'];
        if ($rule->action == self::ACTION_UP) {
            $userPoints += $rule->getValue();
            $userPointsAvailable += $rule->getValue();
        }

        if ($rule->action == self::ACTION_DOWN) {
            if (empty($userPointsAvailable) || $userPointsAvailable < $rule->getValue()) {
                return Ajax::ERROR_POINTS_NOT_ENOUGH;
            }
            $userPoints -= $rule->getValue();
            $userPointsAvailable -= $rule->getValue();
        }

        try {
            // $this->db->begin();
            // 更新积分及分组及默认头像

            $group_id = UserManager::getInstance()->getUserGroup($userPoints);
            //  $badge = UserManager::getInstance()->getGroupBadge();

            $data = array(
                'points' => $userPoints,
                'grade' => $group_id
            );
            if (!Users::updateOne($data, ['id' => $user_id])) {
                $messages = [];
                throw new \Phalcon\Exception(join(',', $messages));
            }

            /* if ($badge) {
                 $user = Users::findOne('id=' . $user_id);
                 $badge['all'][] = '/static/home/images/base/avatar.jpg';
                 //更新默认头像

                 if (in_array($user['avatar'], $badge['all'])) {
                     Users::updateOne(['avatar' => $badge['detail'][$group_id]], ['id' => $user_id]);
                 }
             }*/
            $rule->total = $userPointsAvailable;
            $this->writeLog($user_id, $rule);
            // self::writeLog();
            //  $this->db->commit();
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            //  $this->db->rollback();
            return false;
        }
        return true;
    }

    public function getRule($behavior)
    {
        $rule = $this->getRuleData($behavior);
        $ruleStructure = new RuleStructure();
        if ($rule) {
            $ruleStructure->setAction($rule['action']);
            $ruleStructure->setBehavior($rule['behavior']);
            $ruleStructure->setTerm($rule['term']);
            $ruleStructure->setValue($rule['points']);
            $ruleStructure->setLimit($rule['limit_count']);
        }
        return $ruleStructure;
    }

    private function getRuleData($behavior)
    {
        $where = 'behavior="' . $behavior . '"';
        $rule = UserPointRules::findOne($where);
        return $rule;
    }

    public function getRulePoints($behavior)
    {
        $rule = $this->getRuleData($behavior);
        if (!$rule) {
            return 0;
        }
        return intval($rule->points);
    }

    protected function checkLogUnique($user, RuleStructure $rule)
    {
        if (!$rule->isValid()) {
            return false;
        }
        $behavior = $rule->getBehavior();
        $where = 'user_id=' . $user . ' AND action=' . $behavior;
        switch ($rule->term) {
            case self::TERM_ONLY_ONE:
                //总执行一次
                $pointLog = UserPointLog::findOne($where);
                if ($pointLog) {
                    return false;
                } else {
                    return true;
                }
                break;
            //一天一次
            case self::TERM_ONCE_A_DAY:
                $pointLog = UserPointLog::findOne(array($where, 'order' => 'created DESC'));
                if ($pointLog) {
                    $timeStart = strtotime(date('Y-m-d'));
                    if ($pointLog['logged'] >= $timeStart) {
                        return false;
                    } else {
                        return true;
                    }
                } else {
                    return true;
                }
                break;
            //每天有次数限制
            case self::TERM_DAY_LIMIT:
                $state_time = strtotime(date('Y-m-d'));
                $where .= " and created>=" . $state_time;
                $LogCount = UserPointLog::dataCount(array($where));
                if ($LogCount) {
                    if ($LogCount >= $rule->limit_count) {
                        return false;
                    } else {
                        return true;
                    }
                } else {
                    return true;
                }
                break;
            //每次
            case self::TERM_EVERY_BEHAVIOR:
            default:
                return true;
        }
    }

    /**
     * @param $user
     * @param RuleStructure $rule
     * @return bool|mixed
     * @throws \Phalcon\Exception
     */
    public function writeLog($user, RuleStructure $rule)
    {
        /*   if ($rule->isValid()) {*/
        $data['user_id'] = $user;
        $data['in_out'] = $rule->getAction();
        $data['action'] = $rule->getBehavior();
        /*   $data['total'] = $rule->total;*/
        $data['created'] = time();
        $data['value'] = $rule->getValue();
        $data['action_desc'] = $this->getBehaviorName($rule->getBehavior());
        $pointLog = new UserPointLog();
        if (!$id = $pointLog->insertOne($data)) {
            $messages = [];
            foreach ($pointLog->getMessages() as $msg) {
                $messages[] = (string)$msg;
            }
            Debug::log(json_encode($messages), 'error');
        }
        //   }
        return true;
    }

    /**
     * @param $behavior
     * @param $action
     * @param $value
     * @param $term
     * @return bool|mixed
     */
    public function setRule($behavior, $action, $value, $term)
    {
        if (!self::behaviorCheck($behavior)) {
            return false;
        }

        $rule = UserPointRules::findOne(' behavior=' . $behavior);
        if ($rule) {
            if ($rule['action'] != $action || $value != $rule['points'] || $rule['term'] != $term) {
                return UserPointRules::updateOne(array(
                    'action' => $action,
                    'points' => $value,
                    'term' => $term
                ), ['id' => $rule['id']]);
            } else {
                return true;
            }
        } else {
            $data = array(
                'behavior' => $behavior,
                'action' => $action,
                'points' => $value,
                'term' => $term,
                'created' => time()
            );

            $rule = new UserPointRules();

            return $rule->insertOne($data);
        }
    }
} 

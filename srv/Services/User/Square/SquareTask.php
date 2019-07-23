<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/28
 * Time: 15:26
 */

namespace Services\User\Square;


use Models\Square\RedPackageTaskLog;
use Models\Square\RedPackageTaskRules;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Util\Debug;

class SquareTask extends AbstractTask
{
    private static $instance = null;

    const TASK_SEND_ACTIVITY = 1;//发布活动【红包雨,知识问答等】
    const TASK_UPLOAD_AVATAR = 2;//上传头像
    const TASK_AUTH = 3;//实名认证
    const TASK_TAG = 4;//添加标签
    const TASK_ADD_RED_PACKAGE = 5;//发布广场红包
    const TASK_SEND_ACTIVITY_1 = 6;//发布一个1块钱游戏
    const TASK_SEND_ACTIVITY_2 = 7;//发布一个5块钱游戏
    const TASK_SEND_ACTIVITY_3 = 8;//发布一个10块钱游戏
    const TASK_SEND_ACTIVITY_4 = 9;//发布一个50块钱游戏
    const TASK_SEND_ACTIVITY_5 = 10;//发布一个200块钱游戏
    const TASK_PROMOTE_DOWNLOAD = 11;//推广恐龙谷下载地址


    private static $task_desc = [
        self::TASK_SEND_ACTIVITY => '发布活动'
    ];
    public static $behaviorNameMap = array(
        self::TASK_UPLOAD_AVATAR => "上传头像",
        self::TASK_AUTH => '认证',
        self::TASK_TAG => "添加标签",
        self::TASK_ADD_RED_PACKAGE => "发布广场红包",
        self::TASK_SEND_ACTIVITY_1 => "发布￥1游戏",
        self::TASK_SEND_ACTIVITY_2 => "发布￥5游戏",
        self::TASK_SEND_ACTIVITY_3 => "发布￥10游戏",
        self::TASK_SEND_ACTIVITY_4 => "发布￥50游戏",
        self::TASK_SEND_ACTIVITY_5 => "发布￥200游戏",
        self::TASK_PROMOTE_DOWNLOAD => "分享恐龙谷",
    );

    public static function init($is_cli = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

//
//    /** 完成任务-增加红包领取次数
//     * @param $uid
//     * @param string $device_id
//     * @param $action
//     * @param int $value
//     */
//    public function execute($uid, $device_id = '', $action, $value = 0)
//    {
//        if ($action != self::TASK_SEND_ACTIVITY) {
//            //todo
//            $value = 0;
//        }
//        if ($value <= 0) {
//            return;
//        }
//        if (RedPackageTaskLog::insertOne([
//            'user_id' => $uid,
//            'action' => $action,
//            'action_desc' => self::$task_desc[$action],
//            'created' => time(),
//            'value' => $value
//        ])
//        ) {
//            self::addPickTime($uid, $device_id, $value);
//        }
//
//    }
//

    public function executeRule($user_id, $device_id, $behavior)
    {
        $rule = $this->getRule($behavior);
        return $this->execute($user_id, $device_id, $rule);
    }

    public function execute($user_id, $device_id = '', TaskStructure $rule)
    {
        if (!$rule->isValid()) {
            return false;
        }
        if (!$this->checkLogUnique($user_id, $rule)) {
            return false;
        }
        try {
            if ($this->writeLog($user_id, $rule)) {
                $redis = $this->di->getShared("redis");
                //永久性加
                if ($rule->getPermanent()) {
                    $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, $user_id, $rule->getValue());
                    if ($device_id) {
                        $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, $device_id, $rule->getValue());
                    }
                } //当日加
                else {
                    $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), $user_id, $rule->getValue());
                    if ($device_id) {
                        $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), $device_id, $rule->getValue());
                    }
                }

                //------做任务 把刷新时间清零----------------
                $last_pick_info = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_USER_LAST_PICK, $user_id);
                $last_pick_info = $last_pick_info ? json_decode($last_pick_info, true) : [];
                if ($last_pick_info) {
                    $last_pick_info['flush_time'] = time();
                    $redis->hSet(CacheSetting::KEY_RED_PACKAGE_USER_LAST_PICK, $user_id, json_encode($last_pick_info));
                }

            }
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            return false;
        }
        return true;
    }

    public function getRule($behavior)
    {
        $rule = $this->getRuleData($behavior);
        $ruleStructure = new TaskStructure();
        if ($rule) {
            $ruleStructure->setAction('in');
            $ruleStructure->setBehavior($rule['behavior']);
            $ruleStructure->setTerm($rule['term']);
            $ruleStructure->setValue($rule['add_count']);
            $ruleStructure->setLimit($rule['limit_count']);
            $ruleStructure->setPermanent($rule['is_permanent']);

        }
        return $ruleStructure;
    }

    private function getRuleData($behavior)
    {
        $where = 'behavior="' . $behavior . '" and enable=1';
        $rule = RedPackageTaskRules::findOne($where);
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

    protected function checkLogUnique($user, TaskStructure $rule)
    {
        if (!$rule->isValid()) {
            return false;
        }
        $behavior = $rule->getBehavior();
        $where = 'user_id=' . $user . ' AND action=' . $behavior;
        switch ($rule->term) {
            case self::TERM_ONLY_ONE:
                //总执行一次
                $pointLog = RedPackageTaskLog::findOne($where);
                if ($pointLog) {
                    return false;
                } else {
                    return true;
                }
                break;
            //一天一次
            case self::TERM_ONCE_A_DAY:
                $pointLog = RedPackageTaskLog::findOne(array($where, 'order' => 'created DESC'));
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
                $LogCount = RedPackageTaskLog::dataCount(array($where));
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
     * @param TaskStructure $rule
     * @return bool|mixed
     * @throws \Phalcon\Exception
     */
    public function writeLog($user, TaskStructure $rule)
    {
        /*   if ($rule->isValid()) {*/
        $data['user_id'] = $user;
        $data['action'] = $rule->getBehavior();
        /*   $data['total'] = $rule->total;*/
        $data['created'] = time();
        $data['value'] = $rule->getValue();
        $data['ymd'] = date('Ymd', $data['created']);
        $data['action_desc'] = $this->getBehaviorName($rule->getBehavior());
        $pointLog = new RedPackageTaskLog();
        if (!$id = $pointLog->insertOne($data)) {
            $messages = [];
            foreach ($pointLog->getMessages() as $msg) {
                $messages[] = (string)$msg;
            }
            Debug::log(json_encode($messages), 'error');
            return false;
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

        $rule = RedPackageTaskRules::findOne(' behavior=' . $behavior);
        if ($rule) {
            if ($rule['action'] != $action || $value != $rule['add_count'] || $rule['term'] != $term) {
                return RedPackageTaskRules::updateOne(array(
                    'action' => $action,
                    'add_count' => $value,
                    'term' => $term
                ), ['id' => $rule['id']]);
            } else {
                return true;
            }
        } else {
            $data = array(
                'behavior' => $behavior,
                'action' => $action,
                'add_count' => $value,
                'term' => $term,
                'created' => time()
            );

            $rule = new RedPackageTaskRules();

            return $rule->insertOne($data);
        }
    }
}
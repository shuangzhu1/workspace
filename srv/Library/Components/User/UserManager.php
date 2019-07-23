<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 2015/3/20
 * Time: 17:46
 */

namespace Components\User;

use Models\BaseModel;
use Models\User\UserPointGrade;
use Models\User\Users;
use Phalcon\Exception;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\ResultInterface;
use Phalcon\Mvc\User\Plugin;

class UserManager extends Plugin
{
    /*
     * infinite
     */
    const POSITIVE_INFINITE = '+infi';
    const NEGATIVE_INFINITE = "-infi";

    const ERROR_GROUP_DB_SAVING_FAILED = 0;
    const ERROR_GROUP_DATA_NOT_EXISTS = -1;
    const ERROR_GROUP_HAS_RANGE_BUT_NO_GROUP = -2;
    const ERROR_GROUP_START_GE_END_OR_BOTH_EMPTY = -3;
    const ERROR_GROUP_START_LE_OLD_START = -4;
    const ERROR_GROUP_END_GE_OLD_END = -5;

    static $instacne = null;

    public static $errorMessages = array(
        self::ERROR_GROUP_DB_SAVING_FAILED => "数据保存失败！",
        self::ERROR_GROUP_DATA_NOT_EXISTS => "数据没有找到",
        self::ERROR_GROUP_HAS_RANGE_BUT_NO_GROUP => "设置确切的起始范围值的时候，必须指定等级才可以修改",
        self::ERROR_GROUP_START_GE_END_OR_BOTH_EMPTY => "经验起始值大于等于结束值或者都为空",
        self::ERROR_GROUP_START_LE_OLD_START => "添加高等级时起始经验值不得小于原有数据最高等级的起始值",
        self::ERROR_GROUP_END_GE_OLD_END => "添加低等级时结束经验值不得大于原有数据最低等级的结束值"
    );

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$instacne instanceof UserManager) {
            self::$instacne = new self();
        }
        return self::$instacne;
    }

    /**
     * 获取所有分组列表
     * @return array
     */
    public function getGroups()
    {
        $gs = UserPointGrade::findList(array('', "order" => "grade asc"));
        return $gs;
    }

    public function getGroup($gid)
    {
        if ($gid) {
            $g = UserPointGrade::findOne("id='" . $gid . "'");
            return $g;
        } else {
            return false;
        }
    }

    /**获取用户所处等级及总共等级数
     * @param $user_grad
     * @return array
     */
    public function getUserLevel($user_grad)
    {
        $total = $this->getGroups();
        $res = array('total' => count($total), 'my' => 1, 'next' => array(), 'pre' => array());
        foreach ($total as $key => $item) {
            if ($user_grad == $item['id']) {
                $res['pre'] = $total[$key];
                $res['my'] = $key + 1;
            }
        }
        if ($res['total'] > $res['my']) {
            $res['next'] = $total[$res['my']];
        } else if (($res['total'] == $res['my'])) {
            $res['next'] = $total[$res['my'] - 1];

        }
        return $res;
    }

    /**获取用户所处分组(group)
     * @param $user_point
     * @return int|Model\Resultset
     */
    public function getUserGroup($user_point)
    {
        $where = " ( CONVERT(amount_start,SIGNED)<=" . $user_point . " and CONVERT(amount_end,SIGNED)>=" . $user_point . " and  amount_start <> '' and amount_end <> '') ";
        $where .= " OR ( CONVERT(amount_start,SIGNED)<=" . $user_point . " and amount_end = '') ";
        $where .= " OR ( CONVERT(amount_end,SIGNED)>=" . $user_point . " and amount_start = '' )";
        $res = $this->original_mysql->query("select id,grade from user_point_grade where " . $where)->fetch(\PDO::FETCH_ASSOC);
        return $res ? intval($res['grade']) : 0;
    }

    /**获取所有group等级及及对应的徽章
     * @return array
     */
    public function getGroupBadge()
    {
        $groups = $this->getGroups();
        $badge = array();
        if ($groups) {
            $badge['all'] = array();
            foreach ($groups as $g) {
                $badge['all'][] = '';//$g['badge'];
                $badge['detail'][$g['id']] = '';//$g['badge'];
            }
        }
        return $badge;
    }


    public static function getErrorMessage($errorCode)
    {
        if (array_key_exists($errorCode, self::$errorMessages)) {
            return self::$errorMessages[$errorCode];
        }
        return "未知的错误";
    }

    /**
     * 添加单个等级
     * @param $data
     * @return int|Model\Resultset
     */
    public function addGroup($data)
    {
        $expStart = $data['amount_start'];
        $expEnd = $data['amount_end'];
        if (empty($expEnd) && empty($expStart) && $expStart >= $expEnd) {
            return self::ERROR_GROUP_START_GE_END_OR_BOTH_EMPTY;
        }
        if (is_numeric($expEnd) && is_numeric($expStart)) {
            if (array_key_exists('grade', $data)) {
                $g = UserPointGrade::findOne("grade = '{$data['grade']}'");
                if (!$g instanceof Model) {
                    return self::ERROR_GROUP_DATA_NOT_EXISTS;
                }
                $lg_grade = $data['grade'] + 1;
                $lg = UserPointGrade::findOne("grade = '{$lg_grade}'");

                $le_grade = $data['grade'] - 1;
                $le = UserPointGrade::findOne("grade = '{$le_grade}'");

                if ($expEnd >= $lg['amount_end']) {
                    return self::ERROR_GROUP_END_GE_OLD_END;
                }

                if ($expStart <= $lg['amount_start']) {
                    return self::ERROR_GROUP_START_LE_OLD_START;
                }

                $this->db->begin();
                try {
                    if (!UserPointGrade::updateOne($data, ['grade' => $data['grade']])) {
                        throw new Exception("save group data failed: " . json_encode($data));
                    }

                    if (!UserPointGrade::updateOne(array(
                        'amount_start' => $expEnd - 1
                    ), ['grade' => $lg_grade])
                    ) {
                        throw new Exception("update larger group data failed: " . json_encode($lg));
                    }

                    if (!UserPointGrade::updateOne(array(
                        'amount_end' => $data['amount_start'] - 1
                    ), ['grade' => $le_grade])
                    ) {
                        throw new Exception("update less group data failed: " . json_encode($le));
                    }

                    $this->db->commit();
                    return $g->id;
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                    $this->db->rollback();
                    return self::ERROR_GROUP_DB_SAVING_FAILED;
                }
            } else {
                return self::ERROR_GROUP_HAS_RANGE_BUT_NO_GROUP;
            }
        } else {
            //添加低级别的分组
            if (empty($expStart) && !empty($expEnd)) {
                $this->db->begin();
                try {
                    $allGroup = UserPointGrade::findList(array('order' => "grade DESC"));
                    if ($allGroup) {
                        foreach ($allGroup as $g) {
                            if ($g['grade'] == 1) {
                                if ($g['amount_end'] <= $expEnd) {
                                    throw new Exception("添加低等级时，结束经验值不得高于原始的最低等级的结束值");
                                }
                                if (!UserPointGrade::updateOne(array(
                                    'grade' => 2,
                                    'amount_start' => $expEnd + 1
                                ), ['id' => $g['id']])
                                ) {
                                    throw new Exception("更新原始低等级数据失败！");
                                }
                            } else {
                                if (!UserPointGrade::updateOne(array(
                                    'grade' => $g['grade'] + 1
                                ), ['id' => $g['id']])
                                ) {
                                    throw new Exception("更新旧等级失败");
                                }
                            }
                        }
                    }
                    $data['grade'] = 1;
                    $ng = new UserPointGrade();
                    if (!$id = $ng->insertOne($data)) {
                        throw new Exception("保存新等级数据失败");
                    }
                    $this->db->commit();
                    return $id;
                } catch (Exception $e) {
                    $this->db->rollback();
                    return self::ERROR_GROUP_DB_SAVING_FAILED;
                }

            } //添加高级别的分组
            else if (empty($expEnd) && !empty($expStart)) {
                $this->db->begin();
                try {
                    $highestGrade = UserPointGrade::findOne(array("order" => "grade desc"));
                    $data['grade'] = 1;
                    if ($highestGrade) {
                        $grade = $highestGrade['grade'] + 1;
                        if (!UserPointGrade::updateOne(array(
                            'amount_end' => $data['amount_start'] - 1
                        ), ['id' => $highestGrade['id']])
                        ) {
                            throw new Exception("更新旧等级数据失败！");
                        }
                        $data['grade'] = $grade;
                    }
                    $ng = new UserPointGrade();
                    if (!$id = $ng->insertOne($data)) {
                        throw new Exception("保存新等级数据失败！");
                    }
                    $this->db->commit();
                    return $id;
                } catch (Exception $e) {
                    $this->db->rollback();
                    return self::ERROR_GROUP_DB_SAVING_FAILED;
                }
            } //数据为空
            else {
                return self::ERROR_GROUP_START_GE_END_OR_BOTH_EMPTY;
            }
        }
    }

    /**
     * 删除等级
     * @param $grade 等级值，不是ID
     * @return bool
     */
    public function delGroup($grade)
    {
        if (!$grade) {
            return self::ERROR_GROUP_DATA_NOT_EXISTS;
        }

        $grade = UserPointGrade::findOne("grade = '{$grade}'");
        if (!$grade) {
            return self::ERROR_GROUP_DATA_NOT_EXISTS;
        }

        /*if($grade->grade > 1) {
            $lessGrade = $grade->grade - 1;
            $lg = UserPointGrade::findFirst("grade = '{$lessGrade}'");
        }*/

        $this->db->begin();
        try {
            $greaterGrade = $grade['grade'] + 1;
            $gg = UserPointGrade::findOne("grade = '{$greaterGrade}'");

            if ($gg) {
                if (!UserPointGrade::updateOne(array(
                    'amount_start' => $grade['amount_start']
                ), ['id' => $gg['id']])
                ) {
                    throw new Exception("更新高等级失败！");
                }
            }

            if (!$grade->delete()) {
                throw new Exception("删除等级失败");
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return self::ERROR_GROUP_DB_SAVING_FAILED;
        }
    }

    /**
     * 获得等级徽章
     * @param $gid
     * @return string
     */
    public function getBadge($gid)
    {
        $group = $this->getGroup($gid);
        if ($group) {
            return $group['badge'];
        } else {
            return "";
        }
    }

    /**
     * 通过经验值获取对应等级信息
     * @param $exp
     */
    public function checkGradeByExp($exp)
    {
        $groups = $this->getGroups();
        foreach ($groups as $group) {
            if ((is_numeric($group['amount_start']) && intval($group['amount_start']) > $exp && is_numeric($group['amount_end']) && intval($group['amount_end']) > $exp)
                || (!is_numeric($group['amount_start']) && intval($group['amount_end']) > $exp)
                || (!is_numeric($group['amount_end']) && intval($group['amount_start'] > $exp))
            ) {
                return $group;
            }
        }
    }

    /**更新等级
     * @param $user_id
     * @return int|Model\Resultset
     */
    public function updateGroup($user_id)
    {
        $user = Users::findOne(["id='" . $user_id . "'", 'columns' => 'points']);
        $group_id = UserManager::getInstance()->getUserGroup($user['points']);
        Users::updateOne(array('grade' => $group_id), ['id' => $user_id]);
        return $group_id;
    }

}
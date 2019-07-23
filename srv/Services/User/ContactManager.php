<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/7
 * Time: 18:11
 */

namespace Services\User;

use Components\Rules\Point\PointRule;
use Models\BaseModel;
use Models\Social\SocialDiscuss;
use Models\User\UserContactHistory;
use Models\User\UserCountStat;
use Models\User\UserPersonalSetting;
use Services\Discuss\DiscussManager;
use Services\Im\SysMessage;
use Services\Site\CacheSetting;
use Services\User\UserStatus;
use Components\Yunxin\ServerAPI;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPhoneContact;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\Social\SocialManager;
use Util\Ajax;
use Util\Debug;
use Util\Exception;
use Util\FilterUtil;
use Util\Validator;

class ContactManager extends Plugin
{
    const ATTENTION_SOURCE_NORMAL = 1;//普通关注
    const ATTENTION_SOURCE_CODE = 2;//扫码关注

    private static $instance = null;

    public $ajax = null;

    /** $is_cli 是否php  cli模式
     * @param bool $is_cli
     * @return null|ContactManager
     */
    public static function init($is_cli = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    public function __construct($is_cli = false)
    {
        if (!$is_cli) {
            $this->ajax = new Ajax();
        }
    }

    /**关注
     * @param $uid -关注人uid
     * @param $to_uid -被关注人uid
     * @param $source -关注来源 1-普通 2-扫码
     * @return bool
     */
    public function attention($uid, $to_uid, $source = 1)
    {

        try {
            $this->db->begin();
            $this->original_mysql->begin();

            $user = Users::getByColumnKeyList(['id in(' . $uid . ',' . $to_uid . ')', 'columns' => 'username,id'], 'id');


            $is_friend = false;//是否成为好友
            //已经被对方关注了,再关注对方就成了好友
            if ($attention = UserAttention::findOne(['owner_id=' . $to_uid . ' and user_id=' . $uid, 'columns' => 'id'])) {
                /*---step1 添加两条联系人信息---*/


                // uid 联系人 to_uid
                $person_setting1 = UserPersonalSetting::findOne(["owner_id=" . $uid . ' and user_id=' . $to_uid, 'columns' => 'mark']);
                $contact_member = new UserContactMember();
                $contact_member_data1 = ['owner_id' => $uid, 'user_id' => $to_uid, 'default_mark' => $user[$to_uid]['username'], 'mark' => $person_setting1 ? $person_setting1['mark'] : '', 'created' => time()];
                if (!$contact_member->insertOne($contact_member_data1)) {
                    $message = [];
                    foreach ($contact_member->getMessages() as $msg) {
                        $message[] = $msg;
                    }
                    throw new \Exception('1:' . var_export($message, true));
                }
                // to_uid 联系人 uid
                $person_setting2 = UserPersonalSetting::findOne(["owner_id=" . $to_uid . ' and user_id=' . $uid, 'columns' => 'mark']);
                $contact_member2 = new UserContactMember();
                $contact_member_data2 = ['owner_id' => $to_uid, 'user_id' => $uid, 'default_mark' => $user[$uid]['username'], 'mark' => $person_setting2 ? $person_setting2['mark'] : '', 'created' => time()];
                if (!$contact_member2->insertOne($contact_member_data2)) {
                    $message = [];
                    foreach ($contact_member2->getMessages() as $msg) {
                        $message[] = $msg;
                    }
                    throw new \Exception('2:' . var_export($message, true));
                }
                /*---step2 删除双方的关注记录---*/
                if (!$this->db->query("update  user_attention set enable=0 where owner_id=" . $to_uid . " and user_id=" . $uid)) {
                    throw new \Exception("删除关注信息失败");
                }
                //取消 云信假拉黑的用户 云信拉黑是单向的 所有要提交两条数据
                //  $untrue_blacklist = UserUntrueBlacklist::findOne("(owner_id=" . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')');

                //  $res = ServerAPI::init()->specializeFriend($uid, $to_uid, 1, 0);
                //    Debug::log("云信假拉黑用户:" . var_export($res, true));

                //  $res = ServerAPI::init()->specializeFriend($to_uid, $uid, 1, 0);
                //   Debug::log("云信假拉黑用户:" . var_export($res, true));

                /*    if ($untrue_blacklist) {
                        $this->db->query("delete from user_untrue_blacklist where " . " (owner_id=" . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')');
                    }*/
                /*---step3 云信接口调用*/
                $yx = ServerAPI::init()->addFriend($uid, $to_uid);
                /*  if (!($yx && $yx['code'] == 200)) {
                      throw  new \Exception("云信-添加好友失败" . ($yx ? $yx['desc'] : ''));
                  }*/
                $yx = ServerAPI::init()->addFriend($to_uid, $uid);
                /* if (!($yx && $yx['code'] == 200)) {
                     throw  new \Exception("云信-添加好友失败" . ($yx ? $yx['desc'] : ''));
                 }*/
                //云信备注更新 -解决删除好友云信还会保留之前的备注
                if ($person_setting1 && $person_setting1['mark']) {
                    ServerAPI::init()->updateFriend($uid, $to_uid, $person_setting1['mark']);
                }
                if ($person_setting2 && $person_setting2['mark']) {
                    ServerAPI::init()->updateFriend($to_uid, $uid, $person_setting2['mark']);
                }
                // ServerAPI::init()->updateFriend($uid, $to_uid, $contact_member_data1['mark']);
                //  ServerAPI::init()->updateFriend($to_uid, $uid, $contact_member_data2['mark']);


                $user_attention = UserAttention::init();
                if (!$user_attention->updateOne(['enable' => 0], 'id=' . $attention['id'])) {
                    $message = [];
                    foreach ($user_attention->getMessages() as $msg) {
                        $message[] = $msg;
                    }
                    throw new \Exception('3:' . var_export($message, true));
                }

                $attention = new UserAttention();
                $data = ['owner_id' => $uid, 'user_id' => $to_uid, 'created' => time(), 'enable' => 0, 'source_from' => $source];
                if (!$attention->insertOne($data)) {
                    $message = [];
                    foreach ($attention->getMessages() as $msg) {
                        $message[] = $msg;
                    }
                    throw new \Exception('4:' . var_export($message, true));
                }

                //历史记录  --送经验值时用到
                $history = UserContactHistory::getColumn(["owner_id=" . $uid . ' or owner_id=' . $to_uid, 'columns' => 'user_ids,owner_id'], 'user_ids', 'owner_id');
                if ($history) {
                    if (isset($history[$uid])) {
                        if (stripos($history[$uid] . ',', $to_uid . ',') == false) {
                            /*                    echo "update user_contact_history set user_ids=contact(user_ids,',',$to_uid) where owner_id=" . $uid;
                                                $this->db->rollback();exit;*/
                            $this->original_mysql->execute("update user_contact_history set user_ids=concat(user_ids,',',$to_uid) where owner_id=" . $uid);
                            PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_ADD_CONTACT);
                        }
                    } else {
                        $this->original_mysql->execute("insert into  user_contact_history(owner_id,user_ids) values ($uid,$to_uid)");
                        PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_ADD_CONTACT);

                    }
                    if (isset($history[$to_uid])) {
                        if (stripos($history[$to_uid] . ',', $uid . ',') == false) {
                            $this->original_mysql->execute("update user_contact_history set user_ids=concat(user_ids,',',$uid) where owner_id=" . $to_uid);
                            PointRule::init()->executeRule($to_uid, PointRule::BEHAVIOR_ADD_CONTACT);
                        }
                    } else {
                        $this->original_mysql->execute("insert into  user_contact_history(owner_id,user_ids) values ($to_uid,$uid)");
                        PointRule::init()->executeRule($to_uid, PointRule::BEHAVIOR_ADD_CONTACT);
                    }
                } else {
                    $this->original_mysql->execute("insert into  user_contact_history(owner_id,user_ids) values($uid,'$to_uid'),($to_uid,'$uid')");
                    PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_ADD_CONTACT);
                    PointRule::init()->executeRule($to_uid, PointRule::BEHAVIOR_ADD_CONTACT);
                }
                $is_friend = true;
            } //没有被对方关注
            else {
                $attention = new UserAttention();
                $data = ['owner_id' => $uid, 'user_id' => $to_uid, 'created' => time(), 'source_from' => $source];
                if (!$attention->insertOne($data)) {
                    $message = [];
                    foreach ($attention->getMessages() as $msg) {
                        $message[] = $msg;
                    }
                    throw new \Exception('5:' . var_export($message, true));
                }
            }
            //领红包关注

            ImManager::init()->initMsg(ImManager::TYPE_ATTENTION, ['user_id' => $uid, 'to_user_id' => $to_uid], ['push' => false]);

            /*   if ($source == 3) {
                   ImManager::init()->initMsg(ImManager::TYPE_ATTENTION, ['user_id' => $uid, 'to_user_id' => $to_uid], ['push' => false]);
               } else {
                   ImManager::init()->initMsg(ImManager::TYPE_ATTENTION, ['user_id' => $uid, 'to_user_id' => $to_uid]);
               }*/
            //更新关注数和粉丝数
            UserCountStat::updateOne('fans_cnt=fans_cnt+1', 'user_id=' . $to_uid);
            UserCountStat::updateOne('attention_cnt=attention_cnt+1', 'user_id=' . $uid);

            $this->db->commit();
            $this->original_mysql->commit();

            $this->updateAttentionUid($uid, $to_uid, 1, true);
            $this->updateAttentionUid($to_uid, $uid, 1, false);
            //发送im系统消息
            if ($is_friend) {
                SysMessage::init()->initMsg(SysMessage::TYPE_IN_FRIEND, ["to_user_id" => $uid, "user_id" => $to_uid]);
                SysMessage::init()->initMsg(SysMessage::TYPE_IN_FRIEND, ["user_id" => $uid, "to_user_id" => $to_uid]);
            }


        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();

            Debug::log($e->getMessage(), 'debug');
            return false;
        }
        return true;
    }


    /**批量关注
     * @param $uid -关注人uid
     * @param $to_uid -被关注人uid 多个以,分割
     * @return bool
     */
    public function attentionBatch($uid, $to_uid)
    {
        $to_uid_arr = array_unique(array_filter(explode(',', $to_uid)));
        if (!$to_uid_arr) {
            return true;
        }
        $to_uid = implode(',', $to_uid_arr);
        //已经关注过的人
        $has_attention = UserAttention::getColumn(['owner_id=' . $uid . ' and user_id in(' . $to_uid . ')', 'columns' => 'user_id'], 'user_id');
        if ($has_attention) {
            $to_uid_arr = array_diff($to_uid_arr, $has_attention);
            $to_uid = $to_uid_arr ? implode(',', $to_uid_arr) : '';
        }
        if (!$to_uid) {
            return true;
        }
        //已经存在自己黑名单中
        $has_blacklist = UserBlacklist::getColumn(['owner_id=' . $uid . ' and user_id in(' . $to_uid . ')', 'columns' => 'user_id'], 'user_id');
        if ($has_blacklist) {
            $to_uid_arr = array_diff($to_uid_arr, $has_blacklist);
            $to_uid = $to_uid_arr ? implode(',', $to_uid_arr) : '';
        }
        if (!$to_uid) {
            return true;
        }
        //已经存在对方的黑名单中
        $has_been_blacklist = UserBlacklist::getColumn(['owner_id in(' . $to_uid . ') and user_id=' . $uid, 'columns' => 'id'], 'owner_id');
        if ($has_been_blacklist) {
            $to_uid_arr = array_diff($to_uid_arr, $has_been_blacklist);
            $to_uid = $to_uid_arr ? implode(',', $to_uid_arr) : '';
        }
        if (!$to_uid) {
            return true;
        }
        try {
            // $this->db->begin();
            $user = Users::getByColumnKeyList(['id in(' . $uid . ',' . $to_uid . ')', 'columns' => 'username,id'], 'id');
            foreach ($to_uid_arr as $item) {
                $this->db->begin();
                $this->original_mysql->begin();

                $is_friend = false; //是否成为好友
                //已经被对方关注了,再关注对方就成了好友
                if ($attention = UserAttention::findOne(['owner_id=' . $item . ' and user_id=' . $uid, 'columns' => 'id'])) {

                    $is_friend = true;
                    /*---step1 添加两条联系人信息---*/

                    // uid 联系人 to_uid
                    $person_setting1 = UserPersonalSetting::findOne(["owner_id=" . $uid . ' and user_id=' . $item, 'columns' => 'mark']);
                    $contact_member = new UserContactMember();
                    $contact_member_data1 = ['owner_id' => $uid, 'user_id' => $item, 'default_mark' => $user[$item]['username'], 'mark' => $person_setting1 ? $person_setting1['mark'] : '', 'created' => time()];
                    if (!$contact_member->insertOne($contact_member_data1)) {

                        $message = [];
                        foreach ($contact_member->getMessages() as $msg) {
                            $message[] = $msg;
                        }
                        throw new \Exception('1:' . var_export($message, true));
                    }
                    // to_uid 联系人 uid
                    $person_setting2 = UserPersonalSetting::findOne(["owner_id=" . $item . ' and user_id=' . $uid, 'columns' => 'mark']);
                    $contact_member = new UserContactMember();
                    $contact_member_data2 = ['owner_id' => $item, 'user_id' => $uid, 'default_mark' => $user[$uid]['username'], 'mark' => $person_setting2 ? $person_setting2['mark'] : '', 'created' => time()];
                    if (!$contact_member->insertOne($contact_member_data2)) {

                        $message = [];
                        foreach ($contact_member->getMessages() as $msg) {
                            $message[] = $msg;
                        }
                        throw new \Exception('2:' . var_export($message, true));
                    }
                    /*---step2 删除双方的关注记录---*/
                    if (!$this->db->query("update  user_attention set enable=0 where owner_id=" . $item . " and user_id=" . $uid)) {
                        throw new \Exception("删除关注信息失败");
                    }
                    //取消 云信假拉黑的用户 云信拉黑是单向的 所有要提交两条数据
                    //    $untrue_blacklist = UserUntrueBlacklist::findOne("(owner_id=" . $uid . ' and user_id=' . $item . ') or (owner_id=' . $item . ' and user_id=' . $uid . ')');
                    //  $res = ServerAPI::init()->specializeFriend($uid, $item, 1, 0);
                    //  Debug::log("云信假拉黑用户:" . var_export($res, true));

                    //   $res = ServerAPI::init()->specializeFriend($item, $uid, 1, 0);
                    //   Debug::log("云信假拉黑用户:" . var_export($res, true));

                    //    if ($untrue_blacklist) {
                    //      $this->db->query("delete from user_untrue_blacklist where " . " (owner_id=" . $uid . ' and user_id=' . $item . ') or (owner_id=' . $item . ' and user_id=' . $uid . ')');
                    //    }

                    /*---step3 云信接口调用*/
                    $yx = ServerAPI::init()->addFriend($uid, $item);
                    /*  if (!($yx && $yx['code'] == 200)) {
                          throw  new \Exception("云信-添加好友失败" . ($yx ? $yx['desc'] : ''));
                      }*/
                    $yx = ServerAPI::init()->addFriend($item, $uid);
                    /* if (!($yx && $yx['code'] == 200)) {
                         throw  new \Exception("云信-添加好友失败" . ($yx ? $yx['desc'] : ''));
                     }*/
                    //云信备注更新 -解决删除好友云信还会保留之前的备注
                    if ($person_setting1 && $person_setting1['mark']) {
                        ServerAPI::init()->updateFriend($uid, $item, $person_setting1['mark']);
                    }
                    if ($person_setting2 && $person_setting2['mark']) {
                        ServerAPI::init()->updateFriend($item, $uid, $person_setting2['mark']);
                    }


                    $user_attention = UserAttention::init();
                    if (!$user_attention->updateOne(['enable' => 0], 'id=' . $attention['id'])) {

                        $message = [];
                        foreach ($user_attention->getMessages() as $msg) {
                            $message[] = $msg;
                        }
                        throw new \Exception('3:' . var_export($message, true));
                    }

                    $attention = new UserAttention();
                    $data = ['owner_id' => $uid, 'user_id' => $item, 'created' => time(), 'enable' => 0];
                    if (!$attention->insertOne($data)) {
                        $message = [];
                        foreach ($attention->getMessages() as $msg) {
                            $message[] = $msg;
                        }
                        throw new \Exception('4:' . var_export($message, true));
                    }

                } //没有被对方关注
                else {
                    $attention = new UserAttention();
                    $data = ['owner_id' => $uid, 'user_id' => $item, 'created' => time()];

                    if (!$attention->insertOne($data)) {
                        $message = [];
                        foreach ($attention->getMessages() as $msg) {
                            $message[] = $msg;
                        }
                        throw new \Exception('5:' . var_export($message, true));
                    }
                }

                //发送im消息
                //   ImManager::init()->initMsg(ImManager::TYPE_ATTENTION, ['user_id' => $uid, 'to_user_id' => $item]);
                ImManager::init()->initMsg(ImManager::TYPE_ATTENTION, ['user_id' => $uid, 'to_user_id' => $item], ['push' => false]);


                //更新关注数和粉丝数
                UserCountStat::updateOne('fans_cnt=fans_cnt+1', 'user_id=' . $item);
                UserCountStat::updateOne('attention_cnt=attention_cnt+1', 'user_id=' . $uid);
                $this->db->commit();
                $this->original_mysql->commit();

                $this->updateAttentionUid($uid, $item, 1);
                $this->updateAttentionUid($item, $uid, 1, false);
                if ($is_friend) {
                    //发送im系统消息
                    SysMessage::init()->initMsg(SysMessage::TYPE_IN_FRIEND, ["to_user_id" => $uid, "user_id" => $item]);
                    SysMessage::init()->initMsg(SysMessage::TYPE_IN_FRIEND, ["user_id" => $uid, "to_user_id" => $item]);
                }


            }
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();

            Debug::log($e->getMessage(), 'debug');
            return false;
        }
        return true;
    }

    /**取消关注
     * @param $uid -取消人uid
     * @param $to_uid -被取消人uid
     * @return bool
     */
    public function unAttention($uid, $to_uid)
    {
        //没有关注过 直接返回正确
        if (!UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
            return true;
        }
        try {
            $this->db->begin();
            $this->original_mysql->begin();
            $is_friend = false;//是否是好友
            //已经是好友关系,删除好友关系

            if (UserContactMember::findOne(['(owner_id=' . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')', 'columns' => 'id'])) {
                $res = $this->db->query("delete from user_contact_member where (owner_id=" . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')')->execute();
                if (!$res) {
                    throw new \Exception("删除好友失败:UserContactMember数据删除失败");
                }
                //调用云信删除好友接口
                $yx = ServerAPI::init()->deleteFriend($uid, $to_uid);
                /*  if (!($yx && $yx['code'] == 200)) {
                      throw  new \Exception("云信-删除好友失败" . ($yx ? $yx['desc'] : ''));
                  }*/
                $yx = ServerAPI::init()->deleteFriend($to_uid, $uid);
                /*  if (!($yx && $yx['code'] == 200)) {
                      throw  new \Exception("云信-删除好友失败" . ($yx ? $yx['desc'] : ''));
                  }*/
                $res = $this->db->query("update user_attention set enable=1 where owner_id= " . $to_uid . ' and user_id=' . $uid);
                if (!$res) {
                    throw new \Exception("更新关注信息:enable更新");
                }
                //云信假拉黑用户  云信拉黑是单向的 所有要提交两条数据
                /*  $untrue_blacklist = UserUntrueBlacklist::findOne(["(owner_id=" . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')', 'columns' => 1]);
                  if (!$untrue_blacklist) {
                      $res = ServerAPI::init()->specializeFriend($uid, $to_uid, 1, 1);
                      Debug::log("云信假拉黑用户:" . var_export($res, true));

                      $res = ServerAPI::init()->specializeFriend($to_uid, $uid, 1, 1);
                      Debug::log("云信假拉黑用户:" . var_export($res, true));

                      $untrue_blacklist = new UserUntrueBlacklist();
                      $untrue_blacklist->insertOne(['owner_id' => $uid, 'user_id' => $to_uid]);
                  }*/
                $is_friend = true;
            }
            //删除关注表数据
            $res = $this->db->query("delete from  user_attention where owner_id=" . $uid . ' and user_id=' . $to_uid);
            if (!$res) {
                throw new \Exception("删除关注信息:user_attention数据删除");
            }

            //更新关注数和粉丝数
            UserCountStat::updateOne('fans_cnt=fans_cnt-1', 'user_id=' . $to_uid);
            UserCountStat::updateOne('attention_cnt=attention_cnt-1', 'user_id=' . $uid);

            $this->db->commit();
            $this->original_mysql->commit();

            $this->updateAttentionUid($uid, $to_uid, 0);
            $this->updateAttentionUid($to_uid, $uid, 0, false);
            //发送im系统消息
            if ($is_friend) {
                SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["to_user_id" => $uid, "user_id" => $to_uid]);
                SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["user_id" => $uid, "to_user_id" => $to_uid]);
            }


            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();

            Debug::log("attention:" . $e->getMessage(), 'debug');
            return false;
        }

    }

    /**解除好友关系
     * @param $uid
     * @param $to_uid
     * @return bool
     */
    public function delFriend($uid, $to_uid)
    {
        //不是好友 直接返回正确
        if (!UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id'])) {
            return true;
        }
        try {
            $this->db->begin();
            $this->original_mysql->begin();

            //已经是好友关系,删除好友关系
            $res = $this->db->query("delete from user_contact_member where (owner_id=" . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')')->execute();
            if (!$res) {
                throw new \Exception("删除好友失败:UserContactMember数据删除失败");
            }
            /*  $res = $this->db->query("update user_attention set enable=1 where owner_id= " . $to_uid . ' and user_id=' . $uid);
              if (!$res) {
                  throw new \Exception("更新关注信息:enable更新");
              }*/
            //删除关注表数据
            $res = $this->db->query("delete from  user_attention where (owner_id=" . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')');
            if (!$res) {
                throw new \Exception("删除关注信息:user_attention数据删除");
            }
            //云信删除好友
            ServerAPI::init()->deleteFriend($uid, $to_uid);
            ServerAPI::init()->deleteFriend($to_uid, $uid);

            //云信假拉黑用户
            /* $untrue_blacklist = UserUntrueBlacklist::findOne(["(owner_id=" . $uid . ' and user_id=' . $to_uid . ') or (owner_id=' . $to_uid . ' and user_id=' . $uid . ')', 'columns' => 1]);
             if (!$untrue_blacklist) {
                 $res = ServerAPI::init()->specializeFriend($uid, $to_uid, 1, 1);
                 Debug::log("云信假拉黑用户:" . var_export($res, true));
                 $res = ServerAPI::init()->specializeFriend($to_uid, $uid, 1, 1);
                 Debug::log("云信假拉黑用户:" . var_export($res, true));
                 $untrue_blacklist = new UserUntrueBlacklist();
                 $untrue_blacklist->insertOne(['owner_id' => $uid, 'user_id' => $to_uid]);
             }*/

            //更新关注数和粉丝数
            UserCountStat::updateOne('fans_cnt=fans_cnt-1', 'user_id=' . $to_uid . " or user_id=" . $uid);
            UserCountStat::updateOne('attention_cnt=attention_cnt-1', 'user_id=' . $to_uid . " or user_id=" . $uid);
            $this->db->commit();
            $this->original_mysql->commit();

            $this->updateAttentionUid($uid, $to_uid, 0);
            $this->updateAttentionUid($to_uid, $uid, 0);
            $this->updateAttentionUid($to_uid, $uid, 0, false);
            $this->updateAttentionUid($uid, $to_uid, 0, false);
            //发送im系统消息
            SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["to_user_id" => $uid, "user_id" => $to_uid]);
            SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["user_id" => $uid, "to_user_id" => $to_uid]);


            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();

            Debug::log($e->getMessage(), 'debug');
            return false;
        }
    }

    /**移除粉丝
     * @param $uid
     * @param $to_uid
     * @return bool
     */
    public function delFans($uid, $to_uid)
    {
        //没有粉丝 返回失败
        $fans = UserAttention::getByColumnKeyList(['owner_id in (' . $to_uid . ') and user_id=' . $uid, 'columns' => 'owner_id,enable'], 'owner_id');
        if (!$fans) {
            return false;
        }
        try {
            foreach ($fans as $f) {
                //是好友
                if ($f['enable'] == 0) {
                    //删除好友关系
                    if (!$this->db->query("delete from user_contact_member where (owner_id=" . $uid . ' and user_id=' . $f['owner_id'] . ') or (owner_id=' . $f['owner_id'] . ' and user_id=' . $uid . ')')) {
                        throw new \Exception("删除好友信息:user_contact_member失败");
                    }
                    //删除关注信息
                    if (!$this->db->query("delete from  user_attention where (owner_id=" . $uid . ' and user_id=' . $f['owner_id'] . ') or (owner_id=' . $f['owner_id'] . ' and user_id=' . $uid . ')')) {
                        throw new \Exception("删除关注信息:user_attention数据删除");
                    }
                    //云信删除好友
                    ServerAPI::init()->deleteFriend($uid, $f['owner_id']);
                    ServerAPI::init()->deleteFriend($f['owner_id'], $uid);

                    //云信假拉黑用户
                    /* $untrue_blacklist = UserUntrueBlacklist::exist("(owner_id=" . $uid . ' and user_id=' . $f['owner_id'] . ') or (owner_id=' . $f['owner_id'] . ' and user_id=' . $uid . ')');
                     if (!$untrue_blacklist) {
                         $res = ServerAPI::init()->specializeFriend($uid, $f['owner_id'], 1, 1);
                         Debug::log("云信假拉黑用户:" . var_export($res, true));
                         $res = ServerAPI::init()->specializeFriend($f['owner_id'], $uid, 1, 1);
                         Debug::log("云信假拉黑用户:" . var_export($res, true));

                         $untrue_blacklist = new UserUntrueBlacklist();
                         $untrue_blacklist->insertOne(['owner_id' => $uid, 'user_id' => $f['owner_id']]);

                     }*/
                    //发送im系统消息
                    SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["to_user_id" => $uid, "user_id" => $f['owner_id']]);
                    SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["user_id" => $uid, "to_user_id" => $f['owner_id']]);

                    $this->updateAttentionUid($uid, $to_uid, 0);
                    $this->updateAttentionUid($to_uid, $uid, 0);
                    $this->updateAttentionUid($to_uid, $uid, 0, false);
                    $this->updateAttentionUid($uid, $to_uid, 0, false);

                    //更新关注数和粉丝数
                    UserCountStat::updateOne('fans_cnt=fans_cnt-1', 'user_id=' . $to_uid . " or user_id=" . $uid);
                    UserCountStat::updateOne('attention_cnt=attention_cnt-1', 'user_id=' . $to_uid . " or user_id=" . $uid);


                } //非好友
                else {
                    if (!$this->db->query("delete from  user_attention where owner_id =" . $f['owner_id'] . " and user_id=" . $uid)) {
                        throw new \Exception("删除关注信息:user_attention数据删除");
                    }
                    $this->updateAttentionUid($to_uid, $uid, 0);
                    $this->updateAttentionUid($uid, $to_uid, 0, false);

                    //更新关注数和粉丝数
                    UserCountStat::updateOne('fans_cnt=fans_cnt-1', 'user_id=' . $uid);
                    UserCountStat::updateOne('attention_cnt=attention_cnt-1', 'user_id=' . $to_uid);
                }
            }


            return true;
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'debug');
            return false;
        }
    }

    /**我的关注列表
     * @param $uid
     * @param $key
     * @param $to_uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function followers($uid,$key, $to_uid, $page = 0, $limit = 20)
    {
        $res = [/*'data_count' => 0, */
            'data_list' => []];
        //搜索key输入用户id
        $params = ['owner_id=' . $to_uid /*. ' and enable=1'*/, 'columns' => 'user_id,enable,created', 'order' => 'created desc'];
        if( !empty($key) && !is_numeric($key) )//搜索昵称
        {
            $search_users = Users::getByColumnKeyList(["username like '%" . $key . "%' and status = 1"],'id');
            $search_uids = array_keys($search_users);
            if( !empty($search_uids) )
            {
                $search_uids = implode(',',$search_uids);
                $params = ['owner_id=' . $to_uid . ' and user_id in (' . $search_uids . ')' /*. ' and enable=1'*/, 'columns' => 'user_id,enable,created', 'order' => 'created desc'];
                if ($page > 0) {
                    $params['limit'] = $limit;
                    $params['offset'] = ($page - 1) * $limit;
                }
            }
            else
            {
                $params = ['owner_id=' . $to_uid /*. ' and enable=1'*/, 'columns' => 'user_id,enable,created', 'order' => 'created desc'];
            }


            $user_attention = UserAttention::getByColumnKeyList($params, 'user_id');

        }elseif( !empty($key) && is_numeric($key))
        {
            $params = ['owner_id=' . $to_uid . ' and user_id = ' . $key/*. ' and enable=1'*/, 'columns' => 'user_id,enable,created', 'order' => 'created desc'];
            $user_attention = UserAttention::getByColumnKeyList($params, 'user_id');
        }else
        {
            if ($page > 0) {
                $params['limit'] = $limit;
                $params['offset'] = ($page - 1) * $limit;
            }
            /*  $res['data_count'] = UserAttention::count('owner_id=' . $uid . ' and enable=1');*/
            $user_attention = UserAttention::getByColumnKeyList($params, 'user_id');
        }
        $order_column = [];
        if ($user_attention) {
            $user_ids = array_column($user_attention, 'user_id');
            $uids = implode(',', $user_ids);

            $list = UserInfo::findList(['user_id in (' . $uids . ')', 'columns' => 'user_id as uid, status,sex,avatar,username,is_auth,true_name,auth_desc,signature,"" as newest_dynamic,grade,birthday,constellation,charm,is_vip']);
            $person_setting = UserPersonalSetting::getColumn(['owner_id=' . $uid . " and user_id in (" . $uids . ")", 'columns' => 'mark,user_id,owner_id'], 'mark', 'user_id');
            //查看自己的
            if ($uid == $to_uid) {
                foreach ($list as &$item) {
                    $item['is_contact'] = $user_attention[$item['uid']]['enable'] == 1 ? 0 : 1;
                    if ($person_setting && !empty($person_setting[$item['uid']])) {
                        $item['username'] = $person_setting[$item['uid']];
                    }
                    //星座
                    if ($item['constellation']) {
                        $item['constellation'] = UserStatus::$constellation[$item['constellation']];
                    } else {
                        $item['constellation'] = '';
                    }
                    $res['data_list'][] = $item;
                    $order_column[] = $user_attention[$item['uid']]['created'];
                }
            } //查看别人的
            else {
                foreach ($list as &$item) {
                    $item['is_contact'] = 0;
                    if ($person_setting && !empty($person_setting[$item['uid']])) {
                        $item['username'] = $person_setting[$item['uid']];
                    }
                    //星座
                    if ($item['constellation']) {
                        $item['constellation'] = UserStatus::$constellation[$item['constellation']];
                    } else {
                        $item['constellation'] = '';
                    }
                    $res['data_list'][] = $item;
                    $order_column[] = $user_attention[$item['uid']]['created'];
                }
            }
            //最新动态
            if ($res['data_list']) {
                array_multisort($order_column, SORT_DESC, $res['data_list']);
                $discuss = $this->getNewestDynamic($uid, $user_ids);
                if ($discuss) {
                    foreach ($res['data_list'] as &$item) {
                        if (isset($discuss[$item['uid']])) {
                            if ($discuss[$item['uid']]['content'] == '') {
                                $item['newest_dynamic'] = ($discuss[$item['share']]['share_original_item_id'] > 0 ? "转发" : '') . DiscussManager::$media_type[$discuss[$item['share']]['media_type']];
                            } else {
                                $item['newest_dynamic'] = FilterUtil::unPackageContentTag($discuss[$item['uid']]['content'], $uid);
                            }
                        }
                    }
                }
            }
        }
        return $res;
    }

    /**我的粉丝列表
     * @param $uid
     * @param $key
     * @param $to_uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function fans($uid, $key,$to_uid, $page = 0, $limit = 20)
    {
        $res = [/*'data_count' => 0, */
            'data_list' => []];
        //搜索key输入用户id
        $params = ['user_id=' . $to_uid /*. ' and enable=1'*/, 'columns' => 'owner_id,enable,created', 'order' => 'created desc'];
        if( !empty($key) && !is_numeric($key) )//搜索昵称
        {
            $search_users = Users::getByColumnKeyList(["username like '%" . $key . "%' and status = 1"],'id');
            $search_uids = array_keys($search_users);
            if( !empty($search_uids) )
            {
                $search_uids = implode(',',$search_uids);
                $params = ['user_id=' . $to_uid . ' and owner_id in (' . $search_uids . ')' /*. ' and enable=1'*/, 'columns' => 'owner_id,enable,created', 'order' => 'created desc'];
                if ($page > 0) {
                    $params['limit'] = $limit;
                    $params['offset'] = ($page - 1) * $limit;
                }
            }
            else
            {
                $params = ['user_id=' . $to_uid /*. ' and enable=1'*/, 'columns' => 'owner_id,enable,created', 'order' => 'created desc'];
            }

            $user_attention = UserAttention::getByColumnKeyList($params, 'owner_id');

        }elseif( !empty($key) && is_numeric($key))
        {
            $params = ['user_id=' . $to_uid . ' and owner_id = ' . $key/*. ' and enable=1'*/, 'columns' => 'owner_id,enable,created', 'order' => 'created desc'];
            $user_attention = UserAttention::getByColumnKeyList($params, 'owner_id');
        }else
        {

            if ($page > 0) {
                $params['limit'] = $limit;
                $params['offset'] = ($page - 1) * $limit;
            }
            /*  $res['data_count'] = UserAttention::count('owner_id=' . $uid . ' and enable=1');*/
            $user_attention = UserAttention::getByColumnKeyList($params, 'owner_id');
        }
        // $res['data_count'] = UserAttention::count('user_id=' . $uid . ' and enable=1');
        // $user_ids = UserAttention::getColumn($params, 'owner_id');
        //$user_attention = UserAttention::getByColumnKeyList($params, 'owner_id');
        $order_column = [];
        if ($user_attention) {
            $user_ids = array_column($user_attention, 'owner_id');
            $uids = implode(',', $user_ids);
            $list = UserInfo::findList(['user_id in (' . $uids . ')', 'columns' => 'user_id as uid,status,sex,avatar,username,true_name,is_auth,auth_desc,signature,grade,"" as newest_dynamic,birthday,constellation,charm,is_vip']);
            $person_setting = UserPersonalSetting::getColumn(['owner_id=' . $uid . " and user_id in (" . $uids . ")", 'columns' => 'mark,user_id,owner_id'], 'mark', 'user_id');

            //查看自己的
            if ($uid == $to_uid) {
                foreach ($list as &$item) {
                    $item['is_contact'] = $user_attention[$item['uid']]['enable'] == 1 ? 0 : 1;
                    if ($person_setting && !empty($person_setting[$item['uid']])) {
                        $item['username'] = $person_setting[$item['uid']];
                    }
                    //星座
                    if ($item['constellation']) {
                        $item['constellation'] = UserStatus::$constellation[$item['constellation']];
                    } else {
                        $item['constellation'] = '';
                    }
                    $res['data_list'][] = $item;
                    $order_column[] = $user_attention[$item['uid']]['created'];
                }
            } //查看别人的
            else {
                foreach ($list as &$item) {
                    $item['is_contact'] = 0;
                    if ($person_setting && !empty($person_setting[$item['uid']])) {
                        $item['username'] = $person_setting[$item['uid']];
                    }
                    //星座
                    if ($item['constellation']) {
                        $item['constellation'] = UserStatus::$constellation[$item['constellation']];
                    } else {
                        $item['constellation'] = '';
                    }
                    $res['data_list'][] = $item;
                    $order_column[] = $user_attention[$item['uid']]['created'];
                }
            }

            //最新动态
            if ($res['data_list']) {
                array_multisort($order_column, SORT_DESC, $res['data_list']);
                $discuss = $this->getNewestDynamic($uid, $user_ids);
                if ($discuss) {
                    foreach ($res['data_list'] as &$item) {
                        if (isset($discuss[$item['uid']])) {
                            if ($discuss[$item['uid']]['content'] == '') {
                                $item['newest_dynamic'] = ($discuss[$item['share']]['share_original_item_id'] > 0 ? "转发" : '') . DiscussManager::$media_type[$discuss[$item['share']]['media_type']];
                            } else {
                                $item['newest_dynamic'] = FilterUtil::unPackageContentTag($discuss[$item['uid']]['content'], $uid);
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**获取用户最新的动态
     * @param $uid
     * @param $uids
     * @return array
     */
    public function getNewestDynamic($uid, $uids)
    {
        if (!$uids) {
            return [];
        }
        $user_ids = $uids;
        $uids = implode(',', $uids);

        $res = [];
        //不允许查看动态 过滤
        $person_setting = UserPersonalSetting::getColumn(['owner_id in (' . $uids . ') and user_id=' . $uid . ' and scan_my_discuss=0', 'columns' => 'owner_id as uid'], 'uid');

        if ($person_setting) {
            $user_ids = array_diff($user_ids, $person_setting);
        }
        if ($user_ids) {
            // SocialDiscuss::find("user_id in(" . implode(',', $user_ids) . ') and status=' . DiscussManager::STATUS_NORMAL . " and ((scan_type=".DiscussManager::SCAN_TYPE_ALL.") or (scan_type=".DiscussManager::."))");
            //好友
            $friends = UserContactMember::getColumn("user_id in (" . implode(',', $user_ids) . ") and owner_id=" . $uid, 'user_id');
            //非好友
            if ($friends) {
                $not_friends = array_diff($user_ids, $friends);
                $max_ids = SocialDiscuss::getColumn(["status=" . DiscussManager::STATUS_NORMAL . " and user_id in (" . implode(',', $friends) . ') and ((scan_type=' . DiscussManager::SCAN_TYPE_ALL . ') or (scan_type=' . DiscussManager::SCAN_TYPE_FRIEND . ') or (scan_type=' . DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . DiscussManager::SCAN_TYPE_FORBIDDEN . " and  LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))", 'columns' => 'MAX(id) as mid,user_id', 'group' => 'user_id'], 'mid');
                if ($max_ids) {
                    $res = SocialDiscuss::getByColumnKeyList(["id in (" . implode(',', $max_ids) . ')', 'columns' => 'user_id,content,media_type,share_original_type,share_original_item_id'], 'user_id');
                }
            } else {
                $not_friends = $user_ids;
            }
            if ($not_friends) {
                $max_ids = SocialDiscuss::getColumn(["status=" . DiscussManager::STATUS_NORMAL . " and user_id in (" . implode(',', $not_friends) . ') and ((scan_type=' . DiscussManager::SCAN_TYPE_ALL . ") or(scan_type=" . DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))", 'columns' => 'MAX(id) as mid,user_id', 'group' => 'user_id'], 'mid');
                if ($max_ids) {
                    $not_friend_discuss = SocialDiscuss::getByColumnKeyList(["id in (" . implode(',', $max_ids) . ')', 'columns' => 'user_id,content,media_type,share_original_type,share_original_item_id'], 'user_id');
                    foreach ($not_friend_discuss as $k => $val) {
                        $res[$k] = $val;
                    }
                }
            }

        }
        return $res;
    }

    /**好友列表
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function friends($uid, $page = 0, $limit = 20)
    {
        $res = [/*'data_count' => 0, */
            'data_list' => []];
        $params = ['owner_id=' . $uid, 'columns' => 'user_id as uid,is_star,default_mark,mark,created', 'order' => 'is_star desc,created desc'];
        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }
        /*  $res['data_count'] = UserContactMember::count('owner_id=' . $uid);*/
        $user_members = UserContactMember::getByColumnKeyList($params, 'uid');
        if ($user_members) {
            $user_ids = array_keys($user_members);
            $user_info = UserInfo::findList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'user_id as uid,status,sex,avatar,is_auth,introduce,signature,grade,constellation,birthday,is_vip']);
            foreach ($user_info as $item) {
                $item['constellation'] = $item['constellation'] ? UserStatus::$constellation[$item['constellation']] : '';
                $res['data_list'][] = array_merge($user_members[$item['uid']], $item);
            }
        }
        return $res;
    }

    /**加入黑名单
     * @param $uid
     * @param $to_uid
     * @return bool
     */
    public function addBlacklist($uid, $to_uid)
    {
        //已经加过黑名单了 返回true
        if (UserBlacklist::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
            return true;
        }
        try {
            $this->db->begin();
            $black = new UserBlacklist();
            $data = ['owner_id' => $uid, 'user_id' => $to_uid, 'created' => time()];
            if (!$black->insertOne($data)) {
                $message = [];
                foreach ($black->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(var_export($message, true));
            }
            if ($attention = UserAttention::findOne(["(owner_id=$uid and user_id=$to_uid) or (user_id=$uid and owner_id=$to_uid)", 'columns' => 'owner_id,user_id,enable'])) {
                //单方关注
                if ($attention['enable'] == 1) {
                    if ($attention['owner_id'] == $uid) {

                        //更新关注 粉丝uid
                        $this->updateAttentionUid($uid, $to_uid, 0);
                        $this->updateAttentionUid($to_uid, $uid, 0, false);

                        //更新关注数和粉丝数
                        UserCountStat::updateOne('fans_cnt=fans_cnt-1', 'user_id=' . $to_uid);
                        UserCountStat::updateOne('attention_cnt=attention_cnt-1', 'user_id=' . $uid);

                    } else {
                        //更新关注 粉丝uid
                        $this->updateAttentionUid($to_uid, $uid, 0);
                        $this->updateAttentionUid($uid, $to_uid, 0, false);

                        //更新关注数和粉丝数
                        UserCountStat::updateOne('fans_cnt=fans_cnt-1', 'user_id=' . $uid);
                        UserCountStat::updateOne('attention_cnt=attention_cnt-1', 'user_id=' . $to_uid);
                    }
                } //双方关注 好友
                else {
                    $res = $this->db->execute("delete from user_contact_member where (owner_id=$uid and user_id=$to_uid) or (user_id=$uid and owner_id=$to_uid)");
                    //发送im系统消息
                    SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["to_user_id" => $uid, "user_id" => $to_uid]);
                    SysMessage::init()->initMsg(SysMessage::TYPE_OUT_FRIEND, ["user_id" => $uid, "to_user_id" => $to_uid]);

                    //更新关注数和粉丝数
                    UserCountStat::updateOne('fans_cnt=fans_cnt-1', 'user_id=' . $uid . ' or user_id=' . $to_uid);
                    UserCountStat::updateOne('attention_cnt=attention_cnt-1', 'user_id=' . $to_uid . ' or user_id=' . $uid);

                    //更新关注 粉丝uid
                    $this->updateAttentionUid($uid, $to_uid, 0);
                    $this->updateAttentionUid($to_uid, $uid, 0);
                    $this->updateAttentionUid($to_uid, $uid, 0, false);
                    $this->updateAttentionUid($uid, $to_uid, 0, false);
                }
            }

            //删除好友关系 及关注列表
            $res = $this->db->execute("delete from user_attention where (owner_id=$uid and user_id=$to_uid) or (user_id=$uid and owner_id=$to_uid)");
            if (!$res) {
                throw new \Exception("删除关注信息失败");
            }
            if (!$res) {
                throw new \Exception("删除好友信息失败");
            }
            //云信加入黑名单
            $yx = ServerAPI::init()->specializeFriend($uid, $to_uid, 1, 1);
            //  $yx = ServerAPI::init()->specializeFriend($to_uid, $uid, 1, 1);

            /*
                        if (!$yx || $yx['code'] !== 200) {
                            throw new \Exception('加入黑名单失败:云信错误' . ($yx ? $yx['desc'] : ''));
                        }*/
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log("加黑名单：" . $e->getMessage());
            return false;
        }
    }

    /**取消黑名单
     * @param $uid
     * @param $to_uid
     * @return bool
     */
    public function cancelBlacklist($uid, $to_uid)
    {
        //  Debug::log("移除黑名单:$uid:$to_uid", 'error');
        //黑名单被取消了 返回true
        if (!$black = UserBlacklist::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id'])) {
            return true;
        }
        if (UserBlacklist::remove(['id' => $black['id']])) {
            //云信取消黑名单
            $yx = ServerAPI::init()->specializeFriend($uid, $to_uid, 1, 0);
            if (!$yx || $yx['code'] != 200) {
                Debug::log('yx:' . var_export($yx, true), 'error');

                //  Debug::log("云信取消黑名单失败:" . ($yx ? $yx['desc'] : ''));
                return false;
            }
            return true;
        }
        return false;
    }

    /**黑名单列表
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function blacklist($uid, $page = 0, $limit = 20)
    {
        $res = [/*'data_count' => 0,*/
            'data_list' => []];
        $params = ['owner_id=' . $uid, 'columns' => 'user_id,created', 'order' => 'created desc'];
        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }
        /* $res['data_count'] = UserBlacklist::count('owner_id=' . $uid);*/
        $blackList = UserBlacklist::getByColumnKeyList($params, 'user_id');
        if ($blackList) {
            $user_ids = array_column($blackList, 'user_id');
            $person_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'mark,user_id'], 'user_id');
            $order_column = [];
            $res['data_list'] = UserInfo::findList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'user_id as uid,avatar,username']);
            foreach ($res['data_list'] as &$item) {
                $person_setting[$item['uid']]['mark'] != '' && $item['username'] = $person_setting[$item['uid']]['mark'];
                $order_column[] = $person_setting[$item['uid']]['created'];
            }
            array_multisort($order_column, SORT_DESC, $res['data_list']);
        }
        return $res;
    }

    /**黑名单列表
     * @param int $type
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function specialList($type, $uid, $page = 0, $limit = 20)
    {
        $params = ['owner_id=' . $uid, 'columns' => 'user_id,mark,created', 'order' => 'created desc'];
        //不看他的动态
        if ($type == 1) {
            $params[0] .= " and scan_his_discuss=0 ";
        } //不看我的动态
        else if ($type == 2) {
            $params[0] .= " and scan_my_discuss=0 ";
        }
        $res = [/*'data_count' => 0,*/
            'data_list' => []];
        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }
        /* $res['data_count'] = UserBlacklist::count('owner_id=' . $uid);*/
        $person_setting = UserPersonalSetting::getByColumnKeyList($params, 'user_id');
        if ($person_setting) {
            $user_ids = array_column($person_setting, 'user_id');
            $res['data_list'] = UserInfo::findList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'user_id as uid,avatar,username']);
            $order_column = [];
            foreach ($res['data_list'] as &$item) {
                $person_setting[$item['uid']]['mark'] != '' && $item['username'] = $person_setting[$item['uid']]['mark'];
                $order_column[] = $person_setting[$item['uid']]['created'];
            }
            array_multisort($order_column, SORT_DESC, $res['data_list']);
        }
        return $res;
    }


    /**设置为星标好友
     * @param $uid
     * @param int $to_uid
     * @return bool
     */
    public function setStar($uid, $to_uid)
    {
        $contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id']);
        if (!$contact) {
            $this->ajax->outError(Ajax::ERROR_NOT_FRIEND);
        }
        if (UserContactMember::updateOne(['is_star' => 1, 'modify' => time()], 'id=' . $contact['id'])) {
            return true;
        }
        return false;
    }

    /**取消星标好友
     * @param $uid
     * @param int $to_uid
     * @return bool
     */
    public function cancelStar($uid, $to_uid)
    {
        $contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid . ' and is_star=1', 'columns' => 'id']);
        if (!$contact) {
            return true;
        }
        if (UserContactMember::updateOne(['is_star' => 0, 'modify' => time()], 'id=' . $contact['id'])) {
            return true;
        }
        return false;

    }

    /**设置备注
     * @param $uid
     * @param int $to_uid
     * @param int $mark
     * @return bool
     */
    public function setMark($uid, $to_uid, $mark)
    {
        $personal_setting = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id']);

        //编辑个人设置-》备注
        if (!$personal_setting) {
            $personal_setting = new UserPersonalSetting();
            $data = ['owner_id' => $uid, 'user_id' => $to_uid, 'created' => time(), 'mark' => $mark];
            $res = $personal_setting->insertOne($data);
        } else {
            $data = ['modify' => time(), 'mark' => $mark];
            $res = UserPersonalSetting::updateOne($data, 'id=' . $personal_setting['id']);
        }
        if ($res) {
            //好友备注
            $contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id']);
            if ($contact) {
                //  $this->ajax->outError(Ajax::ERROR_NOT_FRIEND);
                $data = ['mark' => $mark, 'modify' => time()];
                if (UserContactMember::updateOne($data, 'id=' . $contact['id'])) {
                    //云信更新
                    $yx = ServerAPI::init()->updateFriend($uid, $to_uid, $mark/* $mark ? $mark : $contact->default_mark*/);
                    if (!$yx || $yx['code'] !== 200) {
                        Debug::log("云信取消黑名单失败:" . ($yx ? $yx['desc'] : ''), 'yunxin');
                    }
                }
            }
            return true;
        }
        return false;

    }

    /**发起添加为联系人请求
     * @param $uid
     * @param int $to_uid
     * @param string $tip
     * @return bool
     */
    public function addContact($uid, $to_uid, $tip)
    {
        $contact = UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid);
        if ($contact) {
            $this->ajax->outError(Ajax::ERROR_ALREADY_CONTACT_MEMBER);
        }
        ImManager::init()->initMsg(ImManager::TYPE_ADD_CONTACT, ['user_id' => $uid, 'to_user_id' => $to_uid, 'tip' => $tip]);
        return true;
    }

    /**添加好友
     * @param $uid
     * @param $key
     * @param $page
     * @param $limit
     * @return bool|mixed
     */
    public function searchUser($uid, $key, $page = 1, $limit = 20)
    {
        $res = ['data_list' => []];
        $where = " status=" . UserStatus::STATUS_NORMAL;
        if (strlen($key) >= 5 && preg_match('/^[1-9][\d]{4,}$/', $key)) {
            $where .= ' and  user_id=' . $key . ' or username like "%' . $key . '%" ';
        } else {
            $where .= ' and username like "%' . $key . '%"';
        }
        $list = UserInfo::findList([$where, 'columns' => 'username,user_id as uid,sex,grade,signature,avatar', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);

        if ($list) {
            $uids = array_column($list, 'uid');
            $personal_setting = UserPersonalSetting::getColumn(['owner_id=' . $uid . ' and user_id in (' . implode(',', $uids) . ') and mark <>""', 'columns' => 'user_id as uid,mark'], 'mark', 'uid');
            foreach ($list as &$item) {
                isset($personal_setting[$item['uid']]) && $item['username'] = $personal_setting[$item['uid']];
                $res["data_list"][] = $item;
            }
        }
        return $res;

    }

    /**手机联系人匹配
     * @param $uid
     * @param $phone
     * @param $device_id -手机设备号
     * @param $phone_model -手机型号
     * @return mixed|string
     */
    public function phoneUser($uid, $phone, $device_id = '', $phone_model = '')
    {
        $res = ['data_count' => 0, 'data_list' => []];

        if ($device_id) {
            // echo $phone;
            $phones = json_decode(htmlspecialchars_decode($phone), true);
            //  var_dump($phones);exit;
            if (!$phones) {
                return $res;
            }
            $phone = explode(',', $phones['phone']);
            $phone_arr = $phones['phone'];
            $name_arr = $phones['name'];

            $phone_contact = UserPhoneContact::findOne(['owner_id=' . $uid . ' and device_id="' . $device_id . '"', 'columns' => 'id']);
            if ($phone_contact) {
                $data = [];
                $data['phones'] = json_encode(['phone' => $phone_arr, 'name' => $name_arr], JSON_UNESCAPED_UNICODE);
                $data['created'] = time();
                UserPhoneContact::updateOne($data, ['id' => $phone_contact['id']]);
            } else {
                $phone_contact = new UserPhoneContact();
                $data = [
                    'owner_id' => $uid,
                    'device_id' => $device_id,
                    'phone_model' => $phone_model,
                    'phones' => json_encode(['phone' => $phone_arr, 'name' => $name_arr], JSON_UNESCAPED_UNICODE),
                    'created' => time()
                ];
                $phone_contact->insertOne($data);
            }


        } //旧的版本
        else {
            $phone = explode(',', $phone);
        }
        $phone = array_unique(array_filter($phone));
        $users = UserInfo::getByColumnKeyList(['phone in(' . implode(',', $phone) . ')', 'columns' => 'phone,user_id as uid,username,status,avatar,sex,signature'], 'phone');
        if ($users) {
            $uids = implode(',', array_column($users, 'uid'));
            $blackList = UserBlacklist::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid'], 'uid');//黑名单列表
            $contactList = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人列表
            $attentionList = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid'], 'uid');//关注列表
            foreach ($users as &$item) {
                $item['username'] = isset($contactList[$item['uid']]) && $contactList[$item['uid']]['mark'] ? $contactList[$item['uid']]['mark'] : $item['username'];
                $item['is_contact'] = isset($contactList[$item['uid']]) ? 1 : 0;
                $item['is_blacklist'] = isset($blackList[$item['uid']]) ? 1 : 0;
                $item['is_attention'] = (isset($contactList[$item['uid']]) || isset($attentionList[$item['uid']])) ? 1 : 0;
            }
        }
        $res['data_count'] = count($users);
        $res['data_list'] = array_values($users);

        return $res;
    }

    /**获取关注的用户id/粉丝用户id --返回类似5000,30000,40000
     * @param $uid
     * @param bool $attention
     * @param bool $refresh
     * @return string
     */
    public function getAttentionUid($uid, $attention = true, $refresh = false)
    {
        $redis = $this->di->get("redis");
        if ($attention) {
            $key = CacheSetting::KEY_USER_ATTENTION_UID;
        } else {
            $key = CacheSetting::KEY_USER_FOLLOWERS_UID;
        }
        $data = $redis->hGet($key, $uid);
        if (!$data || !$refresh) {
            if ($attention) {
                $uids = UserAttention::getColumn(["owner_id=" . $uid, 'columns' => 'user_id','order'=>'user_id desc'], 'user_id');
            } else {
                $uids = UserAttention::getColumn(["user_id=" . $uid, 'columns' => 'owner_id','order'=>'owner_id desc'], 'owner_id');
            }
            $data = $uids ? implode(',', $uids) : '';
            $redis->hSet($key, $uid, $data);
            return $data;
        } else {
            return $data;
        }
    }


    /** 更新关注uid/粉丝uid
     * @param $uid -拥有者
     * @param $to_uid -被操作者
     * @param bool $is_add -是否属于添加
     * @param bool $attention -是否关注
     */
    public function updateAttentionUid($uid, $to_uid, $is_add = true, $attention = true)
    {
        $redis = $this->di->get("redis");
        if ($attention) {
            $key = CacheSetting::KEY_USER_ATTENTION_UID;
        } else {
            $key = CacheSetting::KEY_USER_FOLLOWERS_UID;
        }

        $data = $redis->hGet($key, $uid);
        if ($data === false) {
            if ($attention) {
                $uids = UserAttention::getColumn(["owner_id=" . $uid, 'columns' => 'user_id'], 'user_id');
            } else {
                $uids = UserAttention::getColumn(["user_id=" . $uid, 'columns' => 'owner_id'], 'owner_id');
            }
            $data = $uids ? implode(',', $uids) : '';
            $redis->hSet($key, $uid, $data);
            return;
        }
        //增加关注
        if ($is_add) {
            //之前有数据
            if ($data) {
                //已经存在
                if (strpos("," . $data, $to_uid) >= 0) {
                    return;
                }
                $data .= ',' . $to_uid;
            } else {
                $data = $to_uid;
            }
        } //删除关注
        else {
            //之前有数据
            if ($data) {
                //不存在
                if (!(strpos("," . $data, $to_uid) >= 0)) {
                    return;
                }
                $data = str_replace("," . $to_uid, "", "," . $data);
                if (substr($data, 0, 1) == ',') {
                    $data = substr($data, 1);
                }
            } else {
                return;
            }
        }
        $redis->hSet($key, $uid, $data);
    }

    /**关注交集/我关注的人也关注了TA
     * @param $uid
     * @param $to_uid
     * @param  bool $is_attention
     * @return array
     */

    public function sameAttentionUid($uid, $to_uid, $is_attention = true)
    {
        $res = [];
        $attention1 = $this->getAttentionUid($uid);

        if ($attention1) {
            $attention2 = $this->getAttentionUid($to_uid, $is_attention);
            if ($attention2) {
                $attention1 = explode(',', $attention1);
                $attention2 = explode(',', $attention2);
                $res = array_intersect($attention1, $attention2);
            }
        }
        return $res;
    }

    /**获取简单的关注交集/我关注的人也关注了TA
     * @param $uid
     * @param $to_uid
     * @param  bool $is_attention
     * @return array
     */
    public function sameAttention($uid, $to_uid, $is_attention = true)
    {
        $res = ["list" => [], 'count' => 0];
        $uids = $this->sameAttentionUid($uid, $to_uid, $is_attention);

        if ($uids) {
            $res['count'] = count($uids);
            $uids = array_slice($uids, 0, 6);
            $info = Users::findList(["id in (" . implode(',', $uids) . ')', 'columns' => 'avatar,id as uid,username']);
            $res['list'] = $info;
        }
        return $res;
    }

    /**获取简单的关注交集
     * @param $uid
     * @param $to_uid
     * * @param $page
     * * @param $limit
     * @return array
     */
    public function sameAttentionList($uid, $to_uid, $page = 1, $limit = 20)
    {
        $res = [/*'data_count' => 0,*/
            "data_list" => []];
        $user_ids = $this->sameAttentionUid($uid, $to_uid);//共同的关注列表
        // $user_ids = $this->sameAttentionUid($uid, $to_uid,false);//我关注的人也关注了TA【微博】
        if ($user_ids) {
            //   $res['data_count'] = count($user_ids);
            $uids = array_slice($user_ids, ($page - 1) * $limit, $limit);
            if ($uids) {
                $list = UserInfo::findList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid,status,sex,avatar,username,true_name,is_auth,auth_desc,signature,grade,"" as newest_dynamic,birthday,charm,constellation']);
                foreach ($list as &$item) {
                    $item['is_contact'] = 0;
                    $res['data_list'][] = $item;
                }
                //最新动态
                if ($res['data_list']) {
                    $discuss = $this->getNewestDynamic($uid, $user_ids);
                    if ($discuss) {
                        foreach ($res['data_list'] as &$item) {
                            if ($item['constellation']) {
                                $item['constellation'] = UserStatus::$constellation[$item['constellation']];
                            }
                            if (isset($discuss[$item['uid']])) {
                                if ($discuss[$item['uid']]['content'] == '') {
                                    $item['newest_dynamic'] = ($discuss[$item['share']]['share_original_item_id'] > 0 ? "转发" : '') . DiscussManager::$media_type[$discuss[$item['share']]['media_type']];
                                } else {
                                    $item['newest_dynamic'] = FilterUtil::unPackageContentTag($discuss[$item['uid']]['content'], $uid);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $res;
    }
}
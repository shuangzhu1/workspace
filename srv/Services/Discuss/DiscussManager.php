<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/15
 * Time: 13:57
 */

namespace Services\Discuss;


use Components\Curl\CurlManager;
use Components\Passport\Identify;
use Components\Rules\Coin\PointRule;
use Components\Time;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussMedia;
use Models\Social\SocialDiscussRecommend;
use Models\Social\SocialDiscussReward;
use Models\Social\SocialDiscussTagFilter;
use Models\Social\SocialDiscussTopLog;
use Models\Social\SocialFav;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserCountStat;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\UserPointGrade;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserVideo;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\Im\SysMessage;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Site\AppVersionManager;
use Services\Site\CacheSetting;
use Services\Site\CashRewardManager;
use Services\Site\SensitiveManager;
use Services\Site\SiteKeyValManager;
use Services\Social\SocialManager;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;

class DiscussManager extends DiscussBase
{
    private static $instance = null;

    const TYPE_TEXT = 1; //纯文本
    const TYPE_VIDEO = 2; //小视频
    const TYPE_PICTURE = 3; //图片
    const TYPE_AUDIO = 4; //语音
    const TYPE_RED_PACKET = 5; //红包
    const TYPE_GOODS = 6; //商品

    //--置顶类型
    const TOP_TYPE_FREE = 3; //免费置顶
    const TOP_TYPE_GRADE = 1; //等级特权
    const TOP_TYPE_COIN = 2; //龙豆

    //--动态状态
    const STATUS_SHIELD = 0; //被屏蔽
    const STATUS_NORMAL = 1; //正常
    const STATUS_DELETED = 2; //被用户删除

    //--查看类型
    const SCAN_TYPE_ALL = 1;//公开
    const SCAN_TYPE_PRIVATE = 2;//私密
    const SCAN_TYPE_PART_FRIEND = 3;//部分好友可见
    const SCAN_TYPE_FORBIDDEN = 4;//不给谁看
    const SCAN_TYPE_FRIEND = 5;//仅好友可见


    static $type = [
        self::TYPE_TEXT, self::TYPE_VIDEO, self::TYPE_PICTURE, self::TYPE_AUDIO, self::TYPE_RED_PACKET
    ];
    static $status = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_SHIELD => '系统已屏蔽',
        self::STATUS_DELETED => '用户已删除',
    ];
    static $scan_type = [
        self::SCAN_TYPE_ALL,
        self::SCAN_TYPE_PRIVATE,
        self::SCAN_TYPE_PART_FRIEND,
        self::SCAN_TYPE_FORBIDDEN,
        self::SCAN_TYPE_FRIEND
    ];
    public static $media_type = [
        self::TYPE_TEXT => "文字",
        self::TYPE_VIDEO => "小视频",
        self::TYPE_AUDIO => "语音",
        self::TYPE_PICTURE => "图片",
        self::TYPE_RED_PACKET => "红包",
        self::TYPE_GOODS => "商品",
    ];

    /**
     * @return  DiscussManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /*后台发布*/
    /**
     * @param $uid
     * @param $media_type
     * @param $content
     * @param $media
     * @param $tags
     * @param bool $open_location
     * @param string $address
     * @param string $lng
     * @param string $lat
     * @return int
     */
    public function adminPublish($uid, $media_type, $content, $media, $tags, $open_location = false, $address = '', $lng = '', $lat = '')
    {
        $user = Users::findOne(['id=' . $uid, 'columns' => 'grade,coins,avatar']);

        $discuss_data = ['user_id' => $uid, 'media_type' => $media_type, 'content' => $content, 'media' => $media, 'tags' => $tags, 'allow_download' => 1, 'is_admin' => 1];

        //公开位置
        if ($open_location) {
            $discuss_data['address'] = $address;
            $discuss_data['lng'] = $lng;
            $discuss_data['lat'] = $lat;
        }
        if ($tags) {
            $discuss_data['tags_name'] = TagManager::getInstance()->getTagNames($tags);
        }
        $discuss_data['scan_type'] = self::SCAN_TYPE_ALL;
        $discuss_data['created'] = time();
        //@的用户
        // $at_uid = FilterUtil::packageContentTagApp($discuss_data['content'], $uid);
        $discuss_data['content'] = SensitiveManager::filterContent($discuss_data['content']);

        $discuss = new SocialDiscuss();
        if (!$discuss_id = $discuss->insertOne($discuss_data)) {
            Debug::log('publish discuss:' . json_encode($discuss->getMessages(), true), 'error');
            return false;
        }

        /*  if (isset($discuss_data['top_type'])) {
              //龙豆置顶 扣除龙豆值
              if ($discuss_data['top_type'] == self::TOP_TYPE_COIN) {
                  PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_TOP_DISCUSS);
              }
              //记录置顶记录
              $log = new SocialDiscussTopLog();
              if (!$log->insertOne(['user_id' => $uid, 'discuss_id' => $discuss_id, 'type' => $discuss_data['top_type'], 'created' => $discuss_data['created']])) ;
              {
                  Debug::log(json_encode($log->getMessages(), JSON_UNESCAPED_UNICODE), 'error');
              }
          }*/
        //送经验值
        //\Components\Rules\Point\PointRule::init()->executeRule($uid, \Components\Rules\Point\PointRule::BEHAVIOR_NEW_DISCUSS);


        /** --------------------------------给好友发消息--------------------------  **/
        //全部可见，仅好友可见
        $contacts = UserContactMember::findList(['user_id=' . $uid, 'columns' => 'owner_id,is_star']);

        if ($contacts) {
            //设为对方不让看我动态的用户过滤掉
            $not_user1 = UserPersonalSetting::getColumn(["owner_id=" . $uid . ' and user_id in (' . implode(',', array_column($contacts, 'owner_id')) . ') and scan_my_discuss=0', 'columns' => 'user_id'], 'user_id');
            //设为不看我动态的用户过滤掉
            $not_user2 = UserPersonalSetting::getColumn(["user_id=" . $uid . ' and owner_id in (' . implode(',', array_column($contacts, 'owner_id')) . ') and scan_his_discuss=0', 'columns' => 'owner_id as user_id'], 'user_id');
            $not_user = array_merge($not_user1, $not_user2);
            if ($not_user) {
                $contacts2 = [];
                foreach ($contacts as $item) {
                    if (!in_array($item['owner_id'], $not_user)) {
                        $contacts2[] = $item;
                    }
                }
                $contacts = $contacts2;
            }
            if ($contacts) {
                $not_star = [];
                foreach ($contacts as $c) {
                    if ($c['is_star'] == 1) {
                        SysMessage::init()->initMsg(SysMessage::TYPE_NEW_DISCUSS, ['user_id' => $uid, 'avatar' => $user['avatar'], 'to_user_id' => $c['owner_id'], 'need_push' => 1, 'item_id' => $discuss_id]);
                    } else {
                        $not_star[] = $c['owner_id'];
                    }
                }
                //非星标好友仅仅发 不需要通知栏的系统通知
                if ($not_star) {
                    SysMessage::init()->initMsg(SysMessage::TYPE_NEW_DISCUSS, ['to_user_id' => $not_star, 'avatar' => $user['avatar']]);
                }
            }
        }

        //更新最新动态封面图
        if ($media_type == DiscussManager::TYPE_PICTURE || $media_type == DiscussManager::TYPE_VIDEO) {

            //相册添加数据
            $media_count = substr_count($media, ',') + 1;
            $discuss_media = new SocialDiscussMedia();
            $data = [
                'discuss_id' => $discuss_id,
                'user_id' => $uid,
                'media' => $media,
                'content' => $content,
                'scan_type' => self::SCAN_TYPE_ALL,
                'scan_user' => '',
                'media_type' => $media_type,
                'media_count' => $media_count,
                'created' => $discuss_data['created'],
                'time' => date('Ymd', $discuss_data['created'])
            ];
            $discuss_media->insertOne($data);
            $this->updateNewestDiscussPic2($uid, $discuss_id, $media, $media_type);

        }
        $cashReward = new CashRewardManager();
        if ($res = $cashReward->sendPackage($uid, $discuss_id)) {
            Debug::log("res:" . var_export($res, true), 'package');
            if ($res && !empty($res['id'])) {
                SocialDiscuss::updateOne(['package_id' => $res['id'], 'package_info' => json_encode($res)], 'id=' . $discuss_id);
            }
        }

        //发at消息
        /* if ($at_uid) {
             foreach ($at_uid as $item) {
                 ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $discuss_id, 'type' => SocialManager::TYPE_DISCUSS, 'content' => $content, 'user_id' => $uid, 'to_user_id' => $item]);
             }
         }*/

        return $discuss_id;
    }

    /** 发动态
     * @param $uid
     * @param $media_type --类型 1-纯文字 2-视频 3-图片【可以包含文字】
     * @param $content -文字内容
     * @param $media -图片/视频地址
     * @param $tags -标签
     * @param $is_top -是否置顶
     * @param bool $open_location -是否公开位置
     * @param string $address -公开地址
     * @param string $lng -经度
     * @param string $lat -纬度
     * @param int $scan_type -查看类型【1-公开，2-私密，3-部分可见，4-不给谁看】
     * @param string $scan_user -允许查看/不给谁看的用户id
     * @param int $allow_download -是否允许下载
     * @param int $package_id -红包id
     * @param string $package_info -红包信息
     * @param string $area_code -地区码
     * @return bool
     */
    public function publish($uid, $media_type, $content, $media, $tags, $is_top, $open_location = false, $address = '', $lng = '', $lat = '', $scan_type = 1, $scan_user = '', $allow_download = 1, $package_id = 0, $package_info = '', $area_code = '')
    {
        $discuss_data = ['user_id' => $uid, 'media_type' => $media_type, 'content' => $content, 'media' => $media, 'tags' => $tags, 'allow_download' => $allow_download == 0 ? 0 : 1];
        $user = Users::findOne(['id=' . $uid, 'columns' => 'grade,coins,avatar']);

        //动态置顶
        if ($is_top) {
            $top_discuss = UserPointGrade::getColumn(['grade=' . $user['grade'], 'columns' => 'top_discuss'], 'top_discuss');
            $grade_top_count = SocialDiscussTopLog::dataCount("user_id=" . $uid . ' and type="' . self::TOP_TYPE_GRADE . '"');
            //等级特权置顶还没用完
            if ($top_discuss[0] > $grade_top_count) {
                $discuss_data['is_top'] = 1;
                $discuss_data['top_type'] = self::TOP_TYPE_GRADE;
            } //判断龙豆是否够
            else {
                /*  $coins = PointRule::init()->getRulePoints(PointRule::BEHAVIOR_TOP_DISCUSS); //置顶所需要的龙豆数
               /龙豆足够
                 if ($user->coins >= $coins) {
                     $data['is_top'] = 1;
                     $data['top_type'] = self::TOP_TYPE_COIN;
                 } else {
                     Ajax::init()->outError(Ajax::ERROR_COIN_NOT_ENOUGH);
                 }*/
                Ajax::init()->outError(Ajax::ERROR_COIN_NOT_ENOUGH);
            }
        }
        //公开位置
        if ($open_location) {
            $discuss_data['address'] = $address;
            $discuss_data['lng'] = $lng;
            $discuss_data['lat'] = $lat;
        }
        if ($package_id) {
            $discuss_data['package_id'] = $package_id;
        }
        if ($package_info) {
            $discuss_data['package_info'] = htmlspecialchars_decode($package_info);
        }
        if ($tags) {
            $discuss_data['tags_name'] = TagManager::getInstance()->getTagNames($tags);
        }
        //查看类型
        if ($scan_type == self::SCAN_TYPE_PRIVATE) {
            $discuss_data['scan_type'] = $scan_type;
        } elseif (($scan_type == self::SCAN_TYPE_PART_FRIEND || $scan_type == self::SCAN_TYPE_FORBIDDEN) && $scan_user != '') {
            $discuss_data['scan_type'] = $scan_type;
            $discuss_data['scan_user'] = $scan_user;
        } else if ($scan_type == self::SCAN_TYPE_FRIEND) {
            $discuss_data['scan_type'] = $scan_type;
        } else {
            $discuss_data['scan_type'] = self::SCAN_TYPE_ALL;
        }
        $discuss_data['created'] = time();
        $discuss_data['area_code'] = $area_code;

        if ($scan_type != self::SCAN_TYPE_PRIVATE) {
            //@的用户
            $at_uid = FilterUtil::packageContentTagApp($discuss_data['content'], $uid);
        } else {
            $at_uid = [];
        }

        $discuss_data['content'] = SensitiveManager::filterContent($discuss_data['content']);
        $discuss = new SocialDiscuss();
        if (!$discuss_id = $discuss->insertOne($discuss_data)) {
            Debug::log('publish discuss:' . json_encode($discuss->getMessages(), true), 'error');
            return false;
        }

        if (isset($discuss_data['top_type'])) {
            //龙豆置顶 扣除龙豆值
            if ($discuss_data['top_type'] == self::TOP_TYPE_COIN) {
                PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_TOP_DISCUSS);
            }
            //记录置顶记录
            $log = new SocialDiscussTopLog();
            if (!$log->insertOne(['user_id' => $uid, 'discuss_id' => $discuss_id, 'type' => $discuss_data['top_type'], 'created' => $discuss_data['created']])) ;
            {
                Debug::log(json_encode($log->getMessages(), JSON_UNESCAPED_UNICODE), 'error');
            }
        }
        //送经验值
        \Components\Rules\Point\PointRule::init()->executeRule($uid, \Components\Rules\Point\PointRule::BEHAVIOR_NEW_DISCUSS);


        /** --------------------------------给好友发消息--------------------------  **/
        $contacts = [];
        //全部可见，仅好友可见
        if ($scan_type == self::SCAN_TYPE_ALL || $scan_type == self::SCAN_TYPE_FRIEND) {
            $contacts = UserContactMember::findList(['user_id=' . $uid, 'columns' => 'owner_id,is_star']);
        } //部分好友可见
        elseif ($scan_type == self::SCAN_TYPE_PART_FRIEND) {
            $contacts = UserContactMember::findList(['user_id=' . $uid . ' and owner_id  in(' . $scan_user . ')', 'columns' => 'owner_id,is_star']);
        } //不给谁看
        elseif ($scan_type == self::SCAN_TYPE_FORBIDDEN) {
            $contacts = UserContactMember::findList(['user_id=' . $uid . ' and owner_id not in(' . $scan_user . ')', 'columns' => 'owner_id,is_star']);
        }
        if ($contacts) {
            //设为对方不让看我动态的用户过滤掉
            $not_user1 = UserPersonalSetting::getColumn(["owner_id=" . $uid . ' and user_id in (' . implode(',', array_column($contacts, 'owner_id')) . ') and scan_my_discuss=0', 'columns' => 'user_id'], 'user_id');
            //设为不看我动态的用户过滤掉
            $not_user2 = UserPersonalSetting::getColumn(["user_id=" . $uid . ' and owner_id in (' . implode(',', array_column($contacts, 'owner_id')) . ') and scan_his_discuss=0', 'columns' => 'owner_id as user_id'], 'user_id');
            $not_user = array_merge($not_user1, $not_user2);
            if ($not_user) {
                $contacts2 = [];
                foreach ($contacts as $item) {
                    if (!in_array($item['owner_id'], $not_user)) {
                        $contacts2[] = $item;
                    }
                }
                $contacts = $contacts2;
            }
            if ($contacts) {
                $not_star = [];
                foreach ($contacts as $c) {
                    if ($c['is_star'] == 1) {
                        SysMessage::init()->initMsg(SysMessage::TYPE_NEW_DISCUSS, ['user_id' => $uid, 'to_user_id' => $c['owner_id'], 'avatar' => $user['avatar'], 'need_push' => 1, 'item_id' => $discuss_id]);
                    } else {
                        $not_star[] = $c['owner_id'];
                    }
                }
                //非星标好友仅仅发 不需要通知栏的系统通知
                if ($not_star) {
                    SysMessage::init()->initMsg(SysMessage::TYPE_NEW_DISCUSS, ['to_user_id' => $not_star, 'avatar' => $user['avatar']]);
                }
            }
        }
        //更新最新动态封面图
        if ($media_type == DiscussManager::TYPE_PICTURE || $media_type == DiscussManager::TYPE_VIDEO) {
            //相册添加数据
            $media_count = substr_count($media, ',') + 1;
            $discuss_media = new SocialDiscussMedia();
            $data = [
                'discuss_id' => $discuss_id,
                'user_id' => $uid,
                'media' => $media,
                'is_top' => $is_top,
                'content' => $content,
                'scan_type' => $scan_type,
                'scan_user' => $scan_user,
                'media_type' => $media_type,
                'media_count' => $media_count,
                'created' => $discuss_data['created'],
                'time' => date('Ymd', $discuss_data['created'])
            ];
            $discuss_media->insertOne($data);
            if ($scan_type == self::SCAN_TYPE_ALL) {
                $this->updateNewestDiscussPic2($uid, $discuss_id, $media, $media_type);
            }
        }

        //更新动态数
        UserCountStat::updateOne("discuss_cnt=discuss_cnt+1", 'user_id=' . $uid);

        //发at消息
        if ($at_uid) {
            foreach ($at_uid as $item) {
                ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $discuss_id, 'type' => SocialManager::TYPE_DISCUSS, 'content' => $content, 'user_id' => $uid, 'to_user_id' => $item]);
            }
        }
        //图片鉴黄入队列
        if ($media_type == self::TYPE_PICTURE) {
            $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "img_check");
            $setting = json_decode($setting, true);
            if ($setting && $setting['enable'] == 1 && $setting['enable_discuss'] == 1) {
                $redis = $this->di->get("redis_queue");
                $media = array_filter(explode(',', $discuss_data['media']));
                foreach ($media as $k => $item) {
                    $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_DISCUSS_LIST, $discuss_id . "|" . ($k) . "|" . $item);
                }
            }
        }


        return $discuss_id;
    }

    /**获取动态列表
     * @param $uid -获取人uid
     * @param int $to_uid -被查看人uid
     * @param int $type -类型 1-圈子 2-广场
     * @param int $tag -标签
     * @param string $area_code -地区码
     * @param int $page -第几页
     * @param int $limit -每页显示数量
     * @param int $first_id -第一页第一条数据的id
     * @param int $last_id -上一页最后一条数据的id
     * @param int $v_id -版本号 --
     * @return array
     */
    public function list($uid, $to_uid = 0, $type, $tag, $area_code = '', $page = 1, $limit = 20, $first_id = 0, $last_id = 0, $v_id = 0)
    {
        //推荐
        if ($type == 2 && !$tag) {
            if (client_type == 'ios' && AppVersionManager::version_compare(app_version, '2.1.2', '>=')) {
                return RecommendManager::getInstance()->list($uid, $page, $limit, $last_id);
            } else if (client_type == 'android' && AppVersionManager::version_compare(app_version, '2.2.2105', '>=')) {
                return RecommendManager::getInstance()->list($uid, $page, $limit, $last_id);
            } else {
                return RecommendManager::getInstance()->staticList($uid, $page, $limit, $last_id);
            }
        }
        //24小时榜
        if ($type == 3) {
            return BillboardManager::getInstance()->getDayBillboard($uid, $last_id, $limit, $v_id);
        }
        //周榜
        if ($type == 4) {
            if ($uid = 50000) {
                Debug::log(var_export($_REQUEST, true), 'debug');
            }
            return BillboardManager::getInstance()->getWeekBillboard($uid, $last_id, $limit, $v_id);
        }
        //城市
        if ($type == 5 && $area_code) {
            return $this->cityList($uid, $area_code, $page, $limit, $last_id);
        }
        $res = ['data_count' => 0, 'data_list' => [], 'last_id' => 0];
        $where = 'status=' . self::STATUS_NORMAL . " and share_original_type<>'share'";
        $order = 'created desc'; //排序  置顶只在查看特定用户的个人主页动态生效
        //查看别人或自己的动态
        if ($to_uid) {
            //查看别人动态,检测是否在其黑名单下/用户设置了访问权限
            if ($uid != $to_uid) {
                if (UserBlacklist::exist('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
                    Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
                }
                if (UserPersonalSetting::exist('owner_id=' . $to_uid . ' and user_id=' . $uid . ' and scan_my_discuss=0')) {
                    Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
                }

                //查看权限检测
                //是好友
                /*   if (UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                       $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_FRIEND . ") or (scan_type=" . self::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . self::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))";
                   } else {
                       $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . self::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . self::SCAN_TYPE_FRIEND;
                   }*/
                $where .= " and scan_type=" . self::SCAN_TYPE_ALL;
            }
            $order = 'is_top desc,created desc';
            $where .= " and user_id=" . $to_uid;
        } //全部动态 去掉黑名单内用户的动态,去掉把我拉黑的用户的动态,去掉我设置为不看其动态的用户的动态，去掉设置不允许我看其动态的用户的动态
        else {
            $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
            if ($black_list) {
                $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
            }
            if ($type) {
                $user_contact = UserContactMember::getColumn(['owner_id=' . $uid, 'columns' => 'user_id as uid'], 'uid');//联系人合集
                $user_attention = UserAttention::getColumn(['owner_id=' . $uid . " and enable=1", 'columns' => 'user_id as uid'], 'uid');//联系人合集

                $uids = array_merge($user_contact, $user_attention);
                $uids = $uids ? implode(',', $uids) : '';
                //圈子 只出现关注人的和好友的以及自己的
                if ($type == 1) {
                    //没有关注过别人 也没有好友
                    if (!$uids) {
                        $where .= " and user_id=" . $uid;
                    } else {
                        //有好友 增加查看权限-仅好友可见检查
                        /*  if ($user_contact) {
                              $where .= " and ((user_id in (" . $uids . ") and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . self::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0) or (scan_type=" . self::SCAN_TYPE_FRIEND . " and user_id  in(" . implode(',', $user_contact) . ")))) or user_id=" . $uid . ") ";
                          } //没有好友 去除仅好友可见的
                          else {
                              $where .= " and ((user_id in (" . $uids . ") and scan_type<>" . self::SCAN_TYPE_FRIEND . " and  ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . self::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))) or user_id=" . $uid . ") ";
                          }*/
                        $where .= " and ((user_id in (" . $uids . ") and scan_type=" . self::SCAN_TYPE_ALL . ") or user_id=" . $uid . ") ";


                        // $where .= " and ((user_id in (" . $uids . ") or user_id=" . $uid . "))";

                        $person_setting = UserPersonalSetting::getColumn(['(owner_id=' . $uid . ' and user_id in(' . $uids . ') and scan_his_discuss=0) or (user_id=' . $uid . ' and owner_id in(' . $uids . ') and scan_my_discuss=0)', 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uid'], 'uid');
                        if ($person_setting) {
                            $where .= " and (user_id not in (" . implode(',', $person_setting) . '))';
                        }
                    }
                } //广场 //关注人的和好友的自己的都不能出现,转发的也过滤掉-暂时去掉该规则
//                else if ($type == 2) {
//                    $where .= " and share_original_item_id=0 ";
//                    /*   if ($uids) {
//                           $where .= " and (user_id not in (" . $uids . ') and user_id <> ' . $uid . ')';
//                       } else {
//                           $where .= " and  user_id <> " . $uid;
//                       }*/
//                    $where .= " and  user_id <> " . $uid;
//                    $person_setting = UserPersonalSetting::getColumn(['(owner_id=' . $uid . ' and scan_his_discuss=0) or (user_id=' . $uid . ' and scan_my_discuss=0)', 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uid'], 'uid');
//                    if ($person_setting) {
//                        $where .= " and (user_id not in (" . implode(',', $person_setting) . '))';
//                    }
//                    //查看权限检测
//                    //  $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . self::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . self::SCAN_TYPE_FRIEND;
//                    $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_PRIVATE . " and user_id=$uid))";
//
//                }
            } else {
                //全部动态 包括标签筛选
                $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_PRIVATE . " and user_id=$uid))";
                $person_setting = UserPersonalSetting::getColumn(['(owner_id=' . $uid . ' and scan_his_discuss=0) or (user_id=' . $uid . ' and scan_my_discuss=0)', 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uid'], 'uid');
                if ($person_setting) {
                    $where .= " and (user_id not in (" . implode(',', $person_setting) . '))';
                }
            }

            if ($tag) {
                $where .= " and (LOCATE('" . $tag . ",',concat(tags,','))>0) ";
            }
        }
        if ($last_id) {
            $where .= " and id<$last_id ";
            $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
                'order' => $order, 'limit' => $limit]);
        } else {
            $page = $page > 0 ? $page : 1;
            $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
                'order' => $order, 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        }
        if ($first_id && $type == 2) {
            $res['data_count'] = SocialDiscuss::dataCount($where . " and id>$first_id");
        }
        //全部动态-标签动态
        if ($list && !$to_uid && $tag) {
            $tag_filter = SocialDiscussTagFilter::getColumn(['discuss_id in (' . implode(',', array_column($list, 'discuss_id')) . ') and user_id<>' . $uid], 'discuss_id');
            if ($tag_filter) {
                foreach ($list as $k => $item) {
                    if (in_array($item['discuss_id'], $tag_filter)) {
                        //
                        unset($list[$k]);
                    }
                }
            }

        }
        //  $res['data_count'] = SocialDiscuss::count($where);
        if ($list) {
            //全部动态
            if (!$to_uid) {
                //
                $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
                $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合

                // if ($type) {
                $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
                $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合
                //  }
                foreach ($list as &$item) {
                    $item['user_info'] = $user_info[$item['uid']];
                    $item['user_info']['is_contact'] = 0;
                    $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
                    $item['user_info']['is_attention'] = 0;
                    //联系
                    if (isset($user_contact[$item['uid']])) {
                        $item['user_info']['is_contact'] = 1;
                        $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
                        $item['user_info']['is_attention'] = 1;
                    } //已关注
                    elseif (isset($user_attention[$item['uid']])) {
                        $item['user_info']['is_attention'] = 1;
                    } else {
                    }
                }
            } //自己的动态
            else if ($to_uid == $uid) {
                $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'user_id as uid,username,sex,avatar,grade']);//用户信息;
                foreach ($list as &$item) {
                    $item['user_info'] = $user_info;
                    $item['user_info']['is_contact'] = 0;
                    $item['user_info']['contact_mark'] = '';
                    $item['user_info']['is_attention'] = 0;
                }
            } //某个人的动态
            else {
                $user_info = UserInfo::findOne(['user_id=' . $to_uid, 'columns' => 'user_id as uid,username,sex,avatar,grade']);//用户信息;
                $is_attention = 0;
                $is_contact = 0;
                $contact_mark = '';

                if ($contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'mark'])) {
                    $is_attention = 1;
                    $is_contact = 1;
                    $contact_mark = $contact['mark'];
                } else if (UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                    $is_attention = 1;
                }
                //个人设置-》备注
                if ($personal_setting = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'mark'])) {
                    $contact_mark = $personal_setting['mark'];
                }
                foreach ($list as &$item) {
                    $item['user_info'] = $user_info;
                    $item['user_info']['is_contact'] = $is_contact;
                    $item['user_info']['contact_mark'] = $contact_mark;
                    $item['user_info']['is_attention'] = $is_attention;
                }
            }
            $list = $this->format($uid, $list);
            $res['data_list'] = $list;
            $list && $res['last_id'] = $list[count($list) - 1]['discuss_id'];
        }
        return $res;

    }

    /**我的/他的动态
     * @param $uid -用户id
     * @param $to_uid -想查看的用户id
     * @param int $type -类型 0-全部 1-图文 2-原创 3-视频
     * @param $page -第几页
     * @param int $limit -每次加载的条数
     * @return array
     */
    public function list2($uid, $to_uid, $type = 0, $page = 1, $limit = 20)
    {
        $res = ['data_list' => []];
        $where = 'status=' . self::STATUS_NORMAL;
        $order = 'is_top desc,created desc'; //排序
        //查看别人或自己的动态

        //查看别人动态,检测是否在其黑名单下/用户设置了访问权限
        $personal_setting = '';
        if ($uid != $to_uid) {
            if (UserBlacklist::exist('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
                Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
            }
            if ($personal_setting = UserPersonalSetting::findOne(['owner_id=' . $to_uid . ' and user_id=' . $uid])) {
                if ($personal_setting['scan_my_discuss'] == 0) {
                    Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
                }
            }

            //查看权限检测
            //是好友
            /*   if (UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                   $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_FRIEND . ") or (scan_type=" . self::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . self::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))";
               } else {
                   $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . self::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . self::SCAN_TYPE_FRIEND;
               }*/
            if (UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ") or (scan_type=" . self::SCAN_TYPE_FRIEND . "))";
            } else {
                $where .= " and ((scan_type=" . self::SCAN_TYPE_ALL . ")) and scan_type<>" . self::SCAN_TYPE_FRIEND;
            }
        }
        $where .= " and user_id=" . $to_uid;
        //图文
        if ($type == 1) {
            $where .= " and (media_type=" . self::TYPE_TEXT . ' or media_type=' . self::TYPE_PICTURE . ') ';
        } //原创
        else if ($type == 2) {
            $where .= " and share_original_item_id=0 ";
        } //视频
        else if ($type == 3) {
            $where .= " and media_type= " . self::TYPE_VIDEO;
        } //音频
        else if ($type == 4) {
            $where .= " and media_type= " . self::TYPE_AUDIO;
        } //商品
        else if ($type == 6) {
            $where .= " and media_type= " . self::TYPE_GOODS;
        }
        $page = $page > 0 ? $page : 1;
        $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,package_info,is_recommend,reward_cnt',
            'order' => $order, 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        //  $res['data_count'] = SocialDiscuss::count($where);
        if ($list) {
            //自己的动态
            if ($to_uid == $uid) {
                $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'user_id as uid,username,sex,avatar,grade']);//用户信息;
                foreach ($list as &$item) {
                    $item['user_info'] = $user_info;
                    $item['user_info']['is_contact'] = 0;
                    $item['user_info']['contact_mark'] = '';
                    $item['user_info']['is_attention'] = 0;
                }
            } //某个人的动态
            else {
                $personal_setting = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid]);
                $user_info = UserInfo::findOne(['user_id=' . $to_uid, 'columns' => 'user_id as uid,username,sex,avatar,grade']);//用户信息;
                $is_attention = 0;
                $is_contact = 0;
                $contact_mark = '';

                if ($contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'mark'])) {
                    $is_attention = 1;
                    $is_contact = 1;
                    $contact_mark = $contact['mark'];
                } else if (UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                    $is_attention = 1;
                }
                //个人设置-》备注
                if ($personal_setting = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'mark'])) {
                    $contact_mark = $personal_setting['mark'];
                }
                foreach ($list as &$item) {
                    $item['user_info'] = $user_info;
                    $item['user_info']['is_contact'] = $is_contact;
                    $item['user_info']['contact_mark'] = ($personal_setting && $personal_setting['mark']) ? $personal_setting['mark'] : $contact_mark;
                    $item['user_info']['is_attention'] = $is_attention;
                }
            }
            $list = $this->format($uid, $list);
            $res['data_list'] = $list;
        }
        return $res;
    }

    //按照城市筛选
    public function cityList($uid, $area_code = '', $page = 1, $limit = 20, $last_id = 0)
    {
        $res = ['data_count' => 0, 'data_list' => [], 'last_id' => 0];
        $where = 'status=' . self::STATUS_NORMAL . " and share_original_type<>'share' and area_code='" . $area_code . "' and scan_type=" . self::SCAN_TYPE_ALL;
        $order = 'created desc'; //排序  置顶只在查看特定用户的个人主页动态生效
        $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
        if ($black_list) {
            $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
        }
        if ($last_id) {
            $where .= " and id<$last_id ";
            $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
                'order' => $order, 'limit' => $limit]);
        } else {
            $page = $page > 0 ? $page : 1;
            $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
                'order' => $order, 'offset' => ($page - 1) * $limit, 'limit' => $limit]);

        }

        if ($list) {
            $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合

            foreach ($list as &$item) {
                $item['user_info'] = $user_info[$item['uid']];
                $item['user_info']['is_contact'] = 0;
                $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
                $item['user_info']['is_attention'] = 0;
                //联系人
                if (isset($user_contact[$item['uid']])) {
                    $item['user_info']['is_contact'] = 1;
                    $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
                    $item['user_info']['is_attention'] = 1;
                } //已关注
                elseif (isset($user_attention[$item['uid']])) {
                    $item['user_info']['is_attention'] = 1;
                } else {
                }
            }
            $list = $this->format($uid, $list);
            $res['data_list'] = $list;
            $list && $res['last_id'] = $list[count($list) - 1]['discuss_id'];
        }
        return $res;
    }

    /**动态详情
     * @param $uid -用户id
     * @param $discuss_id -动态id
     * @return array|static
     */
    public function detail($uid, $discuss_id)
    {
        $discuss = SocialDiscuss::findOne(['id=' . $discuss_id, 'columns' => 'status,id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,reward_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,scan_user,allow_download,package_id,is_recommend,package_info']);
        if (!$discuss) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if ($discuss['status'] == self::STATUS_DELETED) {
            Ajax::outError(Ajax::ERROR_DELETED_BY_USER);
        } else if ($discuss['status'] == self::STATUS_SHIELD) {
            Ajax::outError(Ajax::ERROR_DELETED_BY_SYSTEM);
        }
        //查看别人的动态
        if ($discuss['uid'] != $uid) {

            //对方已把自己拉黑
            if (UserBlacklist::exist('owner_id=' . $discuss['uid'] . ' and user_id=' . $uid)) {
                Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
            } //对方设置了不允许查看其动态
            if ($personal_setting = UserPersonalSetting::findOne(['owner_id=' . $discuss['uid'] . ' and user_id=' . $uid])) {
                if ($personal_setting['scan_my_discuss'] == 0) {
                    Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
                }
            }
            if ($discuss['scan_type'] == self::SCAN_TYPE_PRIVATE) {
                Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
            } elseif ($discuss['scan_type'] == self::SCAN_TYPE_PART_FRIEND && strpos($discuss['scan_user'] . ',', $uid . ',') === false) {
                Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
            } elseif ($discuss['scan_type'] == self::SCAN_TYPE_FORBIDDEN && strpos($discuss['scan_user'] . ',', $uid . ',') !== false) {
                Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
            } else {
                $user_info = UserInfo::findOne(['user_id=' . $discuss['uid'], 'columns' => 'user_id as uid,username,sex,avatar,grade,is_auth']);//用户信息;
                $is_attention = 0;
                $is_contact = 0;
                $contact_mark = '';

                if ($contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $discuss['uid'], 'columns' => 'mark'])) {
                    $is_attention = 1;
                    $is_contact = 1;
                    $contact_mark = $contact['mark'];
                } else if ($attention = UserAttention::findOne(['owner_id=' . $uid . ' and user_id=' . $discuss['uid'], 'columns' => 1])) {
                    $is_attention = 1;
                }
                //个人设置-》备注
                if ($personal_setting = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $discuss['uid'], 'columns' => 'mark'])) {
                    $contact_mark = $personal_setting['mark'];
                }

                $discuss['user_info'] = $user_info;
                $discuss['user_info']['is_contact'] = $is_contact;
                $discuss['user_info']['contact_mark'] = $contact_mark;
                $discuss['user_info']['is_attention'] = $is_attention;
            }
        } else {
            $user_info = UserInfo::findOne(['user_id=' . $discuss['uid'], 'columns' => 'user_id as uid,username,sex,avatar,grade,is_auth']);//用户信息;
            $discuss['user_info'] = $user_info;
            $discuss['user_info']['is_contact'] = 0;
            $discuss['user_info']['contact_mark'] = '';
            $discuss['user_info']['is_attention'] = 0;
        }

        $discuss['is_like'] = 0;
        $discuss['is_collect'] = 0;
        $discuss['like_users'] = [];
        $discuss['reward_users'] = [];

        if ($discuss['like_cnt'] > 0) {
            $like_users = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and item_id=' . $discuss_id . ' and enable=1', 'columns' => 'user_id as uid,created', 'order' => 'created', 'limit' => 5], 'uid');
            if ($like_users) {
                $user_infos = Users::findList(['id in (' . implode(',', array_column($like_users, 'uid')) . ')', 'columns' => 'id as uid,avatar']);
                $order_data = [];//排序

                foreach ($user_infos as $u) {
                    $order_data[] = $like_users[$u['uid']]['created'];
                }
                array_multisort($order_data, SORT_DESC, $user_infos);
                $discuss['like_users'] = $user_infos;
            }
        }
        if ($discuss['reward_cnt'] > 0) {
            $reward_users = SocialDiscussReward::getByColumnKeyList(['discuss_id=' . $discuss_id, 'columns' => 'user_id as uid,created', 'order' => 'created', 'limit' => 5, 'group' => 'uid'], 'uid');
            if ($reward_users) {
                $user_infos = Users::findList(['id in (' . implode(',', array_column($reward_users, 'uid')) . ')', 'columns' => 'id as uid,avatar']);
                $order_data = [];//排序

                foreach ($user_infos as $u) {
                    $order_data[] = $reward_users[$u['uid']]['created'];
                }
                array_multisort($order_data, SORT_DESC, $user_infos);
                $discuss['reward_users'] = $user_infos;
            }
        }
        //是否已赞
        if (SocialLike::exist('type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id=' . $discuss_id . ' and enable=1')) {
            $discuss['is_like'] = 1;
        }
        //是否已收藏
        if (SocialFav::exist('type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id=' . $discuss_id . ' and enable=1')) {
            $discuss['is_collect'] = 1;
        }

        //转发的原始内容
        $discuss['original_info'] = (object)[];

        //显示时间
        $discuss['show_time'] = Time::formatHumaneTime($discuss['created']);
        $discuss['content'] = FilterUtil::unPackageContentTagApp($discuss['content'], $uid);
        $discuss = array_merge($discuss, $this->getOriginalInfo($uid, $discuss));

        return $discuss;
    }

    /**删除动态
     * @param $uid
     * @param $discuss_id
     * @return bool
     */
    public function deleteDiscuss($uid, $discuss_id)
    {
        $discuss = SocialDiscuss::findOne(['user_id=' . $uid . ' and id=' . $discuss_id . ' and status=' . self::STATUS_NORMAL, 'columns' => 'id,forward_cnt,share_original_type,share_original_item_id,is_top,top_type,media_type,media,parent_item_id']);
        if (!$discuss) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        Debug::log("更新转发数1");
        if (!SocialDiscuss::updateOne(['status' => self::STATUS_DELETED, 'modify' => time()], 'id=' . $discuss_id)) {
            return false;
        }
        Debug::log("更新转发数2");
        if ($discuss['share_original_item_id'] == 0 && $discuss['media_type'] != self::TYPE_TEXT) {
            //更新动态相册
            // $media_count = substr_count($discuss['media'], ',') + 1;
            $this->db->execute("delete from social_discuss_media where discuss_id=" . $discuss_id);
            //  $this->db->execute("update user_profile set discuss_media_count=discuss_media_count-" . $media_count . ' where user_id=' . $uid);

            //更新最新动态相册
            $profile = UserProfile::findOne(['user_id=' . $uid, 'columns' => 'newest_discuss_pic']);
            if ($profile['newest_discuss_pic']) {
                $newest_discuss_pic = json_decode($profile['newest_discuss_pic'], true);
                $ids = array_unique(array_column($newest_discuss_pic, 'id'));
                //需要更新动态相册
                if (in_array($discuss_id, $ids)) {
                    $this->updateNewestDiscussPic($uid);
                }
            }
        }
        if ($discuss['is_top'] == 1) {
            //删除记录
            SocialDiscussTopLog::remove("user_id=" . $uid . ' and discuss_id=' . $discuss_id . ' and type="' . $discuss['top_type'] . '"');
        }
        Debug::log("更新转发数3:" . $discuss['parent_item_id']);
        //更新转发数
        if ($discuss['parent_item_id'] > 0) {
            Debug::log("更新转发数4");
            SocialManager::init()->changeCnt(SocialManager::TYPE_DISCUSS, $discuss['parent_item_id'], 'forward_cnt', false);
            if ($discuss['share_original_item_id'] > 0 && $discuss['share_original_type'] == SocialManager::TYPE_DISCUSS && $discuss['parent_item_id'] != $discuss['share_original_item_id']) {
                SocialManager::init()->changeCnt(SocialManager::TYPE_DISCUSS, $discuss['share_original_item_id'], 'forward_cnt', false);
            }
        }
        //删除推荐
        SocialDiscussRecommend::remove('discuss_id=' . $discuss_id);

        //更新搜索引擎缓存
        $this->notifySearchPlugin($discuss_id);

        return true;
    }

    //通知搜索引擎更新状态
    /**
     * @param $discuss_id
     * @param int $type 0-上架 1-下架
     * @return array
     */
    public function notifySearchPlugin($discuss_id, $type = 1)
    {
        if (is_array($discuss_id)) {
            foreach ($discuss_id as &$item) {
                $item = intval($item);
            }
            $discuss_id = json_encode($discuss_id);
        } else {
            $discuss_id = json_encode([intval($discuss_id)]);
        }
        $res = Request::getPost(Base::NOTIFY_SEARCH, ['type' => $type, 'datatype' => 'dynamic', 'ids' => $discuss_id]);
        return $res;
    }

    /**更新最新的5条动态图片
     * @param $uid
     */
    public function updateNewestDiscussPic($uid)
    {
        //暂时都去掉
        // return true;
        $list = SocialDiscussMedia::findList(['user_id=' . $uid . ' and scan_type=1', 'columns' => 'discuss_id,media,media_type,id,created', 'order' => 'created desc', 'limit' => 5], true);
        // $i = 0;
        if ($list) {
            $newest_discuss_pic = [];
            foreach ($list as $item) {
                /*  if ($i == 5) {
                      break;
                  }*/
                if ($item['media_type'] == self::TYPE_PICTURE) {
                    $imgs = explode(',', $item['media']);
                    $newest_discuss_pic[] = ['media_type' => self::TYPE_PICTURE, 'url' => $imgs[0], 'id' => $item['discuss_id']];
                    /*  $j = 0;
                      while ($i < 5 && $j < count($imgs)) {
                          $newest_discuss_pic[] = ['media_type' => self::TYPE_PICTURE, 'url' => $imgs[$j], 'id' => $item['id']];
                          $i++;
                          $j++;
                      }*/
                } else if ($item['media_type'] == self::TYPE_VIDEO) {
                    $newest_discuss_pic[] = ['media_type' => self::TYPE_VIDEO, 'url' => $item['media'], 'id' => $item['discuss_id']];
                    // $i++;
                }
            }
            $this->db->execute("update user_profile set newest_discuss_pic='" . json_encode($newest_discuss_pic, JSON_UNESCAPED_UNICODE) . "' where user_id=" . $uid);
        } else {
            $this->db->execute("update user_profile set newest_discuss_pic='" . json_encode([], JSON_UNESCAPED_UNICODE) . "' where user_id=" . $uid);
        }

    }

    //更新最近动态图片
    public function updateNewestDiscussPic2($uid, $discuss_id, $media, $media_type)
    {

        $profile = UserProfile::findOne(['user_id=' . $uid, 'columns' => 'newest_discuss_pic']);
        //以前有数据
        if ($profile['newest_discuss_pic']) {
            $newest_discuss = json_decode($profile['newest_discuss_pic'], true);
            $new_newest_discuss = [];
            //发布的图片
            //  $i = 0;
            if ($media_type == self::TYPE_PICTURE) {
                $media = array_filter(explode(',', $media));
                $new_newest_discuss[] = ['media_type' => self::TYPE_PICTURE, 'url' => $media[0], 'id' => $discuss_id];

                /*   foreach ($media as $k => $m) {
                       if ($k >= 5) {
                           break;
                       }
                       $new_newest_discuss[] = ['media_type' => self::TYPE_PICTURE, 'url' => $m, 'id' => $discuss_id];
                       $i++;
                   }*/
            } //发布的视频
            else {
                $new_newest_discuss[] = ['media_type' => self::TYPE_VIDEO, 'url' => $media, 'id' => $discuss_id];
                //   $i = 1;
            }
            //不够显示 补齐
            // if ($i < 5) {
            // $new_newest_discuss = array_splice(array_merge($new_newest_discuss, $newest_discuss), 0, 5);
            // }
            $new_newest_discuss = array_splice(array_merge($new_newest_discuss, $newest_discuss), 0, 5);

        } //以前没有数据
        else {
            $new_newest_discuss = [];
            //发布的图片
            if ($media_type == self::TYPE_PICTURE) {
                $media = array_filter(explode(',', $media));
                $new_newest_discuss[] = ['media_type' => self::TYPE_PICTURE, 'url' => $media[0], 'id' => $discuss_id];
                /*  foreach ($media as $k => $m) {
                      if ($k >= 5) {
                          break;
                      }
                      $new_newest_discuss[] = ['media_type' => self::TYPE_PICTURE, 'url' => $m, 'id' => $discuss_id];
                  }*/
            } //发布的视频
            else {
                $new_newest_discuss[] = ['media_type' => self::TYPE_VIDEO, 'url' => $media, 'id' => $discuss_id];
            }
        }
        //更新最新图片
        $this->db->execute("update user_profile set newest_discuss_pic='" . json_encode($new_newest_discuss, JSON_UNESCAPED_UNICODE) . "' where user_id=" . $uid);
    }

    /**动态取消置顶
     * @param $uid
     * @param $discuss_id
     * @return bool
     */
    public function unTopDiscuss($uid, $discuss_id)
    {
        $discuss = SocialDiscuss::findOne(['user_id=' . $uid . ' and id=' . $discuss_id . ' and status=' . self::STATUS_NORMAL, 'columns' => 'top_type']);
        if (!$discuss) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (!SocialDiscuss::updateOne(['is_top' => 0, 'modify' => time()], ['id' => $discuss_id])) {
            return false;
        }
        //删除记录
        $this->original_mysql->execute("delete from social_discuss_top_log where user_id=" . $uid . ' and discuss_id=' . $discuss_id . ' and type="' . $discuss['top_type'] . '"');

        return true;
    }

    /**动态置顶
     * @param $uid
     * @param $discuss_id
     * @return bool
     */
    public function topDiscuss($uid, $discuss_id)
    {
        try {
            $this->db->begin();
            $discuss = SocialDiscuss::findOne(['user_id=' . $uid . ' and id=' . $discuss_id . ' and status=' . self::STATUS_NORMAL, 'columns' => 'is_top']);
            if (!$discuss) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            //已经置过顶了
            if ($discuss['is_top'] == 1) {
                return true;
            }
            $user = Users::findOne(['id=' . $uid, 'columns' => 'grade,coins']);
            $top_discuss = UserPointGrade::getColumn(['grade=' . $user['grade'], 'columns' => 'top_discuss'], 'top_discuss');
            $grade_top_count = SocialDiscussTopLog::dataCount("user_id=" . $uid . ' and type=' . self::TOP_TYPE_GRADE);
            //等级特权置顶还没用完
            if ($top_discuss[0] > $grade_top_count) {
                $data['is_top'] = 1;
                $data['top_type'] = self::TOP_TYPE_GRADE;
            } //判断龙豆是否够
            else {
                Ajax::init()->outError(Ajax::ERROR_COIN_NOT_ENOUGH);
                /*   $coins = PointRule::init()->getRulePoints(PointRule::BEHAVIOR_TOP_DISCUSS); //置顶所需要的龙豆数
                   //龙豆足够
                   if ($user->coins >= $coins) {
                       $data['is_top'] = 1;
                       $data['top_type'] = self::TOP_TYPE_COIN;
                   } else {
                       Ajax::init()->outError(Ajax::ERROR_COIN_NOT_ENOUGH);
                   }*/
            }
            $data['modify'] = time();
            if (!SocialDiscuss::updateOne($data, 'id=' . $discuss_id)) {
                throw new \Exception("更新动态失败");
            }
            //记录置顶记录
            $log = new SocialDiscussTopLog();
            if (!$log->insertOne(['user_id' => $uid, 'discuss_id' => $discuss_id, 'type' => $data['top_type'], 'created' => time()])) {
                throw new \Exception("插入制定记录失败:" . var_export($log->getMessages(), true));
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**检测可以置顶的条数
     * @param $uid
     * @return int
     */
    public static function checkTop($uid)
    {
        $res = ['total' => 0, 'left' => 0];
        $user = Users::findOne(['id=' . $uid, 'columns' => 'grade,coins']);
        if ($user) {
            $top_discuss = UserPointGrade::getColumn(['grade=' . $user['grade'], 'columns' => 'top_discuss'], 'top_discuss');
            $grade_top_count = SocialDiscussTopLog::dataCount("user_id=" . $uid . ' and type=' . self::TOP_TYPE_GRADE);
            //等级特权置顶还没用完
            $remain = $top_discuss[0] - $grade_top_count;
            $res = ['left' => ($remain > 0 ? $remain : 0), 'total' => intval($top_discuss[0])];
        }
        return $res;

    }
}
<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/11
 * Time: 17:12
 */

namespace Multiple\Open\Helper;


use Components\Rules\Point\PointRule;
use Models\Customer\CustomerGame;
use Models\Social\SocialDiscuss;
use Models\User\UserContactMember;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\Im\SysMessage;
use Util\Debug;
use Util\FilterUtil;

class SocialManager extends Plugin
{
    private static $instance = null;

    /**
     * @return null|SocialManager
     */
    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //分享
    public function share($uid, $app_id, $from, $content)
    {
        //第三方转发分享
        $discuss = new  SocialDiscuss();
        $data = [
            'user_id' => $uid,
            'media_type' => DiscussManager::TYPE_TEXT,
            'created' => time(),
            'tags' => '',
            'scan_type' => DiscussManager::SCAN_TYPE_ALL,
            'share_original_type' => \Services\Social\SocialManager::TYPE_SHARE,
            'share_original_item_id' => $app_id
        ];

        $share_content = json_decode($content, true);
        $share_content['from'] = $from;
        $content = json_encode($share_content, JSON_UNESCAPED_UNICODE);
        $data['content'] = $content;

        //@的用户
        $at_uid = FilterUtil::packageContentTagApp($share_content['content'], $uid);
        $data['created'] = time();
        if ($discuss_id = $discuss->insertOne($data)) {
            //送经验值
            PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_NEW_DISCUSS);
            //给好友及粉丝发新动态通知
            $contacts = UserContactMember::getColumn(['user_id=' . $uid, 'columns' => 'owner_id'], 'owner_id');

            if ($contacts) {
                SysMessage::init()->initMsg(SysMessage::TYPE_NEW_DISCUSS, ['to_user_id' => $contacts]);
            }
            //发at消息
            if ($at_uid) {
                foreach ($at_uid as $item) {
                    ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $discuss_id, 'type' => \Services\Social\SocialManager::TYPE_DISCUSS, 'content' => $share_content['content'], 'user_id' => $uid, 'to_user_id' => $item]);
                }
            }
            return $discuss_id;
        }
        return false;

    }
}
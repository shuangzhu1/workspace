<?php
/**
 * Created by PhpStorm.
 * User: ykuang
 * Date: 15-4-3
 * Time: 下午12:43
 */

namespace Components\User;


use Models\User\Users;
use Phalcon\Mvc\User\Plugin;

class UserPrivilege extends Plugin
{
    /*
       * infinite
       */
    static $instacne = null;

    /*权限分组*/
    const PRIVILEGE_LOGIN = 1000;
    const PRIVILEGE_PUBLISH_DISCUSS = 1001;
    const PRIVILEGE_EDIT_DISCUSS = 1002;
    const PRIVILEGE_SHARE = 1003;
    const PRIVILEGE_COLLECT = 1004;
    const PRIVILEGE_LIKE = 1005;
    const PRIVILEGE_COMMENT = 1006;
    const PRIVILEGE_ADD_FRIEND = 1007;
    const PRIVILEGE_SEND_PERSONAL_MESSAGE = 1008;
    const PRIVILEGE_BUY_GOODS = 1009;


    /*用户状态*/
    const USER_STATUS_ZERO = 0;
    const USER_STATUS_ONE = 1;


    public static $ErrorMsg = array(
        self::PRIVILEGE_LOGIN => "你的账号被封,暂时禁止登录！",
        self::PRIVILEGE_PUBLISH_DISCUSS => "你的账号被封,暂时禁止发布帖子",
        self::PRIVILEGE_EDIT_DISCUSS => "你的账号被封,暂时禁止修改帖子",
        self::PRIVILEGE_SHARE => "你的账号被封,暂时禁止分享",
        self::PRIVILEGE_COLLECT => "你的账号被封,暂时禁止收藏",
        self::PRIVILEGE_LIKE => "你的账号被封,暂时禁止点赞",
        self::PRIVILEGE_COMMENT => "你的账号被封,暂时禁止评论",
        self::PRIVILEGE_ADD_FRIEND => "你的账号被封,暂时禁止添加好友",
        self::PRIVILEGE_SEND_PERSONAL_MESSAGE => "你的账号被封,暂时禁止发私信",
        self::PRIVILEGE_BUY_GOODS => "你的账号被封,暂时禁止购买商品",
    );
    public static $StatusPrivilege = array(
        self::USER_STATUS_ZERO => array(),
        self::USER_STATUS_ONE => array(
            self::PRIVILEGE_PUBLISH_DISCUSS,
            self::PRIVILEGE_COMMENT,
        )
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

    /**检查用户权限
     * @param int $user_id //用户id
     * @param $privilege_type //操作类型
     * @return array
     */
    public function checkPrivilege($user_id = 0, $privilege_type)
    {
        $res = array(
            'result' => 0,
            "error" => ""
        );
        if ($user_id > 0) {
            $user_info = Users::findOne(["id=" . $user_id, 'columns' => 'status']);
            if (!$user_info) {
                $res['result'] = 1;
                $res['error'] = "你还没有登录";
            } else if ($privilege_type && in_array($privilege_type, self::$StatusPrivilege[$user_info['status']])) {
                $res['result'] = 1;
                $res['error'] = self::$ErrorMsg[$privilege_type];
            }
        } else {
            $res['result'] = 1;
            $res['error'] = "你还没有登录";
        }
        return $res;
    }

} 
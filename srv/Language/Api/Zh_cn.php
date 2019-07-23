<?php

/**
 *
 * 简体中文
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/7
 * Time: 9:15
 */
namespace Language\Api;

use Util\Ajax;
use Util\Debug;

class Zh_cn extends Ajax
{
    //错误信息定义

    public static $errmsg = array(
        self::ERROR_TOKEN_INVALID => 'token无效',
        self::ERROR_TOKEN_EXPIRES => 'token未获取或已过期',
        self::ERROR_USER_IS_NOT_EXISTS => '用户不存在',
        self::ERROR_USER_HAS_NOT_LOGIN => '用户尚未登陆',
        self::ERROR_USER_HAS_NO_PERMISSION => '用户没有权限',
        self::ERROR_USER_HAS_BEING_USED => '本手机号已注册',
        self::ERROR_USER_IS_INVALID => '用户名格式不正确',
        self::ERROR_USER_BIND_FAILED => '用户绑定失败',
        self::ERROR_PASSWD_IS_INVALID => '密码长度为6-16位字符之间',
        self::ERROR_PASSWD_IS_NOT_CORRECT => '密码不正确',
        self::ERROR_EMAIL_IS_INVALID => '邮箱格式不正确',
        self::ERROR_EMAIL_HAS_BEING_USED => '邮箱已被使用了',
        self::ERROR_YOU_HAS_LIKED => '你已经赞过了',
        self::ERROR_YOU_HASNT_LIKE => '你还没赞过',
        self::ERROR_RUN_TIME_ERROR_OCCURRED => '服务器错误',
        self::ERROR_PHONE_IS_INVALID => '非法的手机号',
        self::ERROR_PHONE_HAS_BEING_USED => '该手机号已被注册',
        self::ERROR_USER_IS_NOT_ACTIVE => '用户未激活',
        self::ERROR_DATA_NOT_EXISTS => '数据不存在',
        self::ERROR_DATA_HAS_EXISTS => '数据已经存在',
        self::ERROR_USER_HAS_BEING_LOCKED => '用户已被锁定',
        self::ERROR_USER_HAS_BEING_LOCKED_MANUAL => '用户已被锁定',
        self::ERROR_USER_DELETED => '账号已被禁用',
        self::ERROR_ACCOUNT_IS_NOT_EXISTS => '账号不存在',
        self::ERROR_PHONE_EXIST => "该手机已被注册,如果你忘记了密码,请尝试找回密码",
        self::ERROR_SIGN => "签名验证失败",
        self::ERROR_VERIFY_CODE => "验证码错误",
        self::ERROR_SEND_PHONE_CODE_TOO_FREQUENCY => "发送手机短信太过频繁",
        self::ERROR_SEND_BYOND_ONE => "一分钟内只能请求一次",
        self::ERROR_VERIFY_CODE_OLD => "验证码未发送或者已过期,请重新发送",
        self::ERROR_USERNAME_PREG => '昵称必须为2-8字符之间',
        self::ERROR_USER_OR_PASSWORD => '手机或密码错误',
        self::ERROR_GROUP_MEMBER_LIMIT => '群聊成员数已达上限',
        self::ERROR_GROUP_MEMBER_EXIST => '你已经是群成员',
        self::ERROR_GROUP_MEMBER_NOT_ADMIN => '你不是群主',
        self::ERROR_GROUP_MEMBER_ADMIN => '群主无法退出,你可以转让权限后退出或者解散群',
        self::ERROR_GROUP_NOT_MEMBER => '你还不是群成员',
        self::ERROR_GROUP_MEMBER_LIMIT_200 => '一次最多只能添加200个成员',
        self::ERROR_HANDLE_NOT_GROUP_MEMBER => '操作的对象必须是群成员',
        self::ERROR_CANNOT_ATTENTION_SELF => '自己不能关注自己',
        self::ERROR_HAS_ATTENTION => "你已经关注过了",
        self::ERROR_IN_BLACKLIST => "对方已经在你的黑名单列表中",
        self::ERROR_REFUSE_YOU_REQUEST => "对方拒绝了你的请求",
        self::ERROR_REPORT_HAS_SENT => "请勿重复提交举报",
        self::ERROR_SEND_VERIFY_CODE => "哇哦,短信发送出了点问题",
        self::ERROR_NOT_FRIEND => "你们还不是好友",
        self::ERROR_ALREADY_CONTACT_MEMBER => "你们已经是联系人了",
        self::ERROR_COIN_NOT_ENOUGH => "龙豆不足",
        self::ERROR_HAS_NO_PRIVILEGE_DISCUSS => "主人设置了访问权限",
        self::ERROR_REQUEST_FREQUENCY => "请求太过频繁",
        self::ERROR_SUBMIT_REPEAT => "请勿重复提交申请",
        self::ERROR_LOGIN_DEVICE => "您已开启设备登录保护,需要进行手机验证",
        self::ERROR_SYSTEM_BLACKLIST => "该用户为系统黑名单用户",
        self::ERROR_INVITOR_NOT_GROUP_MEMBER => "邀请者不是群成员",
        self::ERROR_LOW_APP_VERSION => "App 版本过低",
        self::ERROR_USER_HAS_NO_OPEN_AVATAR => '无第三方头像',
        self::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH => '目标成员权限不足',
        self::ERROR_GIFT_OFF => '该礼物已下架',
        self::ERROR_GIFT_BEYOND_LIMIT => '该礼物的拥有数量已超出限制',
        self::ERROR_TARGET_CAN_NOT_YOURSELF => '目标用户不能是自己',
        self::ERROR_EMPTY_BIND_PHONE => "请先绑定手机号",
        self::ERROR_EMPTY_BIRTHDAY => "请先完善生日信息",
        self::ERROR_SHOW_DISABLE => "对方已关闭秀场",
        self::ERROR_SHOW_HAS_LIKE => "你已经赞过了",
        self::ERROR_SHOW_HAS_DISLIKE => "你已经踩过了",
        self::ERROR_DELETED_BY_SYSTEM => "已被系统屏蔽",
        self::ERROR_DELETED_BY_USER => "已被用户删除",
        self::ERROR_DELETED_NOT_EXPIRE => "视频问答视频上传2小时内可重新录制,14天后才可删除",
        self::ERROR_SHOP_HAS_EXISTS => "请勿重复添加店铺",
        self::ERROR_IP_ABNORMAL => '请求异常',
        self::ERROR_MEMBER_NOT_JOIN => '禁止加入群聊',
        self::ERROR_SHOP_HAS_BEEN_SHIELD => '店铺已被系统屏蔽',
        self::ERROR_SHOP_HAS_BEEN_DELETED => '店铺已被店主关闭',
        self::ERROR_MEMBER_IS_ADMIN => '对方已经是群管理员',
        self::ERROR_PACKAGE_BEYOND_LIMIT => '今日红包领取次数已达上限',
        self::ERROR_NO_AUTH => '请先完成实名认证',
        self::ERROR_OAUTH2_FAIL_GET_VERIFY_TOKEN => '获取登录票据失败',
        self::ERROR_INVITER_HAS_INVITER => '你已经设置过推荐人了',
        self::ERROR_INVITER_IS_LOWER => '推荐人不能为下级用户',
        self::ERROR_ID_CARD_HAS_BEEN_USED => '该身份证已被使用',
        self::ERROR_DRAGON_COIN_NOT_ENOUGH => '龙币不足',
        self::ERROR_MONEY_NOT_ENOUGH => '余额不足',
        self::ERROR_NICK_HAS_BEEN_USED => '该用户昵称已被使用',
        self::ERROR_NICK_UPDATE_ONLY_ONCE => '用户昵称一年只能修改一次',
        self::ERROR_OWN_ONE_COMMUNITY => '每个用户只能创建一个社区',
        self::ERROR_COMMUNITY_NAME_UNIQUE => '相同名字的社区已经存在',
        self::ERROR_COMMUNITY_NOT_MEMBER => '对方还不是社区成员',
        self::ERROR_COMMUNITY_IS_MANAGER => '对方已经是社区管理员',
        self::ERROR_COMMUNITY_MANAGER_HAS_GROUP_NO_TRANSFER => '该管理员还有群未转让',
        self::ERROR_COMMUNITY_GROUP_LIMIT => '你的社群个数已达上限',
        self::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS => '相同名字的社群已经存在',
        self::ERROR_COMMUNITY_GROUP_MEMBER_UNSUBSCRIBE => '社群成员不能取消关注',
        self::ERROR_COMMUNITY_OWNER_ADMIN_UNSUBSCRIBE => '区主或社区管理员不能取消关注',
        self::ERROR_COMMUNITY_PRIVATE_GROUP_NOT_JOIN => '非公开群禁止主动加入',
        self::ERROR_COMMUNITY_DISCUSS_NEED_ADMIN => '仅管理员能发布',
        self::ERROR_COMMUNITY_DISCUSS_NEED_OWNER => '仅区主能操作',
        self::ERROR_COMMUNITY_CHECKING => '申请正在审核中，请勿重复提交',
        self::ERROR_COMMUNITY_APPLY_LIMIT => '今日提交次数已达上限',
        self::ERROR_COMMUNITY_APPLY_HAS_BEEN_HANDLE => '申请已被处理',

        UPLOAD_ERR_INI_SIZE => '文件大小超过了php.ini定义的upload_max_filesize值',
        UPLOAD_ERR_FORM_SIZE => '文件大小超过了HTML定义的MAX_FILE_SIZE值',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',

        self::UPLOAD_ERR_TMP_NAME_NOT_EXIST => '无文件上传',
        self::UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED => '未接收到数据',
        self::UPLOAD_ERR_FILE_EXT_ONLY_ALLOWED => '文件类型不支持',
        self::UPLOAD_ERR_UPLOAD_FILE_IS_TOO_LARGE => '文件太大',
        self::UPLOAD_ERR_BATCH_IS_NOT_ALLOWED => '不允许批量上传',
        self::UPLOAD_ERR_ONLY_SUPPORT_BATCH_UPLOAD => '仅支持批量上传',
        self::UPLOAD_ERR_FASTDFS_SAVE_ERROR_OCCURRED => '文件保存失败',

        self::FAIL_REGISTER => "注册失败",
        self::FAIL_LOGIN => "登录失败",
        self::FAIL_ADD_GROUP => "群聊创建失败",
        self::FAIL_JOIN_GROUP => "加入群聊失败",
        self::FAIL_DISSOLVE_GROUP => "群聊解散失败",
        self::FAIL_LEAVE_GROUP => "退出群聊失败",
        self::FAIL_INVITE_GROUP => "邀请失败",
        self::FAIL_TRANSFER_GROUP => "转让失败",
        self::FAIL_KICK_MEMBER => "删除失败",
        self::FAIL_EDIT => "编辑失败",
        self::FAIL_HANDLE => "操作失败",
        self::FAIL_LOCATION => "位置上报失败",
        self::FAIL_SEND => "请求发送失败",
        self::FAIL_PUBLISH => "发布失败",
        self::FAIL_DELETE => "删除失败",
        self::FAIL_FORWARD => "转发失败",
        self::FAIL_SHARE => "分享失败",
        self::FAIL_SUBMIT => "提交失败",
        self::FAIL_ADD => "添加失败",
        self::FAIL_TOP => '置顶失败',
        self::FAIL_CANCEL => '取消失败',
        self::FAIL_CHARGE => '充值失败',
        self::FAIL_QUESTION => '提问失败',
        self::FAIL_PAY => '支付失败',
        self::FAIL_PICK => '领取失败',
        self::FAIL_PICK_HAS_REACHED_LIMIT => '已今日达领取上限',
        self::FAIL_GET_SHOP_CATEGORY => '获取店铺分类失败',

        self::INVALID_SIGN => "无效的签名",
        self::INVALID_PARAM => "无效的参数",
        self::INVALID_PHONE => "无效的手机号",
        self::INVALID_LOGIN_TYPE => "无效的登录类型",
        self::INVALID_MSG_TYPE => "无效的短信类型",
        self::INVALID_ID_CARD_TYPE => "无效的身份证号",
        self::INVALID_WEBSITE => "无效的网址",
        self::INVALID_REQUEST => "无效的请求",
        self::INVALID_TIMESTAMP => "手机时间异常，请同步网络时间。",
        self::INVALID_INVITE_CODE => "无效的邀请码",

    );
    //成功信息定义

    public static $success_msg = array(
        self::SUCCESS_REGISTER => '注册成功',
        self::SUCCESS_JOIN => '加入成功',
        self::SUCCESS_DISSOLVE => '解散成功',
        self::SUCCESS_LEAVE => '退出成功',
        self::SUCCESS_INVITE => '邀请成功',
        self::SUCCESS_TRANSFER => '转让成功',
        self::SUCCESS_DELETE => '删除成功',
        self::SUCCESS_EDIT => '编辑成功',
        self::SUCCESS_ATTENTION => '关注成功',
        self::SUCCESS_CANCEL => '取消成功',
        self::SUCCESS_REPORT => '举报成功',
        self::SUCCESS_HANDLE => '操作成功',
        self::SUCCESS_SEND => '发送成功',
        self::SUCCESS_PUBLISH => '发布成功',
        self::SUCCESS_FORWARD => '转发成功',
        self::SUCCESS_SHARE => '分享成功',
        self::SUCCESS_SUBMIT => '提交成功',
        self::SUCCESS_REMOVE => '移除成功',
        self::SUCCESS_ADD => '添加成功',
        self::SUCCESS_TOP => '置顶成功',
        self::SUCCESS_QUESTION => '提问成功',

    );
    //通用信息定义

    public static $custom_msg = array(
        self::GROUP_CREATE => '您当前等级只支持创建${1}人群聊',
        self::GROUP_INVITE => '最多还能添加${1}成员',
    );

    /**
     * get error msg by defined code
     * @param $code
     * @return string
     */
    public static function getErrorMsg($code)
    {
        return isset(self::$errmsg[$code]) ? self::$errmsg[$code] : '';
    }

    /**
     * get success msg by defined code
     * @param $code
     * @return string
     */
    public static function getSuccessMsg($code)
    {
        return isset(self::$success_msg[$code]) ? self::$success_msg[$code] : '';
    }

    /**
     * get custom msg by defined code
     * @return string
     */
    public static function getCustomMsg($data)
    {
        $msg = isset(self::$custom_msg[$data[0]]) ? self::$custom_msg[$data[0]] : '';
        $msg && count($data) >= 2 && parent::compileTemplate($msg, $data);
        return $msg;
    }
}

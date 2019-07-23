<?php

/**
 *
 * 繁体中文【香港】
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/7
 * Time: 9:15
 */
namespace Language\Api;

use Util\Ajax;

class Zh_hk extends Ajax
{
    //错误信息定义
    public static $errmsg = array(
        self::ERROR_TOKEN_INVALID => 'token無效',
        self::ERROR_TOKEN_EXPIRES => 'token未獲取或已過期',
        self::ERROR_USER_IS_NOT_EXISTS => '用戶不存在',
        self::ERROR_USER_HAS_NOT_LOGIN => '用戶尚未登陸',
        self::ERROR_USER_HAS_NO_PERMISSION => '用戶沒有權限',
        self::ERROR_USER_HAS_BEING_USED => '本手機號已註冊',
        self::ERROR_USER_IS_INVALID => '用戶名格式不正確',
        self::ERROR_USER_BIND_FAILED => '用戶綁定失敗',
        self::ERROR_PASSWD_IS_INVALID => '密碼長度為6-16位字符之間',
        self::ERROR_PASSWD_IS_NOT_CORRECT => '密碼不正確',
        self::ERROR_EMAIL_IS_INVALID => '郵箱格式不正確',
        self::ERROR_EMAIL_HAS_BEING_USED => '郵箱已被使用了',
        self::ERROR_YOU_HAS_LIKED => '妳已經贊過了',
        self::ERROR_YOU_HASNT_LIKE => '妳還沒贊過',
        self::ERROR_RUN_TIME_ERROR_OCCURRED => '服務器錯誤',
        self::ERROR_PHONE_IS_INVALID => '非法的手機號',
        self::ERROR_PHONE_HAS_BEING_USED => '本機號已註冊',
        self::ERROR_USER_IS_NOT_ACTIVE => '用戶未激活',
        self::ERROR_DATA_NOT_EXISTS => '數據不存在',
        self::ERROR_DATA_HAS_EXISTS => '數據已經存在',
        self::ERROR_USER_HAS_BEING_LOCKED => '用戶已被鎖定',
        self::ERROR_USER_HAS_BEING_LOCKED_MANUAL => '用戶已被鎖定',
        self::ERROR_USER_DELETED => '賬號已被禁用',
        self::ERROR_ACCOUNT_IS_NOT_EXISTS => '賬號不存在',
        self::ERROR_PHONE_EXIST => "該手機號已被註冊,如果妳忘記了密碼,請嘗試找回密碼",
        self::ERROR_SIGN => "簽名驗證失敗",
        self::ERROR_VERIFY_CODE => "驗證碼錯誤",
        self::ERROR_SEND_PHONE_CODE_TOO_FREQUENCY => "發送手機短信太過頻繁",
        self::ERROR_SEND_BYOND_ONE => "壹分鐘內只能請求壹次",
        self::ERROR_VERIFY_CODE_OLD => "驗證碼未發送或者已過期，請重新發送",
        self::ERROR_USERNAME_PREG => '昵稱必須為2-8字符之間',
        self::ERROR_USER_OR_PASSWORD => '手機或密碼錯誤',
        self::ERROR_GROUP_MEMBER_LIMIT => '群聊成員數已達上限',
        self::ERROR_GROUP_MEMBER_EXIST => '妳已經是群成員',
        self::ERROR_GROUP_MEMBER_NOT_ADMIN => '妳不是群主',
        self::ERROR_GROUP_MEMBER_ADMIN => '群主無法退出，妳可以轉讓權限後退出或者解散群',
        self::ERROR_GROUP_NOT_MEMBER => '妳還不是群成員',
        self::ERROR_GROUP_MEMBER_LIMIT_200 => '壹次最多只能添加200個成員',
        self::ERROR_HANDLE_NOT_GROUP_MEMBER => '操作的對象不是群成員',
        self::ERROR_CANNOT_ATTENTION_SELF => '自己不能關註自己',
        self::ERROR_HAS_ATTENTION => "妳已經關註過他了",
        self::ERROR_IN_BLACKLIST => "對方已經在妳的黑名單列表中",
        self::ERROR_REFUSE_YOU_REQUEST => "對方拒絕了妳的請求",
        self::ERROR_REPORT_HAS_SENT => "請勿重復提交舉報",
        self::ERROR_SEND_VERIFY_CODE => "哇哦,短信發送出了點問題",
        self::ERROR_NOT_FRIEND => "妳們還不是好友",
        self::ERROR_ALREADY_CONTACT_MEMBER => "妳們已經是聯系人了",
        self::ERROR_COIN_NOT_ENOUGH => "龍幣不足",
        self::ERROR_HAS_NO_PRIVILEGE_DISCUSS => "主人設置了訪問權限",
        self::ERROR_REQUEST_FREQUENCY => "請求太過頻繁",
        self::ERROR_SUBMIT_REPEAT => "請勿重復提交申請",
        self::ERROR_LOGIN_DEVICE => "您已開啟設備登錄保護，需要進行手機驗證",
        self::ERROR_SYSTEM_BLACKLIST => "該用戶為系統黑名單用戶",
        self::ERROR_INVITOR_NOT_GROUP_MEMBER => "邀請者不是群成員",
        self::ERROR_LOW_APP_VERSION => "App版本過低",
        self::ERROR_USER_HAS_NO_OPEN_AVATAR => '無第三方頭像',
        self::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH => '目標成員權限不足',
        self::ERROR_GIFT_OFF => '該禮物已下架',
        self::ERROR_GIFT_BEYOND_LIMIT => '該禮物的擁有數量已超出限制',
        self::ERROR_TARGET_CAN_NOT_YOURSELF => '目標用戶不能是自己',
        self::ERROR_EMPTY_BIND_PHONE => "請先綁定手機號",
        self::ERROR_EMPTY_BIRTHDAY => "請先完善生日信息",
        self::ERROR_SHOW_DISABLE => "對方已關閉秀場",
        self::ERROR_SHOW_HAS_LIKE => "妳已經贊過了",
        self::ERROR_SHOW_HAS_DISLIKE => "妳已經踩過了",
        self::ERROR_DELETED_BY_SYSTEM => "已被系統屏蔽",
        self::ERROR_DELETED_BY_USER => "已被用戶刪除",
        self::ERROR_DELETED_NOT_EXPIRE => "視頻問答視頻上傳2小時內可重新錄製,14天后方可刪除",

        self::ERROR_SHOP_HAS_EXISTS => "請勿重復添加店鋪",
        self::ERROR_IP_ABNORMAL => '請求異常',
        self::ERROR_MEMBER_NOT_JOIN => '禁止加入群聊',
        self::ERROR_SHOP_HAS_BEEN_SHIELD => '店鋪已被系統屏蔽',
        self::ERROR_SHOP_HAS_BEEN_DELETED => '店鋪已被店主關閉',
        self::ERROR_MEMBER_IS_ADMIN => '對方已經是群管理員',
        self::ERROR_PACKAGE_BEYOND_LIMIT => '今日紅包領取次數已達上限',
        self::ERROR_NO_AUTH => '請先完成實名認證',
        self::ERROR_OAUTH2_FAIL_GET_VERIFY_TOKEN => '請先完成實名認證',
        self::ERROR_INVITER_HAS_INVITER => '妳已經設置過推薦人了',
        self::ERROR_INVITER_IS_LOWER => '推薦人不能為下級用戶',
        self::ERROR_ID_CARD_HAS_BEEN_USED => '該身份證已被使用',
        self::ERROR_DRAGON_COIN_NOT_ENOUGH => '龍幣不足',
        self::ERROR_MONEY_NOT_ENOUGH => '余額不足',
        self::ERROR_NICK_HAS_BEEN_USED => '該用戶昵稱已被使用',
        self::ERROR_NICK_UPDATE_ONLY_ONCE => '用戶昵稱壹年只能修改壹次',
        self::ERROR_OWN_ONE_COMMUNITY => '每個用戶只能創建壹個社區',
        self::ERROR_COMMUNITY_NAME_UNIQUE => '相同名字的社區已經存在',
        self::ERROR_COMMUNITY_NOT_MEMBER => '對方還不是社區成員',
        self::ERROR_COMMUNITY_IS_MANAGER => '對方已經是社區管理員',
        self::ERROR_COMMUNITY_MANAGER_HAS_GROUP_NO_TRANSFER => '該管理員還有群未轉讓',
        self::ERROR_COMMUNITY_GROUP_LIMIT => '妳的社群個數已達上限',
        self::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS => '相同名字的社群已經存在',
        self::ERROR_COMMUNITY_GROUP_MEMBER_UNSUBSCRIBE => '社群成員不能取消關註',
        self::ERROR_COMMUNITY_OWNER_ADMIN_UNSUBSCRIBE => '區主或社區管理員不能取消關註',
        self::ERROR_COMMUNITY_PRIVATE_GROUP_NOT_JOIN => '非公開群禁止主動加入',
        self::ERROR_COMMUNITY_DISCUSS_NEED_ADMIN => '僅管理員能發布',
        self::ERROR_COMMUNITY_DISCUSS_NEED_OWNER => '僅區主能操作',
        self::ERROR_COMMUNITY_CHECKING => '申請正在審核中，請勿重復提交',
        self::ERROR_COMMUNITY_APPLY_LIMIT => '今日提交次數已達上限',
        self::ERROR_COMMUNITY_APPLY_HAS_BEEN_HANDLE => '申請已被處理',


        self::FAIL_REGISTER => "註冊失敗",
        self::FAIL_LOGIN => "登錄失敗",
        self::FAIL_ADD_GROUP => "群聊創建失敗",
        self::FAIL_JOIN_GROUP => "加入群聊失敗",
        self::FAIL_DISSOLVE_GROUP => "群聊解散失敗",
        self::FAIL_LEAVE_GROUP => "退出群聊失敗",
        self::FAIL_INVITE_GROUP => "邀請失敗",
        self::FAIL_TRANSFER_GROUP => "轉讓失敗",
        self::FAIL_KICK_MEMBER => "刪除失敗",
        self::FAIL_EDIT => "編輯失敗",
        self::FAIL_HANDLE => "操作失敗",
        self::FAIL_LOCATION => "位置上報失敗",
        self::FAIL_SEND => "請求發送失敗",
        self::FAIL_PUBLISH => "發布失敗",
        self::FAIL_DELETE => "刪除失敗",
        self::FAIL_FORWARD => "轉發失敗",
        self::FAIL_SHARE => '分享失敗',
        self::FAIL_SUBMIT => "提交失敗",
        self::FAIL_ADD => "添加失敗",
        self::FAIL_TOP => '置頂失敗',
        self::FAIL_CANCEL => '取消失敗',
        self::FAIL_CHARGE => '充值失敗',
        self::FAIL_QUESTION => '提問失敗',
        self::FAIL_PAY => '支付失敗',
        self::FAIL_PICK => '领取失敗',
        self::FAIL_PICK_HAS_REACHED_LIMIT => '已達領取上限',
        self::FAIL_GET_SHOP_CATEGORY => '獲取店鋪分類失敗',


        self::INVALID_SIGN => "無效的簽名",
        self::INVALID_PARAM => "無效的參數",
        self::INVALID_PHONE => "無效的手機號",
        self::INVALID_LOGIN_TYPE => "無效的登錄類型",
        self::INVALID_MSG_TYPE => "無效的短信類型",
        self::INVALID_ID_CARD_TYPE => "無效的身份證號",
        self::INVALID_WEBSITE => "無效的網址",
        self::INVALID_REQUEST => "無效的請求",
        self::INVALID_REQUEST => "手機時間異常，請同步網絡時間。",
        self::INVALID_INVITE_CODE => "無效的邀請碼",

    );
    //成功信息定义
    public static $success_msg = array(
        self::SUCCESS_REGISTER => '註冊成功',
        self::SUCCESS_JOIN => '加入成功',
        self::SUCCESS_DISSOLVE => '解散成功',
        self::SUCCESS_LEAVE => '退出成功',
        self::SUCCESS_INVITE => '邀請成功',
        self::SUCCESS_TRANSFER => '轉讓成功',
        self::SUCCESS_DELETE => '刪除成功',
        self::SUCCESS_EDIT => '編輯成功',
        self::SUCCESS_ATTENTION => '關註成功',
        self::SUCCESS_CANCEL => '取消成功',
        self::SUCCESS_REPORT => '舉報成功',
        self::SUCCESS_HANDLE => '操作成功',
        self::SUCCESS_SEND => '發送成功',
        self::SUCCESS_PUBLISH => "發布成功",
        self::SUCCESS_FORWARD => '轉發成功',
        self::SUCCESS_SHARE => '分享成功',
        self::SUCCESS_SUBMIT => '提交成功',
        self::SUCCESS_REMOVE => '移除成功',
        self::SUCCESS_ADD => '添加成功',
        self::SUCCESS_TOP => '置頂成功',
        self::SUCCESS_QUESTION => '提問成功',

    );
    //通用信息定义
    public static $custom_msg = array(
        self::GROUP_CREATE => '您當前等級只支持創建${1}人群聊',
        self::GROUP_INVITE => '最多還能添加${1}個才成員',
    );

    /**
     * get error msg by defined code
     * @param $code
     * @return string
     */
    public static function getErrorMsg($code)
    {
        return isset($code) && isset(self::$errmsg[$code]) ? self::$errmsg[$code] : '';
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
        $msg && parent::compileTemplate($msg, $data);
        return $msg;
    }
}

<?php

/**
 *
 * 英文
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/7
 * Time: 9:15
 */
namespace Language\Api;

use Util\Ajax;

class En extends Ajax
{
    //错误信息定义
    public static $errmsg = array(
        self::ERROR_TOKEN_INVALID => 'invalid token',
        self::ERROR_TOKEN_EXPIRES => 'token has expired ',
        self::ERROR_USER_IS_NOT_EXISTS => 'User does not exist',
        self::ERROR_USER_HAS_NOT_LOGIN => 'User is not logged in',
        self::ERROR_USER_HAS_NO_PERMISSION => 'User does not have permission',
        self::ERROR_USER_HAS_BEING_USED => 'The phone number is already registered',
        self::ERROR_USER_IS_INVALID => 'Username format is incorrect',
        self::ERROR_USER_BIND_FAILED => 'bind failed',
        self::ERROR_PASSWD_IS_INVALID => 'Password length of 6 to 16 characters',
        self::ERROR_PASSWD_IS_NOT_CORRECT => 'Incorrect password',
        self::ERROR_EMAIL_IS_INVALID => 'E-mail format is incorrect',
        self::ERROR_EMAIL_HAS_BEING_USED => 'The mailbox is already in use',
        self::ERROR_YOU_HAS_LIKED => 'You have already praised the point',
        self::ERROR_YOU_HASNT_LIKE => 'You have not had any praise',
        self::ERROR_RUN_TIME_ERROR_OCCURRED => 'Server Error',
        self::ERROR_PHONE_IS_INVALID => 'Illegal mobile phone number',
        self::ERROR_PHONE_HAS_BEING_USED => 'Phone number has been registered',
        self::ERROR_USER_IS_NOT_ACTIVE => 'User is not activated',
        self::ERROR_DATA_NOT_EXISTS => 'Data does not exist',
        self::ERROR_DATA_HAS_EXISTS => 'The data already exists',
        self::ERROR_USER_HAS_BEING_LOCKED => 'User is locked',
        self::ERROR_USER_HAS_BEING_LOCKED_MANUAL => 'User is locked',
        self::ERROR_USER_DELETED => 'Account has been disabled',
        self::ERROR_ACCOUNT_IS_NOT_EXISTS => 'Account does not exist',
        self::ERROR_PHONE_EXIST => "The phone has been registered, if you forget the password, try to retrieve the password",
        self::ERROR_SIGN => "Signature verification failed",
        self::ERROR_VERIFY_CODE => "Verification code error",
        self::ERROR_SEND_PHONE_CODE_TOO_FREQUENCY => "Send SMS too often",
        self::ERROR_SEND_BYOND_ONE => "Requests can only be made once per minute",
        self::ERROR_VERIFY_CODE_OLD => "The verification code was not sent or has expired. Please resend it",
        self::ERROR_USERNAME_PREG => 'The nickname must be between 2 and 8 characters',
        self::ERROR_USER_OR_PASSWORD => 'Phone or password is wrong',
        self::ERROR_GROUP_MEMBER_LIMIT => 'The maximum number of group members has been reached',
        self::ERROR_GROUP_MEMBER_EXIST => 'You are already a member of the group',
        self::ERROR_GROUP_MEMBER_NOT_ADMIN => 'You are not an owner',
        self::ERROR_GROUP_MEMBER_ADMIN => 'The group owner can not quit, you can transfer the authority to exit or dissolution group',
        self::ERROR_GROUP_NOT_MEMBER => 'You are not a member of the group',
        self::ERROR_GROUP_MEMBER_LIMIT_200 => 'A maximum of 200 members can be added at a time',
        self::ERROR_HANDLE_NOT_GROUP_MEMBER => 'The members of the operation are not members of the group',
        self::ERROR_CANNOT_ATTENTION_SELF => 'Can not focus on yourself',
        self::ERROR_HAS_ATTENTION => "You have paid attention to him",
        self::ERROR_IN_BLACKLIST => "The other party is already in your blacklist",
        self::ERROR_REFUSE_YOU_REQUEST => "The other party refused your request",
        self::ERROR_REPORT_HAS_SENT => "do not resubmit your report",
        self::ERROR_SEND_VERIFY_CODE => "wow,some problem has happened",
        self::ERROR_NOT_FRIEND => "you are not friends",
        self::ERROR_ALREADY_CONTACT_MEMBER => "you are already contact",
        self::ERROR_COIN_NOT_ENOUGH => "coin not enough",
        self::ERROR_COIN_NOT_ENOUGH => "you have no privilege to look them",
        self::ERROR_REQUEST_FREQUENCY => "request too frequency",
        self::ERROR_SUBMIT_REPEAT => "do not resubmit your request",
        self::ERROR_LOGIN_DEVICE => "you have turned on device sign-in protection and need to verify your phone",
        self::ERROR_SYSTEM_BLACKLIST => "the user has been system blacklist",
        self::ERROR_INVITOR_NOT_GROUP_MEMBER => "the invitor not group member",
        self::ERROR_LOW_APP_VERSION => "low app version",
        self::ERROR_USER_HAS_NO_OPEN_AVATAR => 'has no avatar',
        self::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH => 'member has no enough privilege',
        self::ERROR_GIFT_OFF => 'The gift has been removed',
        self::ERROR_GIFT_BEYOND_LIMIT => 'The gift has been beyond limit',
        self::ERROR_TARGET_CAN_NOT_YOURSELF => 'target can not yourself',
        self::ERROR_EMPTY_BIND_PHONE => "please bind your phone first",
        self::ERROR_EMPTY_BIRTHDAY => "please finish your birthday info first",
        self::ERROR_SHOW_DISABLE => "the show is disable",
        self::ERROR_SHOW_HAS_LIKE => "you has liked",
        self::ERROR_SHOW_HAS_DISLIKE => "you has disliked",
        self::ERROR_DELETED_BY_SYSTEM => "has been deleted by system",
        self::ERROR_DELETED_BY_USER => "has been deleted by user",
        self::ERROR_DELETED_NOT_EXPIRE => "This video can be deleted after 14 days",
        self::ERROR_SHOP_HAS_EXISTS => "don't add same shop",
        self::ERROR_MEMBER_NOT_JOIN => 'forbidden join',
        self::ERROR_SHOP_HAS_BEEN_SHIELD => 'shop has been shielded',
        self::ERROR_SHOP_HAS_BEEN_DELETED => 'shop has been closed',
        self::ERROR_MEMBER_IS_ADMIN => 'target is admin',
        self::ERROR_PACKAGE_BEYOND_LIMIT => 'The number of red packets received today has reached an upper limit',
        self::ERROR_NO_AUTH => 'Please complete the real-name authentication first',
        self::ERROR_INVITER_HAS_INVITER => 'you has inviter',
        self::ERROR_INVITER_IS_LOWER => 'inviter can not be your lower',
        self::ERROR_ID_CARD_HAS_BEEN_USED => 'this id card has been used',
        self::ERROR_DRAGON_COIN_NOT_ENOUGH => 'your coin not enough',
        self::ERROR_MONEY_NOT_ENOUGH => 'your money not enough',
        self::ERROR_NICK_HAS_BEEN_USED => 'nickname has been used',
        self::ERROR_NICK_UPDATE_ONLY_ONCE => 'nickname can only be modified once one year',
        self::ERROR_OWN_ONE_COMMUNITY => 'everyone can only create one community',
        self::ERROR_COMMUNITY_NAME_UNIQUE => 'same name community has exist',
        self::ERROR_COMMUNITY_NOT_MEMBER => 'the other party is not a member of the community',
        self::ERROR_COMMUNITY_IS_MANAGER => 'the other party is manager of the community',
        self::ERROR_COMMUNITY_MANAGER_HAS_GROUP_NO_TRANSFER => 'the manager has group not transferred',
        self::ERROR_COMMUNITY_GROUP_LIMIT => 'you have reached the maximum number of community group',
        self::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS => 'same group  has exists in this community',
        self::ERROR_COMMUNITY_GROUP_MEMBER_UNSUBSCRIBE => 'community member cannot  unsubscribe',
        self::ERROR_COMMUNITY_OWNER_ADMIN_UNSUBSCRIBE => 'community owner or manager cannot unsubscribe',
        self::ERROR_COMMUNITY_PRIVATE_GROUP_NOT_JOIN => 'private group are prohibited from actively joining',
        self::ERROR_COMMUNITY_DISCUSS_NEED_ADMIN => 'only admin can publish',
        self::ERROR_COMMUNITY_DISCUSS_NEED_OWNER => 'only community owner can handle',
        self::ERROR_COMMUNITY_CHECKING => 'your apply is checking，do not repeat apply',
        self::ERROR_COMMUNITY_APPLY_LIMIT => "today's submission has reached the limit",
        self::ERROR_COMMUNITY_APPLY_HAS_BEEN_HANDLE => 'submission has been handed',


        self::FAIL_REGISTER => "registration failed",
        self::FAIL_LOGIN => "login failed",
        self::FAIL_ADD_GROUP => "failed to create group chat",
        self::FAIL_JOIN_GROUP => "join group chat failed",
        self::FAIL_DISSOLVE_GROUP => "group chat dismiss failed",
        self::FAIL_LEAVE_GROUP => "quitting Group Chat failed",
        self::FAIL_INVITE_GROUP => "the invitation failed",
        self::FAIL_TRANSFER_GROUP => "transfer failed",
        self::FAIL_KICK_MEMBER => "delete failed",
        self::FAIL_EDIT => "edit failed",
        self::FAIL_HANDLE => "operation failed",
        self::FAIL_LOCATION => "location report failed",
        self::FAIL_SEND => "send failed",
        self::FAIL_PUBLISH => "publish failed",
        self::FAIL_DELETE => "delete failed",
        self::FAIL_FORWARD => "forward failed",
        self::FAIL_SHARE => 'share failed',
        self::FAIL_SUBMIT => "submit failed",
        self::FAIL_ADD => "add failed",
        self::FAIL_TOP => 'top failed',
        self::FAIL_CANCEL => 'cancel failed',
        self::FAIL_CHARGE => 'charge failed',
        self::FAIL_QUESTION => 'question failed',
        self::ERROR_IP_ABNORMAL => 'abnormal request',
        self::FAIL_PAY => 'pay fail',
        self::FAIL_PICK => 'pick fail',
        self::FAIL_PICK_HAS_REACHED_LIMIT => 'has reached its limit per day',
        self::FAIL_GET_SHOP_CATEGORY => 'fail to get the category of shop ',

        self::INVALID_SIGN => "invalid signature",
        self::INVALID_PARAM => "invalid parameter",
        self::INVALID_PHONE => "invalid mobile phone number",
        self::INVALID_LOGIN_TYPE => "invalid login type",
        self::INVALID_MSG_TYPE => "invalid message type",
        self::INVALID_ID_CARD_TYPE => "invalid id card",
        self::INVALID_WEBSITE => "invalid website",
        self::INVALID_REQUEST => "invalid request",
        self::INVALID_TIMESTAMP => "Mobile time is abnormal，Please synchronize network time。",
        self::INVALID_INVITE_CODE => "invalid invite code",

    );
    //成功信息定义
    public static $success_msg = array(
        self::SUCCESS_REGISTER => 'registration success',
        self::SUCCESS_JOIN => 'join success',
        self::SUCCESS_DISSOLVE => 'dismiss success',
        self::SUCCESS_LEAVE => 'quit success',
        self::SUCCESS_INVITE => 'invite success',
        self::SUCCESS_TRANSFER => 'transfer success',
        self::SUCCESS_DELETE => 'delete success',
        self::SUCCESS_EDIT => 'edit success',
        self::SUCCESS_ATTENTION => 'focus on success',
        self::SUCCESS_CANCEL => 'cancel success',
        self::SUCCESS_REPORT => 'report success',
        self::SUCCESS_HANDLE => 'successful operation',
        self::SUCCESS_SEND => 'send success',
        self::SUCCESS_PUBLISH => "publish success",
        self::SUCCESS_FORWARD => 'forward success',
        self::SUCCESS_SHARE => 'share success',
        self::SUCCESS_SUBMIT => 'submit success',
        self::SUCCESS_REMOVE => 'remove success',
        self::SUCCESS_ADD => 'add success',
        self::SUCCESS_TOP => 'top success',
        self::SUCCESS_QUESTION => 'question success',

    );
    //通用信息定义
    public static $custom_msg = array(
        self::GROUP_CREATE => 'Your current level only supports creating ${1}-person group chat',
        self::GROUP_INVITE => 'Up to ${1} additional members can be added',
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

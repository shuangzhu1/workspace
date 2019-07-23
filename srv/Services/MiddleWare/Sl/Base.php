<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/1
 * Time: 10:34
 */

    namespace Services\MiddleWare\Sl;


class Base
{
    public static $system_money_account = 15; //公账账号id
    public static $gift_money_account = 16; //礼物资金账号

    protected static $baseUrl = 'http://service.klgwl.com/';//基础地址
    protected static $testBaseUrl = 'http://120.78.182.253:80/';//测试地址基础地址

    const NOTIFY_SEARCH = 'klgsearch/operate';// 搜索引擎更新状态
    const OPEN_ACCOUNT = 'wallet/account/open';// 开通钱包功能
    const CASH_REWARD = 'wallet/balance/transfer';// 现金奖励
    const RELATION_MAKE = 'promote/relation/make';// 全民推广 设置推荐人
    const RELATION_ACTIVATE = 'promote/user/activate';// 全民推广 激活 给上级用户返利
    const SKILL_APPLY_LIST = 'rent/user/seller/skill/apply/list';// 技能申请
    const SKILL_CONFIG = 'rent/config/check';// 技能配置详情
    const SKILL_CONFIG_UPDATE_BASIC = 'rent/config/update/basic';// 技能配置更新
    const SKILL_CONFIG_UPDATE_SUBTYPE = 'rent/config/update/skill/subtype';// 技能基本配置更新-子技能
    const SKILL_CONFIG_UPDATE_TYPE = 'rent/config/update/skill/type';// 更新顶级技能
    const SKILL_CONFIG_MOVE_SUBTYPE = 'rent/config/move/skill/subtype';// 子技能移动
    const SKILL_DELETE = 'rent/user/seller/skill/delete';// 删除技能


    const SKILL_APPLY_CHECK = 'rent/user/seller/skill/confirm';// 技能审核
    const SKILL_SERVICE = 'rent/user/seller/skill';// 用户技能列表
    const SKILL_LIST = 'rent/source/skill/list';// 技能服务列表

    const USER_INFO_UPDATE = 'rent/callback/userinfo/update';// 用户信息更新
    const HTQ_TASK = 'htq/task';//异步任务
    const SEND_RED_PACKAGE = 'redbag/newbag';//发红包
    const SEND_TEST = 'rent/source/city/list';//测试
    const PACKAGE_DETAIL = 'redbag/detail';//红包详情
    const PACKAGE_STATUS = 'redbag/status';//红包状态
    const PACKAGE_PICK = 'redbag/grabbag';//抢红包
    const PACKAGE_IS_CAN_PICK = 'redbag/cache/info';//查询红包是否可抢
    const PACKAGE_PICKER = 'redbag/resultlist';//查询红包被抢记录
    const PACKAGE_VIRTUAL_COIN = 'virtualCoin/remain';//虚拟币余额
    const PACKAGE_CONFIG = 'redbag/config/check';//红包配置
    const WALLET_BALANCE_TRANSFER = 'wallet/balance/transfer';//余额转账/充值
    const WALLET_BALANCE_CONSUME = 'wallet/balance/consume';//余额消费
    const VIRTUAL_COIN_RECORDS = 'virtualCoin/records';//虚拟币充值记录
    const AUTH_DETAIL = 'rent/user/authentication/check';//实名认证详情
    const VIRTUAL_COIN_UPDATE = 'virtualCoin/update';//虚拟币更新
    const ACTIVITY_REWARD_CONFIG = 'activity/config/update/squareredbag/rewardnum';//活动奖励配置

    const REDBAG_CONFIG_PROVINCE_CITY = 'redbag/config/province_city';//推广红包城市列表

    protected static $sign_type = 'MD5';

    const topic_uums_update = 'uums_update'; #用户主题#
    const topic_discuss_weight_change = 'dis_item_weight'; #用户主题#
    const topic_behavior_statis = 'behavior_statis'; #行为抄送#

}
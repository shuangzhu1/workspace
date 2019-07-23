<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/23
 * Time: 19:55
 */

namespace Services\Admin;


use Models\Admin\AdminLogs;
use Models\Admin\Admins;
use Phalcon\Mvc\User\Plugin;


class AdminLog extends Plugin
{
    private static $instance = null;
    const TYPE_DISCUSS = 'discuss'; //动态
    const TYPE_USER = 'user'; //用户
    const TYPE_GROUP = 'group'; //群聊
    const TYPE_AUTH = 'user_auth';//用户认证
    const TYPE_MSG_TEMPLATE = 'msg_template';//短信消息模板
    const TYPE_SYS_MSG_TEMPLATE = 'sys_msg_template';//系统消息模板
    const TYPE_LOGIN = 'login';//登录
    const TYPE_TAGS = 'tags';//标签相关
    const TYPE_INDUSTRY = 'industry';//行业
    const TYPE_REPORT_REASON = 'report_reason';//举报原因
    const TYPE_REPORT = 'report';//举报
    const TYPE_FEEDBACK = 'user_feedback';//反馈
    const TYPE_PORN = 'porn';//鉴黄
    const TYPE_COMMENT = 'comment';//评论
    const TYPE_ADMIN = 'admin';//后台账号操作
    const TYPE_MENU = 'menu';//后台菜单
    const TYPE_ADS = 'ads';//广告操作
    const TYPE_ARTICLE = 'article';//文章操作
    const TYPE_GIFT = 'gift';//礼物操作
    const TYPE_GAME = 'game';//游戏操作
    const TYPE_APP_SETTING = 'app_setting';//app设置
    const TYPE_SHOW = 'show';//秀场
    const TYPE_RENT = 'show';//租人业务
    const TYPE_MUSIC = 'music';//音乐相关
    const TYPE_SHOP = 'shop';//商铺管理
    const TYPE_GOOD = 'good';//商品管理
    const TYPE_VIRTUAL_VIDEO = 'virtual_video';//虚拟视频
    const TYPE_SQUARE_PACKAGE = 'square_package';//红包广场
    const TYPE_DIAMOND = 'diamond';//龙钻操作
    const TYPE_AGENT = 'agent';//合伙人
    const TYPE_COMMUNITY = 'community';//社区


    const TYPE_VERSION = 'version';//app版本操作

    const STATUS_DELETED = 0;//被删除
    const STATUS_NORMAL = 1;//正常
    const STATUS_LOCKED = 2;//被禁用


    public static $type_name = [
        self::TYPE_AUTH => '用户认证操作',
        self::TYPE_USER => '用户操作',
        self::TYPE_GROUP => '群聊相关操作',
        self::TYPE_DISCUSS => '动态操作',
        self::TYPE_MSG_TEMPLATE => '短信模板操作',
        self::TYPE_SYS_MSG_TEMPLATE => '系统消息模板操作',
        self::TYPE_LOGIN => '登录',
        self::TYPE_TAGS => '标签操作',
        self::TYPE_INDUSTRY => '行业操作',
        self::TYPE_REPORT_REASON => '举报原因操作',
        self::TYPE_REPORT => '举报',
        self::TYPE_FEEDBACK => '用户意见反馈',
        self::TYPE_PORN => '鉴黄处理',
        self::TYPE_COMMENT => '评论',
        self::TYPE_ADMIN => '后台账号操作',
        self::TYPE_MENU => '后台菜单操作',
        self::TYPE_ADS => '广告操作',
        self::TYPE_ARTICLE => '文章操作',
        self::TYPE_GIFT => '礼物操作',
        self::TYPE_GAME => '游戏操作',
        self::TYPE_APP_SETTING => 'app设置',
        self::TYPE_RENT => '租人业务',
        self::TYPE_MUSIC => '音乐管理',
        self::TYPE_SHOP => '商铺管理',
        self::TYPE_GOOD => '商品管理',
        self::TYPE_VERSION => 'app版本操作',
        self::TYPE_SQUARE_PACKAGE => '红包广场操作',
        self::TYPE_DIAMOND => '龙钻操作',
        self::TYPE_AGENT => '合伙人操作',
        self::TYPE_COMMUNITY => '社区操作',

    ];

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**添加日志
     * @param string $param
     * @param string $action
     * @param string $type
     * @param string $item_id
     * @param array $data
     */
    public function add($action = '', $type, $item_id, $param = '', $data = [])
    {
        $data = array(
            'uid' => $this->session->get('admin')['id'],
            'user_name' => $this->session->get('admin')['name'],
            'api' => $this->request->getURI(),
            'param' => json_encode($param, JSON_UNESCAPED_UNICODE),
            'action' => $action,
            'type' => $type,
            'item_id' => $item_id,
            'created' => time(),
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $log = new AdminLogs();
        $log->insertOne($data);
        unset($param);
    }

    /*获取相关日志*/

    public function getLogs($type, $item_id)
    {
        $logs = AdminLogs::findList(['type="' . $type . '" and  LOCATE("' . $item_id . ',",concat(item_id,","))>0', 'order' => 'created desc']);
        if ($logs) {
            $uids = array_column($logs, 'uid');
            $admins = Admins::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'id,name'], 'id');
            foreach ($logs as &$item) {
                $item['admin_info'] = isset($admins[$item['uid']]) ? $admins[$item['uid']] : [];
            }
        }
        return $logs;
    }
}
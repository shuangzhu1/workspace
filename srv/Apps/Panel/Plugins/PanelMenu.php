<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 5/14/14
 * Time: 10:50 AM
 */

namespace Multiple\Panel\Plugins;

// 说明
// 分别在shop和panel里面请controller+action不要重复
use Phalcon\Mvc\User\Component;

class PanelMenu extends Component
{
    protected static $_sideFstMenu = array(
        'sys' => array('name' => '系统设置', 'icon' => 'icon-cog', 'role' => [1]),
        'web' => array('name' => '内容管理', 'icon' => 'icon-adjust', 'role' => [1, 4, 5]),
        'shop' => array('name' => '商城管理', 'icon' => 'icon-bell', 'role' => [1, 2, 3]),
        'user' => array('name' => '用户管理', 'icon' => 'icon-lightbulb', 'role' => [1, 2, 3, 4, 5]),
        'forum' => array('name' => '社区管理', 'icon' => 'icon-qrcode', 'role' => [1, 4, 5]),
        'func' => array('name' => '营销管理', 'icon' => 'icon-cog', 'role' => [1, 2, 3]),
    );

    protected static $_sideSecMenu = array(
        array('fst' => 'info', 'name' => '企业资料', 'icon' => 'icon-laptop', 'c' => 'setting', 'a' => 'index', 'role' => [1, 2]),
        array('fst' => 'info', 'name' => '修改密码', 'icon' => 'icon-laptop', 'c' => 'setting', 'a' => 'password', 'role' => [1, 2, 3, 4, 5]),
        array('fst' => 'sys', 'name' => '账号管理', 'icon' => 'icon-laptop', 'c' => 'setting', 'a' => 'admin', 'role' => [1]),
        array('fst' => 'sys', 'name' => '支付方式', 'icon' => 'icon-laptop', 'c' => 'payment', 'a' => 'payment', 'role' => [1]),

        'pc_setting' => array('fst' => 'sys', 'name' => '网站设置', 'icon' => 'icon-laptop', 'c' => 'store', 'a' => 'index', 'role' => [1]),
        'wx_setting' => array('fst' => 'sys', 'name' => '微信设置', 'icon' => 'icon-laptop', 'c' => 'site', 'a' => 'info', 'role' => [1]),
        'shop_freight' => array('fst' => 'sys', 'noFree' => true, 'icon' => 'icon-laptop', 'name' => '物流运费', 'icon' => 'icon-lightbulb', 'c' => 'freight', 'a' => 'list', 'role' => [1]),
        'point_rule' => array('fst' => 'sys', 'name' => '积分规则', 'icon' => 'icon-laptop', 'c' => 'users', 'a' => 'rules', 'icon' => "icon-asterisk", 'role' => [1]),
        'user_group' => array('fst' => 'sys', 'name' => '用户分组', 'icon' => 'icon-screenshot', 'c' => 'users', 'a' => 'group', 'role' => [1]),
        'wechat' => array('fst' => 'sys', 'name' => '微信接入', 'icon' => 'icon-folder-open-alt', 'c' => 'wechat', 'a' => 'binding', 'role' => [1]),

//        'web_info' => array('fst' => 'web', 'name' => '公司信息', 'icon' => 'icon-laptop', 'c' => 'site', 'a' => 'page'),
        'web_wiki' => array('fst' => 'web', 'name' => '帮助文档', 'icon' => 'icon-laptop', 'c' => 'wiki', 'a' => 'list', 'role' => [1, 4, 5]),
        'web_content' => array('fst' => 'web', 'name' => '微信资讯', 'icon' => 'icon-laptop', 'c' => 'article', 'a' => 'list', 'role' => [1, 4, 5]),
        //  'web_tpl' => array('fst' => 'web', 'name' => '微站模板', 'icon' => 'icon-laptop', 'c' => 'tpl', 'a' => 'index'),
        // 商城设置
        'shop_attr' => array('fst' => 'shop', 'name' => '类目属性', 'icon' => 'icon-laptop', 'c' => 'product', 'a' => 'cat', 'role' => [1, 2]),
        'shop_product' => array('fst' => 'shop', 'name' => '商品管理', 'icon' => 'icon-laptop', 'c' => 'product', 'a' => 'list', 'role' => [1, 2, 3]),
        'shop_order' => array('fst' => 'shop', 'noFree' => true, 'name' => '订单管理', 'icon' => 'icon-tablet', 'c' => 'order', 'a' => 'list', 'role' => [1, 2, 3]),

        //用户管理
//        'user' => array('fst' => 'user', 'name' => '用户设置', 'icon' => 'icon-screenshot', 'c' => 'users', 'a' => 'setting'),
        'user_list' => array('fst' => 'user', 'name' => '用户列表', 'icon' => 'icon-screenshot', 'c' => 'users', 'a' => 'index', 'role' => [1, 2, 3, 4, 5]),
        'mine' => array('fst' => 'func', 'name' => '营销列表', 'c' => 'module', 'a' => 'mine', 'icon' => 'icon-gift', 'role' => [1, 2, 3]),

        'forum_discuss' => array('fst' => 'forum', 'name' => '社区讨论', 'c' => 'discuss', 'a' => 'forumList', 'icon' => 'icon-gift', 'role' => [1, 4, 5]),
        'forum_diy' => array('fst' => 'forum', 'name' => 'DIY专区', 'c' => 'discuss', 'a' => 'diyList', 'icon' => 'icon-gift', 'role' => [1, 4, 5]),
    );

    // 页面内部
    public static $_innerMenu = array(
        'web_info' => array(
            array('name' => '信息列表', 'c' => 'site', 'a' => 'page', 'role' => [1, 4, 5]),
            array('name' => '添加信息', 'c' => 'site', 'a' => 'pageAdd', 'role' => [1, 4, 5]),
            array('name' => '修改信息', 'c' => 'site', 'a' => 'pageUp', 'hide' => true, 'role' => [1, 4, 5]),
        ),

        'web_wiki' => array(
            array('name' => '信息列表', 'c' => 'wiki', 'a' => 'list', 'role' => [1, 4, 5]),
            array('name' => '帮助栏目', 'c' => 'wiki', 'a' => 'cat', 'role' => [1, 4, 5]),
            array('name' => '添加文档', 'c' => 'wiki', 'a' => 'add', 'role' => [1, 4, 5]),
            array('name' => '修改信息', 'c' => 'wiki', 'a' => 'update', 'hide' => true, 'role' => [1, 4, 5]),
        ),

        'web_content' => array(
            array('name' => '资讯列表', 'c' => 'article', 'a' => 'list', 'role' => [1, 4, 5]),
            array('name' => '资讯栏目', 'c' => 'article', 'a' => 'cat', 'role' => [1, 4, 5]),
            array('name' => '添加资讯', 'c' => 'article', 'a' => 'add', 'role' => [1, 4, 5]),
            array('name' => '修改资讯', 'c' => 'article', 'a' => 'update', 'hide' => true, 'role' => [1, 4, 5]),
        ),

        'wechat' => array(
            array('name' => '微信绑定', 'c' => 'wechat', 'a' => 'binding', 'role' => [1]),
            array('name' => '微信菜单', 'c' => 'wechat', 'a' => 'menu', 'role' => [1]),
            array('name' => '自动回复', 'c' => 'wechat', 'a' => 'respond', 'role' => [1]),
        ),

        'user_group' => array(
            array('name' => '自定义分组', 'c' => 'users', 'a' => 'group', 'role' => [1]),
            array('name' => '微信用户组', 'c' => 'users', 'a' => 'wechatGroup', 'role' => [1]),
        ),

        'pc_setting' => array(
            array('name' => '商城信息设置', 'c' => 'store', 'a' => 'index', 'role' => [1]),
            array('name' => '首页焦点图', 'c' => 'store', 'a' => 'pcfocus', 'role' => [1]),
            array('name' => '首页底部4图设置', 'c' => 'store', 'a' => 'content', 'role' => [1]),
            array('name' => '底部信息设置', 'c' => 'store', 'a' => 'pchomepage', 'role' => [1]),
        ),

        'wx_setting' => array(
            array('name' => '微网站信息设置', 'c' => 'site', 'a' => 'info', 'role' => [1]),
            array('name' => '微信商城首页海报大图', 'c' => 'store', 'a' => 'focus', 'role' => [1]),
            array('name' => '微信商城首页广告小图', 'c' => 'store', 'a' => 'smallfocus', 'role' => [1]),
            array('name' => '微信关于坐客焦点图片', 'c' => 'site', 'a' => 'focus', 'role' => [1]),
        ),

        'shop_attr' => array(
            array('name' => '商品类目', 'c' => 'product', 'a' => 'cat', 'role' => [1]),
            array('name' => '商品属性', 'c' => 'product', 'a' => 'attr', 'role' => [1]),
            array('name' => '型号规格', 'c' => 'product', 'a' => 'spec', 'role' => [1]),
            array('name' => '商品品牌', 'c' => 'product', 'a' => 'brand', 'role' => [1]),
        ),

        'shop_product' => array(
            array('name' => '商品列表', 'c' => 'product', 'a' => 'list', 'role' => [1, 2, 3]),
            array('name' => '添加商品', 'c' => 'product', 'a' => 'edit', 'hide' => true, 'role' => [1, 2]),
        ),

        'shop_order' => array(
            array('name' => '订单列表', 'c' => 'order', 'a' => 'list', 'role' => [1, 2, 3]),
            array('name' => '退货订单', 'c' => 'order', 'a' => 'refund', 'role' => [1, 2, 3]),
            array('name' => '订单详情', 'c' => 'order', 'a' => 'detail', 'hide' => true, 'role' => [1, 2, 3]),
            array('name' => '退换详情', 'c' => 'order', 'a' => 'refundDetail', 'hide' => true, 'role' => [1, 2, 3]),
        ),

        'shop_freight' => array(
            array('name' => '沙发物流', 'c' => 'freight', 'a' => 'list', 'role' => [1]),
            array('name' => '运费模板', 'c' => 'freight', 'a' => 'index', 'role' => [1]),
            array('name' => '物流方式', 'c' => 'freight', 'a' => 'logistic', 'role' => [1]),
            array('name' => '添加模板', 'c' => 'freight', 'a' => 'add', 'hide' => true, 'role' => [1]),
            array('name' => '添加物流', 'c' => 'freight', 'a' => 'addlogistic', 'hide' => true, 'role' => [1]),
        ),

        'forum_discuss' => array(
            array('name' => '社区帖列表', 'c' => 'discuss', 'a' => 'forumList', 'role' => [1, 4, 5]),
            array('name' => '社区分类', 'c' => 'discuss', 'a' => 'forumCats', 'role' => [1, 4, 5]),
            array('name' => '社区头部设置', 'c' => 'forum', 'a' => 'discussSetting', 'role' => [1, 4, 5]),
        ),

        'forum_diy' => array(
            array('name' => 'DIY帖列表', 'c' => 'discuss', 'a' => 'diylist', 'role' => [1, 4, 5]),
            array('name' => '创意帖列表', 'c' => 'discuss', 'a' => 'creativediyList', 'role' => [1, 4, 5]),
            array('name' => 'DIY分类', 'c' => 'discuss', 'a' => 'diyCats', 'role' => [1, 4, 5]),
            array('name' => 'DIY头部设置', 'c' => 'forum', 'a' => 'diySetting', 'role' => [1, 4, 5]),
            array('name' => '我要发帖', 'c' => 'discuss', 'a' => 'publishDiy', 'role' => [1, 4, 5])
        ),
    );

    public static function init()
    {
        return new self();
    }

    public function getCurMenuKey()
    {
        $fstMenu = self::$_sideFstMenu;
        $secMenu = self::$_sideSecMenu;
        $innerMenu = self::$_innerMenu;

        $controller = $this->view->getControllerName();
        $action = $this->view->getActionName();
        $curFst = '';
        $curSec = '';

        $_isFound = false;
        foreach ($innerMenu as $secKey => $menus) {
            foreach ($menus as $inner) {
                if ($inner['c'] == $controller && $inner['a'] == $action) {
                    $curSec = $secKey;
                    $curFst = $secMenu[$curSec]['fst'];
                    $_isFound = true;
                    break;
                }
            }
        }
        // 2级菜单找当前
        if (!$_isFound) {
            foreach ($secMenu as $k => $sec) {
                if ($sec['c'] == $controller && $sec['a'] == $action) {
                    $curFst = $sec['fst'];
                    $curSec = $k;
                    break;
                }
            }
        }

        if (!$curFst) {
            foreach ($fstMenu as $k => $fst) {
                if (isset($fst['c']) && $fst['c'] == $controller && $fst['a'] == $action) {
                    $curFst = $k;
                    break;
                }
            }
        }

        // 模块
        if ($controller == 'module' && $action == 'run') {
            $curFst = 'func';
        }



        return array('side' => array('fst' => $curFst, 'sec' => $curSec));
    }

    public function getPermission()
    {

    }

    public static function getMenu()
    {
        return array('side' => array('fst' => self::$_sideFstMenu, 'sec' => self::$_sideSecMenu, 'inner' => self::$_innerMenu));
    }
}
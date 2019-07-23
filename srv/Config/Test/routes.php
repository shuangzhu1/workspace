<?php
$router = new Phalcon\Mvc\Router(false);
$router->setDefaultController('index');
$router->setDefaultAction('index');
$app = 1;
// 通过主域查找
// cookie 跨子域读取问题（统一cookie域名，修复下单错误来源情况）
$domain_segments = explode('.', MAIN_DOMAIN);
// 没考虑类似com.cn等后缀情况
if (count($domain_segments) > 2) {
    if (!defined('COOKIE_DOMAIN')) define('COOKIE_DOMAIN', $domain_segments[count($domain_segments) - 2] . '.' . $domain_segments[count($domain_segments) - 1]);
} else {
    if (!defined('COOKIE_DOMAIN')) define('COOKIE_DOMAIN', MAIN_DOMAIN);
}
//echo "<h1>",$_SERVER['SERVER_PORT'],ROOT,"</h1>";
if ($_SERVER['SERVER_PORT']=='8182') {
    define('IS_WAP', true);
    $router->setDefaultModule("wap");//wap网页是手机上网网页
    $_router_module[] = array('module' => 'wap', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'wap', 'root_path' => '/api', 'namespace' => 'Multiple\Wap\Api');
} else if ($_SERVER['SERVER_PORT']=='8181') {
    define('IS_APP', true);
    $router->setDefaultModule("api");//APP
    $_router_module[] = array('module' => 'api', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'api', 'root_path' => '/window', 'namespace' => 'Window');
    $_router_module[] = array('module' => 'api', 'root_path' => '/merchant', 'namespace' => 'Merchant');

} else if ($_SERVER['SERVER_PORT']=='8081') {//PC浏览器
    $_router_module[] = array('module' => 'panel', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'panel', 'root_path' => '/panel', 'namespace' => '');
    #$_router_module[] = array('module' => 'api', 'root_path' => '/api', 'namespace' => '');
    $_router_module[] = array('module' => 'panel', 'root_path' => '/api', 'namespace' => 'Multiple\Panel\Api');

    $router->setDefaultModule("panel");
    // 追加不规则的
    $router->add("/account([/index]?)", array('module' => 'panel', 'controller' => 'account', 'action' => 'login'));
    $router->add("/account/:action/:params", array('module' => 'panel', 'controller' => 'account', 'action' => 1, 'params' => 2));
    $router->add("/customer([/index]?)", array('module' => 'panel', 'controller' => 'customer', 'action' => 'login',));
    $router->add("/customer/:action/:params", array('module' => 'panel', 'controller' => 'customer', 'action' => 1, 'params' => 2));
    $router->add("/panel/api/:controller/:action/:params", array(
        'namespace' => 'Multiple\Panel\Api',
        'module' => 'panel',
        'controller' => 1,
        'action' => 2,
        'params' => 3
    ));

} //开发者
else if ($_SERVER['SERVER_PORT']=='8184') {
    $_router_module[] = array('module' => 'developer', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'developer', 'root_path' => '/developer', 'namespace' => '');
    #$_router_module[] = array('module' => 'api', 'root_path' => '/api', 'namespace' => '');
    $_router_module[] = array('module' => 'developer', 'root_path' => '/api', 'namespace' => 'Multiple\Developer\Api');
    $router->setDefaultModule("developer");
    // 追加不规则的
    $router->add("/account([/index]?)", array('module' => 'developer', 'controller' => 'account', 'action' => 'login'));
    $router->add("/account/:action/:params", array('module' => 'developer', 'controller' => 'account', 'action' => 1, 'params' => 2));
    $router->add("/customer([/index]?)", array('module' => 'developer', 'controller' => 'customer', 'action' => 'login',));
    $router->add("/customer/:action/:params", array('module' => 'developer', 'controller' => 'customer', 'action' => 1, 'params' => 2));
    $router->add("/developer/api/:controller/:action/:params", array(
        'namespace' => 'Multiple\Developer\Api',
        'module' => 'developer',
        'controller' => 1,
        'action' => 2,
        'params' => 3
    ));

} //开放平台
else if ($_SERVER['SERVER_PORT']=='8185') {
    $router->setDefaultModule("open");
    $_router_module[] = array('module' => 'open', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'open', 'root_path' => '/rob', 'namespace' => 'Multiple\Open\Module');

} /* else if (false !== strpos($_SERVER['HTTP_HOST'], 'www') || $_SERVER['HTTP_HOST'] == 'klgwl.com'|| $_SERVER['HTTP_HOST'] == 'klgwl.cn') {

} */ else {
    $router->add("/about", array('module' => 'home', 'controller' => 'index', 'action' => 'about'));
    $router->add("/agreement", array('module' => 'home', 'controller' => 'index', 'action' => 'agreement'));
    $router->setDefaultModule("home");

    /*$_router_module[] = array('module' => 'home', 'root_path' => '', 'namespace' => '');*/
    /*$router->add("/home/",array('module'=>'home','controller'=>'index','action'=>'index'));*/

    /* $router->add("/home/index/:action", array(
         'module' => 'home',
         'controller' => 'index',
         'action' => 1
     ));*/
}

// 公用部分
// payment
/*$_router_module[] = array('module' => 'payment', 'root_path' => '/payment', 'namespace' => '');*/

foreach ($_router_module as $_module) {
    genRouter($_module, $router);
}

function genRouter($module, \Phalcon\Mvc\Router $router)
{
    $_repeat = array(
        'root' => '([/]?)',
        'controller' => '/:controller([/]?)',
        'action' => '/:controller/:action([/]?)',
        'param' => '/:controller/:action/:params',
    );

    $_disp = array(
        'root' => array('controller' => 'index', 'action' => 'index'),
        'controller' => array('controller' => 1, 'action' => 'index'),
        'action' => array('controller' => 1, 'action' => 1),
        'param' => array('controller' => 1, 'action' => 2, 'params' => 3),
    );

    foreach ($_repeat as $_i => $_rout) {
        $mvc = $_disp[$_i];
        $mvc['module'] = $module['module'];
        if ($module['namespace']) {
            $mvc['namespace'] = $module['namespace'];
        }
//        print_r($module['root_path'] . $_rout . "\n");
        $router->add($module['root_path'] . $_rout, $mvc);
    }
}

/*!defined('IS_WAP') && define('IS_WAP', false);*/
return $router;

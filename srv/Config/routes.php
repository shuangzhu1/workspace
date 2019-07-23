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
if (false !== strpos($_SERVER['HTTP_HOST'], 'wap')) {
    define('IS_WAP', true);
    $router->setDefaultModule("wap");
    $_router_module[] = array('module' => 'wap', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'wap', 'root_path' => '/api', 'namespace' => 'Multiple\Wap\Api');
    //追加不规则
    $router->add("/s/:params", array('module' => 'wap', 'controller' => 'article', 'action' => 'material','params' => 1));
} else if (false !== strpos($_SERVER['HTTP_HOST'], 'api')) {
    define('IS_APP', true);
    $router->setDefaultModule("api");
    $_router_module[] = array('module' => 'api', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'api', 'root_path' => '/window', 'namespace' => 'Window');
    $_router_module[] = array('module' => 'api', 'root_path' => '/merchant', 'namespace' => 'Merchant');
    $_router_module[] = array('module' => 'api', 'root_path' => '/community', 'namespace' => 'Community');

} else if (false !== strpos($_SERVER['HTTP_HOST'], 'admin')) {
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
else if (false !== strpos($_SERVER['HTTP_HOST'], 'developer')) {
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
else if (false !== strpos($_SERVER['HTTP_HOST'], 'open')) {
    $router->setDefaultModule("open");
    $_router_module[] = array('module' => 'open', 'root_path' => '', 'namespace' => '');
    $_router_module[] = array('module' => 'open', 'root_path' => '/rob', 'namespace' => 'Multiple\Open\Module');

}else {
    if( $_SERVER['HTTP_HOST'] === 'klgwl.cn' || ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] === 'www.klgwl.cn/index') )
    {
        Header("HTTP/1.1 301 Moved Permanently");
        Header("Location: http://www.klgwl.cn");
        exit;
    }


    $router->add("/about", array('module' => 'home', 'controller' => 'index', 'action' => 'about'));
    $router->add("/agreement", array('module' => 'home', 'controller' => 'index', 'action' => 'agreement'));
    $router->add("/dynamic", array('module' => 'home', 'controller' => 'index', 'action' => 'dynamic'));
    $router->add("/dynamic/([0-9]*).html", array('module' => 'home', 'controller' => 'index', 'action' => 'dynamic','id' => 1));
    $router->add("/service", array('module' => 'home', 'controller' => 'index', 'action' => 'service'));
    $router->add("/guide", array('module' => 'home', 'controller' => 'index', 'action' => 'guide'));
    $router->add("/api/guide", array('module' => 'home', 'controller' => 'api', 'action' => 'guide'));
    $router->setDefaultModule("home");

}


// payment
$_router_module[] = array('module' => 'payment', 'root_path' => '/payment', 'namespace' => '');

//回调
$_router_module[] = array('module' => 'callback', 'root_path' => '/callback', 'namespace' => '');

foreach ($_router_module as $_module) {
    genRouter($_module, $router);
}

// 公用部分
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
        $router->add($module['root_path'] . $_rout, $mvc);
    }
}
return $router;

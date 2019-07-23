<?php
namespace Multiple\Open\Module;

use Multiple\Open\Helper\Ajax;
use Multiple\Open\Helper\Identify;
use Phalcon\Mvc\Controller;
use Services\Site\CacheSetting;

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/20
 * Time: 15:23
 * @property Ajax $ajax
 * @property \Components\Redis\RedisComponent $redis
 *
 */
class ModuleBase extends Controller
{
    public $ajax;
    private static $secret_key = "rob_klgwl.com@2017";
    protected $params = [];
    protected $uid = 0;
    protected $redis = null;
    protected $robot = null;

    protected function initialize()
    {
        $this->view->disable();
        $this->ajax = new Ajax();
        $params = $this->request->get('params', 'string', '');

        if (!$params) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "参数为空");
        }

        $verify = Identify::init()->getParams($params, self::$secret_key, 'RSA', ROOT . '/Library/Components/Rsa/key/open/rob_rsa_private_key.pem');
        if (!$verify) {
            $this->ajax->outError(Ajax::ERROR_SIGN, "验签失败");
        }
        $this->uid = intval($verify['uid']);
        if (empty($verify['uid']) || !$this->uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "无效的参数【缺少uid】");
        }

        $this->redis = $this->di->get("redis");
        if (!$this->robot = $this->redis->originalGet(CacheSetting::KEY_OPEN_ROBOT . $this->uid)) {
            $this->ajax->outError(Ajax::ERROR_USER_NOT_SUPPORT, "用户不被支持【" . $this->uid . "】");
        }
        $this->robot = json_decode($this->robot, true);
        $this->params = $verify;
    }
}
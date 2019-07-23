<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/1
 * Time: 10:39
 */

namespace Services\MiddleWare\Sl;


use Components\Passport\Identify;
use Models\Statistics\UserWalletOpenLog;
use Services\Site\CurlManager;
use Util\Debug;

class Request extends Base
{
    /**生成签名
     * @param $data
     * @return array
     */
    public static function createSign($data)
    {
        $data = array_merge($data, ['time_stamp' => time(), 'sign_type' => self::$sign_type, 'rand' => rand(0, 10000)]);
        $data = array_filter($data);
        $data['sign'] = Identify::init()->buildRequestMysign($data, self::$sign_type);
        return $data;
    }

    /**接口请求
     * @param $api
     * @param $data
     * @param $result
     * @return  array
     */
    public static function getPost($api, $data, $result = false)
    {
        $baseUrl = self::$baseUrl;
        if (TEST_SERVER) {
            $baseUrl = self::$testBaseUrl;
        }
        $data = self::createSign($data);
        // Debug::log("data:" . var_export($data, true), 'payment');
        $res = CurlManager::init()->CURL_POST($baseUrl . $api, $data);

        Debug::log('curl:' . self::$baseUrl . $api . ":" . var_export($res, true), 'curl');
        Debug::log('curl:data' . var_export($data, true), 'curl');
        self::writeLog($api, $data, $res);
        if ($result) {
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                return $content['data'];
            } else {
                return false;
            }
        }
        return $res;
    }

    /**异步任务请求
     * @param string $url 任务需要调用的api
     * @param string $method POST/GET
     * @param array $params 参数 k1=v1&k2=v2
     * @param int $outer 内部调用 0-外部 1-内部
     * @param int $delay 延迟执行的时间 秒
     * @param int $taskid 任务id
     * @param string $notify 回调url
     * @return array
     */
    public static function asyncPost($url = '', $params = [], $method = 'POST', $outer = 0, $delay = -1, $taskid = -1, $notify = '')
    {
        $baseUrl = self::$baseUrl;
        if (TEST_SERVER) {
            $baseUrl = self::$testBaseUrl;
        }
        $api = $baseUrl . self::HTQ_TASK;
        $data = ['url' => $url, 'method' => $method, 'params' => ''];
        if ($params) {
            $tmp_params = [];
            foreach ($params as $k => $item) {
                if ($item) {
                    $tmp_params[] = $k . "=" . $item;
                }
            }
            if ($outer) {
                $tmp_params[] = "time_stamp=" . time();
                $tmp_params[] = "rand=" . rand(0, 10000);
            }
            if ($tmp_params) {
                $data['params'] = implode('&', $tmp_params);
            }
        }
        if (!$outer) {
            $data['url'] = self::$baseUrl . $data['url'];
        }
        if ($delay > 0) {
            $data['delay'] = $delay;
        }
        if ($taskid > 0) {
            $data['taskid'] = $taskid;
        }
        if ($notify > 0) {
            $data['notify'] = $notify;
        }
        $res = CurlManager::init()->CURL_POST($api, $data);
        Debug::log('curl:' . self::$baseUrl . $api . ":" . var_export($res, true), 'curl');
        //   Debug::log('curl:data' . var_export($data, true), 'curl');
        // self::writeLog($url, $data, $res);
        return $res;
    }

    /**记录日志
     * @param $api
     * @param $data
     * @param $res
     */
    public static function writeLog($api, $data, $res)
    {
        if (in_array($api, [self::OPEN_ACCOUNT])) {
            switch ($api) {
                //开通钱包账户
                case self::OPEN_ACCOUNT:
                    $log = [
                        'user_id' => $data['uid'],
                        'status' => 0,
                        'created' => time(),
                        'result' => ''
                    ];
                    if ($res['curl_is_success'] && !empty($res['data'])) {
                        $res_data = json_decode($res['data'], true);
                        $log['status'] = (isset($res_data['code']) && $res_data['code'] == 200) ? 1 : 0;
                        $log['result'] = json_encode(['return' => $res_data, 'http_code' => $res['curl_data']['http_code']], JSON_UNESCAPED_UNICODE);
                    } else if (!$res['curl_is_success']) {
                        $log['status'] = 0;
                        $log['result'] = json_encode(['return' => $res['data'], 'http_code' => $res['curl_data']['http_code']], JSON_UNESCAPED_UNICODE);
                    }
                    UserWalletOpenLog::insertOne($log);
                    break;
                default:
                    if (!$res['curl_is_success']) {
                        Debug::log("curl:api:" . $api . "res:" . var_export($res, true));
                    }
                    break;
            }

        }
    }
}
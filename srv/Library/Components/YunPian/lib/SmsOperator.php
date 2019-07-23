<?php
/**
 * Created by PhpStorm.
 * User: bingone
 * Date: 16/1/19
 * Time: 下午5:42
 */

namespace Components\YunPian\lib;

use Phalcon\Mvc\User\Plugin;

class SmsOperator extends Plugin
{
    public $apikey;
    public $api_secret;
    public $yunpian_config;

    public function __construct($apikey = null, $api_secret = null)
    {
        $this->yunpian_config = $this->di->get('config')->yun_pian;
        if ($api_secret == null)
            $this->api_secret = $this->yunpian_config->app_secret;
        else
            $this->api_secret = $apikey;
        if ($apikey == null)
            $this->apikey = $this->yunpian_config->app_key;
        else
            $this->apikey = $api_secret;
    }

    public function encrypt(&$data)
    {

    }

    /**
     *
     * apikey    String    是    是    用户唯一标识，在管理控制台获取    9b11127a9701975c734b8aee81ee3526
     * mobile    String    是    是    接收的手机号，仅支持单号码发送；
     * 国际号码需包含国际地区前缀号码，格式必须是"+"号开头("+"号需要urlencode处理，否则会出现格式错误)    urlencode("+93701234567")
     * text    String    是    是    短信内容    【云片网】您的验证码是1234
     * extend    String    否    否    下发号码扩展号，纯数字    001
     * uid    String    否    否    该条短信在您业务系统内的ID，如订单号或者短信发送记录流水号    10001
     * callback_url    String    否    是    短信发送后将向这个地址推送(运营商返回的)状态报告。
     * 如推送地址固定，建议在"数据推送与获取”做批量设置。
     * 如后台已设置地址，且请求内也包含此参数，将以请求内地址为准    http://yourreceiveurl_address
     * @param array $data
     * @return Result
     */
    public function single_send($data = array())
    {
        if (!array_key_exists('mobile', $data))
            return new Result(null, $data, null, 'mobile 为空');
        if (!array_key_exists('text', $data))
            return new Result(null, $data, null, 'text 为空');
        $data['apikey'] = $this->apikey;

        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/sms/single_send.json', $data);
    }

    public function batch_send($data = array())
    {
        if (!array_key_exists('mobile', $data))
            return new Result(null, $data, null, $error = 'mobile 为空');
        if (!array_key_exists('text', $data))
            return new Result(null, $data, null, $error = 'text 为空');
        $data['apikey'] = $this->apikey;

        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/sms/batch_send.json', $data);
    }

    public function multi_send($data = array())
    {
        if (!array_key_exists('mobile', $data))
            return new Result(null, $data, null, $error = 'mobile 为空');
        if (!array_key_exists('text', $data))
            return new Result(null, $data, null, $error = 'text 为空');
        if (count(explode(',', $data['mobile'])) != count(explode(',', $data['text'])))
            return new Result(null, $data, null, $error = 'mobile 与 text 个数不匹配');
        $data['apikey'] = $this->apikey;
        $text_array = explode(',', $data['text']);
        $data['text'] = '';
        for ($index = 0; $index < count($text_array); $index++) {
            $data['text'] .= urlencode($text_array[$index]) . ',';
        }
        $data['text'] = substr($data['text'], 0, -1);
        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/sms/multi_send.json', $data);
    }

    public function tpl_send($data = array())
    {
        if (!array_key_exists('mobile', $data))
            return new Result(null, $data, null, 'mobile 为空');
        if (!array_key_exists('tpl_id', $data))
            return new Result(null, $data, null, 'tpl_id 为空');
        if (!array_key_exists('tpl_value', $data))
            return new Result(null, $data, null, 'tpl_value 为空');

        $data['apikey'] = $this->apikey;

        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/sms/tpl_send.json', $data);
    }
}

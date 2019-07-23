<?php
/**
 * Created by PhpStorm.
 * User: bingone
 * Date: 16/1/20
 * Time: 上午10:37
 */
namespace Components\YunPian\lib;

use Phalcon\Mvc\User\Plugin;

class TplOperator extends Plugin
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

    public function get_default($data = array())
    {
        $data['apikey'] = $this->apikey;

        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/tpl/get_default.json', $data);
    }

    /**
     * apikey    String    是    用户唯一标识    9b11127a9701975c734b8aee81ee3526
     * tpl_id    Long    否    模板id，64位长整形。指定id时返回id对应的
     * 模板。未指定时返回所有模板    1
     * @param array $data
     * @return Result
     *
     *  指定id时，调用成功的返回值示例：
     * {
     * "tpl_id": 1,
     * "tpl_content": "您的验证码是#code#",
     * "check_status": "FAIL",
     * "reason": "模板开头必须带签名，如【云片网络】" //审核未通过的原因
     * "country_code": "CN,TW,MO,HK" // 支持地区
     * }
     * 未指定id时，调用成功的返回值示例：
     * [{
     * "tpl_id": 1,
     * "tpl_content": "您的验证码是#code#",
     * "check_status": "FAIL",
     * "reason ": "模板开头必须带签名，如【云片网】"
     * "country_code": "CN,TW,MO,HK" // 支持地区
     * },
     * {
     * "tpl_id": 2,
     * "tpl_content": "【云片网】您的验证码是#code#。如非本人操作，请忽略本短信",
     * "check_status": "SUCCESS",
     * "reason ": null
     * "country_code": "CN,TW,MO,HK" // 支持地区
     * }]
     */
    public function get($data = array())
    {
        $data['apikey'] = $this->apikey;

        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/tpl/get.json', $data);
    }

    /**
     * apikey    String    是    用户唯一标识    9b11127a9701975c734b8aee81ee3526
     * tpl_content    String    是    模板内容，必须以带符号【】的签名开头    【云片网】您的验证码是#code#
     * lang    String    否    国际短信模板所需参数，模板语言:简体中文zhcn; 英文en; 繁体中文 zhtw; 韩文ko,日文 ja    zh_cn
     * notify_type    Integer    否    审核结果短信通知的方式:
     *    0表示需要通知,默认;
     *    1表示仅审核不通过时通知;
     *    2表示仅审核通过时通知;
     *    3表示不需要通知
     * @param array $data
     * @return Result
     *
     * {
     * "tpl_id": 1,                 //模板id
     * "tpl_content": "【云片网】您的验证码是#code#", //模板内容
     * "check_status": "CHECKING",     //审核状态：CHECKING/SUCCESS/FAIL
     * "reason": null,      //审核未通过的原因
     * "country_code": "CN,TW,MO,HK" // 支持地区
     * }
     */
    public function add($data = array())
    {
        /*  if (!array_key_exists('tpl_id', $data))
              return new Result(null, $data, null, $error = 'tpl_id 为空');*/
        if (!array_key_exists('tpl_content', $data))
            return new Result(null, $data, null, $error = 'tpl_content 为空');
        $data['apikey'] = $this->apikey;
        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/tpl/add.json', $data);
    }

    /**
     * apikey    String    是    用户唯一标识    9b11127a9701975c734b8aee81ee3526
     * tpl_id    Long    是    模板id，64位长整形，指定id时返回id对应的模板。未指定时返回所有模板    9527
     * tpl_content    String    是    模板id，64位长整形。指定id时返回id对应的模板。未指定时返回所有模板模板内容，必须以带符号【】的签名开头    【云片网】您的验证码是#code#
     * lang    String    否    国际短信模板所需参数，模板语言:简体 中文zh_cn; 英文en; 繁体中文 zh_tw; 韩文ko,日文 ja    zh_cn
     * @param array $data
     * @return Result
     * 情况1: 修改国际短信模板但未修改语言(带参数lang但lang参数不变) 或 把国内短信模板修改为国际短信模板(带参数lang)):
     * {
     * "tpl_id": 9527,                 //模板id
     * "tpl_content": "【云片网】您的验证码是#code#", //模板内容
     * "check_status": "CHECKING",     //审核状态：CHECKING/SUCCESS/FAIL
     * "reason": null                  //审核未通过的原因
     * "lang": "ko",
     * "country_code": "KR"
     * }
     * 情况2:修改了国际短信模板的语言,即lang参数改变):
     * {
     * "code": 0,
     * "msg": "OK",
     * "template": {
     * "tpl_id": 9527,
     * "tpl_content": "【云片网】您的验证码是#code#",
     * "check_status": "CHECKING",
     * "reason": null,
     * "lang": "ja",
     * "country_code": "JP"
     * }
     * }
     * 情况3:国际短信模板修改为国内短信模板,不带lang参数):
     * {
     * "msg": "OK",
     * "template": {
     * "tpl_id": 9527,
     * "tpl_content": "【云片网】您的验证码是#code#",
     * "check_status": "CHECKING",
     * "reason": null
     * }
     * }
     */
    public function upd($data = array())
    {
        if (!array_key_exists('tpl_id', $data))
            return new Result(null, $data, null, $error = 'tpl_id 为空');
        if (!array_key_exists('tpl_content', $data))
            return new Result(null, $data, null, $error = 'tpl_content 为空');
        $data['apikey'] = $this->apikey;
        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/tpl/update.json', $data);
    }

    /**
     * apikey    String    是    用户唯一标识    9b11127a9701975c734b8aee81ee3526
     * tpl_id    Long    是    模板id，64位长整形。指定id时返回id对应的模板。未指定时返回所有模板    9527
     * @param array $data
     * @return Result
     *
     * {
     * "tpl_id": 9527,                 //模板id
     * "tpl_content": "【云片网】您的验证码是#code#", //模板内容
     * "check_status": "CHECKING",     //审核状态：CHECKING/SUCCESS/FAIL
     * "reason": null                  //审核未通过的原因
     * }
     */
    public function del($data = array())
    {
        if (!array_key_exists('tpl_id', $data))
            return new Result(null, $data, null, $error = 'tpl_id 为空');
        $data['apikey'] = $this->apikey;

        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/tpl/del.json', $data);
    }

}
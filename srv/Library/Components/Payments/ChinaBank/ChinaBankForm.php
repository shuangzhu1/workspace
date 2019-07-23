<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-5-16
 * Time: 上午10:37
 */

namespace Components\Payments\ChinaBank;


class ChinaBankForm
{
    public $gateway = "https://Pay3.chinabank.com.cn/PayGate";

    public $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param $v_oid 返回的订单ID
     * @param $v_pstatus 订单状态
     * @param $v_amount 订单金额
     * @param $v_moneytype 订单货币类型
     * @param $v_md5str 返回的签名字符串
     * @return bool
     */
    public function checkSign($v_oid, $v_pstatus, $v_amount, $v_moneytype, $v_md5str)
    {
        $md5string = strtoupper(md5($v_oid . $v_pstatus . $v_amount . $v_moneytype . $this->config['key']));
        return $v_md5str == $md5string;
    }

    public function buildRequestSign($v_oid, $v_mid, $v_url, $v_amount, $v_moneytype)
    {
        $text = $v_amount . $v_moneytype . $v_oid . $v_mid . $v_url . $this->config['key']; //md5加密拼凑串,注意顺序不能变
        return strtoupper(md5($text));
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param array $para_temp 请求参数数组
     * @param string $method 提交方式。两个值可选：post、get
     * @param string $button_name 确认按钮显示文字
     * @return string 提交表单HTML文本
     */
    public function buildRequestForm($para, $method, $button_name)
    {
        $sHtml = "<form id='alipaysubmit' name='E_FORM' action='" . $this->gateway . "?encoding=UTF-8' method='" . $method . "'>";
        while (list ($key, $val) = each($para)) {
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<div style='width: 200px; height: 200px; margin: 200px auto;'>" . $button_name . "</div></form>";

        $sHtml = $sHtml . "<script>document.forms['E_FORM'].submit();</script>";

        return $sHtml;
    }

} 
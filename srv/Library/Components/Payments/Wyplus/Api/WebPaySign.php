<?php

namespace Components\Payments\Wyplus\Api;
use Components\Payments\Wyplus\ConfigUtil;
use Components\Payments\Wyplus\SignUtil;

/**
 * 模拟支付-商户签名
 * @author wylitu
 *
 */
class WebPaySign {
	
	public function  execute(){
				
		$param = array();
		$param["currency"] = $_POST["currency"];
		$param["ip"] = $_POST["ip"];
		$param["merchantNum"] = $_POST["merchantNum"];
		$param["merchantRemark"] = $_POST["merchantRemark"];
		$param["notifyUrl"] = $_POST["notifyUrl"];
		$param["successCallbackUrl"] = $_POST["successCallbackUrl"];
		$param["tradeAmount"] = $_POST["tradeAmount"];
		$param["tradeDescription"] = $_POST["tradeDescription"];
		$param["tradeName"] = $_POST["tradeName"];
		$param["tradeNum"] = $_POST["tradeNum"];
		$param["tradeTime"] = $_POST["tradeTime"];
		$param["version"] = $_POST["version"];
		$param["token"] = $_POST["token"];

		$sign = SignUtil::signWithoutToHex($param);
		$param["merchantSign"] = $sign;
	$_SESSION['tradeAmount'] = $_POST["tradeAmount"];
	$_SESSION['tradeName'] = $_POST["tradeName"];
	$_SESSION['tradeInfo'] = $param;
	$serverUrl = ConfigUtil::get_val_by_key("serverPayUrl");
	$url=$serverUrl."?version=".(urlencode($param["version"]))
		."&token=".(urlencode($param["token"]))
		."&merchantNum=".(urlencode($param["merchantNum"]))
		."&merchantRemark=".(urlencode($param["merchantRemark"]))
		."&tradeNum=".(urlencode($param["tradeNum"]))
		."&tradeName=".(urlencode($param["tradeName"]))
		."&tradeDescription=".(urlencode($param["tradeDescription"]))
		."&tradeTime=".(urlencode($param["tradeTime"]))
		."&tradeAmount=".(urlencode($param["tradeAmount"]))
		."&currency=".(urlencode($param["currency"]))
		."&notifyUrl=".(urlencode($param["notifyUrl"]))
		."&successCallbackUrl=".(urlencode($param["successCallbackUrl"]))
		."&forPayLayerUrl=".(urlencode( $_POST["forPayLayerUrl"]))
		."&ip=".(urlencode($_POST["ip"]))
		."&merchantSign=".(urlencode($sign));
	 error_log($url, 0);
	header(("location:".$url));
}
}

$webPaySign = new WebPaySign();
$webPaySign->execute();


?>
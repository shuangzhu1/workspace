<?php

namespace Components\Payments\Wyplus\Api;

use Components\Payments\Wyplus\RSAUtils;
use Components\Payments\Wyplus\TDESUtil;

use Components\Payments\Wyplus\ConfigUtil;

/**
 * 退款-验签
 * @author wylitu
 *
 */
class WebRefundSign {
	
	
	public function  http_post_data($url, $data_string) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
				'Content-Type: application/json; charset=utf-8',
				'Content-Length: ' . strlen ( $data_string ) 
		) );
		ob_start ();
		curl_exec ( $ch );
		$return_content = ob_get_contents ();
		ob_end_clean ();
		
		$return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
		return array (
				$return_code,
				$return_content 
		);
	}
	
	public function execute() {
		
		$params = $this->prepareParms();

		$data = json_encode ($params);

		list ( $return_code, $return_content ) = $this->http_post_data (ConfigUtil::get_val_by_key("serverRefundUrl"), $data );
		
		$return_content = str_replace("\n", '', $return_content);
		$return_data = json_decode ($return_content,true);
		
		$_SESSION ['errorMsg'] = null;
		$_SESSION ['resultData'] =null;
		//执行状态 成功
		if ($return_data['resultCode'] == 0) {
			$mapResult =  $return_data ['resultData'];

			//有返回数据
			if (null != $mapResult) {
				$data = $mapResult["data"];
				$sign = $mapResult["sign"];
				//1.解密签名内容
				$decryptStr = RSAUtils::decryptByPublicKey($sign);
		
				//2.对data进行sha256摘要加密
				$sha256SourceSignString = hash ( "sha256",$data);
		
				//3.比对结果
				if ($decryptStr == $sha256SourceSignString) {
					/**
					 * 验签通过
					 */
					//解密data
					$decrypData = TDESUtil::decrypt4HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$data);
		
					//退款结果实体
					$resultData= json_decode($decrypData,true);
		
					//错误消息
					if(null==$resultData){
						$_SESSION['errorMsg'] = $decrypData;
					}
					else{
						$_SESSION['resultData'] = $resultData;
					}
				} else {
					/**
					 * 验签失败  不受信任的响应数据
					 * 终止
					 */
					$_SESSION ['errorMsg'] ="签名失败!";
		
				}
			}
		}
		//执行退款 失败
		else{
			$_SESSION['errorMsg'] = $return_data['resultMsg'];
		}

		header("location:../tpl/refundResult.php");
		
	}
	
	public function  prepareParms(){
		
		$tradeJsonData= "{\"tradeNum\": \"".$_POST["tradeNum"]."\",\"oTradeNum\": \"".$_POST["oTradeNum"]."\",\"tradeAmount\":\"".$_POST["tradeAmount"]."\",\"tradeCurrency\": \"".$_POST["tradeCurrency"]."\",\"tradeDate\": \"".$_POST["tradeDate"]."\",\"tradeTime\": \"".$_POST["tradeTime"]."\",\"tradeNotice\": \"".$_POST["tradeNotice"]."\",\"tradeNote\": \"".$_POST["tradeNote"]."\"}";

		$tradeData = TDESUtil::encrypt2HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$tradeJsonData);
        
		$sha256SourceSignString = hash ( "sha256", $tradeData);	
        $sign = RSAUtils::encryptByPrivateKey ($sha256SourceSignString);

		$params= array();
		$params["version"] = $_POST["version"];
		$params["merchantNum"] = $_POST["merchantNum"];
		$params["merchantSign"] = $sign;
		$params["data"] = $tradeData;
		
		return $params;
	}
	
}

$webRefundSign = new WebRefundSign();
$webRefundSign->execute();


?>
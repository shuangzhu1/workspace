<?php

namespace Components\Payments\Wyplus\Api;

use Components\Payments\Wyplus\RSAUtils;
use Components\Payments\Wyplus\TDESUtil;
use Components\Payments\Wyplus\ConfigUtil;


/**
 * 交易查询-验签
 *
 * @author wylitu
 *        
 */
class WebQuerySign {

	public function http_post_data($url, $data_string) {
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
	public function query() {
		$params = $this->prepareParms ();

		$data = json_encode ($params);
		list ( $return_code, $return_content ) = $this->http_post_data (ConfigUtil::get_val_by_key("serverQueryUrl"), $data);

		$return_content = str_replace("\n", '', $return_content);
        
		$return_data = json_decode ($return_content,true);

		// 执行状态 成功
		$_SESSION ['errorMsg'] = null;
		$_SESSION ['queryDatas'] =null;
		if ($return_data ['resultCode'] == 0) {
			$mapResult = $return_data ['resultData'];
			// 有返回数据
			if (null != $mapResult) {
				$data = $mapResult ["data"];
				$sign = $mapResult ["sign"];
				// 1.解密签名内容
				$decryptStr = RSAUtils::decryptByPublicKey($sign);

				// 2.对data进行sha256摘要加密
				$sha256SourceSignString = hash ( "sha256",$data);
				
				// 3.比对结果
				if ($decryptStr == $sha256SourceSignString) {
					/**
					 * 验签通过
					 */
					// 解密data
					$decrypData = TDESUtil::decrypt4HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$data);
					
					// 注意 结果为List集合
					$decrypData = json_decode ( $decrypData, true );
					//var_dump($decrypData);
					// 错误消息
					if (count ( $decrypData ) < 1) {
						$_SESSION ['errorMsg'] = decrypData;
						$_SESSION ['queryDatas'] =null;
					} else {
						$_SESSION ['queryDatas'] = $decrypData;
					}
				} else {
					/**
					 * 验签失败 不受信任的响应数据
					 * 终止
					 */
					$_SESSION ['errorMsg'] ="验签失败!";
				}
			}
		} 		// 执行查询 失败
		else {
			$_SESSION ['errorMsg'] = $return_data ['resultMsg'];
			$_SESSION ['queryDatas'] =null;
		}

		header ( "location:../tpl/queryResult.php" );
	}
	public function prepareParms() {

		$tradeJsonData = "{\"tradeNum\": \"". $_POST ["tradeNum"]."\"}";
		
		// 1.对交易信息进行3DES加密
		$tradeData = TDESUtil::encrypt2HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$tradeJsonData);
				
		// 2.对3DES加密的数据进行签名
		$sha256SourceSignString = hash ( "sha256", $tradeData );
		$sign = RSAUtils::encryptByPrivateKey ( $sha256SourceSignString);
		
		$params = array ();
		$params ["version"] = $_POST ["version"];
		$params ["merchantNum"] = $_POST ["merchantNum"];
		$params ["merchantSign"] = $sign;
		$params ["data"] = $tradeData;
		return $params;
	}
}
$webQuerySign = new WebQuerySign ();
$webQuerySign->query ();

?>
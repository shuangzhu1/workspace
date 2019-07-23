<?php

namespace Components\Payments\Wyplus;

use Phalcon\Mvc\User\Plugin;

class ConfigUtil extends Plugin {

	const PYA_MODE_PC_DIRECT = "pc_direct";
	const PAY_MODE_WAP_DIRECT = "wap_direct";
	/**
	 * @var ConfigUtil
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	public $configData = null;

	/**
	 * @return ConfigUtil
	 */
	public static function  init($config = null) {
		if(!self::$instance instanceof ConfigUtil) {
			self::$instance = new self();
		}

		if(is_array($config) && count($config) > 0) {
			foreach($config as $k => $v) {
				self::$instance->configData[$k] = $v;
			}
		}
		return self::$instance;
	}

	private function __construct() {
		$this->configData = $this->di->get('config')->payment->cbpay->toArray();
	}

	public function get_val_by_key($key = null) {
		if(is_string($key) && strlen(trim($key)) > 0) {
			return isset($this->configData[trim($key)]) ? $this->configData[trim($key)] : "";
		}
		else {
			return $this->configData;
		}
	}
	public function get_trade_num() {
		return $this->get_val_by_key ( 'merchantNum' ) . self::getMillisecond ();
	}
	public static function getMillisecond() {
		list ( $s1, $s2 ) = explode ( ' ', microtime () );
		return ( float ) sprintf ( '%.0f', (floatval ( $s1 ) + floatval ( $s2 )) * 1000 );
	}
}

?>
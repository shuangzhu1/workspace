<?php

namespace Components\Payments\Wyplus\Api;
use Components\Payments\Wyplus\ConfigUtil;

/**
 * 接收异步通知控制器
 *
 * @author wylitu
 *
 */
class WebAsynNotificationCtrl
{

    public function xml_to_array($xml)
    {
        $array = ( array )(simplexml_load_string($xml));
        foreach ($array as $key => $item) {
            $array [$key] = $this->struct_to_array(( array )$item);
        }
        return $array;
    }

    public function struct_to_array($item)
    {
        if (!is_string($item)) {
            $item = ( array )$item;
            foreach ($item as $key => $val) {
                $item [$key] = $this->struct_to_array($val);
            }
        }
        return $item;
    }

    /**
     * 签名
     */
    public function generateSign($data, $md5Key)
    {
        $sb = $data ['VERSION'] [0] . $data ['MERCHANT'] [0] . $data ['TERMINAL'] [0] . $data ['DATA'] [0] . $md5Key;

        return md5($sb);
    }

    public function execute($md5Key, $desKey, $resp)
    {
        // 获取通知原始信息
        echo "异步通知原始数据:" . $resp . "\n";
        if (null == $resp) {
            return;
        }

        // 获取配置密钥
        echo "desKey:" . $desKey . "\n";
        echo "md5Key:" . $md5Key . "\n";
        // 解析XML
        $params = $this->xml_to_array(base64_decode($resp));

        $ownSign = $this->generateSign($params, $md5Key);
        $params_json = json_encode($params);
        echo "解析XML得到对象:" . $params_json . '\n';
        echo "根据传输数据生成的签名:" . $ownSign . "\n";

        if ($params ['SIGN'] [0] == $ownSign) {
            // 验签不对
            echo "签名验证正确!" . "\n";
        } else {
            echo "签名验证错误!" . "\n";
            return;
        }
        // 验签成功，业务处理
        // 对Data数据进行解密
        $des = new DesUtils (); // （秘钥向量，混淆向量）
        $decryptArr = $des->decrypt($params ['DATA'] [0], $desKey); // 加密字符串
        echo "对<DATA>进行解密得到的数据:" . $decryptArr . "\n";
        $params ['data'] = $decryptArr;
        echo "最终数据:" . json_encode($params) . '\n';
        echo "**********接收异步通知结束。**********";

        return;
    }
}
?>





















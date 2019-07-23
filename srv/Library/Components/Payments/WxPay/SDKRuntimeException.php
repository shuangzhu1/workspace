<?php
namespace Components\Payments\WxPay;

class  SDKRuntimeException extends \Exception
{
    public function errorMessage()
    {
        return $this->getMessage();
    }

}

?>
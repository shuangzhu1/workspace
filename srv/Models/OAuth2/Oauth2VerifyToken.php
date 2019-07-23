<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/30
 * Time: 16:05
 */
namespace Models\OAuth2;
use Models\BaseModel;

class Oauth2VerifyToken extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService('db_open');
    }
}
<?php
namespace Models\User;
use Models\BaseModel;

class MessageTimingPush extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}
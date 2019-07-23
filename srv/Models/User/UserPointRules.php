<?php
namespace Models\User;

use Models\BaseModel;
use Phalcon\Mvc\Model;

class UserPointRules extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}

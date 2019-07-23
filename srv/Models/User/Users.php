<?php
namespace Models\User;

use Models\BaseModel;
use Models\Modules\Vipcard\VipcardNumber;
use Models\Modules\Vipcard\VipcardRules;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness as UniquenessValidator;
use Phalcon\Mvc\Model\Query\Builder as Querybuilder;
use Models\Modules\Vipcard\VipcardRecords;

class Users extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}

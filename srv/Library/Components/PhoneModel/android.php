<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/12
 * Time: 15:59
 */

namespace Components\PhoneModel;


class android extends phoneModelAbstract implements phoneModelAdapter
{
    public function getName($model)
    {
        return $model;
    }
}
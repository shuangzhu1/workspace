<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/6
 * Time: 9:53
 */

namespace Services\User\Square;


class TaskStructure
{
    public $behavior = 0;
    public $action = '';
    public $term = 0;
    public $value = 0;
    public $created;
    public $total = 0;
    public $limit_count = 0;
    public $is_permanent = 0;

    public function getBehavior()
    {
        return $this->behavior;
    }

    public function setBehavior($behavior)
    {
        $this->behavior = $behavior;
        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit_count;
    }

    public function setLimit($limit_count)
    {
        $this->limit_count = $limit_count;
        return $this;
    }

    public function getPermanent()
    {
        return $this->is_permanent;
    }

    public function setPermanent($is_permanent)
    {
        $this->is_permanent = $is_permanent;
        return $this;
    }

    public function getTerm()
    {
        return $this->term;
    }

    public function setTerm($term)
    {
        $this->term = $term;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    public function isValid()
    {
        if (!empty($this->action) && $this->behavior > 0 && $this->value > 0 && $this->term > 0) {
            return true;
        } else {
            return false;
        }

    }
}
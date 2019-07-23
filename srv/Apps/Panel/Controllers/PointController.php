<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/19
 * Time: 17:23
 */

namespace Multiple\Panel\Controllers;


use Components\Rules\Point\PointRule;
use Models\User\UserPointRules;

class PointController extends ControllerBase
{
    public function rulesAction()#经验值规则#
    {
        $where = [];
        \Phalcon\Tag::setTitle("经验值规则");
        $behaviorNameMap = PointRule::$behaviorNameMap;
        $rule = new PointRule(0);
        $termNameMap = $rule->termNameMap;
        $pointTypeMap = $rule->actionNameMap;

        $list = UserPointRules::findList($where);

        $exits_rules = array_map(function ($row) {
            return $row['behavior'];
        }, $list);

        $new_add = array_diff_key($behaviorNameMap, array_flip($exits_rules));

        $this->view->data = $list;
        $this->view->new_add = $new_add;
        $this->view->behaviorNameMap = $behaviorNameMap;
        $this->view->termNameMap = $termNameMap;
        $this->view->pointTypeMap = $pointTypeMap;
    }

}
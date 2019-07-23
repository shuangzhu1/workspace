<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/2
 * Time: 15:22
 */

namespace Multiple\Wap\Controllers;


use Models\Agent\Agent;
use Models\User\UserInfo;
use Models\User\Users;

class AgentController extends ControllerBase
{
    public function relationAction()
    {
        $uid = $this->request->get('uid', 'int', 0);
        $type = $this->request->get('type', 'int', 0);
        $role = [1 => 'parent_merchant', 3 => 'parent_partner'];
        $noData = false;
        if ($uid !== 0 && in_array($type, [1, 3]) && Users::exist('id = ' . $uid) && Agent::exist($role[$type] . ' = ' . $uid))//有效用户
        {
            $first = $second = $third = $fourth = [];
            $columns = 'user_id as uid,' . $role[$type] . ' as pid';
            //  $where = "user_id <> " . $role[$type] . ' and ';//去除自己邀请码开店
            $where = "";
            $first = Agent::findList([$where . $role[$type] . ' = ' . $uid, 'columns' => $columns]);
            //$first['count'] = count($first);
            $first_temp = array_diff(array_column($first, 'uid'), [$uid]);
            if ($first_temp) {
                $second = Agent::findList([$where . $role[$type] . ' in (' . implode(',', $first_temp) . ')', 'columns' => $columns]);
                //$second['count'] = count($second);
                $second_temp = array_diff(array_column($second, 'uid'), [$uid]);
                if ($second_temp) {
                    $third = Agent::findList([$where . $role[$type] . ' in (' . implode(',', $second_temp) . ')', 'columns' => $columns]);
                    //$third['count'] = count($third);
                    $third_temp = array_diff(array_column($third, 'uid'), [$uid]);
                    if ($third_temp) {
                        $fourth = Agent::findList([$where . $role[$type] . ' in (' . implode(',', $third_temp) . ')', 'columns' => $columns]);
                    }
                }
            }
            //查询用户简要信息
            $uids = [$uid];
            $uids = array_merge($uids, array_column($first, 'uid'), array_column($second, 'uid'), array_column($third, 'uid'), array_column($fourth, 'uid'));
            $userInfo = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid,username,avatar'], 'uid');
            $users = array_merge($first, $second, $third, $fourth);
            $users = array_combine(array_column($users, 'uid'), $users);
//            var_dump($users);
//            exit;
            $userTree = self::getTree($users, $uid);
            $this->view->setVar('userTree', $userTree);
            $this->view->setVar('userInfo', $userInfo);
        } else {
            $noData = true;
        }
        $this->view->setVar('title', '邀请详情');
        $this->view->setVar('noData', $noData);

    }

    /*private function getTree($list)
    {
        $tree = [];
        foreach($list as $key => $node){
            if(isset($list[$node['pid']])  ){
                $list[$node['pid']]['children'][$key] = &$list[$key];
            }else{
                $tree[] = &$list[$node['uid']];
            }
            unset($node['pid']);
        }
        $tree = array_combine(array_column($tree,'uid'),$tree);
        return $tree;
    }*/

    private function getTree($data, $pid)
    {
        $tree = array();
        foreach ($data as $v) {
            if ($v['pid'] == $pid) {
                if ($v['pid'] == $v['uid']) {
                    $v['children'] = [];
                    $tree[] = $v;
                } else {
                    $v['children'] = self::getTree($data, $v['uid']);
                    $tree[] = $v;
                }
            }
        }
        return $tree;
    }


}
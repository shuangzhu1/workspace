<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/9
 * Time: 14:48
 */

namespace Multiple\Panel\Controllers;


use Models\Admin\Admins;
use Models\Social\SocialDiscuss;
use Models\User\UserInfo;
use Models\User\Users;
use Models\Virtual\VirtualDiscuss;
use Services\Discuss\TagManager;
use Services\Site\AreaManager;
use Util\Pagination;
use Util\Uri;

class VirtualController extends ControllerBase
{
    //添加虚拟动态
    public function discussAddAction()#添加虚拟动态#
    {
        /*   @ini_set('memory_limit', '25999990000000000000000060M');
         * $f = fopen(ROOT . '/uploads/test.mp4', 'r');
         * $f2 = fopen(ROOT . '/uploads/test3.mp4', 'r');
         * // $content2 = strlen(file_get_contents(ROOT . '/uploads/test.mp4'));
         * // $content = strlen(file_get_contents(ROOT . '/uploads/test3.mp4'));
         * $content = fread($f2, 10000);
         * $content2 = fread($f, 10000);
         * var_dump(str_replace(array("\r\n", "\r", "\n"), "", substr($content2, 0, 10)) . substr($content2, 10));
         * // $charset[1] = substr($content, 0, 1);
         * // $charset[2] = substr($content, 1, 1);
         * //  $charset[3] = substr($content, 2, 1);
         * //   echo  "99".ord($charset[1])."/".ord($charset[2]).'/'.ord($charset[3]);
         * // file_put_contents($name, $content);
         * echo "<br/>";
         * var_dump($content);
         * //var_dump(fread($f, 10000));
         *
         * exit;*/
        /*  if (!$this->admin['app_uid']) {
              $this->flash->error("请先绑定app账号再发帖");
              exit;
          }*/
        $provinces = AreaManager::getInstance()->getProvinces();
        $this->view->setVar('provinces', $provinces);
        $tags = TagManager::getInstance()->list();
        $users = Users::findList(['id in (' . $this->admin['app_uid'] . ')', 'columns' => 'username,id,avatar']);
        $this->view->setVar('tags', $tags);
        $this->view->setVar('users', $users);
    }

    //虚拟动态列表
    public function discussListAction()#虚拟动态列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $key = $this->request->get('key', 'string', '');//关键字
        $status = $this->request->get('status', 'int', '-1');//状态
        $media_type = $this->request->get('media_type', 'int', 0);//媒体类型
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $admin_id = $this->request->get('admin_id', 'int', 0);//管理员id
        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username="' . $key . '" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($status != -1) {
            $params[0][] = ' status = ' . $status;
        }
        if ($media_type) {
            $params[0][] = ' media_type = ' . $media_type;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($admin_id) {
            $params[0][] = ' admin_id  =' . $admin_id;
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = VirtualDiscuss::dataCount($params[0]);
        $list = VirtualDiscuss::findList($params);

        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('media_type', $media_type);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $admin = Admins::getByColumnKeyList(['status=1', 'columns' => 'id,name'], 'id');
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $discuss_ids = array_column($list, 'discuss_id');
            $admin_ids = array_column($list, 'admin_id');

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $discuss = SocialDiscuss::getByColumnKeyList(['id in (' . implode(',', $discuss_ids) . ')'], 'id');
            foreach ($list as &$item) {
                unset($discuss[$item['discuss_id']]['id']);
                $item = array_merge($item, $discuss[$item['discuss_id']]);
                $item['admin_name'] = $admin[$item['admin_id']]['name'];
            }
            $this->view->setVar('list', $list);
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('admin_list', $admin);
        $this->view->setVar('admin_id', $admin_id);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //虚拟视频
    public function videoAction()#添加记录#
    {
        $admin = Admins::getByColumnKeyList(['status=1', 'columns' => 'id,name'], 'id');
        $this->view->setVar('admin_list', $admin);
        $users = Users::findList(['id in (' . $this->admin['app_uid'] . ')', 'columns' => 'username,id,avatar']);
        $this->view->setVar('users', $users);
    }
}
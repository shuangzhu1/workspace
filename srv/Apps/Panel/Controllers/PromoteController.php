<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/21
 * Time: 11:34
 */

namespace Multiple\Panel\Controllers;


use Components\Curl\CurlManager;
use Util\Ajax;
use Util\Pagination;

class PromoteController extends ControllerBase
{
    public function indexAction()
    {

    }

    /**
     * 配置
     */
    public function configAction()#推广配置#
    {
        if( $this->request->isPost() )
        {
            $post = $this->request->getPost();
            $data = [];
            $data['switch'] = (int) $post['data']['switch'];
            $data['grade1reward'] = (int) ($post['data']['grade1reward'] * 100);
            $data['grade2reward'] = (int) ($post['data']['grade2reward'] * 100);
            $data['grade3reward'] = (int) ($post['data']['grade3reward'] * 100);
            $data['explain'] = str_replace("\n",'\n',$post['data']['explain']);
            $this->postApi('promote/config/update',['config' => json_encode($data)]);
            if( $data['switch'] == 1)
            {
                Ajax::init()->outRight('start');
            }else
            {
                Ajax::init()->outRight('end');
            }
        }
        //获取配置
        $res = $this->postApi('promote/config/check',[]);
        $res['explain'] = str_replace('\n',"\n",$res['explain']);
        //获取当前奖励池余额
        $money = $this->postApi('promote/remain/check',[]);
        $this->view->setVar('data',$res);
        $this->view->setVar('money',$money);
    }

    /**
     * 增加奖励池金额
     */
    public function addRemainAction()
    {
        $money = (int) $this->request->getPost('money');
        $this->postApi('promote/remain/add',['money' => $money]);
        Ajax::init()->outRight();
    }

    /**
     * 参加推广成员列表
     */
    public function memberListAction()#推广成员列表#
    {
        if( $this->request->isPost() && !empty($this->request->get('uid')))//搜索
        {
            $uid = $this->request->get('uid');
            $url = 'promote/user/check';
            $data[] = $this->postApi($url,['uid' => $uid]);
            if( $data[0]['num'] == null )
            {
                $data = [];
            }
            $this->view->setVar('list',$data);
            $this->view->setVar('val',$uid);
        }else
        {
            $page = $this->request->get('p','int',1);
            $limit = $this->request->get('limit','int',20);
            $url = 'promote/user/list';
            $data = $this->postApi($url,['page'=>$page,'limit'=>$limit]);
            $this->view->setVar('list',$data['list']);
            Pagination::instance($this->view)->showPage($page, $data['tatol'], $limit);
        }

    }

    /**
     * 显示特定用户下的某一等级的推广列表
     */
    public function someLevelListAction()#推广列表#
    {
        $uid = $this->request->get('uid');
        $level = $this->request->get('level');
        $page = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',20);
        $url = 'promote/subordinate/list';
        $data = $this->postApi($url,['uid' => $uid, 'level' => $level, 'page' => $page, 'limit' => $limit]);
        $this->view->setVar('list',$data['list']);
        $this->view->setVar('uid',$uid);
        Pagination::instance($this->view)->showPage($page, $data['tatol'], $limit);
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/23
 * Time: 16:36
 */

namespace Multiple\Panel\Api;


use Models\Site\SiteMaterial;
use Services\Im\SysMessage;
use Util\Ajax;
use Util\EasyEncrypt;

class NoticeController extends ApiBase
{
    public function addAction()
    {
        $post = $this->request->getPost();
        if (strpos($post['thumb'], 'http') === false)//前端传过来的默认图片不保存
            $post['thumb'] = '';
        if( !empty($post['id']) )//编辑
        {
            $res = SiteMaterial::updateOne(
                [
                    'title' => $post['title'] ,
                    'content' => $post['editorValue'] ,
                    'thumb' => $post['thumb'],
                    'updated' => time()
                ],
                [
                    'id' => $post['id']
                ]);
            if( (int) $post['type'] === 2)//红包广场公告推送
            {
                $this->noticeModifyAction(2);
            }
        }else
        {
            $link = EasyEncrypt::encode(time() . rand(1000, 9999));

            $res = SiteMaterial::insertOne(
                [
                    'title' => $post['title'] ,
                    'link' => $link,
                    'content' => $post['editorValue'] ,
                    'thumb' => $post['thumb'] ,
                    'type' => $post['type'],
                    'created' => time(),
                    'updated' => time()
                ]
            );
            if( (int) $post['type'] === 2)//红包广场公告推送
            {
                $this->noticeModifyAction(1);
            }
        }
        if( $res )
            $this->ajax->outRight();
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG);
    }

    //删除公告
    public function noticeDelAction()
    {
        $id = $this->request->get('id', 'int', 0);
        if ($id <= 0)
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, 'id不合法');
        $res = SiteMaterial::updateOne(['enable' => 0], ['id' => $id]);
        if ($res) {
            if( SiteMaterial::exist(['type = 2 and id = ' . $id ]) )//红包广场公告推送
            {
                $this->noticeModifyAction(3);
            }
            Ajax::init()->outRight('');
        } else
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '删除失败');

    }
    //删除与恢复
    public function toggleEnableAction()
    {
        $id = $this->request->get('id', 'int', 0);
        $enable = $this->request->get('enable', 'int', 0);
        if ($id <= 0)
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, 'id不合法');
        $res = SiteMaterial::updateOne(['enable' => $enable], ['id' => $id]);
        if ($res) {
            Ajax::init()->outRight('');
        } else
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '删除失败');
    }
    /**
     * 公告更新，推送系统消息到客户端
     * @param $type 1:添加公告
     * @param $type 2:编辑公告
     * @param $type 3:删除公告
     */
    private function noticeModifyAction($type)
    {
        //$type = $this->request->get('type','int',0);
        $body['type'] = $type;
        $where = 'type = 2 and enable = 1';
        $start = strtotime(date('Y/m/d', strtotime('-4 days')));
        $where .= ' and created >= ' . $start;
        $notices = SiteMaterial::findList([$where, 'columns' => 'id,title'], 'id');

        if (!empty($notices)) {
            $body['notices'] = $notices;
        } else//最近5天无公告，取最新一条
        {
            $notice = SiteMaterial::findOne(['type = 2 and enable = 1', 'order' => 'created desc', 'columns' => 'id,title']);
            if (!empty($notice))
                $body['notices'][] = $notice;
            else
                $body['notices'] = [];
        }
        SysMessage::init()->initMsg(SysMessage::TYPE_NOTICE_MODIFY, $body);
        Ajax::init()->outRight('推送成功');
    }
}
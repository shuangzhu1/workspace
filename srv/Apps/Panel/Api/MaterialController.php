<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/8
 * Time: 10:38
 */

namespace Multiple\Panel\Api;

use Models\Site\SiteMaterial;
use Util\Ajax;
use Util\EasyEncrypt;

class MaterialController extends ApiBase
{
    //添加素材
    public function addAction()
    {

        $id = $this->request->get('id','int',0);

        $data['title'] = $this->request->getPost('title');

        $data['thumb'] = $this->request->getPost('thumb');
        $data['content'] = $this->request->getPost('editorValue');
        $data['type'] = 1;
        $time = time();
        if(empty($id))//增加
        {
            $data['link'] = EasyEncrypt::encode(time() . rand(1000,9999));
            $data['created'] = $time;
            $data['updated'] = $time;
            $res = SiteMaterial::insertOne($data);
            if( $res )
                Ajax::init()->outRight('操作成功');
            else
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'保存失败');
        }else//编辑
        {
            $data['updated'] = $time;
            $res = SiteMaterial::updateOne($data,['id' => $id]);
            if( $res )
                Ajax::init()->outRight('操作成功');
            else
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'保存失败');
        }
    }

    //删除
    public function delAction()
    {
        $id = $this->request->get('id','int',0);
        if($id <= 0)
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'id不合法');
        $res = SiteMaterial::updateOne(['enable' => 0],['id' => $id]);
        if( $res )
            Ajax::init()->outRight('');
        else
            Ajax::init()->outError(Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'删除失败'));

    }

    //图片上传
    public function uploadAction()
    {
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("static/panel/js/ueditor/php/config.json")), true);
        $action = $_GET['action'];
        switch ($action) {
            case 'config':
                $result =  json_encode($CONFIG);
                break;

            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = include(ROOT . "/static/panel/js/ueditor/php/action_upload.php");
                break;

            /* 列出图片 */
            case 'listimage':
                $result = include(ROOT . "/static/panel/js/ueditor/php/action_list.php");
                break;
            /* 列出文件 */
            case 'listfile':
                $result = include(ROOT . "/static/panel/js/ueditor/php/action_list.php");
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = include(ROOT . "/static/panel/js/ueditor/php/action_crawler.php");
                break;

            default:
                $result = json_encode(array(
                    'state'=> '请求地址出错'
                ));
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state'=> 'callback参数不合法'
                ));exit;
            }
        } else {
            echo $result;exit;
        }
    }
}
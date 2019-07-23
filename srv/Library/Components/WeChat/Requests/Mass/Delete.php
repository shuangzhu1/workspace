<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;
use Components\WeChat\ResourceManager;

class SendMessage extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=";
    protected $grantType = "client_credential";
    protected $toUser = '';
    protected $msgType = 'text';
    protected $message = '';

    public function run()
    {
        $this->msgType = strtolower($this->msgType);
        return $this->singleRequest($this->requestUri, array(
            'touser' => $this->toUser,
            'msgtype' => $this->msgType,
            $this->msgType => $this->getMessageStructure($this->msgType, $this->message)
        ), true, true);
    }

    private function getMessageStructure($type, $data)
    {
        if (empty($type)) {
            return false;
        }
        $type = strtolower($type);
        switch ($type) {
            case ResourceManager::MESSAGE_TYPE_TEXT: {
                return array(
                    'content' => $data
                );
            }
            case ResourceManager::MESSAGE_TYPE_VOICE:
            case ResourceManager::MESSAGE_TYPE_IMAGE: {
                return array(
                    'media_id' => $data['media_id']
                );
            }
            case ResourceManager::MESSAGE_TYPE_VIDEO: {
                return array(
                    'media_id' => $data['media_id'],
                    'title' => $data['title'],
                    'description' => $data['desc']
                );
            }
            case ResourceManager::MESSAGE_TYPE_MUSIC: {
                return array(
                    'media_id' => $data['media_id'],
                    'title' => $data['title'],
                    'description' => $data['desc'],
                    'musicurl' => $data['music_url'],
                    'hqmusicurl' => $data['hq_music_url'],
                    'thumb_media_id' => $data['thumb_media_id']
                );
            }
            case ResourceManager::MESSAGE_TYPE_NEWS: {
                $articles = array();
                foreach ($data['articles'] as $article) {
                    $articles[] = array(
                        'title' => $data['title'],
                        'description' => $data['desc'],
                        'url' => $data['music_url'],
                        'picurl' => $data['pic_url']
                    );
                }
                return array(
                    'articles' => $articles
                );
            }
        }
        return false;
    }
}

?>
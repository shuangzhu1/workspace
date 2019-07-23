<?php

namespace Components\WeChat;

// use Components\JSON2XML;
use Phalcon\Mvc\User\Plugin;
use Util\EasyEncrypt;

class MessageCreator extends Plugin
{

    /**
     * @var MessageCreator
     */
    private static $instance = null;

    public $baseUrl = "";

    /**
     * @var \Phalcon\Logger\Adapter\File
     */
    public $logger = null;

    private function __construct()
    {
        $this->logger = $this->di->get("wechatLogger");
    }

    public static function instance()
    {
        if (!self::$instance instanceof MessageCreator) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function createTextMessage($from, $to, $content)
    {
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_TEXT;
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[{$type}]]></MsgType>
    <Content><![CDATA[{$content}]]></Content>
</xml>
EOF;
        return $data;
    }

    public function createTCServiceMessage($from, $to)
    {
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_TRANSFER_CUSTOMER_SERVICE;
        $this->di->get('wechatLogger')->info($from . ':' . $to);
        /*if($servant && strlen($servant) > 0) {
            $servantData = <<<EOF
    <TransInfo>
        <KfAccount>{$servant}</KfAccount>
    </TransInfo>
EOF;
        }
        else {
            $servantData = '';
        }*/
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[{$type}]]></MsgType>
</xml>
EOF;
        return $data;
    }

    public function createVoiceMessage($from, $to, $mediaId)
    {
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_VOICE;
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[{$type}]]></MsgType>
    <Video>
       <MediaId><![CDATA[{$mediaId}]]></MediaId>
    </Video>
</xml>
EOF;
        return $data;
    }

    public function createImageMessage($from, $to, $mediaId)
    {
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_IMAGE;
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[{$type}]]></MsgType>
    <Image>
        <MediaId><![CDATA[{$mediaId}]]></MediaId>
    </Image>
EOF;
        return $data;
    }

    public function createVideoMessage($from, $to, $mediaId, $title, $desc)
    {
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_VIDEO;
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[{$type}]]></MsgType>
    <Video>
        <MediaId><![CDATA[{$mediaId}]]></MediaId>
        <Title><![CDATA[{$title}]]></Title>
        <Description><![CDATA[{$desc}]]</Description>
    </Video>
</xml>
EOF;
        return $data;
    }

    public function createMusicMessage($from, $to, $musicUrl, $title, $desc, $hqUrl, $thumb)
    {
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_MUSIC;
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[{$type}]]></MsgType>
    <Music>
        <Title><![CDATA[{$title}]]></Title>
        <Description><![CDATA[{$desc}]]></Description>
        <MusicUrl><![CDATA[{$musicUrl}]]></MusicUrl>
        <HQMusicUrl><![CDATA[{$hqUrl}]]></HQMusicUrl>
        <ThumbMediaId'><![CDATA[{$thumb}]]></ThumbMediaId>
    </Music>
</xml>
EOF;
        return $data;
    }

    public function createNewsMessage($from, $to, $customerId, $articles = array(), $defaultLink = null)
    {
        $this->logger->info("news message");
        if ($customerId <= 0) {
            return false;
        }
        $articlesArr = "";
        $articlesNum = 0;
        foreach ($articles as $article) {
            if ($articlesNum > 10) {
                break;
            }
            if (empty($articlesNum) && is_string($defaultLink) && strlen($defaultLink) > 0) {
                $articleUrl = $defaultLink;
            } else {
//                $articleUrl = $this->request->getScheme() . '://' . CUR_APP_ID . '.' . WAP_DOMAIN_DS . '.' .  MAIN_DOMAIN . '/article/detail/' . EasyEncrypt::encode($article['value']);
                $articleUrl = $this->request->getScheme() . '://' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/article/detail/' . EasyEncrypt::encode($article['value']);
            }
            if (strpos($articleUrl, '?') === false) {
                $articleUrl .= '?from_user=' . EasyEncrypt::encode($to) . '&platform=' . MessageManager::PLATFORM_TYPE_WEIXIN;
            } else {
                $articleUrl .= '&from_user=' . EasyEncrypt::encode($to) . '&platform=' . MessageManager::PLATFORM_TYPE_WEIXIN;
            }

            $image = $article['image'];
            if (strpos($image, "http://") === false && strpos($image, "https://") === false) {
//                $image = $this->request->getScheme() . '://' . CUR_APP_ID . '.' . WAP_DOMAIN_DS . '.' .  MAIN_DOMAIN . '/' . ltrim($image, '/');
                $image = $this->request->getScheme() . '://' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/' . ltrim($image, '/');
            }

            $articlesArr .= <<<EOF
<item>
    <Title><![CDATA[{$article['title']}]]></Title>
    <Description><![CDATA[{$article['desc']}]]></Description>
    <PicUrl><![CDATA[{$image}]]></PicUrl>
    <Url><![CDATA[{$articleUrl}]]></Url>
</item>
EOF;

            $articlesNum++;
        }
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_NEWS;
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>{$articlesNum}</ArticleCount>
    <Articles>
        {$articlesArr}
    </Articles>
</xml>
EOF;
        return $data;
    }

    public function createProductMessage($from, $to, $customerId, $products = array(), $defaultLink = null)
    {
        if ($customerId <= 0) {
            return false;
        }
        $articlesArr = "";
        $articlesNum = 0;
        foreach ($products as $article) {
            if ($articlesNum > 10) {
                break;
            }
            if (empty($articlesNum) && is_string($defaultLink) && strlen($defaultLink) > 0) {
                $productUrl = $defaultLink;
            } else {
//                $productUrl = $this->request->getScheme() . '://' .  CUR_APP_ID . '.' . WAP_DOMAIN_DS . '.' .  MAIN_DOMAIN . '/shop/item/' . EasyEncrypt::encode($article['value']);
                $productUrl = $this->request->getScheme() . '://' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/shop/item/' . EasyEncrypt::encode($article['value']);
            }
            if (strpos($productUrl, '?') === false) {
                $productUrl .= '?from_user=' . EasyEncrypt::encode($to) . '&platform=' . MessageManager::PLATFORM_TYPE_WEIXIN;
            } else {
                $productUrl .= '&from_user=' . EasyEncrypt::encode($to) . '&platform=' . MessageManager::PLATFORM_TYPE_WEIXIN;
            }

            $image = $article['image'];
            if (strpos($image, "http://") === false && strpos($image, "https://") === false) {
//                $image = $this->request->getScheme() . '://' . CUR_APP_ID . '.' . WAP_DOMAIN_DS . '.' .  MAIN_DOMAIN . '/' . ltrim($image, '/');
                $image = $this->request->getScheme() . '://' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/' . ltrim($image, '/');
            }
            $articlesArr .= <<<EOF
<item>
    <Title><![CDATA[{$article['title']}]]></Title>
    <Description><![CDATA[{$article['desc']}]]></Description>
    <PicUrl><![CDATA[{$image}]]></PicUrl>
    <Url><![CDATA[{$productUrl}]]></Url>
</item>
EOF;

            $articlesNum++;
        }
        $time = time();
        $type = ResourceManager::MESSAGE_TYPE_NEWS;
        $data = <<<EOF
<xml>
    <ToUserName><![CDATA[{$to}]]></ToUserName>
    <FromUserName><![CDATA[{$from}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>{$articlesNum}</ArticleCount>
    <Articles>
        {$articlesArr}
    </Articles>
</xml>
EOF;
        return $data;
    }
}

?>
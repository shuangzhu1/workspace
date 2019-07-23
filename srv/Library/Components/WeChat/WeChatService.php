<?php

namespace Components\WeChat;

use Components\UserManager;
use Models\CustomerOpenInfo;
use Models\User\UserForCustomers;
use Models\User\UserLocations;
use Models\User\UsersWechat;
use Models\WeChat\CustomerMenus;
use Models\WeChat\EventHistory;
use Models\WeChat\MessageHistory;
use Models\WeChat\MessageResourceImages;
use Models\WeChat\MessageResourceVideos;
use Models\WeChat\MessageResourceVoices;
use Phalcon\Logger;
use Phalcon\Mvc\User\Component;

/**
 * @author wgwang
 *
 */
class WeChatService extends Component
{
    /**
     * 平台用户的ID
     * @var int
     */
    private $customer;

    /**
     * 平台用户微信号
     * @var string
     */
    private $customerName;

    /**
     * 微信对接token
     * @var string
     */
    private $token;

    /**
     * 微信app id
     * @var string
     */
    private $appId;

    /**
     * 微信app secret
     * @var string
     */
    private $appSecret;

    /**
     * 发送消息过来的微信用户微信号
     * @var string
     */
    private $toUser;

    private $respondMessage = '';

    private $respondMessageType = ResourceManager::MESSAGE_TYPE_TEXT;

    private $respondDefaultLink = null;

    /**
     * @var UsersWechat
     */
    private $user_info = null;

    /**
     * @var \Phalcon\Http\Request
     */
    private $request;

    /**
     * @var null|\Phalcon\Mvc\Model
     */
    private $open_info = null;

    /**
     * @var \Phalcon\Logger\Adapter\File
     */
    private $logger;

    /**
     * @param array $config
     * @param \Phalcon\Http\RequestInterface $request
     */
    public function __construct(array $config = NULL, \Phalcon\Http\RequestInterface $request)
    {
        $this->logger = $this->di->get("wechatLogger");
//         $this->logger = new \Phalcon\Logger\Adapter\File(ROOT . '/Cache/log/wechat.log');
        if (is_array($config)) {
            $this->config($config);
        }
        $this->request = $request;
        $this->logger->info("-----------start handle a request.-----------------------");
        $this->open_info = CustomerOpenInfo::findFirst("customer_id='{$this->customer}' AND platform='" . MessageManager::PLATFORM_TYPE_WEIXIN . "'");
    }

    public function config(array $config)
    {
        if (isset($config['customer_id'])) {
            $this->customer = $config['customer_id'];
        }
        if (isset($config['customer_app_account'])) {
            $this->customerName = $config['customer_app_account'];
        }
        if (isset($config['token'])) {
            $this->token = $config['token'];
        }

        if (isset($config['app_id'])) {
            $this->appId = $config['app_id'];
        }

        if (isset($config['app_secret'])) {
            $this->appSecret = $config['app_secret'];
        }

        $this->logger->info("service received config data:" . join(',', $config));
    }

    public function handle()
    {
        if (!$this->checkSignature()) {
            return false;
        }
        $receivedData = $this->request->getRawBody();
        $this->logger->log('received data:' . $receivedData, Logger::INFO);
        $postXml = simplexml_load_string($receivedData, 'SimpleXMLElement', LIBXML_NOCDATA);
        //有正常的数据请求
        if (strlen($receivedData) > 0 && $postXml instanceof \SimpleXMLElement) {
            $msgType = $postXml->MsgType;
            $fromUsername = $postXml->FromUserName;
            $this->toUser = $fromUsername;
            $toUsername = $postXml->ToUserName;
            $this->customerName = $toUsername;
            $receivedTime = $postXml->CreateTime;

            $this->user_info = UserManager::instance()->addWechatUser($this->customer, $postXml->FromUserName, $this->appId, $this->appSecret);
            $this->di->get('errorLogger')->debug("msg_type:" . join(",", $msgType));
            //handle events
            if ($msgType == ResourceManager::MESSAGE_TYPE_EVENT) {
                //排重
                $eventHistory = EventHistory::findFirst(array("customer_id" => $this->customer, 'msg_id' => $postXml->MsgId));
                if (!$eventHistory) {
                    $eventHistory = new EventHistory();
                    $eventHistory->msg_id = (string)$postXml->MsgId;
                    $eventHistory->customer_id = $this->customer;
                    $eventHistory->from = (string)$fromUsername;
                    $eventHistory->type = ResourceManager::MESSAGE_TYPE_EVENT;
                    $eventHistory->received = (string)$receivedTime;
                    $eventHistory->message = (string)$postXml->Content;
                    $eventHistory->event = (string)$postXml->Event;

                    switch ($postXml->Event) {
                        case MessageManager::EVENT_TYPE_LOCATION: {
                            $eventHistory->extra_data = json_encode([
                                'latitude' => (string)$postXml->Latitude,
                                'longitude' => (string)$postXml->Longitude,
                                'precision' => (string)$postXml->Precision
                            ], JSON_UNESCAPED_UNICODE);
                            break;
                        }
                        case MessageManager::EVENT_TYPE_SCAN: {
                            $eventHistory->extra_data = json_encode([
                                'event_key' => (string)$postXml->EventKey,
                                'ticket' => (string)$postXml->Ticket
                            ], JSON_UNESCAPED_UNICODE);
                            break;
                        }
                        default: {
                            isset($postXml->EventKey) && $this->extra_data = ['event_key' => (string)$postXml->EventKey];
                        }
                    }
                    if (!$eventHistory->save()) {
                        $errorMsg = [];
                        foreach ($eventHistory->getMessages() as $msg) {
                            $errorMsg[] = (string)$msg;
                        }
                        $this->di->get('errorLogger')->debug("save weibo event log failed:" . join(",", $errorMsg));
                    }
                }
                $returnMessage = $this->handleEvent($postXml);
            } //handle messages
            else {
                //排重
                $messageHistory = MessageHistory::findFirst(array(array("customer_id" => $this->customer, 'msg_id' => $postXml->MsgId), 'order' => array('received' => -1)));
                if (!$messageHistory) {
                    $messageHistory = new MessageHistory();
                    $messageHistory->customer_id = $this->customer;
                    $messageHistory->from = (string)$fromUsername;
                    $messageHistory->type = (string)$msgType;
                    $messageHistory->received = (string)$receivedTime;
                    $messageHistory->msg_id = (string)$postXml->MsgId;
                    $messageHistory->is_replied = 0;
                    $messageHistory->replied = 0;
                    $messageHistory->reply = "";

                    $messageHistory->user_info = json_encode(array(
                        'nickname' => $this->user_info->nickname,
                        'sex' => $this->user_info->sex,
                        'avatar' => $this->user_info->headimgurl
                    ), true);

                    switch ($msgType) {
                        case ResourceManager::MESSAGE_TYPE_IMAGE: {
                            $messageHistory->content = (string)$postXml->PicUrl;
                            $messageHistory->extra_data = ['media_id' => (string)$postXml->MediaId];
                            break;
                        }
                        case ResourceManager::MESSAGE_TYPE_VOICE: {
                            if (isset($postXml->Recognition)) {
                                $messageHistory->content = (string)$postXml->Recognition;
                                $messageHistory->extra_data = json_encode(['media_id' => (string)$postXml->MediaId, 'format' => (string)$postXml->Format], JSON_UNESCAPED_UNICODE);
                            } else {
                                $messageHistory->content = "语音消息";
                                $messageHistory->extra_data = json_encode(['media_id' => (string)$postXml->MediaId, 'format' => (string)$postXml->Format], JSON_UNESCAPED_UNICODE);
                            }
                            break;
                        }
                        case ResourceManager::MESSAGE_TYPE_TEXT: {
                            $messageHistory->content = (string)$postXml->Content;
                            break;
                        }
                        case ResourceManager::MESSAGE_TYPE_VIDEO: {
                            $messageHistory->content = $postXml->ThumbMediaId;
                            $messageHistory->extra_data = json_encode(['media_id' => (string)$postXml->MediaId], JSON_UNESCAPED_UNICODE);
                            break;
                        }
                        case ResourceManager::MESSAGE_TYPE_LOCATION: {
                            $messageHistory->extra_data = json_encode(['x' => (string)$postXml->Location_X, 'y' => (string)$postXml->Location_Y, 'scale' => (string)$postXml->Scale], JSON_UNESCAPED_UNICODE);
                            $messageHistory->content = (string)$postXml->Label;
                            break;
                        }
                    }
                    $messageHistory->message = (string)$postXml->Content;
                    if (!$messageHistory->save()) {
                        $errorMsg = [];
                        foreach ($messageHistory->getMessages() as $msg) {
                            $errorMsg[] = (string)$msg;
                        }
                        $this->di->get('errorLogger')->debug("save weibo event log failed:" . join(",", $errorMsg));
                    }
                }

                $returnMessage = $this->handleMessage($postXml);
            }
//            $this->logger->info("respond message: " . $returnMessage);
            return $returnMessage;
        } //首次做接入验证时使用
        else {
            $customer = CustomerOpenInfo::findFirst("customer_id={$this->customer}");
            if (!$customer->update(array('is_binded' => 1))) {
                $messages = [];
                foreach ($customer->getMessages() as $message) {
                    $messages[] = (string)$message;
                }
                $this->logger->debug(join('\n', $messages));
            }
            $str = $this->request->get('echostr');
            if ($str) {
                return $str;
            } else {
                return false;
            }
        }
    }

    private function handleMessage($xmlData)
    {
        $msgType = $xmlData->MsgType;

        $message = "";
        if ($msgType == ResourceManager::MESSAGE_TYPE_TEXT || ($msgType == ResourceManager::MESSAGE_TYPE_VOICE && isset($xmlData->Recognition))) {
            if ($msgType == ResourceManager::MESSAGE_TYPE_TEXT) {
                $message = $xmlData->Content;
            } else {
                $message = $xmlData->Recognition;
            }
            $msgType = ResourceManager::MESSAGE_TYPE_TEXT;
        }

        //keyword respond
        if ($msgType == ResourceManager::MESSAGE_TYPE_TEXT) {
            $respondSettings = MessageManager::instance()->getMessageSettings(MessageManager::PLATFORM_TYPE_WEIXIN, $this->customer, MessageManager::EVENT_TYPE_KEYWORD);
            if ($respondSettings) {
                $this->logger->info(json_encode($respondSettings));
                $hasMatched = false;
                foreach ($respondSettings as $setting) {
                    $keywordSetting = json_decode($setting["keywords"], true);
                    foreach ($keywordSetting as $keyword) {
                        if (empty($keyword['keyword']) || strlen($keyword['keyword']) == 0) {
                            continue;
                        }
                        if ($keyword['full_text'] > 0 && $message == $keyword['keyword']) {
                            $hasMatched = true;
                            $this->respondMessage = $setting['message'];
                            $this->respondMessageType = $setting['message_type'];
                            break;
                        } else if (strpos($message, $keyword['keyword']) !== false) {
                            $hasMatched = true;
                            $this->respondMessage = $setting['message'];
                            $this->respondMessageType = $setting['message_type'];
                            break;
                        }
                    }
                }
                if ($hasMatched) {
                    $message = $this->responseMessage();
                    $this->logger->log("response:" . $message, Logger::INFO);
                    return $message;
                }
            }
        }

        if ($this->open_info && intval($this->open_info->enable_dkf) > 0) {
            $time = time();
            $message = <<<EOF
<xml>
    <ToUserName><![CDATA[{$xmlData->FromUserName}]]></ToUserName>
    <FromUserName><![CDATA[{$xmlData->ToUserName}]]></FromUserName>
    <CreateTime>{$xmlData->CreateTime}</CreateTime>
    <MsgType><![CDATA[transfer_customer_service]]></MsgType>
</xml>
EOF;
            $this->logger->info("transfer_customer_service:" . $message);
            return $message;
        }
        //normal respond
        $respondSetting = MessageManager::instance()->getMessageSettings(MessageManager::PLATFORM_TYPE_WEIXIN, $this->customer, MessageManager::EVENT_TYPE_NORMAL);
        if ($respondSetting) {
            $this->logger->info(json_encode($respondSetting));
            if (strlen(trim($respondSetting['values'])) > 0) {
                $this->respondMessage = $respondSetting['values'];
                $this->respondMessageType = $respondSetting['message_type'];
                $message = $this->responseMessage();
                $this->logger->log("response:" . $message, Logger::INFO);
                return $message;
            }
        }

        return $message;
    }

    private function handleEvent($xmlData)
    {
        //subscribe event respond
        $event = $xmlData->Event;
        if ($event == MessageManager::EVENT_TYPE_SUBSCRIBE) {
            $respondMessage = '';
            $this->logger->info("----------log------------------");
            $respondSetting = MessageManager::instance()->getMessageSettings(MessageManager::PLATFORM_TYPE_WEIXIN, $this->customer, MessageManager::EVENT_TYPE_SUBSCRIBE);
            $this->logger->info("设置结果：" . json_encode($respondSetting));
            if ($respondSetting) {
                $this->respondMessage = $respondSetting['values'];
                $this->respondMessageType = $respondSetting['message_type'];
                $this->respondDefaultLink = $respondSetting['default_link'];
                $respondMessage = $this->responseMessage();
            }
            //log user info into system
            //log user into customer user list
//            $this->addUser($xmlData);
            return $respondMessage;
        } else if ($event == MessageManager::EVENT_TYPE_UN_SUBSCRIBE) {
            //remove user from customer user list
            $this->logger->info("开始处理unsubscribe事件");
            $user = UserForCustomers::findFirst("open_id='{$xmlData->FromUserName}' AND customer_id='{$this->customer}'");
            if ($user && $user->subscribe) {
                if (!$user->update(array('subscribe' => 0, 'subscribe_time' => 0))) {
                    $messages = [];
                    foreach ($user->getMessages() as $message) {
                        $messages[] = (string)$message;
                    }
                    $this->logger->debug(join('\n', $messages));
                }
            }
        } else if ($event == MessageManager::EVENT_TYPE_MASS_SEND_JOB_FINISH) {
            //log mass message sending results
        } else if ($event == MessageManager::EVENT_TYPE_SCAN) {
            //user scan the qr code
        } else if ($event == MessageManager::EVENT_TYPE_CLICK) {
            //user click a menu item to get pre-set messages
            $eventKey = $xmlData->EventKey;
            $menuItem = CustomerMenus::findFirst("key='{$eventKey}'");
            if ($menuItem) {
                $messageType = $menuItem->message_type;
                $target_value = $menuItem->target_value;
                $defaultLink = $menuItem->default_link;
                $this->respondMessageType = $messageType;
                $this->respondMessage = ResourceManager::instance()->getMessage($messageType, $target_value);
                $this->respondDefaultLink = $defaultLink;
                return $this->responseMessage();
            }
        } else if ($event == MessageManager::EVENT_TYPE_VIEW) {
            //user click a link
        } else if ($event == MessageManager::EVENT_TYPE_LOCATION) {
            //report location
            $openId = "{$xmlData->FromUserName}";
            $location = new UserLocations();
            $location->customer_id = $this->customer;
            $location->platform = MessageManager::PLATFORM_TYPE_WEIXIN;
            $location->open_id = $openId;
            $location->latitude = $xmlData->Latitude;
            $location->longitude = $xmlData->Longitude;
            $location->precision = $xmlData->Precision;
            $location->created = $xmlData->CreateTime;
            if (!$location->save()) {
                $messages = [];
                foreach ($location->getMessages() as $message) {
                    $messages[] = (string)$message;
                }
                $this->logger->debug("上报位置信息储存失败！" . join(',', $messages));
            }
        }
        return false;
    }

    public function responseMessage($type = null, $message = null, $defaultLink = null)
    {
        if (!is_null($type)) {
            $this->respondMessageType = $type;
        }
        if (!is_null($message)) {
            $this->respondMessage = $message;
        }

        if (!is_null($defaultLink)) {
            $this->respondDefaultLink = $defaultLink;
        }

        switch ($this->respondMessageType) {
            case ResourceManager::MESSAGE_TYPE_TEXT: {
                return MessageCreator::instance()->createTextMessage($this->customerName, $this->toUser, $this->respondMessage);
            }
            case ResourceManager::MESSAGE_TYPE_IMAGE: {
                $messageDetail = MessageResourceImages::findFirst("customer_id='{$this->customer}' AND id='{$this->respondMessage}'");
                if ($messageDetail) {
                    return MessageCreator::instance()->createImageMessage($this->customerName, $this->toUser, $messageDetail->media_id);
                } else {
                    return '';
                }
            }
            case ResourceManager::MESSAGE_TYPE_VOICE: {
                $messageDetail = MessageResourceVoices::findFirst("customer_id='{$this->customer}' AND id='{$this->respondMessage}'");
                if ($messageDetail) {
                    return MessageCreator::instance()->createVoiceMessage($this->customerName, $this->toUser, $messageDetail->media_id);
                } else {
                    return '';
                }
            }
            case ResourceManager::MESSAGE_TYPE_VIDEO: {
                $messageDetail = MessageResourceVideos::findFirst("customer_id='{$this->customer}' AND id='{$this->respondMessage}'");
                if ($messageDetail) {
                    return MessageCreator::instance()->createVideoMessage($this->customerName, $this->toUser, $messageDetail->media_id, $messageDetail->title, $messageDetail->desc);
                } else {
                    return '';
                }
            }
            case ResourceManager::MESSAGE_TYPE_NEWS: {
                if ($this->respondMessage) {
                    return MessageCreator::instance()->createNewsMessage($this->customerName, $this->toUser, $this->customer, $this->respondMessage, $this->respondDefaultLink);
                } else {
                    return '';
                }
            }
            case ResourceManager::MESSAGE_TYPE_PRODUCT: {
                if ($this->respondMessage) {
                    return MessageCreator::instance()->createProductMessage($this->customerName, $this->toUser, $this->customer, $this->respondMessage, $this->respondDefaultLink);
                } else {
                    return '';
                }
            }
            case ResourceManager::MESSAGE_TYPE_TRANSFER_CUSTOMER_SERVICE: {
                return MessageCreator::instance()->createTCServiceMessage($this->customerName, $this->toUser);
            }
        }
    }

    /**
     * @return boolean
     */
    private function checkSignature()
    {
        $this->logger->info("request query:" . json_encode($this->request->getQuery()));
        $signature = $this->request->get("signature");
        $timestamp = $this->request->get("timestamp");
        $nonce = $this->request->get("nonce");
        $tmpArr = array($this->token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * wechat php test
 */

?>

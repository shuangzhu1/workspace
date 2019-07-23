<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-4-8
 * Time: 下午3:22
 */

namespace Components\WeChat;

use Models\Article\SiteArticles;
use Models\Product\Product;
use Phalcon\Mvc\User\Plugin;

class ResourceManager extends Plugin
{

    const MESSAGE_TYPE_TEXT = "text";
    const MESSAGE_TYPE_IMAGE = "image";
    const MESSAGE_TYPE_VOICE = "voice";
    const MESSAGE_TYPE_VIDEO = "video";
    const MESSAGE_TYPE_LOCATION = "location";
    const MESSAGE_TYPE_POSITION = "position";
    const MESSAGE_TYPE_LINK = "link";
    const MESSAGE_TYPE_EVENT = "event";
    const MESSAGE_TYPE_MENTION = "mention";
    const MESSAGE_TYPE_NEWS = "news";
    const MESSAGE_TYPE_MUSIC = "music";
    const MESSAGE_TYPE_PRODUCT = 'product';
    const MESSAGE_TYPE_TRANSFER_CUSTOMER_SERVICE = 'transfer_customer_service';

    /**
     * @var ResourceManager
     */
    private static $instance = null;

    /**
     * @return ResourceManager
     */
    public static function instance()
    {
        if (!self::$instance instanceof ResourceManager) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string $messageType
     * @param string $messageId
     * @return array
     */
    public function getMessage($messageType, $messageId)
    {
        $data = array();
        if ($messageType == self::MESSAGE_TYPE_TEXT) {
            $data = $messageId;
        } else if ($messageType == self::MESSAGE_TYPE_NEWS) {
            $msgIds = explode(',', $messageId);
            foreach ($msgIds as $id) {
                $message = SiteArticles::findFirst("id='{$id}'");
                if ($message) {
                    $message = $message->toArray();
                    $data[] = array(
                        'value' => $id,
                        'image' => $message['cover'],
                        'title' => $message['title'],
                        'desc' => $message['meta_keyword']
                    );
                }
            }
        } else if ($messageType == self::MESSAGE_TYPE_PRODUCT) {
            $msgIds = explode(',', $messageId);
            foreach ($msgIds as $id) {
                $message = Product::findFirst("id='{$id}'");
                if ($message) {
                    $message = $message->toArray();
                    $data[] = array(
                        'value' => $id,
                        'image' => $message['thumb'],
                        'title' => $message['name'],
                        'desc' => $message['name']
                    );
                }
            }
        }
        return $data;
    }

    /**
     * @param $customer
     * @param $type
     * @param int $page
     * @return \Phalcon\Paginator\Adapter\stdClass
     */
    public function getCustomerMessage($customer, $type, $page = 1)
    {
        $resource = array();
        switch ($type) {
            case ResourceManager::MESSAGE_TYPE_IMAGE: {
                $queryBuilder = $this->modelsManager->createBuilder()
                    ->addFrom('\\Models\\WeChat\\MessageResourceImages', 'image')
                    ->where("image.customer_id='{$customer}'");
                break;
            }
            case ResourceManager::MESSAGE_TYPE_VOICE: {
                $queryBuilder = $this->modelsManager->createBuilder()
                    ->addFrom('\\Models\\WeChat\\MessageResourceVoices', 'image')
                    ->where("image.customer_id='{$customer}'");
                break;
            }
            case ResourceManager::MESSAGE_TYPE_VIDEO: {
                $queryBuilder = $this->modelsManager->createBuilder()
                    ->addFrom('\\Models\\WeChat\\MessageResourceVideos', 'image')
                    ->where("image.customer_id='{$customer}'");
                break;
            }
            case ResourceManager::MESSAGE_TYPE_MUSIC: {
                $queryBuilder = $this->modelsManager->createBuilder()
                    ->addFrom('\\Models\\WeChat\\MessageResourceMusics', 'image')
                    ->where("image.customer_id='{$customer}'");
                break;
            }
            case ResourceManager::MESSAGE_TYPE_NEWS: {
                $queryBuilder = $this->modelsManager->createBuilder()
                    ->addFrom('\\Models\\Article\\SiteArticles', 'article')
                    ->where("article.customer_id='{$customer}' AND LENGTH(article.cover) > 0")
                    ->columns('article.id, article.cover, article.title');
                break;
            }
            case ResourceManager::MESSAGE_TYPE_PRODUCT: {
                $queryBuilder = $this->modelsManager->createBuilder()
                    ->addFrom('\\Models\\Product\\Product', 'product')
                    ->where("product.customer_id='{$customer}' AND LENGTH(product.thumb) > 0");
                break;
            }
            default:
                $queryBuilder = $this->modelsManager->createBuilder()
                    ->addFrom('\\Models\\WeChat\\MessageResourceImages', 'image')
                    ->where("image.customer_id='{$customer}'");
        }

        $pagination = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $queryBuilder,
            "limit" => 10,
            "page" => $page
        ));
        $data = $pagination->getPaginate();
        return $data;
    }
} 
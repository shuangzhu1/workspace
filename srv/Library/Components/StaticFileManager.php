<?php

namespace Components;

use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\View;

class StaticFileManager extends Plugin
{

    protected static $jsFiles = array();
    protected static $cssFiles = array();

    /**
     * Listen to the event beforeRender
     * @param Event $event
     * @param View $view
     */
    public function beforeRender(Event $event, View $view)
    {
        $view->setVar('jsFiles', self::$jsFiles);
        $view->setVar('cssFiles', self::$cssFiles);
    }

    /**
     * Listen to the event afterRender
     * @param Event $event
     * @param View $view
     */
    public function afterRender(Event $event, View $view)
    {
        self::$jsFiles = array();
        self::$cssFiles = array();
    }

    public static function addJsFile($file)
    {
        array_push(self::$jsFiles, $file);
    }

    public static function preAddJsFile($file)
    {
        array_unshift(self::$jsFiles, $file);
    }

    public static function addCssFile($file)
    {
        array_push(self::$cssFiles, $file);
    }

    public static function preAddCssFile($file)
    {
        array_unshift(self::$cssFiles, $file);
    }

    public static function addBatchJsFiles(array $files)
    {
        self::$jsFiles = array_merge(self::$jsFiles, $files);
    }

    public static function addBatchCssFiles(array $files)
    {
        self::$cssFiles = array_merge(self::$cssFiles, $files);
    }
}

?>
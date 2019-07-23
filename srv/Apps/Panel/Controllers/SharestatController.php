<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 7/25/14
 * Time: 4:48 PM
 */

namespace Multiple\Panel\Controllers;


use Phalcon\Tag;

class SharestatController extends ControllerBase
{
    public function indexAction()
    {
        Tag::setTitle('分享统计');
    }
} 
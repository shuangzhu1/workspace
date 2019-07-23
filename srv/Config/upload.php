<?php
/** 上传及相应格式配置 **/

$config['baseUrl'] = 'http://' . STATIC_DOMAIN;

/**
 * upload pic
 */
$config['pic'] = array(
    'adpter' => 'fdfs', //[fdfs, local]
    'fastDFS' => [
        'tracker_host' => '112.124.106.166',
        'tracker_port' => '22121',
        'group' => 'pic',
    ],
    'maxAttachSize' => '30720000',
    'ext' => 'jpg|jpeg|gif|png|bmp|ico',
);

/**
 * upload video
 */
$config['video'] = [
    'fastDFS' => [
        'tracker_host' => '112.124.106.166',
        'tracker_port' => '22122',
        'group' => 'media',
        'baseUrl' => 'http://assets.local/'
    ],
    'maxAttachSize' => '209715200', # 200M=200x1024x1024
    'ext' => 'mp4|3gp|ogg|webm|flv|f4v|m4v',
];

/**
 * upload audio
 */
$config['audio'] = [
    'fastDFS' => [
        'tracker_host' => '112.124.106.166',
        'tracker_port' => '22122',
        'group' => 'media',
    ],
    'maxAttachSize' => '20971520', # 200M=200x1024x1024
    'ext' => 'mp3|m4a|ogg|spx|oga',
];


/**
 * upload file
 */
$config['file'] = [
    'fastDFS' => [
        'tracker_host' => '112.124.106.166',
        'tracker_port' => '22122',
        'group' => 'media',
    ],
    'maxAttachSize' => '20971520', # 200M=200x1024x1024
    'ext' => 'zip|rar|doc|xls|ppt|docx|pptx|xlsx|txt|png',
];

return $config;
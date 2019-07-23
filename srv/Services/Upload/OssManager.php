<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/30
 * Time: 10:57
 */

namespace Services\Upload;


class OssManager
{
    const BUCKET_AUDIO = "klg-audio"; //音频
    const BUCKET_CHAT_IMG = "klg-chatimg"; //聊天图片
    const BUCKET_CIRCLE_IMG = "klg-circleimg"; //动态图片
    const BUCKET_USER_AVATOR = "klg-useravator"; //用户头像
    const BUCKET_VIDEO = "klg-video"; //视频
    const BUCKET_MUSIC = "klg-music"; //音乐
    const BUCKET_SHOP = "klg-shopimg"; //店铺、商品
    const BUCKET_APK = "klg-clientdownapk"; //apk文件存放bucket

    public static $sts_roleArn = "acs:ram::1245730968038356:role/konglonggu";
    public static $sts_access_key = "LTAI2dwXPSgmshoz";
    public static $sts_access_key_secret = "5gNndxmZyq8moDSN2Ig8477GEg8wbk";
    public static $sts_region = "cn-shenzhen";
    public static $expire = 3600;//过期一个小时

    //1.单边缩放

    //x-oss-process=image/resize,h_100  将图缩略成高度为100，宽度按比例处理。
    //2.强制宽高缩略

    //x-oss-process=image/resize,m_fixed,h_100,w_100 将图强制缩略成宽度为100，高度为100
    //3.等比缩放，限定在矩形框内

    //x-oss-process=image/resize,m_lfit,h_100,w_100 将图缩略成宽度为100，高度为100，按长边优先
    //x-oss-process=image/resize,m_lfit,h_100,w_100/format,png 将图缩略成宽度为100，高度为100，按长边优先，将图片保存成png格式

    //4.等比缩放，限定在矩形框外
    //x-oss-process=image/resize,m_mfit,h_100,w_100 将图缩略成宽度为100，高度为100，按短边优先

    //5.固定宽高，自动裁剪
    //x-oss-process=image/resize,m_fill,h_100,w_100 将图自动裁剪成宽度为100，高度为100的效果图

    //6.固定宽高，缩略填充
    //x-oss-process=image/resize,m_pad,h_100,w_100  将原图指定按短边缩略100x100, 剩余的部分以单色填充
    //x-oss-process=image/resize,m_pad,h_100,w_100,color_FF0000 将图按短边缩略到100x100, 然后按红色填充
    //x-oss-process=image/resize,p_50 将图按比例缩略到原来的1/2

    //sts policy
    public static $sts_policy = <<<POLICY
{
  "Statement": [
    {
      "Action": [
      "oss:PutObject",
      "oss:GetObject"
      ],
      "Effect": "Allow",
      "Resource":[
      "acs:oss:*:*:*"
        ]
    }
  ],
  "Version": "1"
}
POLICY;
    //oss 外网域名
    public static $original_domain = [
        self::BUCKET_AUDIO => 'http://klg-audio.oss-cn-shenzhen.aliyuncs.com/',
        self::BUCKET_CHAT_IMG => 'http://klg-chatimg.oss-cn-shenzhen.aliyuncs.com/',
        self::BUCKET_CIRCLE_IMG => 'http://klg-circleimg.oss-cn-shenzhen.aliyuncs.com/',
        self::BUCKET_USER_AVATOR => 'http://klg-useravator.oss-cn-shenzhen.aliyuncs.com/',
        self::BUCKET_VIDEO => 'http://klg-video.oss-cn-shenzhen.aliyuncs.com/',
        self::BUCKET_MUSIC => 'http://klg-music.oss-cn-shenzhen.aliyuncs.com/',
        self::BUCKET_SHOP => 'http://klg-shopimg.oss-cn-shenzhen.aliyuncs.com/',
        self::BUCKET_APK => 'http://klg-clientdownapk.oss-cn-shenzhen.aliyuncs.com/',
    ];
    //绑定域名
    public static $bind_domain = [
        self::BUCKET_AUDIO => 'http://audio.klgwl.com/',
        self::BUCKET_CHAT_IMG => 'http://chatimg.klgwl.com/',
        self::BUCKET_CIRCLE_IMG => 'http://circleimg.klgwl.com/',
        self::BUCKET_USER_AVATOR => 'http://avatorimg.klgwl.com/',
        self::BUCKET_VIDEO => 'http://video.klgwl.com/',
        self::BUCKET_MUSIC => 'http://music.klgwl.com/',
        self::BUCKET_SHOP => 'http://shopimg.klgwl.com/',
        self::BUCKET_APK => 'http://apk.klgwl.com/',
    ];


}
<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/11
 * Time: 10:15
 */

namespace Multiple\Api\Controllers;


use Components\Kafka\Consumer;
use Components\Kafka\Producer;
use Components\Music\Music;
use Components\Music\Tools\Baidu\BaiduMusicApi;
use Components\Music\Tools\MusicBaidu;
use Components\Music\Tools\MusicXiami;
use Components\Music\Tools\NeteaseCloud\NeteaseCloudMusicApi;
use Components\Passport\Identify;
use Components\PhoneModel\phoneModel;
use Components\PhpReader\IniReader;
use Components\Rsa\BaseSign;
use Components\Rsa\lib\Sign;
use Components\User\UserManager;
use Components\Yunxin\ServerAPI;
use Green\Core\DefaultAcsClient;
use Green\Core\Profile\DefaultProfile;
use Green\ImageSyncScanRequest;
use Models\Admin\AdminLogs;
use Models\Agent\Agent;
use Models\Agent\AgentApply;
use Models\Agent\AgentIncome;
use Models\BaseMigration;
use Models\Group\Group;
use Models\Group\GroupMember;
use Models\Shop\Shop;
use Models\Shop\ShopApply;
use Models\Shop\ShopGoods;
use Models\Site\AreaCity;
use Models\Site\AreaProvince;
use Models\Site\SiteTags;
use Models\Social\SocialComment;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussMedia;
use Models\Social\SocialDiscussViewLog;
use Models\Social\SocialLike;
use Models\Social\SocialShare;
use Models\Square\RedPackage;
use Models\Square\RedPackagePickLog;
use Models\Square\RedPackageTaskLog;
use Models\Statistics\StatisticsGroup;
use Models\System\SystemRedPackageAds;
use Models\User\Message;
use Models\User\UserAttention;
use Models\User\UserAuthApply;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserCountStat;
use Models\User\UserDragonCoin;
use Models\User\UserInfo;
use Models\User\UserLocation;
use Models\User\UserPersonalSetting;
use Models\User\UserPointGrade;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserShowLike;
use Models\User\UserTags;
use Models\User\UserUntrueBlacklist;
use Models\User\UserVisitor;
use Models\Viewer\GoodViewer;
use Multiple\Panel\Plugins\AdminPrivilege;
use OSS\OssClient;
use Phalcon\Exception;
use Phalcon\Paginator\Adapter\QueryBuilder;
use Services\Admin\AdminLog;
use Services\Agent\AgentManager;
use Services\Aliyun\GreenManager;
use Services\Community\CommunityGroupManager;
use Services\Community\CommunityManager;
use Services\Discuss\DiscussManager;
use Services\Discuss\TagManager;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Shop\ShopManager;
use Services\Site\AppVersionManager;
use Services\Site\AreaManager;
use Services\Site\CacheSetting;
use Services\Site\CashRewardManager;
use Services\Site\CurlManager;
use Services\Site\SiteKeyValManager;
use Services\Social\SocialManager;
use Services\Stat\StatManager;
use Services\Upload\OssManager;
use Services\User\Behavior\Behavior;
use Services\User\Behavior\BehaviorDefine;
use Services\User\ContactManager;
use Services\User\DragonCoin;
use Services\User\GroupManager;
use Services\User\OfficialUser;
use Services\User\OrderManager;
use Services\User\Show\ShowManager;
use Services\User\Square\SquareTask;
use Services\User\SquareManager;
use Services\User\UserStatus;
use Services\User\WelfareManager;
use Services\Vip\VipCore;
use Util\Ajax;
use Util\Debug;
use Util\EasyEncrypt;
use Util\Encrypt;
use Util\FilterUtil;
use Util\LatLng;
use Util\Qqwry;
use Util\Validator;
use Models\SqlModal;

/**
 *  * @property \Phalcon\Db\AdapterInterface $original_mysql
 */
class IndexController extends ControllerBase
{
    public function indexAction()
    {
        //  $redis1 = $this->di->get("publish_queue");
        $redis2 = $this->di->get("publish_queue");
        var_dump($redis2->publish('test', 9));

        echo "你好啊";
        exit;
    }

    public function randNumber($c = 6)
    {
        $rand = "";
        for ($i = $c; $i > 0; $i--) {
            $rand .= rand(0, 9);
        }
        return $rand;
    }

    public function syncDiscuss()
    {

    }

    public function test($instance, $channelName, $message)
    {
        var_dump(func_get_args());
    }


    public function testAction()
    {
        //   var_dump(CommunityManager::getInstance()->check(14,true,31));exit;
//       var_dump(Producer::getInstance($this->di->get("config")->kafka->host)->setTopic("community_create_group")->produce(
//            ['is_success' => 1, 'executor' => 40001, 'apply_id' => 1]
//        ));
        // Producer::getInstance($this->di->get("config")->kafka->host)->setTopic("test")->produce(["comm_id" => 1, 'type' => 'comm_news', 'item_id' => 1]);
        // exit;
        //Producer::getInstance($this->di->get("config")->kafka->host)->setTopic("community_group_push")->produce(["comm_id" => 1, 'type' => 'comm_news', 'item_id' => 1]);
//        for ($i = 1; $i <= 100; $i++) {
//            (ServerAPI::init()->sendMsg(50000, 0, 62457, 0, ['msg' => $i]));
//            (ServerAPI::init()->sendMsg(63390, 0, 62457, 0, ['msg' => $i]));
//            (ServerAPI::init()->sendMsg(50001, 0, 50037, 0, ['msg' => $i]));
//            (ServerAPI::init()->sendMsg(63390, 0, 50037, 0, ['msg' => $i]));
//        }
//        echo "发送成功";
//        exit;
//        ImManager::init()->initMsg(ImManager::TYPE_VIP_DEADLINE_SOON, ['to_user_id' => 1006600,'day'=>'3']);
//        ImManager::init()->initMsg(ImManager::TYPE_VIP_DEADLINE_HAS_ARRIVED, ['to_user_id' => 1006600,'day'=>'4']);
//        exit;
//        $list = ShopApply::findList(['columns' => 'created,id']);
//        foreach ($list as $item) {
//            $time = strtotime(date('Ymd', $item['created'])) + 86400 * 365 * 3 + 86400 * 2;
//            ShopApply::updateOne(['combo_deadline' => $time], 'id=' . $item['id']);
//        }
//
//        $apply = Shop::findList(['columns' => 'id,user_id']);
//        foreach ($apply as $item) {
//            $apply = ShopApply::findOne(['user_id=' . $item['user_id'] . " and status=1", 'order' => 'created desc', 'columns' => 'combo_deadline']);
//            if ($apply) {
//                Shop::updateOne(['combo_deadline' => $apply['combo_deadline']], 'user_id=' . $item['user_id']);
//            } else {
//                $time = strtotime(date('Ymd', strtotime("+3 month"))) + 86400;
//                Shop::updateOne(['combo_deadline' => $time], 'user_id=' . $item['user_id']);
//            }
//        }
//        exit;
//        $apply = ShopApply::findList(['columns' => 'id,created']);
//        foreach ($apply as $item) {
//            $day = date('Ymd', $item['created']);
//            $deadline = strtotime($day) + ShopManager::$combo_deadline + 86400;
//            ShopApply::updateOne(['combo_deadline' => $deadline], 'id=' . $item['id']);
//        }
//
//        exit;
        //  var_dump(ServerAPI::init()->queryGroupDetail(405667523));exit;
        // exit;
//        for ($i = 5000; $i <= 5500; $i++) {
//            var_dump(ServerAPI::init()->sendMsg(50000, 0, 62457, 0, ['msg' => $i]));
//        }
//        exit;

//        $dragon = DragonCoin::getInstance();
//        $res = $dragon
//            ->setType(DragonCoin::TYPE_CHANGE_DIAMOND)
//            ->setInOut(DragonCoin::IN_OUT_OUT)
//            ->setVal(11)
//            ->setUid(40001)
//            ->execute("");
//        var_dump($res);
//        var_dump($dragon->getMsg());
//        exit;


//        try {
//            $this->original_mysql->begin();
//            $coin = UserDragonCoin::findOne(['user_id=50000', 'columns' => 'history_count,available_count,frozen_count'], true);
//            $res = (UserDragonCoin::updateOne(['history_count' => $coin['history_count'] + 1, 'available_count' => $coin['available_count'] + 1, 'frozen_count' => $coin['frozen_count'] + 1], 'user_id=50000'));
//            $this->original_mysql->commit();
//            $this->ajax->outRight($res ? $coin['history_count'] : 'fail');
//        } catch (Exception $e) {
//            $this->original_mysql->rollback();
//            $this->ajax->outError(Ajax::FAIL_HANDLE);
//        }

//        $res = (UserDragonCoin::updateOne(['history_count' => 'history_count+1', 'available_count' => 'available_count+1', 'frozen_count' => 'frozen_count+1'], 'user_id=50000'));
//        $this->ajax->outRight($res ? 'success' : 'fail');
//        $user_id = 50037;
//        $sex = 1;
//        //发送推荐用户名片
//
//        $recommend_users = $this->db->query("select user_id,avatar,username,birthday,grade,constellation from users as u  left join user_profile as p on u.id=p.user_id where " . ' u.status=' . UserStatus::STATUS_NORMAL . ' and u.id<>' . $user_id . " and u.last_login_time>=" . strtotime("-7 days") . " and u.avatar<>'" . UserStatus::$default_avatar . "' and p.sex=" . ($sex == 1 ? 2 : 1) . " and p.birthday<>'' and u.user_type=1 order by rand() limit 2")->fetchAll(\PDO::FETCH_ASSOC);
//        //  var_dump($recommend);exit;
//        //  $recommend_users = Users::findList(['status=' . UserStatus::STATUS_NORMAL . ' and id<>' . $user_id . " and last_login_time>=" . strtotime("-7 days") . " and avatar<>'" . UserStatus::$default_avatar . "'", 'order' => 'rand()', 'limit' => 2, 'columns' => 'id,username,avatar,grade']);
//        // $recommend_users = UserInfo::findList(['status=' . UserStatus::STATUS_NORMAL . ' and user_id<>' . $user_id . " and sex=" . ($sex == 1 ? 2 : 1), 'order' => 'rand()', 'limit' => 2, 'columns' => 'user_id,username,avatar,sex,grade']);
//        //var_dump($recommend_users);exit;
//        if ($recommend_users) {
//            foreach ($recommend_users as $i) {
//                ImManager::init()->initMsg(ImManager::TYPE_USER,
//                    [
//                        'to_user_id' => $user_id,
//                        'user_name' => $i['username'],
//                        'constellation' => UserStatus::$constellation[$i["constellation"]],
//                        'avatar' => $i['avatar'],
//                        'user_id' => $i['user_id'],
//                        'sex' => $i['sex'],
//                        'grade' => $i['grade'],
//                        'birthday' => $i['birthday'],
//                    ]);
//            }
//        }
//        echo "完成";
//        exit;
//        set_time_limit(0);
//        $p = 1;
//        $limit = 1000;
//        $r = 1;
//        while ($r) {
//            $user = Users::findList(['', 'limit' => $limit, 'offset' => ($p - 1) * $limit, 'columns' => 'id']);
//            if (!$user) {
//                $r = 0;
//            } else {
//                foreach ($user as $u) {
//                    DiscussManager::getInstance()->updateNewestDiscussPic($u['id']);
//                }
//
//                $p++;
//            }
//        }
//        echo "完成";
//        exit;

        // var_dump(SquareTask::init()->executeRule(50000, device_id, SquareTask::TASK_ADD_RED_PACKAGE));exit;
        //  var_dump(exec("sh /data/shell/mysql/recovery.sh /data/shell/mysql/backup/test1_test2_2018-03-09_16_16_16_sql.gz >>/data/shell/mysql/log/".date('Y-m-d_H_i_s').".log 2>&1", $output, $return_val));
        // var_dump(exec("ll", $output, $return_val));

        // var_dump($output);
        // var_dump($return_val);

        // exit;
        // set_time_limit(0);
//        $users = Users::getColumn(['avatar<>"http://avatorimg.klgwl.com/default/avatar.png" and user_type=1'], 'last_device_id', 'id');
//        foreach ($users as $u => $device_id) {
//            var_dump(SquareTask::init()->executeRule($u, $device_id, SquareTask::TASK_UPLOAD_AVATAR));
//        }
//        echo "更新完成";
//        $uids = UserProfile::getColumn(['is_auth=1', 'columns' => 'user_id'], 'user_id');
//        $users = Users::getColumn(['id in (' . implode(',', $uids) . ') and user_type=1', 'columns' => 'last_device_id,id'], 'last_device_id', 'id');
//        foreach ($users as $u => $device_id) {
//            var_dump(SquareTask::init()->executeRule($u, $device_id, SquareTask::TASK_AUTH));
//        }
//        echo "更新完成";

//        $uids = UserTags::getColumn(['tags_name<>""', 'columns' => 'user_id'], 'user_id');
//        $users = Users::getColumn(['id in (' . implode(',', $uids) . ') and user_type=1', 'columns' => 'last_device_id,id'], 'last_device_id', 'id');
//        foreach ($users as $u => $device_id) {
//            var_dump(SquareTask::init()->executeRule($u, $device_id, SquareTask::TASK_TAG));
//        }
//        echo "更新完成";

        // var_dump(serverAPI::init()->queryGroup([284066057]));exit;
//        set_time_limit(0);
//        $config = $this->di->get('config')->oss;
//
//        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
//        $res = $oss->listObjects('klg-common')->getPrefixList();//获取文件夹列表
//        var_dump($oss->listObjects('klg-chatimg', ['prefix' => ''])->getNextMarker());
//
//        var_dump($oss->listObjects('klg-common', ['prefix' => 'crash_log/android/20180305/62194/'])->getObjectList());
//
//        var_dump($res);
//        while ($res = $oss->listObjects('klg-common')->getObjectList()) {
//            var_dump($res);
//            foreach ($res as $item) {
//                // $oss->deleteObject('klg-common', $item->getKey());
//                // 删除文件夹下面的文件
//                $list = $oss->listObjects('klg-common', ['prefix' => $item->getPrefix()]);
//                $files = $list->getObjectList();
//                var_dump($files);
////                foreach ($files as $f) {
////                    var_dump($oss->deleteObject('klg-circleimg', $f->getKey()));
////                }
//            }
//        }
        //  \Services\Stat\UserManager::getInstance()->loginStat('20180228');
//
//        exit;
//        $shop_apply = ShopApply::findList(['status=1', 'columns' => 'user_id']);
//        foreach ($shop_apply as $item) {
//            //是合伙人
//            if (Agent::exist("user_id=" . $item['user_id'])) {
//                Agent::updateOne(['is_merchant' => 1], 'user_id=' . $item['user_id']);
//            } else {
//                $code = AgentManager::init()->createCode();
//                Agent::insertOne(['is_merchant' => 1, 'is_partner' => 0, 'user_id' => $item['user_id'], 'code' => $code, 'created' => time()]);
//            }
//        }
//        echo "完成";
//        exit;
//        $members = GroupMember::getColumn(["gid=11461", 'columns' => 'user_id'], 'user_id');
//        $r = Users::getColumn(['id in(' . implode(',', $members) . ') and user_type=2', 'columns' => 'id'], 'id');
//        var_dump($r);exit;
//        if ($r) {
//            $res = GroupManager::init()->kickMember(60008, implode(',', $r), 11461, '');
//            var_dump($res);
//        }
//
//        exit;

//        for ($i = 20180101; $i <= 20180131; $i++) {
//            \Services\Stat\UserManager::getInstance()->onlineStat($i);
//        }
//        echo "完成";
//        $list = UserAuthApply::findList(['status=1', 'columns' => 'true_name,user_id']);
//        foreach ($list as $l) {
//            var_dump(Users::updateOne(['true_name' => $l['true_name']], 'id=' . $l['user_id']));
//            var_dump(AgentApply::updateOne(['true_name' => $l['true_name']], 'user_id=' . $l['user_id']));
//            var_dump(Agent::updateOne(['true_name' => $l['true_name']], 'user_id=' . $l['user_id']));
//        }
//        exit;
//        $list = AgentIncome::findList(['status=' . AgentManager::income_status_wait_income, 'columns' => 'id,money']);
//        foreach ($list as $item) {
//            var_dump(AgentManager::init()->incomeToAccountSingle($item['id']));
//        }
//        exit;

//        $data = [
//            'uid' => 12,
//            'to_uid' => intval(40013),
//            'money' => intval(100),
//            'record' => json_encode(["uid" => intval(40013), 'type' => 0, "sub_type" => 6, "money" => 100, "description" => "测试", "created" => time()], JSON_UNESCAPED_UNICODE),
//            'timestamp' => time(),
//        ];
//        $res = Request::getPost(Base::WALLET_BALANCE_TRANSFER, $data);
//        var_dump($res);
//        exit;
        // $this->original_mysql->begin();
        //  $this->di->getShared("original_mysql")->begin();
        // Users::updateOne(['username' => '轻风来客'], 'id=40000');
        //  $this->di->getShared("original_mysql")->commit();
        // $this->original_mysql->commit();

//        SquareManager::init()->dayStat("20180104");
//        SquareManager::init()->dayStat("20180105");
//        SquareManager::init()->dayStat("20180106");
//        SquareManager::init()->dayStat("20180107");
//        SquareManager::init()->dayStat("20180108");
//        SquareManager::init()->dayStat("20180109");
//        SquareManager::init()->dayStat("20180110");
//        SquareManager::init()->dayStat("20180111");
//        SquareManager::init()->dayStat("20180112");
//        SquareManager::init()->dayStat("20180113");
//        SquareManager::init()->dayStat("20180114");
//        SquareManager::init()->dayStat("20180115");
//        SquareManager::init()->dayStat("20180116");
//        SquareManager::init()->dayStat("20180117");
//        SquareManager::init()->dayStat("20180118");
//        SquareManager::init()->dayStat("20180119");
//        SquareManager::init()->dayStat("20180120");
//        SquareManager::init()->dayStat("20180121");
//        SquareManager::init()->dayStat("20180122");
//        SquareManager::init()->dayStat("20180123");
//        SquareManager::init()->dayStat("20180124");
        // echo AgentManager::init()->createCode(600100);
        //  exit;

//        $uids = Users::getColumn(["user_type=2", 'columns' => 'id', 'limit' => 20000], 'id');
//        var_dump($this->redis->originalSet(CacheSetting::KEY_ROBOT_UIDS, implode(',', $uids)));
//        exit;
        //   var_dump();exit;
//        $sign = "OMv+EAEChsJ2QA5cvd1x6d0E1e0zP43png3aA4Wm7Tz3xBX54gLAkJjjFjzLFSCQ60+Xq1ZAma2t3qTz7f0qrUt4DwUyEILZuq0XGsHDNdIX60lqSiQz3p53SHSvZh8gyiXIvIW372LX3aOAE32MeeqAzQlkA1bG+JUDBwXv1opUC2S3dhTkKQ+N81GdkvGL0KYNCE5xm3x73cXmb4Cvm7btMVp+Qe+kazqZLmQBmyVnBrHD5RQ/KzrxoCX0wz7EWmtdblLp/UD+dv2hAMFMQPm7xDMUARH3D4prkT1eJVX0WcvnSKd8MMjzk/ovXOFd7ms9agFDydCvHWaoo8YtJRgGlKbLb9UrmOjlY62RCKXuX4dNQ3Fa2JRdYFYy975sxwHWU/oF0UmRZ1GjrPixHF+LL6g4gfzfWB/JuZXdtDVUBGdcUt5lQoxis6snYK+0pcZ+4Z5EEWGeJRGL0pTLxbP4s7LQI3LSXkG9gYkKrF30oZFDmLD/G3NIV1Vo2NoTIP0mkM+DyxlAERW1Q0XAJmyE0xuW9b9YsQRKRan2pAC0vBRJTDiYypkLS0hnu3QGPm0e3nG3sQoPkF1Zzf5NOUpLD4FdEuNcz3Pz9NJKb1L5T+xKNOB1EXOqQcSf1qwMrDu3TauM+wz5ec+l6I+5FZtemI0JvlfS0iiTDpy+MauYnfF3kpj1Hw7wihlz/ec+Fx7kX7HI6Eh0FiJN4uhw5a//MrmInGHt/Ds1omewPTB97WdU8JnBr9nsu2ngKFVzp5O8PedoRu9ct+E1tpS9J9z4WTzhdlS+bbNuqDvQczkSyPDdUa2RA+a8Qq8Pi8821fpQsSaR4YldIvT5I/Q==";
//        echo BaseSign::rsa_decrypt($sign, ROOT . '/Library/Components/Rsa/key/v2/rsa_private_key.pem');
//        exit;

//        $res=0;
//        for($i=1;$i<=48;$i++){
//            $res+=$i;
//        }
//        echo $res*36/60/60;exit;
        //  var_dump(2.24>2.22105);
        // var_dump(version_compare('2.2.4000', '2.2.2105', '>='));
        // exit;
        // exit;
//        var_dump($list);
//        exit;
//        var_dump(RedPackage::updateOne(['lng' => sprintf("%.6f", 120.75)], 'id=270 and user_id=60410'));
//        exit;
//        $encrypt = Encrypt::instance();
//        $res = ($encrypt->setData("88778")->encode());
//        var_dump($res);
//        var_dump($encrypt->setData($res)->decode());
//        exit;

//        $data = Request::getPost(Request::VIRTUAL_COIN_RECORDS, ["uid" => intval(50000), "coin_type" => 0, "way" => 5, "lastid" => 0, 'limit' => 20], true);
//        var_dump($data);
//        exit;
        // var_dump(LatLng::getRandPos("28.6833572", "115.9112015", 2));exit;

//        $redis = $this->di->get("redis");
//        $list = RedPackage::findList(['', 'columns' => 'package_id,sum(money) as money,user_id', 'group' => 'user_id']);
//        foreach ($list as $item) {
//            $redis->hSet(CacheSetting::KEY_RED_PACKAGE_EXPENSE, $item['user_id'], $item['money']);
//        }
//        exit;
//        $list = RedPackage::getColumn(['', 'columns' => 'package_id'], 'package_id');
//        foreach ($list as $item) {
//            $detail = Request::getPost(Base::PACKAGE_PICKER, ["limit" => 100, "redid" => $item]);
//            if ($detail && $detail['curl_is_success']) {
//                $content = json_decode($detail['data'], true);
//                $list = $content['data'];
//                if ($list) {
//                    foreach ($list as $u) {
//                        $this->redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_INCOME, $u['uid'], $u['money']);
//                        $res = RedPackagePickLog::insertOne(['user_id' => $u['uid'], 'package_id' => $item, 'money' => $u['money'], 'created' => $u['created']]);
//                    }
//                }
//            }
//        }
//        echo "更新完成";
//        exit;

//        echo gettype(1139196553419);
//        echo "\n\r";
//        echo 1139877390581;
        //   echo mt_getrandmax();
        //    var_dump(mt_rand(1139196553419, 1139877390581));
//        echo doubleval(1139196553419);
//        exit;

//        $location = LatLng::getRandPos(22.5419254, 113.9536972, 3);
//        echo LatLng::getDistance(22.5419254, 113.9536972, $location['lat'], $location['lng']);
//        exit;
        // echo LatLng::getDistance(22.586841164972,114.0023284273,22.5419254,113.9536972);exit;
//        $list = RedPackage::getColumn(['', 'columns' => 'package_id'], 'package_id');
//        foreach ($list as $item) {
//            $detail = Request::getPost(Base::PACKAGE_DETAIL, ['uid' => 13, 'redid' => $item]);
//            if ($detail && $detail['curl_is_success']) {
//                $content = json_decode($detail['data'], true);
//                $res['package_info'] = $content['data'];
//                if ($res['package_info']['grabnum'] == $res['package_info']['num']) {
//                    RedPackage::updateOne(['status' => SquareManager::STATUS_PICKED_OUT], 'package_id="' . $item . '"');
//                } else {
//                    //   RedPackage::updateOne(['status' => SquareManager::STATUS_NORMAL], 'package_id="' . $item . '"');
//                }
//            }
//        }
//        echo "更新完成";exit;
//        exit;
//        $list = RedPackage::getColumn(['', 'columns' => 'package_id'], 'package_id');
//        foreach ($list as $item) {
//            $detail = Request::getPost(Base::PACKAGE_PICKER, ["limit" => 100, "redid" => $item]);
//            if ($detail && $detail['curl_is_success']) {
//                $content = json_decode($detail['data'], true);
//                $list = $content['data'];
//                if ($list) {
//                    foreach ($list as $u) {
//                        $pick_list = $this->redis->hGet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd', $u['created']), $u['uid']);
//                        if (!$pick_list) {
//                            $pick_list = [$item => $u['created']];
//                        } else {
//                            $pick_list = json_decode($pick_list, true);
//                            $pick_list[$item] = $u['created'];
//                        }
//                        $pick_list = json_encode($pick_list);
//                        $this->redis->hSet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd', $u['created']), $u['uid'], $pick_list);
//                        $this->redis->hIncrBy(CacheSetting::KEY_PACKAGE_PICK_COUNT .date('Ymd', $u['created']),  $u['uid'], 1);
//                    }
//                }
//            }
//        }
//        echo "更新完成";exit;
        //  echo  (urldecode("IuuXeOMlc67%2BtSS1x5%2BbCTYSW12lM9L6uxHg%2B504OWFlWCjctH3dgK%2FQe48AfGwb"));exit;
//        $sign = 'l7ioJgeYTD1vcjQqwMj9ocZDsIVtNQ3eKhAKNukhmwuK2EVa5f6+qPz2V+lfs6S1dD1VsAVqr0vEqdspgu9mA7J6M6/tXUjNMuUtDhlu8VQbDFATzqW0TTNeoiK1MEl4sJVH1NWGNSFgO2X71MCaKDorDhkyfTWYcErGycM5u4EKIBrybnY9j93t0F5Ha59yxjgjiv2rxESOYLyz5K8doPJSh4hPjk7H3L+BQQnN4j41lgxokPkwxDkaH2k6BlLXLC10bDY9fUlhOStFqvBRZ3Z9RfZZ6l6iD00ibMQgRQ6sIf6C/0yYSGcEyHl04FnjvAIYsbj/Rrwb5b4ZfuwZ9EBVln3tbvuMTnidFAEvpodKFmgAzPRoo4kabtd1b7PUcO+0fm1cJiPDCucBopGTWi6pOuTwanTKtLnb7XnmXDazdvIVXrPyd4nl3fORGxnq5JAO1pGmDkbKZMBA/uRD5oS0ofmB0ImvGk4zYlB5noFe/sdvE/i+8UdFwosfTQRTXM3mSBAf5I5M33Pp9rXd1RTK7G8gzC7lOpfpnnYclejriGly9UH1+5/KrwPR9XY9qAefrNpzyqHT8W1S2hf5W0T9ciE9QziwR3CVI2RhnM5S8AdFMNm4Wd4I2gDiZ4IbhcpfFWoJDwLihWAB/dHTgOAsGgQU9w7FYEM7x7Vl50k=';
//        echo BaseSign::rsa_decrypt($sign, ROOT . '/Library/Components/Rsa/key/v2/rsa_private_key.pem');
//        echo Identify::init()->getSalt(1514432469);

        //var_dump(FilterUtil::parseContentUrl("wap.klgwl.com/index/openapp"));exit;
        //$music = new Music('baidu');
        //var_dump($music->recommendPlaylist("流行", 1, 10));
//        echo base64_encode(urldecode("%22%EB%97x%3F%25s%AE%FE%3F%24%B5%C7%9F%9B%096%12%5B%5D%3F3%D2%FA%3F%11%E0%FB%3F89aeX%28%DC%B4%7D%DD%80%AF%D0%7B%3F%00%7Cl%1B"));exit;
//        $list = RedPackage::findList(['columns' => 'package_info']);
//        foreach ($list as $item) {
//            $m = json_decode($item['package_info'], true);
//            $this->redis->hSet(CacheSetting::KEY_RED_PACKAGE, $m['id'], $item['package_info']);
//        }
//
//        exit;
        //$music = new MusicXiami();
        //var_dump($music->playlistDetail(354924283));exit;
        //var_dump($music->searchMusic());
        //exit;
        //  var_dump($music->detail("1776156051"));exit;
//        $redis = $this->di->get('redis');
//        $list = Message::findList(['gid<>0', 'group' => 'gid desc', 'columns' => 'max(id) as mid,gid']);
//        $messages = Message::getByColumnKeyList(['id in (' . implode(',', array_column($list, 'mid')) . ')', 'columns' => 'gid,message_id,from_uid,body,media_type,extend_json,send_time'], 'gid');
//        foreach ($messages as $item) {
//            $redis->hSet(CacheSetting::KEY_GROUP_CONVERSATION_LIST, $item['gid'],
//                json_encode([
//                    'send_time' => $item['send_time'],
//                    'body' => $item['body'],
//                    'media_type' => $item['media_type'],
//                    'message_id' => $item['message_id'],
//                    'extend_json' => $item['extend_json'],
//                    'from_uid' => $item['from_uid'],
//                    // 'to_uid' => $msg['to_uid'],
//                    'gid' => $item['gid'],
//                    'mix_id' => 0
//                ], JSON_UNESCAPED_UNICODE));
//        }
//        echo "完成";
//        exit;
        //var_dump($GoodViewer::find());exit;
        // var_dump(GoodViewer::findList());exit;

//        $Migration = new BaseMigration();
//        var_dump($Migration->createTable("good_viewer_1"));
//        exit;
        exit;
        /*   $city = (json_decode(Request::getPost(Base::SEND_TEST, [])['data'], true));
             var_dump($city);exit;*/
        /*  $list = [];
          foreach ($city['data'] as $c) {
              $list[] = $c['n'];
          }*/
        //  var_dump($city);exit;
//        $content = (file_get_contents(ROOT . "/Data/db/China.json"));
//        if (substr($content, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
//            $content = substr($content, 3);
//        }
//        $content = (json_decode($content, true));
//      /*  var_dump($content);
//        exit;*/
//        $province = [];
//        $city = [];
//        $county = [];
//        //$province = AreaProvince::getByColumnKeyList(['', 'columns' => 'name,id,code'], 'name');
//        $city = AreaCity::getByColumnKeyList(['', 'columns' => 'name,id,city_code'], 'name');
////var_dump($city);exit;
//        foreach ($content as $item) {
//            if ($item['diji'] == '') {
//                // echo $item['ShengJiName'] . "-" . $item['quHuaDaiMa'] . "\n\r";
//            }
//            /*  if ($item['xianji'] == '' && $item['diji'] != '' && !key_exists($item['diji'], $city)) {
//                  $city[$item['diji']] = [
//                      'name' => $item['diji'],
//                      'area_code' => $item['quhao'],
//                      'mail_code' => $item['YouBian'],
//                      'province_name' => $item['ShengJiName'],
//                      'province' => $province[$item['ShengJiName']],
//                      'city_code' => $item['quHuaDaiMa'],
//                  ];
//              }*/
//            if ($item['xianji'] != '') {
//                $county[$item['xianji']] = [
//                    'name' => $item['xianji'],
//                    'area_code' => $item['quhao'],
//                    'mail_code' => $item['YouBian'],
//                    'city_name' => $item['xianji'],
//                    'city' => $city[$item['diji']],
//                    'county_code' => $item['quHuaDaiMa'],
//                ];
//            }
//        }
//       // var_dump($county);exit;
//        /*   foreach ($city as $c) {
//               var_dump($this->original_mysql->execute("insert into area_city(`city_code`,`name`,`area_code`,`province_id`,`province_code`,`mail_code`) values('" . $c['city_code'] . "','" . $c['name'] . "','" . $c['area_code'] . "','" . $c['province']['id'] . "','" . $c['province']['code'] . "','" . $c['mail_code'] . "')"));
//           }*/
//        foreach ($county as $c) {
//            var_dump($this->original_mysql->execute("insert into area_county(`county_code`,`name`,`city_code`,`city_id`) values('" . $c['county_code'] . "','" . $c['name'] . "','" . $c['city']['city_code'] . "','" . $c['city']['id'] . "')"));
//        }
//       // var_dump($county);
//        exit;
//
//        //   exit;
//        $city_name = array_column($city, 'name');
//
//
//        $list = AreaCity::findList(["name not in('" . implode("','", $city_name) . "')", 'columns' => 'name']);
//        var_dump($list);
//        exit;
//        // var_dump($city);exit;
////        //  var_dump($city);exit;
////       $city = array_column($city, 'name');
//        $list = array_column($list, 'name');
//        var_dump(array_diff($city, $list));
//        exit;
//        exit;
//        /*   $music=new NeteaseCloudMusicApi();
//           var_dump($music->mvSearch("solo dance"));exit;*/
//        $city = (json_decode(Request::getPost(Base::SEND_TEST, [])['data'], true));
//        $list = [];
//        foreach ($city['data'] as $c) {
//            $list[] = $c['n'];
//        }
//        //  var_dump($list);
//        $city = AreaCity::findList(["name in('" . implode("','", $list) . "')", 'columns' => 'name']);
//        // var_dump($city);exit;
////        //  var_dump($city);exit;
////       $city = array_column($city, 'name');
//        $city = array_column($city, 'name');
//        var_dump(array_diff($list, $city));
//        exit;
//        exit;
//        echo md5(file_get_contents(ROOT . "/klg.apk"));
//        exit;
//        set_time_limit(0);
//        $list = Users::getByColumnKeyList(['user_type=' . UserStatus::USER_TYPE_ROBOT . " and (id<71041 or id>71078)", 'columns' => 'id,avatar', 'limit' => 5000], 'id');
//        $uids = array_column($list, 'id');
//        $profile = UserProfile::getByColumnKeyList(['user_id in(' . implode(',', $uids) . ')', 'columns' => 'photos'], 'user_id');
//        foreach ($list as $item) {
//            if ($profile[$item['id']]['photos']) {
//                if (explode(',', $profile[$item['id']]['photos'])[0] == $item['avatar']) {
//                    continue;
//                }
//            }
//            $photos = $profile[$item['id']]['photos'] ? $item['avatar'] . "," . $profile[$item['id']]['photos'] : $item['avatar'];
//            /*   echo $photos;
//               exit;*/
//            UserProfile::updateOne(['photos' => $photos], 'user_id=' . $item['id']);
//        }
//        echo "完成";
//        exit;

        /*  echo date('Ymd H:i:s', 1511419273);
          exit;
          set_time_limit(0);*/
        /* $tag1 = [
             '美术', '程序员', 'UI设计', '程序开发', '专业客服',
             '视觉设计', 'IT', '导游', '美容师', '运动员',
             '中学教师', '小学老师', '护士', '教练员', '药剂师', '售货员',
             '专业客服', '打字员', '清洁工', '保育员', '导游', '秘书',
             '演员', '导游'
         ];
         $tag2 = [
             '泡吧', '动漫', '写字', '唱歌', '绘画',
             '桌游', '手游', '看剧', '手工', '旅行', '烟酒',
             '读书', '运动', '看电影', '自驾', '游戏', '逛街',
             'LOL', '烘焙', '摄影', '二次元', 'NBA', 'MBA',
             '台球', '露营', '高尔夫', '羽毛球', '游泳', '街舞', '爬山', '射箭',
             '篮球', '健身', '跑步', '瑜伽', '滑雪', '乒乓球', '舞蹈', '跆拳道',
             '垂钓', '骑行', '足球', '徒步'
         ];*/
        /* $robot = Users::getColumn(['user_type=' . UserStatus::USER_TYPE_ROBOT . " and (id<71041 or id>71078)", 'columns' => 'id,rand() as rand', 'limit' => 50], 'id');
         foreach ($robot as $item) {
             $t1 = mt_rand(0, count($tag1));
             $t2 = mt_rand(0, count($tag2));
             $tag = $tag1[$t1] . ',' . $tag2[$t2];
             $data = ['tags_name' => $tag];
             $data['user_id'] = $item;
             $data['created'] = time();
             $data['modify'] = $data['created'];
             $data['brief'] = "这个人很懒，什么也没留下";
             $res = UserTags::insertOne($data);
         }*/


        /*  echo "添加成功";
          exit;*/
        // var_dump($res = ServerAPI::init()->updateGroupNick(82635545, 62386, 62457, ''));exit;
        /*
                $users = Users::getColumn(['', 'columns' => 'id', 'limit' => 10000], 'id');
                foreach ($users as $item) {
                    DiscussManager::getInstance()->updateNewestDiscussPic($item);
                }
                echo "更新完成";*/
        /* set_time_limit(0);
         $group = Group::findList(['status=1', 'columns' => 'id,yx_gid,user_id']);
         foreach ($group as $item) {
             echo $item['yx_gid']."\n\r";
             echo $item['user_id']."\n\r";
             var_dump(ServerAPI::init()->updateGroup($item['yx_gid'], $item['user_id'], ['icon' => "http://avatorimg.klgwl.com/default/group.png"]));
             //exit;
         }
         exit;*/
        /*  $redis = $this->di->get('redis');
          $redis->originalSet('test', 1, 5);
          //$redis->expire('test', 10);
          exit;*/

        // echo BaseSign::rsa_encrypt("t=" . time() . "&uid=71078&sk=rob_klgwl.com@2017", ROOT . '/Library/Components/Rsa/key/open/rob_rsa_public_key.pem');
        //echo BaseSign::rsa_decrypt("gvfCcRkVTaOMuEtbF5+Ny/+QoQKuLdYZs4QzVwSadT4EQElO7joueKdp66d21nUCCZJYIWA3sNOx7pZklk1wADjYtm40/dYAfGfhaCSHy59dEbY45cjBDlBW2CswIARWwSWrIbrberdovnzVdC6Ilqe4v8cHgvTEzCWvP+Du+mg=", ROOT . '/Library/Components/Rsa/key/open/rob_rsa_private_key.pem');
//exit;
        //   exit;
        //var_dump(ServerAPI::init()->updateUserToken(60987));exit;
        /*   $redis = $this->di->get('redis');
           $list = UserProfile::findList(['user_id>=71041 and user_id<=71078', 'columns' => 'user_id,yx_token']);
           $base = CacheSetting::$setting[CacheSetting::KEY_OPEN_ROBOT];
           foreach ($list as $i) {
               $yx = ServerAPI::init()->updateUserToken($i['user_id']);
               var_dump($redis->originalSet($base['prefix'] . $i['user_id'], json_encode(['token' => $yx['info']['token'], 'expire' => time() + $base['life_time'] - 5]), $base['life_time']));
               UserProfile::updateOne(['yx_token' => $yx['info']['token']], 'user_id=' . $i['user_id']);
           }
           exit;*/

        /*   $cashReward = new CashRewardManager();
           if ($res = $cashReward->sendPackage(60653, 29835)) {
               var_dump($res);exit;
               SocialDiscuss::updateOne(['package_id' => $res['id'], 'package_info' => json_encode($res)], 'id=' . 29835);
           }
           exit;*/
        /*  set_time_limit(0);
          $users = Users::findList(['user_type=2', 'columns' => 'id']);
          foreach ($users as $u) {
              //已经拉黑对方
              if (!UserBlacklist::exist('owner_id=' . $u['id'] . ' and user_id=' . 63388)) {
                  //已经关注过了|//已经是好友关系
                  if (!UserAttention::findOne(['owner_id=' . $u['id'] . ' and user_id=' . 63388, 'columns' => 'id'])) {
                      $redis = $this->di->get("publish_queue");
                      $redis->publish(CacheSetting::KEY_ATTENTION, json_encode(['uid' => $u['id'], 'to_uid' => 63388, 'source' => 1]));
                  }
              }
              //   ContactManager::init()->attention($u['id'], 63388);
          }
          echo "完成";*/

        //$name = $this->request->get("name", "string", '');
        // AdminLogs::updateOne(['user_name' => $name], 'id=2');
        //exit;
        //var_dump(ServerAPI::init()->muteTlist(178556869, 50000, 62220, 1));

        //var_dump(ServerAPI::init()->muteTlist(178556869, 50000, 62220, 0));
        //var_dump(ServerAPI::init()->muteTlistAll(178556869, 50000, 'false'));
        //exit;
        // var_dump(UserShowLike::findList([]));exit;
        //  $res = SocialDiscuss::findList(['share_original_type="good" and parent_item_id=0', 'columns' => 'share_original_item_id,id,content']);
        // var_dump($res);
        /*  if ($res) {
              $goood_ids = [];
              foreach ($res as $item) {
                  $content = json_decode($item['content'], true);
                  $goood_ids[] = $content['good_id'];
              }
              $goods = ShopGoods::getByColumnKeyList(['id in (' . implode(',', $goood_ids) . ')', 'columns' => 'user_id,id'], 'id');
              foreach ($res as $item) {
                  $content = json_decode($item['content'], true);
                  unset($content['user_id']);
                  $content['uid'] = isset($goods[$content['good_id']]['user_id']) ? $goods[$content['good_id']]['user_id'] : 50000;
                  SocialDiscuss::updateOne(['content' => json_encode($content, JSON_UNESCAPED_UNICODE)], 'id=' . $item['id']);
                  $goood_ids[] = $content['good_id'];
              }
              // var_dump($goods);exit;
              //var_dump(array_unique($goood_ids));
          }*/

        /*    set_time_limit(0);
            $list = Users::getColumn(["", 'columns' => 'id'], 'id');
            foreach ($list as $i) {
                DiscussManager::getInstance()->updateNewestDiscussPic($i);
            }
            echo "完成";*/


//        $str = urldecode("BfMjQmI7KJw87LsaAGu7V6jzmhlS2cgcw8v9o_aAKdk958Y9211FN6Z6CUMO_bdBVm8K0u0Wioh7kY_b_aBK0ladJ8An62jGfAkXNoCOED9ONs_bopREjusC5ojHu62kvj02SrnxrjdJTH7I_bEuEo4Sk1Syk59kCdq7z3siEYqdVcKdxv6Y6ZKGY_ax3g739mAiJCg1DoHdurwORKmJA8WuDTGAc1UJvn8NuYcwD16mVg9PEDR8_ba4h3j_btQKC8tG6VK6gOuHcyWVuJyeO7iZBNnspk4tQnPKHX7oZbLRGmQGiSmqDR8gtw6AZruAqV8b9x16Pk2lU_bMzpaECadQ8fVblj6A_c_c");
//        $sign = (Sign::rsa_decrypt($str, ROOT . "/Data/test/private_key.pem"));
//        var_dump($sign);
//        exit;
        // $salt = (string)(2017 * 11 * 6);
        //  echo Identify::init()->SDBMHash($salt);
        //  exit;
        /*  $list = $this->db->query("select from_uid,to_uid,body,extend_json,extend_type,mix_id,message_id,send_time,media_type from message where id in (select MAX(id) from message where mix_id>0  GROUP BY mix_id)")->fetchAll(\PDO::FETCH_ASSOC);
           if ($list) {
               $redis = $this->di->get('redis');
               foreach ($list as $msg) {
                   //记录会话列表
                   $redis->hSet(CacheSetting::KEY_CONVERSATION_LIST . $msg['from_uid'], $msg['to_uid'],
                       json_encode([
                           'send_time' => $msg['send_time'],
                           'body' => $msg['body'],
                           'media_type' => $msg['media_type'],
                           'message_id' => $msg['message_id'],
                           'extend_json' => $msg['extend_json'],
                           'from_uid' => $msg['from_uid'],
                           'to_uid' => $msg['to_uid'],
                           'mix_id' => $msg['mix_id']
                       ], JSON_UNESCAPED_UNICODE));
                   $redis->hSet(CacheSetting::KEY_CONVERSATION_LIST . $msg['to_uid'], $msg['from_uid'],
                       json_encode([
                           'send_time' => $msg['send_time'],
                           'body' => $msg['body'],
                           'media_type' => $msg['media_type'],
                           'message_id' => $msg['message_id'],
                           'extend_json' => $msg['extend_json'],
                           'from_uid' => $msg['from_uid'],
                           'to_uid' => $msg['to_uid'],
                           'mix_id' => $msg['mix_id']
                       ], JSON_UNESCAPED_UNICODE));
               }
           }
           exit;*/
        /*   $list = ($this->redis->hGetAll("user_online_list"));
             foreach ($list as $k => $item) {
                 $tmp = json_decode($item, true);
                 if (time() - $tmp['time'] > 86400) {
                     $this->redis->hDel("user_online_list", $k);
                 }
             }
             exit;*/
        /*  $behavior = new Behavior(Behavior::TYPE_DISCUSS_PUBLISH, 50000);
          var_dump($behavior->checkBehavior());
          var_dump(json_decode($behavior->getBehavior(),true));
          exit;*/
        /*   SocialManager::init()->changeCnt(SocialManager::TYPE_GOOD,'13','forward_cnt');
           exit;*/

        // \Services\Stat\UserManager::getInstance()->statistic("20171031");
        //  $redis1 = $this->di->get("publish_queue");
        /*  $val = $this->request->get("val", 'string', 9);
          $redis2 = $this->di->get("publish_queue");
          var_dump($redis2->publish('test', $val));*/
        //var_dump($redis2->subscribe(['test'], array($this, "test")));
        //   exit;
        // var_dump($redis1->publish('test', 10));

        // var_dump(ShowManager::init()->sendMessage(1));exit;
        /*  $this->db->begin();
          $this->db->execute("UNLOCK TABLES");
          $this->db->commit();
          echo 999;*/
        /*    $this->db->execute("LOCK TABLES user_show_like WRITE");
            $this->db->begin();

            var_dump(UserShowLike::insertOne(['owner_id' => 50001, 'user_id' => 50000, 'created' => time()]));
            $this->db->execute("UNLOCK TABLES");
            $this->db->commit();*/

        /*  $p = 1;
          while ($profile = UserProfile::findList(['birthday=""', 'columns' => 'user_id,birthday', 'offset' => ($p - 1) * 100, 'limit' => 100])) {
              if ($profile) {
                  foreach ($profile as $item) {
                      $birthday = UserStatus::getInstance()->createRandBirthday();
                      $constellation = UserStatus::getInstance()->getConstellation($birthday);
                      echo "constellation:" . $constellation . '<br/>';
                      if ($constellation) {
                          UserProfile::updateOne(['constellation' => $constellation, 'birthday' => $birthday], ['user_id' => $item['user_id']]);
                      }
                  }
              }

              $p++;
          }*/
        /* set_time_limit(0);
         $page = 1;
         while ($list = UserUntrueBlacklist::findList(['', 'offset' => ($page - 1) * 50, 'limit' => 50])) {
             foreach ($list as $item) {
                 $res = ServerAPI::init()->specializeFriend($item['owner_id'], $item['user_id'], 1, 0);
                 Debug::log("res:" . $item['owner_id'] . '->' . $item['user_id'], 'debug');
                 Debug::log("res:" . var_export($res, true), 'debug');
                 $res = ServerAPI::init()->specializeFriend($item['user_id'], $item['owner_id'], 1, 0);
                 Debug::log("res:" . $item['user_id'] . '->' . $item['owner_id'], 'debug');
                 Debug::log("res:" . var_export($res, true), 'debug');

             }
             $page++;
         }
         echo "去除家假拉黑成功";
         exit;*/
        // var_dump();exit;
        /*  set_time_limit(0);
          $i = 1;

          while ($i == 1 && $list = UserContactMember::findList(['mark=""', 'offset' => ($i - 1) * 50, 'limit' => 50, 'columns' => 'owner_id,user_id,default_mark,mark'])) {
              foreach ($list as $item) {
                  $res = ServerAPI::init()->updateFriend($item['owner_id'], $item['user_id'], $item['default_mark']);
                  var_dump($res);
              }
              echo $i . '<br/>';
              $i++;
          }
          echo "更新完成";
          exit;*/
        // set_time_limit(0);
        //   $config = $this->di->get('config')->oss;

        //  $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        //  $res = $oss->listObjects('klg-circleimg')->getObjectList();//获取文件列表
        //$res = $oss->listObjects('klg-circleimg')->getPrefixList();//获取文件夹列表

        //  while ($res = $oss->listObjects('klg-circleimg')->getObjectList()) {
        //     foreach ($res as $item) {
        //         $oss->deleteObject('klg-circleimg', $item->getKey());
        /*删除文件夹下面的文件
       $list = $oss->listObjects('klg-circleimg', ['prefix' => $item->getPrefix()]);
       $files = $list->getObjectList();
       foreach ($files as $f) {
           var_dump($oss->deleteObject('klg-circleimg', $f->getKey()));
       }*/
        //      }
        //  }

        //

        /* ['prefix' => '60059/']*/
        /*$uids = [71041,71042,71043,71044,71046,71047,71048,71049,71050,71051,71052,71053,71055,71057,71059,71060,71061,71062,71063,71064,71067,71068,71069,71070,71072,71073,71074,71076,71077,71078];
        foreach($uids as $uid)
        {

            $yx_token = ServerAPI::init()->updateUserToken($uid)['info']['token'];
            //var_dump($yx_token);exit;
            UserProfile::updateOne(['yx_token' => $yx_token],['user_id' => $uid]);
            $username = Users::findOne(["id = " . $uid ,'columns' => 'username'])['username'];
           Debug::log($uid . "\t" . $username . "\t" .$yx_token);
        }
        echo "完成";*/

    }


}
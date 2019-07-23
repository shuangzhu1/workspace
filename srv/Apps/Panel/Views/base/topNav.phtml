<?php
$auth_count = \Models\User\UserAuthApply::dataCount("status=2");
$unread_msg = $this->redis->hGet(\Services\Site\CacheSetting::KEY_UNREAD_MESSAGE, \Services\Site\CacheSetting::KEY_UNREAD_MESSAGE_TOTAL);//未读消息数
$report_user_count = \Models\Social\SocialReport::dataCount("status=0 and type='user'");
$report_group_count = \Models\Group\GroupReport::dataCount("status=0");
$report_discuss_count = \Models\Social\SocialReport::dataCount("status=0 and type='discuss'");
$feedback_count = \Models\User\UserFeedback::dataCount("check_status=1");

?>
<style>
    ul.ace-nav > li > a{
        background-color: #2c6aa0;
    }
</style>
<ul class="nav ace-nav">
    <li class="">
        <a class="newTarget" data-href="/srv/panel/report/user" href="javascript:;" data-title="用户举报列表" title="用户举报列表">
            <i class="fa fa-user"></i>
            <span class="badge badge-important msg_count"><?php echo $report_user_count ?></span>
        </a>
    </li>
    <li class="">
        <a class="newTarget" data-href="/srv/panel/report/group" href="javascript:;" data-title="群聊举报列表" title="群聊举报列表">
            <i class="fa fa-users"></i>
            <span class="badge badge-important msg_count"><?php echo $report_group_count ?></span>
        </a>
    </li>
    <li class="">
        <a class="newTarget" data-href="/srv/panel/report/discuss" href="javascript:;" data-title="动态举报列表" title="动态举报列表">
            <i class="fa fa-video-camera"></i>
            <span class="badge badge-important msg_count"><?php echo $report_discuss_count ?></span>
        </a>
    </li>
    <li class="">
        <a class="newTarget" data-href="/srv/panel/auth/list" href="javascript:;" data-title="认证列表" title="认证待处理">
            <i class="fa fa-drivers-license-o"></i>
            <span class="badge badge-important msg_count"><?php echo $auth_count ?></span>
        </a>
    </li>
    <li class="">
        <a class="newTarget" data-href="/srv/panel/system/feedback" href="javascript:;" data-title="用户反馈待处理"
           title="用户反馈待处理">
            <i class="fa fa-pencil-square-o"></i>
            <span class="badge badge-important msg_count"><?php echo $feedback_count ? $feedback_count : 0 ?></span>
        </a>
    </li>
    <li class="">
        <a class="newTarget" data-href="/srv/panel/message/getList" href="javascript:;" data-title="恐龙君未读消息"
           title="恐龙君未读消息">
            <i class="fa fa-envelope-o"></i>
            <span class="badge badge-important msg_count"><?php echo $unread_msg ? $unread_msg : 0 ?></span>
        </a>
    </li>
    <li class="">
        <a data-toggle="dropdown" href="#" class="dropdown-toggle">

            <span class="user-info">
                <small>欢迎光临</small>
                <?php echo $admin['name']; ?>
            </span>

            <i class="fa fa-caret-down"></i>
        </a>


        <ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">

            <li>
                <a class="newTarget" href="javascript:;"  data-title="安全密码" id="setSecurityPassword" data-href="/panel/setting/securityPassword">
                    <i class="fa fa-shield"></i>
                    安全密码
                </a>
            </li>
            <li>
                <a class="newTarget" href="javascript:;"  data-title="设置密码" data-href="/panel/setting/password">
                    <i class="fa fa-lock"></i>
                    密码设置
                </a>
            </li>
            <li>
                <a  class="newTarget" href="javascript:;"  data-title="资料设置"  data-href="/panel/setting/index">
                    <i class="fa fa-user"></i>
                    资料设置
                </a>
            </li>
            <li class="divider"></li>
            <li>
                <a href="account/logout">
                    <i class="fa fa-sign-out"></i>
                    退出
                </a>
            </li>
        </ul>
    </li>
</ul>
<script>
    //头部
    $(".nav").on("click", '.newTarget', function () {
        Hui_admin_tab(this);
        $(this).find(".msg_count").html(0);
    });
</script>
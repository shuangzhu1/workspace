<link rel="stylesheet" href="/srv/static/panel/css/stat.css"/>

<style>
    .stat_component .filters {
        padding-bottom: 10px;
    }

    .stat_component .filter {
        margin-left: 15px;
        display: inline-block;
        overflow: hidden;
    }

    .stat_component .control-bar-wrapper {
        position: relative;
        padding-top: 20px;
        padding-bottom: 15px;
        color: #323437;
        padding-left: 20px;
    }

    .stat_component .stat_label {
        display: inline-block;
        height: 26px;
        padding-right: 5px;
        line-height: 26px;
        color: #787a7d;
    }

    .stat_component .control-bar-wrapper .control-bar .date-select-bar {
        border: 1px solid #e1e3e4;
        border-radius: 2px;
    }

    .stat_component .control-bar-wrapper .control-bar .date-select-bar a {
        display: inline-block;
        float: left;
        height: 24px;
        line-height: 24px;
        padding: 0 10px;
        color: #323437;
        background-color: #fff;
    }

    .stat_component .control-bar-wrapper .control-bar .date-select-bar a {
        display: inline-block;
        float: left;
        height: 24px;
        line-height: 24px;
        padding: 0 10px;
        color: #323437;
        background-color: #fff;
    }

    .stat_component .control-bar-wrapper .control-bar .date-select-bar a:last-child.cur {
        border-radius: 0 2px 2px 0;
        right: -1px;
    }

    .stat_component .control-bar-wrapper .control-bar .date-select-bar .cur {
        position: relative;
        bottom: -1px;
        margin-top: -2px;
        padding-top: 1px;
        height: 25px;
        background-color: #438eb9;
        color: #fff;
    }

    .stat_component .filter .group {
        float: left;
        background-color: #fff;
        border-radius: 2px;
        margin: 0;
    }

    .stat_component .filter .group li:first-child {
        margin-left: 0;
        border-top-left-radius: 2px;
        border-bottom-left-radius: 2px;
    }

    .stat_component .filter .group .cur {
        position: relative;
        z-index: 1;
        border: 1px solid #438eb9;
        color: #fff;
    }

    .stat_component .filter .group li:hover {
        background-color: #f3f4f4;
    }

    .stat_component .filter .group .cur, .filter .group li.cur:hover {
        background-color: #438eb9;
    }

    .stat_component .filter .group li {
        cursor: pointer;
        padding: 0 12px;
        border: 1px solid #e1e3e4;
        margin-left: -1px;
    }

    .stat_component .filter .group li, .filter label {
        height: 24px;
        line-height: 24px;
        float: left;
    }

    .stat_component .time {
        width: 80px;
        height: 24px;
        line-height: 24px;
        margin: 0 0 0 7px;
        padding: 0 0 0 6px;
        border: 1px solid #e1e3e4;
        border-right: none;
        text-indent: 0;
        color: #323437;
        cursor: pointer;
        float: left;
        border-top-left-radius: 2px !important;
        border-bottom-left-radius: 2px !important;
    }

    .stat_component .time_picker {
        width: 24px;
        height: 24px;
        display: inline-block;
        float: left;
        border: 1px solid #e4e4e4;
        border-top-right-radius: 2px;
        border-bottom-right-radius: 2px;
        text-align: center;
        color: #666;
        cursor: pointer;
    }

    .stat_component .split_line {
        display: inline-block;
        float: left;
        margin-left: 7px;
        font-size: 16px;
        color: #666
    }

    .stat_component .control-bar-wrapper .select-bar-item {
        position: relative;
        margin-left: 5px;
        height: 24px;
        line-height: 24px;
    }

    .stat_component .filter .group li:last-child {
        border-top-right-radius: 2px;
        border-bottom-right-radius: 2px;
    }

    .newTarget {
        cursor: pointer;
    }

    .main-container:after, .page-content {
        background: #ececec;
        padding: 30px;
        padding-top: 10px;
    }

    .row {
        margin-left: 0;
        margin-right: 0;
    }

    .block1 {
        float: left;
        width: 15%;
        padding: 20px;
        vertical-align: middle;
        border-radius: 5px;
        background: #6FBFE2;
        margin-right: 2%;
        color: white;
    }

    .block1:last-child {
        margin-right: 0;
    }

    .block1 i {
        font-size: 40px;
        color: #f4f4f4;
        margin-top: 16px
    }

    .block1 i.more {
        font-size: 30px;
        margin-top: 20px;
    }

    .block1 .block1_left {
        float: left;
        width: 30%
    }

    .block1 .block1_middle {
        float: left;
        width: 50%;
    }

    .block1 .block1_right {
        width: 20%;
        float: left;
        text-align: right
    }

    .block1.user {
        background-color: #6FBFE2;
    }

    .block1.group {
        background-color: #9A74B1;
    }

    .block1.discuss {
        background-color: #84B3E9;
    }

    .block1.report {
        background-color: #4372A6;
    }

    .block1.message {
        background-color: #87B87F;
    }

    .block1.show {
        background-color: #9A82D0;
    }

    .page-header {
        padding-bottom: 10px;
        padding-top: 1px;

    }

    .block3 {
        padding: 10px;
    }

    .block4 {
        width: 100%;
        height: auto;
        overflow: hidden;
        background: #fff;
        border-radius: 2px;
        padding: 10px
    }
</style>

<!--<div class="page-header">
    <h1>欢迎来到管理中心</h1>
</div>-->
<?= $this->getContent() ?>

<div class="block" style="height: auto ;overflow: hidden;width: 100%;margin-bottom: 10px;border: 1px solid #e4e4e4;background-color: #fff;padding: 30px;border-radius: 5px">
    <div class="row">
        <div class="block1 user">
            <div class="block1_left"><i class="fa fa-user-o"></i></div>
            <div class="block1_middle">
                <p>平台用户</p>
                <h2><?php echo $user_count ?></h2>
            </div>
            <div class="block1_right">
                <a href="javascript:;" data-title="用户列表" data-id="users" class="newTarget"
                   data-href="/panel/users/index"><i class="more fa fa-angle-right"></i></a>
            </div>
        </div>
        <div class="block1 group">
            <div class="block1_left"><i class="fa fa-user-plus"></i></div>
            <div class="block1_middle">
                <p>群聊</p>
                <h2><?php echo $group_count ?></h2>
            </div>
            <div class="block1_right">
                <a href="javascript:;" data-title="群组列表" data-id="group" class="newTarget"
                   data-href="/panel/group/list"><i class="more fa fa-angle-right"></i></a>
            </div>
        </div>
        <div class="block1 discuss">
            <div class="block1_left"><i class="fa fa-paw"></i></div>
            <div class="block1_middle">
                <p>动态</p>
                <h2><?php echo $discuss_count ?></h2>
            </div>
            <div class="block1_right">
                <a href="javascript:;" data-title="动态列表" data-id="discuss" class="newTarget"
                   data-href="/panel/discuss/list"><i class="more fa fa-angle-right"></i></a>
            </div>
        </div>
        <div class="block1 report">
            <div class="block1_left"><i class="fa fa-hand-paper-o"></i></div>
            <div class="block1_middle">
                <p>举报</p>
                <h2><?php echo $report_count ?></h2>
            </div>
            <div class="block1_right">
                <a href="javascript:;" data-title="用户举报" data-id="report" class="newTarget"
                   data-href="/panel/report/user"><i class="more fa fa-angle-right"></i></a>
            </div>
        </div>
        <div class="block1 message">
            <div class="block1_left"><i class="fa fa-comments-o"></i></div>
            <div class="block1_middle">
                <p>恐龙君消息</p>
                <h2><?php echo $message_count ?></h2>
            </div>
            <div class="block1_right">
                <a href="javascript:;" data-title="恐龙君" data-id="message" class="newTarget"
                   data-href="/panel/message/getList"><i class="more fa fa-angle-right"></i></a>
            </div>
        </div>
        <div class="block1 show">
            <div class="block1_left"><i class="fa fa-video-camera"></i></div>
            <div class="block1_middle">
                <p>附近视频</p>
                <h2><?php echo $show_count ?></h2>
            </div>
            <div class="block1_right">
                <a href="javascript:;" data-title="选手列表" data-id="show" class="newTarget"
                   data-href="/panel/show/list"><i class="more fa fa-angle-right"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">


    <div class="block3"
         style="width: 33%;height: 400px;background: #fff;margin-right: 1%;float: left;border-radius: 2px;">
        <div class="stat_component user_component">
            <div class="page-header" style="margin-bottom: 2px"><b style="padding-left: 10px;">注册用户<span
                        class="total_count total"></span></b>
                <a class="right newTarget" data-href="/panel/stat/user" data-title="注册统计">查看更多&nbsp;<i
                        class="fa fa-angle-double-right"></i></a>
            </div>
            <div id="filters" class="filters">
                <div class="control-bar-wrapper clearfix" id="control-bar-wrapper">
                    <div class="control-bar bg-iframe l" id="control-bar">
                        <span class="l stat_label">时间：</span>
                        <div class="l date-select-bar" id="date-select-bar">
                            <a href="javascript:;" data-id="today" data-type="total"
                               class="trackable date-bar-single-day cur">今天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="yesterday" data-type="total"
                               class="trackable date-bar-single-day">昨天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="7" data-type="total" class="trackable ">最近7天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="30" data-type="total" class="trackable">最近30天</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main" id="total" style="width: 100%; height: 260px"></div>
        </div>
    </div>

    <div class="block3"
         style="width: 32%;height: 400px;background: #fff;float: left;margin-right: 1%;border-radius: 2px">
        <div class="stat_component discuss_component">
            <div class="page-header" style="margin-bottom: 2px"><b style="padding-left: 10px;">动态发布<span
                        class="discusss_count discusss"></span></b>
                <a class="right newTarget" data-href="/panel/stat/discuss" data-title="动态统计">查看更多&nbsp;<i
                        class="fa fa-angle-double-right"></i></a>
            </div>
            <div id="filters" class="filters">
                <div class="control-bar-wrapper clearfix" id="control-bar-wrapper">
                    <div class="control-bar bg-iframe l" id="control-bar">
                        <span class="l stat_label">时间：</span>
                        <div class="l date-select-bar" id="date-select-bar">
                            <a href="javascript:;" data-id="today" data-type="total"
                               class="trackable date-bar-single-day cur">今天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="yesterday" data-type="total"
                               class="trackable date-bar-single-day">昨天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="7" data-type="total" class="trackable ">最近7天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="30" data-type="total" class="trackable">最近30天</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main" id="discusss" style="width: 100%; height: 260px"></div>
        </div>
    </div>
    <div class="block3" style="width: 33%;height: 400px;background: #fff;float: left;border-radius: 2px">
        <div class="stat_component group_component">
            <div class="page-header" style="margin-bottom: 2px"><b style="padding-left: 10px;">群聊创建<span
                        class="groups_count groups"></span></b>
                <a class="right newTarget" data-href="/panel/stat/group" data-title="群聊统计">查看更多&nbsp;<i
                        class="fa fa-angle-double-right"></i></a>
            </div>
            <div id="filters" class="filters">
                <div class="control-bar-wrapper clearfix" id="control-bar-wrapper">
                    <div class="control-bar bg-iframe l" id="control-bar">
                        <span class="l stat_label">时间：</span>
                        <div class="l date-select-bar" id="date-select-bar">
                            <a href="javascript:;" data-id="today" data-type="total"
                               class="trackable date-bar-single-day cur">今天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="yesterday" data-type="total"
                               class="trackable date-bar-single-day">昨天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="7" data-type="total" class="trackable ">最近7天</a>
                            <span class="seprator"></span>
                            <a href="javascript:;" data-id="30" data-type="total" class="trackable">最近30天</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main" id="groups" style="width: 100%; height: 260px"></div>
        </div>
    </div>
</div>
<style>
    .api li {
        float: left;
        padding: 30px;
    }
</style>
<div class="clearfix"></div>
<div class="row" style="margin-bottom: 10px;margin-top: 10px;overflow: hidden">
    <div class="block4" style="">
        <ul class="api">
            <li style="border-right: 1px solid #f4f4f4;padding-left: 10px">
                <div class="easy-pie-chart percentage" data-percent="30" data-color="#D15B47"
                     style="margin-bottom: 10px">

                    <span class="percent"><?php echo $error_count ?></span>
                </div>
                <p>今日接口错误统计</p>
            </li>
            <li style="border-right: 1px solid #f4f4f4;padding-left: 10px">
                <div class="easy-pie-chart percentage" data-percent="40" data-color="#9A74B1"
                     style="margin-bottom: 10px">

                    <span class="percent"><label class="apiCallCount"><?php echo $api_call_total_count ?></label></span>
                </div>
                <p>今日接口总调用</p>
            </li>
            <li style="border-right: 1px solid #f4f4f4;padding-left: 10px">
                <div class="easy-pie-chart percentage" data-percent="50" data-color="#87B87F"
                     style="margin-bottom: 10px">
                    <span class="percent"><label class="sMessage"><?php echo $s_message_count ?></label></span>
                </div>
                <p>今日消息总量</p>
            </li>
            <li style="border-right: 1px solid #f4f4f4;padding-left: 10px">
                <div class="easy-pie-chart percentage" data-percent="60" data-color="#308E1C"
                     style="margin-bottom: 10px">
                    <span class="percent"><label class=""><?php echo $coin_count ?></label></span>
                </div>
                <p>今日龙豆流动值</p>
            </li>
            <li style="border-right: 1px solid #f4f4f4;padding-left: 10px">
                <div class="easy-pie-chart percentage" data-percent="60" data-color="#8D44AD"
                     style="margin-bottom: 10px">
                    <span class="percent"><label class=""><?php echo $user_online ?></label></span>
                </div>
                <p>当前在线用户</p>
            </li>
            <li style="border-right: 1px solid #f4f4f4;padding-left: 10px">
                <div class="easy-pie-chart percentage" data-percent="60" data-color="#3598DC"
                     style="margin-bottom: 10px">
                    <span class="percent"><label class=""><?php echo $feedback_count ?></label></span>
                </div>
                <p>今日问题反馈</p>
            </li>
            <li style="border-right: 1px solid #f4f4f4;padding-left: 10px">
                <div class="easy-pie-chart percentage" data-percent="60" data-color="#648E44"
                     style="margin-bottom: 10px">
                    <span class="percent"><label class=""><?php echo $package_count ?></label></span>
                </div>
                <p>今日视频红包</p>
            </li>

        </ul>
    </div>
</div>
<style>
    .block5 .list .item {
        margin-right: 20px;
    }

    .block5 .list tr td {
        line-height: 30px
    }

    .block5 .list .item b {
        color: #4372A6;
    }
</style>
<div class="row" style="margin-bottom: 10px;margin-top: 10px;overflow: hidden;">
    <div class="block5" style="width: 100%;overflow: hidden;background-color: #fff;padding: 10px">
        <div class="page-header" style="margin-bottom: 2px;padding-top: 10px"><i class="fa fa-database"></i>&nbsp;<b
                style="padding-left: 10px;">服务器信息</b>
            <table id="article-list" class='list'>
                <thead>
                <tr class="head">
                    <th style='width:120px'>标识</th>
                    <th>参数值</th>
                </tr>
                </thead>
                <tbody class="listData">
                <?php
                $memory = \Util\Linux::getMemoryUse();
                $cpu = \Util\Linux::getCpuUse();
                $disk = \Util\Linux::getDiskUse();
                if ($memory) { ?>
                    <tr>
                        <td>内存使用情况</td>
                        <td>
                            <?php foreach ($memory as $k => $item) { ?>
                                <?php echo "<label class='item'>" . (\Util\Linux::$mem_name[$k]) . "：<b>" . $item . '</b></label>' ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($cpu) { ?>
                    <tr>
                        <td>CPU使用情况</td>
                        <td>
                            <?php foreach ($cpu as $k => $item) { ?>
                                <?php if ($k == 'core') { ?>
                                    <?php echo "<label class='item'>核数：<b>" . $item . '核</b></label>' ?>
                                <?php } else { ?>
                                    <?php echo "<label class='item'>" . (\Util\Linux::$cpu_name[$k]) . "：<b>" . $item . '%</b></label>' ?>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($disk) { ?>
                    <tr>
                        <td>系统盘信息</td>
                        <td>
                            <?php foreach ($disk['system'] as $k => $item) { ?>
                                <?php echo "<label class='item'>" . (\Util\Linux::$disk_name[$k]) . "：<b>" . $item . '</b></label>' ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php if ($disk['mount']) { ?>
                        <?php foreach ($disk['mount'] as $k => $item) { ?>
                            <tr>
                                <td>硬盘</td>
                                <td>
                                    <?php foreach ($item as $j => $i) { ?>
                                        <?php echo "<label class='item'>" . (\Util\Linux::$disk_name[$j]) . "：<b>" . $i . '</b></label>' ?>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
                <tr>
                    <td>PHP版本号</td>
                    <td>
                        <?php echo PHPVERSION(); ?>
                    </td>
                </tr>
                <tr>
                    <td>PHP框架</td>
                    <td>
                        <?php
                        $phalcon = new ReflectionExtension('phalcon');
                        echo 'phalcon-' . $phalcon->getVersion(); ?>
                    </td>
                </tr>
                <tr>
                    <td>服务器引擎</td>
                    <td>
                        <?php
                        echo $_SERVER['SERVER_SOFTWARE']
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!--        <h4 class="box-title">账户信息</h4>-->
<!---->
<!--        <div class="base-info">-->
<!--            <p>-->
<!--                <span class="right">-->
<!--                    <a href="javascript:;" class="newTarget" data-title="个人资料编辑" data-id="edit_info"-->
<!--                       data-href="/panel/setting/index">编辑</a>-->
<!--                </span>-->
<!--                <strong>账号ID:</strong>-->
<!--                --><?php //echo $admin['id']; ?>
<!--                <strong>管理账号:</strong>-->
<!--                --><?php //echo $admin['account']; ?>
<!--                <!-- <strong>管理邮箱</strong>-->
<!--                 --><?php ///*echo $admin->email; */ ?>
<!--            </p>-->
<!--            <p>-->
<!--                <strong>用户名称</strong>-->
<!--                --><?php //echo $admin['name']; ?>
<!---->
<!--                <strong>你的角色</strong>-->
<!--                --><?php
//                echo $admin['group_name'];
//                ?>
<!--            </p>-->
<!---->
<!--        </div>-->
<!--        <h4 class="box-title">服务器信息</h4>-->
<!---->
<!--        <div class="base-info">-->
<!--            <p>-->
<!--                <span class="right">-->
<!--                  <!--  <a href="/panel/setting/index">编辑</a>-->
<!--                </span>-->
<!--                <strong>php版本:</strong>-->
<!--                --><?php //echo PHPVERSION(); ?>
<!--                <strong>php框架:</strong>-->
<!--                --><?php
//                $phalcon = new ReflectionExtension('phalcon');
//                echo 'phalcon' . $phalcon->getVersion(); ?>
<!--                <!-- <strong>管理邮箱</strong>-->
<!--                 --><?php ///*echo $admin->email; */ ?>
<!--            </p>-->
<!--            <p>-->
<!--                <strong>服务器引擎</strong>-->
<!--                --><?php //echo $_SERVER['SERVER_SOFTWARE']; ?>
<!---->
<!--            </p>-->
<!---->
<!---->
<!--        </div>-->
</div>
</div>
<script src="/srv/static/ace/js/jquery.knob.min.js"></script>

<script src="/srv/static/ace/js/jquery.easy-pie-chart.min.js"></script>

<script>
    var oldie = /msie\s*(8|7|6)/.test(navigator.userAgent.toLowerCase());
    $(".knob").knob();
    $('.easy-pie-chart.percentage').each(function () {
        $(this).easyPieChart({
            barColor: $(this).data('color'),
            trackColor: '#EEEEEE',
            scaleColor: false,
            lineCap: 'butt',
            lineWidth: 8,
            animate: oldie ? false : 1000,
            size: 100
        }).css('color', $(this).data('color'));
    });
</script>
<script type="text/javascript" src="/srv/static/panel/js/echarts/echarts.min.js"></script>
<script type="text/javascript" src="/srv/static/panel/js/jquery/jquery.numberChange.js"></script>

<script type="text/javascript">
    $(function(){
        $(".page-content").css({'width':$(window.parent.document).find(".main-content").width()});
        seajs.use(['/srv/static/panel/js/app/panel/welcome.js?v=1.0', '/srv/static/panel/js/app/panel/panel.base'], function (api, base) {
            api.statistic();
            var getApiCount = function () {
                base.requestApi('/api/index/apiCallCount', {}, function (res) {
                    var start = $('.apiCallCount').html();
                    start = isNaN(start) ? 0 : parseInt(start);
                    $('.apiCallCount').countTo({from: start, to: parseInt(res.data.count), 'speed': 1000})
                }, true, true);
                base.requestApi('/api/index/messageCount', {}, function (res) {
                    var start = $('.sMessage').html();
                    start = isNaN(start) ? 0 : parseInt(start);
                    $('.sMessage').countTo({from: start, to: parseInt(res.data), 'speed': 1000})
                }, true, true)
            };
            //setInterval(getApiCount, 2000);
        })
    })

</script>
<style>
    .inner-nav, .page-header {
        background: #fff;
    }

    .inner-nav {
        box-shadow: none;
    }

    .page-header {
        margin: -5px;;
        margin-bottom: 5px;
        border-bottom: 1px solid #e4e4e4;
        position: fixed;
        top: 85px;
        left: 196px;
        width: 100%;
        max-width: 100%;
        padding-right: 280px;;
        padding-bottom: 5px;
    }

    .page-content {
        padding: 4px;
    }

    .main-container:after, .page-content {
        background: #ececec
    }

</style>
<form action="" method="get"
      style="border: 1px solid #e4e4e4;border-top:none;position:fixed;width:100%;padding: 8px;margin: -5px; line-height: 50px;background-color: #fff;">
    <label for="name">关键字</label>
    <input name="key" type="text" id="key" placeholder="用户昵称/手机/用户ID" value="<?php echo $key; ?>">
    &nbsp;<label for="name">时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="开始时间" name="start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="结束时间" name="end"
             data-date-format="yyyy-mm-dd"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
</form>
<link rel="stylesheet" type="text/css" href="/srv/static/panel/css/plugins/jquery/jquery.datetimepicker.css">
<script type="text/javascript" src="/srv/static/panel/js/jquery/jquery.datetimepicker.js"></script>

<script>

    //  var receive_start_val;
    $('#start').datetimepicker({
        lang: "ch",
        step: 5,
        format: "Y-m-d",
        maxDate: 0,
        timepicker: false,
        allowBlank: true,
        onChangeDateTime: function () {
        }
    });
    $('#end').datetimepicker({
        lang: "ch",
        step: 5,
        format: "Y-m-d",
        maxDate: 0,
        timepicker: false,
        allowBlank: true,
        onChangeDateTime: function () {
        }
    });
</script>
<style>
    .message_list {
        margin: 0;
        padding: 10px;
        height: auto;
        overflow: hidden;
        width: 99%;
        float: left;
        background-color: #fff;
        border-bottom: 1px solid #f5f5f5;

    }

    .message_list li {
        /*  margin-bottom: 10px;*/
        width: 50%;
        height: auto;
        overflow: hidden;
        border-bottom: 1px solid #f5f5f5;
        padding: 5px;
        cursor: pointer;
        float: left;
        background-color: #fff;
        border-left: 5px solid #fff;

    }

    /*  .message_list li:nth-child(2n) {
          float: right;
      }

      .message_list li:nth-child(2n+1) {
          float: left;
  !*
          margin-right: 5px;
  *!
      }*/

    .message_list li:nth-child(2n):last-child, .message_list li:nth-child(2n+1):last-child {
        border-bottom: none;
    }

    .message_list li:hover {
        /*  background-color: #438ea7;
          border-bottom: 1px solid #438ea7;*/
        border-left: 5px solid purple;
        background-color: #eee;
    }

    .message_list li .avatar {
        width: 50px;
        height: 50px;
        border-radius: 100%;
        float: left
    }

    .message_list li .info {
        float: left;
        max-width: 500px;
        display: block;
        margin-left: 10px
    }

    .message_list li .info .username {
        height: 30px;
        line-height: 30px;
        vertical-align: middle
    }

    .message_list li .info .msg {
        height: 30px;
        line-height: 30px;
        vertical-align: middle
    }

    .message_list li .right_content {
        float: right;
        width: 200px;
        display: block;
        margin-left: 10px
    }

    .message_list li .right_content .time {
        height: 30px;
        line-height: 30px;
        vertical-align: middle
    }

    .message_list li .right_content .unread_count {
        height: 30px;
        line-height: 30px;
        vertical-align: middle
    }

    .message_list li .right_content .unread_count .count {
        width: 26px;
        height: 26px;
        line-height: 26px;
        vertical-align: middle;
        text-align: center;
        display: inline-block;
        border-radius: 100%;
        color: #f6f6f6;
        background-color: #FC3F3B;
        font-size: 12px;

    }

    .list {
        width: 99%;
    }

    .list td, .list th {
        border: none;
        height: 40px;
        line-height: 40px;
        vertical-align: middle;
    }

    #content_wrap {
        width: 99%;
        margin: auto;
        min-height: 500px;
        background-color: #ececec;
        border: 1px solid #ececec;
        margin-top: 90px;
    }
</style>
<div id="content_wrap">

    <ul class="message_list">
        <?php foreach ($list as $item) {
            $media_type = strtoupper($item['media_type']);
            $unread_count = $this->redis->hGet("unread_message", $item['from_uid']);
            ?>
            <li><a href="javascript:;"
                   class="newTarget"
                   data-title="会话详情"
                   data-id="converstation_<?php echo $item['from_uid'] ?>"
                   data-href="/panel/message/converstation?uid=<?php echo $item['from_uid'] ?>"
                   style="display: block;overflow: hidden" target="_blank"><img class="avatar"
                                                                                src="<?php echo $user_info[$item['from_uid']]['avatar'] ?>?x-oss-process=image/resize,m_fill,h_160,w_160"
                    />
                    <div class="info">
                        <p class="username"><?php echo $user_info[$item['from_uid']]['username'] ?> </p>

                        <p class="msg"><?php
                            if ($media_type == \Services\Im\NotifyManager::msgType_PICTURE) {
                                $body = json_decode($item['body'], true);
                                ?>
                                【图片信息】
                            <?php } else if ($media_type == \Services\Im\NotifyManager::msgType_AUDIO) { ?>
                                【语音信息】
                            <?php } else { ?>
                                <?php echo $item['body'] ?>
                            <?php } ?>

                        </p>

                    </div>
                    <div class="right_content">
                        <p class="time"><?php echo \Util\Time::formatHumaneTime($item['send_time']/1000) ?></span></p>

                        <p class="unread_count">
                            <?php if ($unread_count) { ?>
                                <span class="count">
                                <?php echo $unread_count < 100 ? $unread_count : '99+' ?>
                            </span>
                            <?php } ?>
                        </p>
                    </div>
                </a>
            </li>
        <?php } ?>
    </ul>
    <table class=' list'>
        <tbody class="listData">
        <tr class="showpage">
            <th class="name">分页</th>
            <td colspan="17">
                <?php \Util\Pagination::instance($this->view)->display($this->view); ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<script>
    seajs.use('app/panel/panel.base', function (api) {

    })
</script>

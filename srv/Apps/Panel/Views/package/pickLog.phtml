<link rel="stylesheet" type="text/css" href="/srv/static/panel/css/plugins/jquery/jquery.datetimepicker.css">
<script type="text/javascript" src="/srv/static/panel/js/jquery/jquery.datetimepicker.js"></script>
<form action="" method="get"
      style="border-bottom: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px; line-height: 50px;">
    <label for="name">红包ID</label>
    <input name="package_id" type="text" id="package_id" placeholder="红包ID"
           value="<?php echo $package_id ? $package_id : ''; ?>">
    &nbsp;
    <label for="name">用户ID</label>
    <input name="uid" type="text" id="uid" placeholder="用户ID" value="<?php echo $uid ? $uid : ''; ?>">
    <label for="name">设备号</label>
    <input name="device_id" type="text" id="device_id" placeholder="设备号" value="<?php echo $device_id ? $device_id : ''; ?>">
    &nbsp;<label for="name">时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="领取开始时间" name="start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="领取结束时间" name="end"
             data-date-format="yyyy-mm-dd"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
</form>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:36px'>ID</th>
        <th style='width:80px'> 用户ID</th>
        <th style='width:200px'> 用户名</th>
        <!--        <th style='width:80px'> 用户头像</th>-->
        <!--  <th style='width:56px'>批量</th>-->
        <th style='width:200px'>红包ID</th>
        <th style='width:120px'> 领取金额</th>
        <th style='width:400px'> 设备号</th>
        <th style='width:200px'> 领取时间</th>
        <!--        <th style='width:80px'>是否有效</th>-->
        <th>操作</th>
    </tr>
    </thead>
    <?php
    if ($list) {
    ?>

    <tbody class="listData">
    <?php
    foreach ($list as $item) {
        ?>
        <tr class="item" data-id="<?php echo $item['id']; ?>">
            <th class='name'><?php echo $item['id']; ?></th>
            <td>
                <?php echo $item['user_id'] ?>
            </td>
            <td>
                <?php echo $users[$item['user_id']]['username'] ?>
            </td>

            <!--            <td><a href="-->
            <?php //echo $users[$item['user_id']]['avatar']; ?><!--" data-lightbox="roadtrip"><img-->
            <!--                        src="-->
            <?php //echo $users[$item['user_id']]['avatar']; ?><!--?x-oss-process=image/resize,m_fill,h_160,w_160"-->
            <!--                        style="width: 40px; height: 40px;border-radius: 100%"/></a>-->
            <!--            </td>-->
            <!--   <td class="center"><input type="checkbox" class="chk" data-id="<?php /*echo $item['id']; */ ?>"/>
            </td>-->
            <td><?php echo $item['package_id']; ?></td>
            <td>
                <?php echo '￥' . sprintf("%.2f", $item['money'] / 100) ?>
            </td>
            <td><?php echo $item['device_id'] ?></td>

            <!--   <td>
                <?php /*echo \Services\Site\AdvertiseManager::$_type_name[$item['content_type']] */ ?>
            </td>-->

            <td>
                <?php echo date('Y年m月d日 H:i:s', $item['created']) ?>
            </td>
            <td>
                <a href="javascript:;" data-href="/panel/users/detail/?user_id=<?php echo $item['user_id']; ?>"
                   data-title="用户详情"
                   class="btn btn-primary up_btn btn-sm newTarget"><i class="fa fa-user"></i> 用户详情</a>
                <a href="javascript:;" data-href="/package/detail?p_id=<?php echo $item['package_id'] ?>"
                   data-title="红包详情-<?php echo $item['package_id'] ?>" class="btn btn-sm btn-success newTarget"><i
                        class="fa fa-money"></i> 红包详情</a>
            </td>

        </tr>
        <?php
    }
    } else {
        ?>
        <tr>
            <td colspan="17">
                <p style="margin: 20px;color:#f00;"> 暂无内容 </p>
            </td>
        </tr>
    <?php } ?>
    </tbody>
    <!--  <tr class="showpage">
          <th class="name">操作</th>
          <td colspan="17">
                  <span>
                      [ <a href="javascript:;" class="selectAll">全选</a> ]
                      [ <a href="javascript:;" class="selectNone">全不选</a> ]
                      [ <a href="javascript:;" class="selectInvert">反选</a> ]
                      <a class="btn-light delAllSelected" href="javascript:;">批量屏蔽</a>
                  </span>
          </td>
      </tr>-->
    <tr class="showpage">
        <th class="name">分页</th>
        <td colspan="17">
            <?php \Util\Pagination::instance($this->view)->display($this->view); ?>
        </td>
    </tr>
</table>
<script>
    seajs.use('app/panel/system/package.ads.js?v=1.0.1', function (api) {
        api.removeAds();
    });
</script>
<script src="/srv/static/ace/js/jquery.colorbox-min.js"></script>

<script type="text/javascript">
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

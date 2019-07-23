<link rel="stylesheet" type="text/css" href="/static/panel/js/tools/datetimepicker/bootstrap-datetimepicker.min.css">
<link rel="stylesheet" type="text/css" href="/static/panel/ali_iconfont/iconfont.css?v=1.0">
<script src="/static/panel/ali_iconfont/iconfont.js?v=1.0"></script>
<style>
    .icon {
        width: 1em;
        height: 1em;
        vertical-align: -0.15em;
        fill: currentColor;
        overflow: hidden;
        display: inline-block;
    }

</style>
<form action="" method="get"
      style="border-bottom: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px; line-height: 50px;">
    <label for="name">用户ID</label>
    <input name="uid" type="text" id="uid" placeholder="用户ID" value="<?php echo $uid ? $uid : ''; ?>">
    &nbsp;
    <label for="name">任务类型</label>
    <select name="task_key">
        <option value="-1" <?php echo $status == -1 ? 'selected' : ''; ?>>全部</option>
        <?php foreach (\Services\User\Square\SquareTask::$behaviorNameMap as $key => $item) { ?>
            <option
                value="<?php echo $key ?>" <?php echo $task_key == $key ? 'selected' : ''; ?>><?php echo $item ?></option>
        <?php } ?>
    </select>
    &nbsp;<label for="name">时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="发布开始时间" name="start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="发布结束时间" name="end"
             data-date-format="yyyy-mm-dd"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
</form>
<div class="tabs">
    <a href="<?php echo $this->uri->setUrl(['type' => 0], ['p']); ?>"
       class="tab <?php echo $type == 0 ? 'active' : ''; ?>">默认</a>
    <a href="<?php echo $this->uri->setUrl(['type' => 1], ['p']); ?>"
       class="tab <?php echo $type == 1 ? 'active' : ''; ?>">以用户和任务类型聚合</a>
    <a href="<?php echo $this->uri->setUrl(['type' => 2], ['p']); ?>"
       class="tab <?php echo $type == 2 ? 'active' : ''; ?>">以任务类型聚合</a>
    <a href="<?php echo $this->uri->setUrl(['type' => 3], ['p']); ?>"
       class="tab <?php echo $type == 3 ? 'active' : ''; ?>">以用户聚合</a>
</div>
<table id="article-list" class=' list' style="width: 80%">
    <thead>
    <tr class="head">
        <?php if ($type == 0) { ?>
            <th style='width:200px'>用户ID</th>
            <th style='width:200px'>用户名</th>
            <th style='width:200px'>任务类型</th>
            <th style='width:160px'> 完成时间</th>
            <th style='width:200px' class="arrow" data-sort="" data-order="value">
                <span class="text">增加红包领取次数</span>
            <span class="arrow_wrap">
                <i class="fa fa-caret-down arrow-down"></i>
                <i class="fa fa-caret-up arrow-up "></i>
            </span>
            </th>
        <?php } else if ($type == 1) { ?>
            <th style='width:200px'>用户ID</th>
            <th style='width:200px'>用户名</th>
            <th style='width:200px'>任务类型</th>
            <th style='width:160px'> 最后完成时间</th>
            <th style='width:200px' class="arrow" data-sort="" data-order="value">
                <span class="text">增加红包领取次数</span>
            <span class="arrow_wrap">
                <i class="fa fa-caret-down arrow-down"></i>
                <i class="fa fa-caret-up arrow-up "></i>
            </span>
            </th>
        <?php } else if ($type == 2) { ?>
            <th style='width:200px'>任务类型</th>
            <th style='width:200px' class="arrow" data-sort="" data-order="value">
                <span class="text">增加红包领取次数</span>
            <span class="arrow_wrap">
                <i class="fa fa-caret-down arrow-down"></i>
                <i class="fa fa-caret-up arrow-up "></i>
            </span>
            </th>
        <?php } else if ($type == 3) { ?>
            <th style='width:200px'>用户ID</th>
            <th style='width:200px'>用户名</th>
            <th style='width:200px' class="arrow" data-sort="" data-order="value">
                <span class="text">增加红包领取次数</span>
            <span class="arrow_wrap">
                <i class="fa fa-caret-down arrow-down"></i>
                <i class="fa fa-caret-up arrow-up "></i>
            </span>
            </th>
        <?php } ?>

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
            <?php if ($type == 0) { ?>
                <td><?php echo $item['user_id']; ?></td>
                <td><?php echo $users[$item['user_id']]['username']; ?></td>
                <td class='name'><?php echo $item['action_desc']; ?></td>
                <td><?php echo date('Y-m-d H:i', $item['created']) ?></td>
                <td><?php echo $item['value'] ?></td>
            <?php } else if ($type == 1) { ?>
                <td><?php echo $item['user_id']; ?></td>
                <td><?php echo $users[$item['user_id']]['username']; ?></td>
                <td class='name'><?php echo $item['action_desc']; ?></td>
                <td><?php echo date('Y-m-d H:i', $item['created']) ?></td>
                <td><?php echo $item['value'] ?></td>
            <?php } else if ($type == 2) { ?>
                <td class='name'><?php echo $item['action_desc']; ?></td>
                <td><?php echo $item['value'] ?></td>
            <?php } else if ($type == 3) { ?>
                <td><?php echo $item['user_id']; ?></td>
                <td><?php echo $users[$item['user_id']]['username']; ?></td>
                <td><?php echo $item['value'] ?></td>
            <?php } ?>
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
    <tr class="showpage">
        <th class="name">分页</th>
        <td colspan="17">
            <?php \Util\Pagination::instance($this->view)->display($this->view); ?>
        </td>
    </tr>
</table>


<link rel="stylesheet" type="text/css" href="/srv/static/panel/css/plugins/jquery/jquery.datetimepicker.css">
<script type="text/javascript" src="/srv/static/panel/js/jquery/jquery.datetimepicker.js"></script>
<script src="/srv/static/panel/js/tools/Url.js"></script>

<script>
    $('[data-rel=tooltip]').tooltip();
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
    var order = '<?php echo $sort ?>'
    var sort_order = '<?php echo $sort_order ?>'
    if (order != '' && sort_order != '') {
        var current = $(".arrow[data-order='" + order + "']");
        current.addClass("active");
        current.attr('data-sort', sort_order);
        if (sort_order == 'desc') {
            current.find('.arrow-down').addClass('active');
            current.find('.arrow-up').addClass('disabled');
        } else {
            current.find('.arrow-down').addClass('disabled');
            current.find('.arrow-up').addClass('active');
        }
    }
    var url = new Url();
    $(".arrow").on('click', function () {
        var order = $(this).attr('data-order');
        var sort = $(this).attr('data-sort');
        sort = sort == 'desc' ? 'asc' : 'desc';
        url.setArgs({order: sort, sort: order, p: 1});
        window.location.href = url.getUrl();
    });
</script>
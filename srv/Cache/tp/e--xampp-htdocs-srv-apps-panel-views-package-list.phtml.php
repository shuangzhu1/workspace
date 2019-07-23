<link rel="stylesheet" type="text/css" href="/srv/static/panel/js/tools/datetimepicker/bootstrap-datetimepicker.min.css">
<link rel="stylesheet" type="text/css" href="/srv/static/panel/ali_iconfont/iconfont.css?v=1.0">
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
    <label for="name">红包ID</label>
    <input name="key" type="text" id="key" placeholder="红包ID" value="<?php echo $key ? $key : ''; ?>">
    &nbsp;
    <label for="name">用户ID</label>
    <input name="uid" type="text" id="uid" placeholder="用户ID" value="<?php echo $uid ? $uid : ''; ?>">
    &nbsp;
    <label for="name">状态</label>
    <select name="status">
        <option value="-1" <?php echo $status == -1 ? 'selected' : ''; ?>>全部</option>
        <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?>>未过期</option>
        <option value="2" <?php echo $status == 2 ? 'selected' : ''; ?>>已过期</option>
    </select>
    &nbsp;
    <label for="name">状态</label>
    <select name="pick_status">
        <option value="-1" <?php echo $pick_status == -1 ? 'selected' : ''; ?>>全部</option>
        <option value="3" <?php echo $pick_status == 3 ? 'selected' : ''; ?>>已领完</option>
        <option value="1" <?php echo $pick_status == 1 ? 'selected' : ''; ?>>派发中</option>
    </select>
    <label for="name">是否机器人</label>
    <select name="robot">
        <option value="-1" <?php echo $robot == -1 ? 'selected' : ''; ?>>全部</option>
        <option value="1" <?php echo $robot == 1 ? 'selected' : ''; ?>>是</option>
        <option value="0" <?php echo $robot == 0 ? 'selected' : ''; ?>>否</option>
    </select>
    &nbsp;<label for="name">分类</label>
    <select name="type">
        <option value="-1" <?php echo $type == -1 ? 'selected' : ''; ?>>全部</option>
        <option value="1" <?php echo $type == 1 ? 'selected' : ''; ?>>商品红包</option>
        <option value="2" <?php echo $type == 2 ? 'selected' : ''; ?>>普通红包</option>
    </select>
    &nbsp;<label for="name">时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="发布开始时间" name="start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="发布结束时间" name="end"
             data-date-format="yyyy-mm-dd"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
    <a class="btn btn-primary btn-sm right newTarget" href="javascript:;" data-href="/package/mapList"
       data-title="红包广场地图展示"><i class="fa fa-map-pin"></i> 地图展示</a>
</form>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:200px'>红包ID</th>
        <!-- <th style='width:60px'>批量</th>-->
        <th style='width:200px'>发布人</th>
        <th style='width:160px' class="arrow" data-sort="" data-order="created">
            <span class="text">发布时间</span>
            <span class="arrow_wrap">
                <i class="fa fa-caret-down arrow-down"></i>
                <i class="fa fa-caret-up arrow-up "></i>
            </span>
        </th>
        <th style='width:200px' class="arrow" data-sort="" data-order="deadline">
            <span class="text">过期时间</span>
            <span class="arrow_wrap">
                <i class="fa fa-caret-down arrow-down"></i>
                <i class="fa fa-caret-up arrow-up "></i>
            </span></th>
        <th style='width:60px'>类型</th>
        <th style='width:100px' class="arrow" data-sort="" data-order="package">
            <span class="text">红包金额</span>
            <span class="arrow_wrap">
                <i class="fa fa-caret-down arrow-down"></i>
                <i class="fa fa-caret-up arrow-up "></i>
            </span></th>
        <th style='width:120px'>
            红包个数
        </th>
        <th style='width:80px'>状态</th>
        <th style='width:80px'>红包范围</th>
        <th style='width:200px'>地址</th>
        <th style='width:80px'>领取状态</th>
        <th style='width:80px'>机器人</th>
        <th>操作</th>
    </tr>
    </thead>
    <?php
    if ($list) {
    ?>
    <tbody class="listData">
    <?php
    foreach ($list as $item) {
        $content = \Util\FilterUtil::unPackageContentTag($item['content'], 0, "/panel/users/detail?user_id=");
        $package_info = json_decode($item['package_info'], true);
        ?>
        <tr class="item" data-id="<?php echo $item['id']; ?>">
            <th class='name'><?php echo $item['package_id']; ?></th>
            <!--   <td class="center"><label>
                    <input type="checkbox" class="chk ace" data-id="<?php /*echo $item['id']; */ ?>"/>
                    <span class="lbl"></span>
                </label>
            </td>-->
            <td><?php echo $item['user_id'] . '【' . $users[$item['user_id']]['username'] . '】'; ?></td>
            <td><?php echo date('Y-m-d H:i:s', $item['created'] / 1000); ?></td>
            <td>
                <?php echo date('Y-m-d H:i:s', $item['deadline']); ?>
            </td>
            <td>
                <?php echo \Services\User\SquareManager::$type_name[$item['type']]; ?>
            </td>
            <td>
                <?php echo sprintf('%.2f', $item['money'] / 100) . "元" ?>
            </td>
            <td>
                <?php echo $item['num']; ?>
            </td>

            <td>
                <?php echo ($item['deadline'] > time()) ? '未过期' : '已过期'; ?>
            </td>
            <td><?php echo \Services\User\SquareManager::$range_type_name[$item['range_type']] ?></td>
            <td>
                <?php echo $item['address'] ? $item['address']['province'] . '-' . $item['address']['city'] . '-' . $item['address']['district'] : '' ?></td>
            <td>
                <?php if ($item['status'] == 1 && $item['deadline'] > time()) { ?>
                    <label class="badge badge-success">派发中</label>
                <?php } elseif ($item['status'] == 3) { ?>
                    <label class="badge badge-grey">已领完</label>
                <?php } else { ?>
                    <label class="badge badge-grey">已过期</label>
                <?php } ?>
            <td><?php echo $item['is_rob'] == 1 ? '是' : '否' ?></td>
            <td>
                <a href="javascript:;" data-href="/package/detail?p_id=<?php echo $item['package_id'] ?>"
                   data-title="红包详情-<?php echo $item['package_id'] ?>" class="btn btn-sm btn-primary newTarget">查看详情</a>
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
<script type="text/javascript" src="/srv/static/panel/js/tools/clipboard/clipboard.min.js"></script>

<script>
    var clipboard = new Clipboard('.copy-link', {});
    clipboard.on('success', function (e) {
        alert("复制成功");
        /* var sel = "#" + e.text;
         $(sel).tooltip('show');
         setTimeout(function () {
         // $(sel).tooltip('hide');
         }, 1500);*/
    });
</script>

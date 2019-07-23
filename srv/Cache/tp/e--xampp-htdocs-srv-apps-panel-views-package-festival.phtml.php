<link rel="stylesheet" type="text/css" href="/srv/static/panel/js/tools/datetimepicker/bootstrap-datetimepicker.min.css">
<link rel="stylesheet" type="text/css" href="/srv/static/panel/ali_iconfont/iconfont.css?v=1.0">
<script src="/srv/static/panel/ali_iconfont/iconfont.js?v=1.0"></script>
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
    &nbsp;
    <label for="name">状态</label>
    <select name="status">
        <option value="-1" <?php echo $status == -1 ? 'selected' : ''; ?>>全部</option>
        <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?>>待发布</option>
        <option value="2" <?php echo $status == 2 ? 'selected' : ''; ?>>已发布</option>
        <option value="3" <?php echo $status == 3 ? 'selected' : ''; ?>>发布失败</option>
    </select>
    &nbsp;<label for="name">时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="发布开始时间" name="start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="发布结束时间" name="end"
             data-date-format="yyyy-mm-dd"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
    <a class="btn btn-primary btn-sm right newTarget" href="javascript:;" data-title="添加节日红包"
       data-href="/package/festivalAdd"><i class="fa fa-plus"></i> 添加红包</a>
</form>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <!-- <th style='width:60px'>批量</th>-->

        <th style='width:80px'>用户id</th>
        <th style='width:150px'>用户名</th>
        <th style='width:160px'>红包id</th>
        <th style='width:160px'>发布时间</th>
        <th style="width: 200px">广告图片</th>
        <th style="width: 300px">广告内容</th>
        <th style='width:100px'> 红包金额</th>
        <th style='width:120px'>红包个数</th>
        <th style='width:80px'>状态</th>
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

            <th class='name'><?php echo $item['user_id'] ? $item['user_id'] : ''; ?></th>
            <th class='name'><?php echo $item['user_id'] ? $users[$item['user_id']]['username'] : ''; ?></th>
            <th class='name'><?php echo $item['package_id'] ? $item['package_id'] : ''; ?></th>
            <th class='name'><?php echo date('Y-m-d H:i:s', $item['send_time']); ?></th>
            <!--   <td class="center"><label>
                    <input type="checkbox" class="chk ace" data-id="<?php /*echo $item['id']; */ ?>"/>
                    <span class="lbl"></span>
                </label>
            </td>-->
            <td>  <?php if ($item['media'] != '') {
                    $images = explode(',', $item['media']);
                    ?>
                    <ul class="ace-thumbnails" data-id="<?php echo $item['id']; ?>">
                        <?php foreach ($images as $img) { ?>
                            <li style="width: 50px;height: 50px;">
                                <a href="<?php echo $img; ?>" data-rel="<?php echo $item['id']; ?>">
                                    <img alt="100x100" style="width: 50px; height: 50px"
                                         src="<?php echo $img . '?x-oss-process=image/resize,m_fixed,h_50,w_50'; ?>"/>
                                    <div class="text">
                                        <div class="inner">点击查看大图</div>
                                    </div>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?></td>
            <td><?php echo $item['content']; ?></td>
            <td>
                ￥<?php echo sprintf('%.2f', $item['money'] / 100) ?>
            </td>
            <td>
                <?php echo $item['num']; ?>
            </td>

            <td>
                <?php if ($item['status'] == \Services\User\SquareManager::festival_wait_publish) { ?>
                    <span class="badge badge-primary">待发布</span>
                <?php } else if ($item['status'] == \Services\User\SquareManager::festival_deleted) { ?>
                    <span class="badge badge-gray">已删除</span>
                <?php } else if ($item['status'] == \Services\User\SquareManager::festival_has_published) { ?>
                    <span class="badge badge-success">已发布</span>
                <?php } else if ($item['status'] == \Services\User\SquareManager::festival_publish_fail) { ?>
                    <span class="badge badge-error">发布失败</span>
                <?php } ?>

            </td>
            <td>
                <?php if ($item['status'] == \Services\User\SquareManager::festival_wait_publish) { ?>
                    <a href="javascript:;" data-href="/panel/package/festivalAdd?id=<?php echo $item['id'] ?>"
                       data-title="假日红包详情"
                       class="btn btn-success up_btn btn-sm newTarget"><i class="fa fa-edit"></i>编辑</a>
                    <a href="javascript:;" class="btn del_btn btn-sm btnRemove" data-id="<?php echo $item['id'] ?>"><i
                            class="fa fa-trash"></i> 删除</a>
                <?php } ?>


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
    seajs.use('app/panel/system/package.ads.js?v=1.0.3', function (api) {
        api.removeFestivalPackage();
    });
</script>
<script src="/srv/static/ace/js/jquery.colorbox-min.js"></script>

<script type="text/javascript">
    $(function () {
        var colorbox_params = {
            reposition: true,
            scalePhotos: true,
            scrolling: false,
            previous: '<i class="fa fa-arrow-left"></i>',
            next: '<i class="fa fa-arrow-right"></i>',
            close: '&times;',
            current: '{current}/{total}',
            maxWidth: '100%',
            maxHeight: '100%',
            onOpen: function () {
                document.body.style.overflow = 'hidden';
            },
            onClosed: function () {
                document.body.style.overflow = 'auto';
            },
            onComplete: function () {
                $.colorbox.resize();
            }
        };
        $('.ace-thumbnails').each(function () {
            $('.ace-thumbnails [data-rel="' + $(this).attr('data-id') + '"]').colorbox(colorbox_params);
        });
        /*
         $('.ace-thumbnails [data-rel="colorbox"]').colorbox(colorbox_params);
         */
        $("#cboxLoadingGraphic").append("<i class='fa fa-spinner orange'></i>");//let's add a custom loading icon
    })
</script>
<link rel="stylesheet" href="/srv/static/panel/css/lightbox/lightbox.css"/>

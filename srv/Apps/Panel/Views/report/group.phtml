<link rel="stylesheet" type="text/css" href="/srv/static/panel/js/tools/datetimepicker/bootstrap-datetimepicker.min.css">

<form action="" method="get" style="border-bottom: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px;">
    <label for="name">被举报人/群</label>
    <input name="user_id" type="text" id="user_id" placeholder="用户/群ID"
           value="<?php echo $user_id ? $user_id : ''; ?>">
    <label for="name">举报人</label>
    <input name="reporter" type="text" id="reporter" placeholder="用户ID"
           value="<?php echo $reporter ? $reporter : ''; ?>">
    &nbsp;
    &nbsp;<label for="name">举报时间:</label>
    <input type="text" id="report_start" value="<?php echo $report_start; ?>" placeholder="开始时间" name="report_start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="report_end" value="<?php echo $report_end; ?>" placeholder="结束时间" name="report_end"
             data-date-format="yyyy-mm-dd"/>
    &nbsp;<label for="name">处理时间:</label>
    <input type="text" id="check_start" value="<?php echo $check_start; ?>" placeholder="开始时间" name="check_start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="check_end" value="<?php echo $check_end; ?>" placeholder="结束时间" name="check_end"
             data-date-format="yyyy-mm-dd"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
</form>
<div class="tabs">
    <a href="<?php echo $this->uri->setUrl(['type' => 0], ['p']); ?>"
       class="tab <?php echo $type == 0 ? 'active' : ''; ?>">全部</a>
    <a href="<?php echo $this->uri->setUrl(['type' => 1], ['p']); ?>"
       class="tab <?php echo $type == 1 ? 'active' : ''; ?>">已处理</a>
    <a href="<?php echo $this->uri->setUrl(['type' => 2], ['p']); ?>"
       class="tab <?php echo $type == 2 ? 'active' : ''; ?>">待处理</a>
</div>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:36px'>ID</th>
        <th style='width:60px'>批量</th>
        <th style='width:150px'>举报人</th>
        <th style='width:150px'>举报时间</th>
        <th style='width:150px'>被举报群</th>
        <th style='width:150px'>被举报人</th>
        <th style='width:150px'>举报原因</th>
        <th style='max-width:500px'>图片</th>
        <!-- <th style='width:250px'>证据</th>-->
        <th style='width:200px'>处理结果</th>
        <th style='width:100px'>处理人</th>
        <th style='width:150px'>处理时间</th>
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
            <td class="center"><?php if ($item['status'] == 0) { ?><label>
                    <input type="checkbox" class="chk ace" data-id="<?php echo $item['id']; ?>"/>
                    <span class="lbl"></span>
                    </label><?php } ?>
            </td>
            <td>
                <a href="javascript:;"
                   href="javascript:;"
                   data-title="用户详情"
                   class="newTarget"
                   data-id="user_<?php echo $item['reporter']; ?>"
                   data-href="/panel/users/detail/?user_id=<?php echo $item['reporter']; ?>"><?php echo $item['reporter'] . '【' . $users[$item['reporter']]['username'] . '】'; ?></a>
            </td>
            <td>
                <?php echo date('Y-m-d H:i', $item['created']); ?>
            </td>

            <td>
                <a target="_blank"
                   href="javascript:;"
                   data-title="群聊详情"
                   class="newTarget"
                   data-id="group_<?php echo $item['gid']; ?>"
                   data-href="/panel/group/detail/<?php echo $item['gid']; ?>"><?php echo $item['gid'] . '【' . ($groups[$item['gid']]['name'] ? $groups[$item['gid']]['name'] : $groups[$item['gid']]['default_name']) . '】'; ?></a>
            </td>
            <td>
                <?php if ($item['user_id'] > 0) { ?>
                    <a target="_blank"
                       href="javascript:;"
                       data-title="用户详情"
                       class="newTarget"
                       data-id="user_<?php echo $item['gid']; ?>"
                       data-href="/panel/users/detail/?user_id=<?php echo $item['user_id']; ?>"><?php echo $item['user_id'] . '【' . $users[$item['user_id']]['username'] . '】'; ?></a>
                <?php } else { ?>
                    无
                <?php } ?>
            </td>

            <td>
                <?php echo $item['reason_content']; ?>
            </td>
            <td>
                <?php if ($item['imgs'] != '') {
                    $images = explode(',', $item['imgs']);
                    ?>
                    <ul class="ace-thumbnails" data-id="<?php echo $item['id']; ?>">
                        <?php foreach ($images as $img) { ?>
                            <li style="width: 80px;height: 80px;">
                                <a href="<?php echo $img; ?>" data-rel="<?php echo $item['id']; ?>">
                                    <img alt="100x100" style="width: 80px; height: 80px"
                                         src="<?php echo $img; ?>"/>
                                    <div class="text">
                                        <div class="inner">点击查看大图</div>
                                    </div>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

            </td>
            <!--  <td class="center">
                <?php /*if ($item['images']) {

                    $images = explode(',', $item['images']);
                    foreach ($images as $i) {
                        */ ?>
                        <img src="<?php /*echo $i; */ ?>"/>
                    <?php /*}
                } else { */ ?>
                    无
                <?php /*} */ ?>

            </td>-->
            <td>

                <?php if ($item['status'] == 0) {
                    echo "<label class='badge badge-primary'><i class='fa fa-circle-o-notch fa-spin'></i> 待处理</label>";
                } else if ($item['status'] == 1) {
                    echo "<label class='badge badge-success'>举报属实</label><br/>【" . $admins[$item['check_user']]['name'] . ' ' . date('Y-m-d H:i', $item['check_time']) . "】";
                } else if ($item['status'] == 2) {
                    echo "<label class='badge badge-gray'><i class='fa fa-trash'></i> 举报不属实【" . $item['check_reason'] . "】</label>";
                }; ?>
            </td>
            <td>
                <?php echo $item['check_user'] ? $admins[$item['check_user']]['name'] : ''; ?>
            </td>
            <td>
                <?php echo $item['check_user'] ? date('Y-m-d H:i', $item['check_time']) : ''; ?>
            </td>
            <td>
                <!-- <a href="/panel/discuss/detail/<?php /*echo $item['id']; */ ?>" class="btn btn-minier btn-primary">查看详情</a>-->
                <?php if ($item['status'] == 0) { ?>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>" data-type="group"
                       class="btn btn-success btn-sm   checkBtn">举报通过</a>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>" data-type="group"
                       class="btn btn-danger btn-sm  failBtn">不通过</a>
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
        <th class="name">操作</th>
        <td colspan="17">
                <span>
                    [ <a href="javascript:;" class="selectAll">全选</a> ]
                    [ <a href="javascript:;" class="selectNone">全不选</a> ]
                    [ <a href="javascript:;" class="selectInvert">反选</a> ]
                    <a class="btn-light checkAllSelected" href="javascript:;" data-type="discuss">批量审核通过</a>
                </span>
        </td>
    </tr>
    <tr class="showpage">
        <th class="name">分页</th>
        <td colspan="17">
            <?php \Util\Pagination::instance($this->view)->display($this->view); ?>
        </td>
    </tr>
</table>

<script type="text/javascript" src="/srv/static/panel/js/tools/datetimepicker/bootstrap-datetimepicker.min.js"></script>

<div class="modal fade" id="checkModal">
    <div class="modal-dialog" style="width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">审核不通过</h4>
            </div>
            <div class="modal-body" style="overflow:hidden;">
                <div>
                    <!-- <label for="form-field-11">审核失败原因</label>-->

                    <textarea id="reason" placeholder="请填写原因" class=" form-control"
                              style="overflow: hidden; word-wrap: break-word; resize: horizontal; height: 100px;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" value="0" id="apply_id"/>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-type="group" id="sureBtn">确定</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>
    seajs.use('app/panel/report/report', function (api) {
        api.checkUser('.checkBtn');
        api.failCheck();
    });
    var receive_start_val;
    $('#report_start').datetimepicker({
        language: 'zh-CN',
        weekStart: 1,
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        forceParse: 0,
        minView: 'month',
        endDate: new Date()
    }).on('changeDate', function (ev) {
        receive_start_val = ev.date.valueOf();
    });
    $('#check_start').datetimepicker({
        language: 'zh-CN',
        weekStart: 1,
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        forceParse: 0,
        minView: 'month',
        endDate: new Date()
    }).on('changeDate', function (ev) {
        receive_start_val = ev.date.valueOf();
    });
    $('#report_end').datetimepicker({
        language: 'zh-CN',
        weekStart: 1,
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        forceParse: 0,
        minView: 'month',
        endDate: new Date()
    }).on('changeDate', function (ev) {
        receive_start_val = ev.date.valueOf();
    });
    $('#check_end').datetimepicker({
        language: 'zh-CN',
        weekStart: 1,
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        forceParse: 0,
        minView: 'month',
        endDate: new Date()
    }).on('changeDate', function (ev) {
        receive_start_val = ev.date.valueOf();
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
        $("#cboxLoadingGraphic").append("<i class='fa fa-spinner orange'></i>");//let's add a custom loading icon
    })
</script>
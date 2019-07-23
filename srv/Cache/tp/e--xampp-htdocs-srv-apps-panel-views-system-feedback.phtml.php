<?php
$type_class = [
    '1' => 'badge-pink',
    '2' => 'badge-yellow',
    '3' => 'badge-primary',
    '4' => 'badge-success'
];
$status_class = [
    '0' => ['label label-grey arrowed arrowed-right  label-lg', 'fa fa-remove'],
    '1' => ['label label-info arrowed arrowed-right arrowed-right label-lg', 'fa fa-circle-o-notch fa-spin'],
    '2' => ['label label-success arrowed arrowed-right label-lg', 'fa fa-check']
]
?>
<form action="" method="get" style="border-bottom: 1px solid #DEEFFB;padding: 8px;margin: 0 0 8px;">
    <label for="name">关键字</label>
    <input name="key" type="text" id="key" placeholder="用户昵称/手机/用户ID" value="<?php echo $key; ?>">
    &nbsp;
    <label for="name">状态</label>
    <select name="status">
        <option value="-1" <?php echo $status == -1 ? 'selected' : ''; ?>>全部</option>
        <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?>>待审核</option>
        <option value="2" <?php echo $status == 2 ? 'selected' : ''; ?>>审核通过</option>
        <option value="0" <?php echo $status == 0 ? 'selected' : ''; ?>>审核失败</option>
    </select>
    &nbsp;<label for="name">时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="开始时间" name="start"
           data-date-format="yyyy-mm-dd"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="结束时间" name="end"
             data-date-format="yyyy-mm-dd"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
</form>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:50px'>ID</th>
        <th style='width:50px'>批量</th>
        <th style='width:150px'>提交时间</th>
        <th style='width:100px'>用户ID</th>
        <!--    <th style='width:300px'>审核</th>-->
        <th style='width:200px'>提交内容</th>
        <th style='max-width:500px'>图片</th>
        <th style='width:100px'>联系方式</th>
        <th style='width:90px'>状态</th>
        <th style='width:100px'>审核专员</th>
        <th style='width:150px'>审核意见</th>
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
            <th><?php echo $item['id']; ?></th>
            <td><label>
                    <input type="checkbox" class="chk ace" data-id="<?php echo $item['id']; ?>"/>
                    <span class="lbl"></span>
                </label></td>
            <td><?php echo date('Y-m-d H:i', $item['created']); ?></td>
            <td>
                <a class="newTarget" data-title="用户详情" href="javascript:;"
                   data-id="user_detail_<?php echo $item['user_id']; ?>"
                   data-href="/panel/users/detail/?user_id=<?php echo $item['user_id']; ?>"><?php echo $item['user_id']; ?></a>
            </td>
            <td>
                <?php echo $item['content']; ?>
            </td>
            <td class="">
                <?php if ($item['images'] != '') {
                    $images = explode(',', $item['images']);
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
                <?php } ?>

            </td>
            <td>
                <?php echo $item['contact']; ?>
            </td>

            <td>
                <label class="<?php echo $status_class[$item['check_status']][0]; ?>"><i
                        class="<?php echo $status_class[$item['check_status']][1]; ?>"></i> <?php echo $item['check_status'] == 0 ? '审核失败' : ($item['check_status'] == 1 ? '待审核' : "审核通过"); ?>
                </label>
            </td>
            <td>
                <?php echo $item['check_status'] != 1 ? $admins[$item['check_user']] : ""; ?>
            </td>
            <td>
                <?php echo $item['check_reason']; ?>
            </td>

            <td>
                <?php if ($item['check_status'] == 1) { ?>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-success btn-sm   checkBtn">审核通过</a>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-danger btn-sm  failBtn">不通过</a>
                <?php } else if ($item['check_status'] == 3) { ?>
                <?php } ?>
            </td>
        </tr>
        <?php
    }
    } else {
        ?>
        <tr>
            <td colspan="15">
                <p style="margin: 20px;color:#f00;"> 暂无内容 </p>
            </td>
        </tr>
    <?php } ?>
    </tbody>
    <tr class="showpage">
        <th class="name">操作</th>
        <td colspan="15">
                <span>
                    [ <a href="javascript:;" class="selectAll">全选</a> ]
                    [ <a href="javascript:;" class="selectNone">全不选</a> ]
                    [ <a href="javascript:;" class="selectInvert">反选</a> ]
                    <a class="btn-light checkAllSelected" href="javascript:;">批量审核通过</a>
                </span>
        </td>
    </tr>
    <tr class="showpage">
        <th class="name">分页</th>
        <td colspan="15">
            <?php \Util\Pagination::instance($this->view)->display($this->view); ?>
        </td>
    </tr>
</table>

<link rel="stylesheet" type="text/css" href="/srv/static/panel/css/plugins/jquery/jquery.datetimepicker.css">
<script type="text/javascript" src="/srv/static/panel/js/jquery/jquery.datetimepicker.js"></script>

<script>
    seajs.use('app/users/user.feedback', function (api) {
        api.checkUser('.checkBtn');
        api.failCheck();
    });
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
                <button type="button" class="btn btn-primary" id="sureBtn">确定</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
$type_class = [
    '1' => 'badge-pink',
    '2' => 'badge-yellow',
    '3' => 'badge-primary',
    '4' => 'badge-success'
];
$status_class = [
    '3' => ['label label-grey arrowed arrowed-right  label-lg', 'fa fa-remove'],
    '2' => ['label label-info arrowed arrowed-right arrowed-right label-lg', 'fa fa-circle-o-notch fa-spin'],
    '1' => ['label label-success arrowed arrowed-right label-lg', 'fa fa-check']
]
?>

<form action="" method="get" style="border-bottom: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px;">
    <label for="name">关键字</label>
    <input name="key" type="text" id="key" placeholder="用户昵称/真实姓名手机/用户ID" value="<?php echo $key; ?>">
    &nbsp;
    <label for="name">身份证号</label>
    <input name="id_card" type="text" id="id_card" placeholder="身份证号" value="<?php echo $id_card; ?>">
    &nbsp;
    <label for="name">状态</label>
    <select name="status">
        <option value="0" <?php echo $status == 0 ? 'selected' : ''; ?>>全部</option>
        <option value="2" <?php echo $status == 2 ? 'selected' : ''; ?>>待审核</option>
        <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?>>审核通过</option>
        <option value="3" <?php echo $status == 3 ? 'selected' : ''; ?>>审核失败</option>
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

        <th style='width:80px'>用户ID</th>
        <!--    <th style='width:300px'>审核</th>-->
        <th style='width:90px'>状态</th>

        <!--        <th style='width:90px'>申请类型</th>-->
        <th style='width:90px'>真实姓名</th>
        <th style='width:90px'>身份证</th>
        <th style='width:120px'>身份证正面</th>
        <th style='width:120px'>身份证反面</th>
        <th style='width:120px'>手持身份证</th>
        <th style='width:150px'>审核时间</th>
        <th style='width:100px'>审核人</th>
        <th style='width:200px'>失败原因</th>
        <!--        <th style='width:90px'>手机</th>-->
        <!--        <th style='width:90px'>个人网址</th>-->
        <!--        <th style='width:200px'>人物介绍</th>-->
        <!--        <th style='width:90px'>行业</th>-->
        <!--        <th style='width:150px'>公司/运动队/机构</th>-->
        <!--        <th style='width:100px'>职位/职业</th>-->
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
            <td><label>
                    <input type="checkbox" class="chk ace" data-id="<?php echo $item['id']; ?>"/>
                    <span class="lbl"></span>
                </label></td>
            <td><?php echo date('Y-m-d H:i', $item['created']); ?></td>

            <td>
                <?php echo $item['user_id']; ?>
            </td>
            <td>
                <label class="<?php echo $status_class[$item['status']][0]; ?>"><i
                        class="<?php echo $status_class[$item['status']][1]; ?>"></i> <?php echo \Services\User\AuthManager::$status[$item['status']]; ?>
                </label>

            </td>

            <!--            <td>-->
            <!--                <label-->
            <!--                    class="badge --><?php //echo $type_class[$item['type']] ?><!--">-->
            <?php //echo \Services\User\AuthManager::$auth_type_name[$item['type']]; ?><!--</label>-->
            <!--            </td>-->
            <td>
                <?php echo $item['true_name']; ?>
            </td>
            <td>
                <?php echo $item['id_card']; ?>
            </td>
            <td>

                <a href="<?php echo $item['card_front']; ?>" data-lightbox="roadtrip<?php echo $item['id']; ?>"><img
                        src="<?php echo $item['card_front']; ?>?x-oss-process=image/resize,m_fill,h_160,w_160"
                        style="width: 40px; height: 40px;"/></a>

            </td>
            <td>

                <a href="<?php echo $item['card_back']; ?>" data-lightbox="roadtrip<?php echo $item['id']; ?>"><img
                        src="<?php echo $item['card_back']; ?>?x-oss-process=image/resize,m_fill,h_160,w_160"
                        style="width: 40px; height: 40px;"/></a>

            </td>
            <td>

                <a href="<?php echo $item['card_hand']; ?>" data-lightbox="roadtrip<?php echo $item['id']; ?>"><img
                        src="<?php echo $item['card_hand']; ?>?x-oss-process=image/resize,m_fill,h_160,w_160"
                        style="width: 40px; height: 40px;"/></a>

            </td>
            <!--            <td>-->
            <!--                --><?php //echo $item['phone']; ?>
            <!--            </td>-->
            <!--            <td>-->
            <!--                --><?php //echo $item['website'] ? "<a class='btn btn-minier  btn-purple' target='_black' href=" . $item['website'] . "><i class='fa fa-link'></i>点击查看</a>" : '没有填写'; ?>
            <!--            </td>-->
            <!--            <td>-->
            <!--                <span title="--><?php //echo $item['introduce'] ?><!--">-->
            <?php //echo mb_strlen($item['introduce'])>=50 ? mb_substr($item['introduce'],0,50).'...':$item['introduce']; ?><!--</span>-->
            <!--            </td>-->
            <!--            <td>-->
            <!--                --><?php //echo $item['industry']; ?>
            <!--            </td>-->
            <!--            <td>-->
            <!--                --><?php //echo $item['company']; ?>
            <!--            </td>-->
            <!--            <td>-->
            <!--                --><?php //echo $item['job']; ?>
            <!--            </td>-->
            <td><?php echo $item['modify'] ? date('Y-m-d H:i', $item['modify']) : ''; ?></td>
            <td>
                <?php echo $item['check_user'] ? $admins[$item['check_user']] : '' ?>
            </td>
            <td>
                <?php if ($item['status'] == \Services\User\AuthManager::AUTH_STATUS_FAILED) { ?>
                    <?php echo $item['check_reason'] ?>
                <?php } ?>

            </td>
            <td>
                <!--                <a data-title="认证详情" data-href="/panel/auth/detail/-->
                <?php //echo $item['id']; ?><!--"-->
                <!--                   class="btn btn-sm btn-primary newTarget">详情</a>-->
                <?php if ($item['status'] == 1) { ?>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-danger btn-sm  failBtn">撤回</a>
                <?php } else if ($item['status'] == 2) { ?>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-success btn-sm checkBtn">通过</a>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-danger btn-sm  failBtn">不通过</a>
                <?php } else if ($item['status'] == 3) { ?>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-success btn-sm checkBtn">重审通过</a>
                <?php } ?>
            </td>
        </tr>
        <?php
    }
    }
    else {
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
    seajs.use('app/users/user.auth.js?v=1.0', function (api) {
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
<link rel="stylesheet" href="/srv/static/panel/css/lightbox/lightbox.css"/>

<script src="/srv/static/panel/js/jquery/lightbox/lightbox.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        lightbox.option({
            albumLabel: '%1/%2',
            'resizeDuration': 200,
            "fadeDuration": 0,
            "imageFadeDuration": 0
        });
    })
    $('[data-rel=tooltip]').tooltip();
</script>
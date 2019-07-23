<style>
    .total {
        border: 1px solid #e4e4e4;
        padding: 10px;
        border-radius: 3px;
    }
</style>
<form action="" method="get" style="border-bottom: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px;">
    <label for="name">用户id</label>
    <input name="user_id" type="text" id="user_id" placeholder="店主id" value="<?php echo $user_id ? $user_id : ''; ?>">
    &nbsp;
    <label for="name">邀请码</label>
    <input name="code" type="text" id="code" placeholder="邀请码" value="<?php echo $code ? $code : ''; ?>"> &nbsp;
    <select name="status">
        <option value="-1" <?php echo $status == -1 ? 'selected' : ''; ?>>全部</option>
        <option value="0" <?php echo $status == 0 ? 'selected' : ''; ?>>待支款</option>
        <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?>>已付款</option>
        <option value="2" <?php echo $status == 2 ? 'selected' : ''; ?>>支付超时</option>
    </select> &nbsp;
    <label for="name">提交订单时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="开始时间" name="start"
           data-date-format="yyyy-mm-dd hh:ii"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="结束时间" name="end"
             data-date-format="yyyy-mm-dd hh:ii"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">

    <section class="right">
        <span class="total">已付款总人数:<b class="green"><?php echo $paid_user_count ?></b></span>
        <span class="total">已付款总金额:<b class="green">￥<?php echo sprintf("%.2f", $total_money / 100) ?></b></span>

    </section>
</form>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:80px'>用户id</th>
        <th style='width:150px'>用户名</th>
        <th>邀请码</th>
        <th style='width:180px'>邀请码所属用户id</th>
        <th style='width:80px'>交易号</th>
        <th style='width:120px'>需支付金额</th>
        <th style='width:100px'>优惠金额</th>
        <th style='width:200px'>创建时间</th>
        <th style='width:200px'>支付时间</th>
        <th style='width:200px'>订单状态</th>
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
            <th class='name'><?php echo $item['user_id']; ?></th>
            <td><?php echo $users[$item['user_id']]['username'] ?></td>
            <td>
                <?php echo $item['code']; ?>
            </td>

            <td>
                <?php if ($item['code_owner']) {
                    ?>
                    <a href="javascript:;" class="newTarget" data-title="用户详情"
                       data-href="/panel/users/detail/?user_id=<?php echo $item['code_owner'] ?>"><?php echo $item['code_owner'] . "【" . $users[$item['code_owner']]['username'] . "】"; ?></a>
                <?php } ?>
            </td>

            <td>
                <?php echo $item['trade_no'] ?>
            </td>

            <td>
                ￥<?php echo sprintf("%.2f", $item['money'] / 100) ?>
            </td>
            <td>
                ￥<?php echo sprintf("%.2f", $item['favorable_money'] / 100) ?>
            </td>
            <td><?php echo date('Y-m-d H:i', $item['created']); ?></td>
            <td><?php echo $item['paid_time'] ? date('Y-m-d H:i', $item['paid_time']) : ''; ?></td>
            <td><?php echo $item['status'] == 0 ? "<span class='badge badge-gray'><i class='fa fa-circle-o-notch fa-spin'></i> 待付款</span>" :
                    ($item['status'] == 1 ? "<span class='badge badge-success'>已付款</span>" : "<span class='badge badge-warning'>已过期</span>"); ?></td>
            <td>

            </td>
        </tr>
        <?php
    }
    } else {
        ?>
        <tr>
            <td colspan="9">
                <p style="margin: 20px;color:#f00;"> 暂无内容 </p>
            </td>
        </tr>
    <?php } ?>
    </tbody>
    <!--  <tr class="showpage">
          <th class="name">操作</th>
          <td colspan="13">
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
        <td colspan="13">
            <?php \Util\Pagination::instance($this->view)->display($this->view); ?>
        </td>
    </tr>
</table>
<div class="modal fade" id="addressModal">
    <div class="modal-dialog" style="width: 1000px;">
        <div class="modal-content">
            <!--   <div class="modal-header">
                   <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                   <h4 class="modal-title"><i class="fa fa-music"></i> 申请位置</h4>
               </div>-->
            <div class="modal-body" style="overflow:hidden;position: relative">
                <div id="mapWrap" style="width:100%;height: 500px;border: 1px solid #eee;"></div>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="checkModal">
    <div class="modal-dialog" style="width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">封杀原因</h4>
            </div>
            <div class="modal-body" style="overflow:hidden;">
                <div>
                    <div class="form-group">
                        <label for="form-field-11">封杀原因:</label>
                        <select name="reason_list" class="reason_list">
                            <option value="0">无</option>
                            <option value="1">信誉炒作</option>
                            <option value="2">虚假代理</option>
                            <option value="3">卖高仿品牌货或品牌假货</option>
                            <option value="4">重复开店</option>
                            <option value="5">商品名有禁用的词或字</option>
                            <option value="6">出售禁售品</option>
                            <option value="7">盗用他店图片被投诉</option>
                            <option value="8">商品和宝贝描述不符</option>

                        </select>
                    </div>
                    <textarea id="reason" placeholder="请填写具体原因" class=" form-control"
                              style="overflow: hidden; word-wrap: break-word; resize: horizontal; height: 100px;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" value="0" id="shop_id"/>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-type="user" id="sureBtn">确定</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<script src="/srv/static/panel/js/tools/Url.js"></script>
<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=MWkGH8HcEfA5nbdkiYXp67VmxbgL4iGe"></script>
<link rel="stylesheet" type="text/css" href="/srv/static/panel/css/plugins/jquery/jquery.datetimepicker.css">
<script type="text/javascript" src="/srv/static/panel/js/jquery/jquery.datetimepicker.js"></script>
<script>
    /*   seajs.use('app/users/users.bind', function (e) {
     e.deleteUsers('.delBtn');
     e.recoveryUsers('.recoveryBtn');
     e.forbidUsers('.forbidBtn');
     e.unForbidUsers('.unForbidBtn');

     });*/
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


<script type="text/javascript">
    $(function () {
        seajs.use('app/panel/shop/shop', function (api) {
            api.del();
            api.lngLat();
            $(".reason_list").on('change', function () {
                var r_value = $(this).val();
                if (r_value != '0') {
                    $("#reason").val($(".reason_list option[value='" + r_value + "']").html());
                }
            })
        });
    })

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

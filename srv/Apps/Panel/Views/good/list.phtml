<style>
    .anchorBL {
        display: none
    }
</style>
<form action="" method="get" style="border-bottom: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px;">
    <label for="name">用户id</label>
    <input name="user_id" type="text" id="user_id" placeholder="店主id" value="<?php echo $user_id ? $user_id : ''; ?>">
    &nbsp;
    <label for="name">店铺id</label>
    <input name="shop_id" type="text" id="shop_id" placeholder="店铺id" value="<?php echo $shop_id ? $shop_id : '';; ?>">
    &nbsp;
    <label for="name">商品名称</label>
    <input name="key" type="text" id="key" placeholder="商品名称" value="<?php echo $key; ?>">
    &nbsp;
    <label for="name">添加时间:</label>
    <input type="hidden" name="status" value="<?php echo $status ?>">
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="开始时间" name="start"
           data-date-format="yyyy-mm-dd hh:ii"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="结束时间" name="end"
             data-date-format="yyyy-mm-dd hh:ii"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
</form>
<div class="tabs">
    <a href="<?php echo $this->uri->setUrl(['status' => -1], ['p']); ?>"
       class="tab <?php echo $status == -1 ? 'active' : ''; ?>">全部</a>
    <a href="<?php echo $this->uri->setUrl(['status' => 1], ['p']); ?>"
       class="tab <?php echo $status == 1 ? 'active' : ''; ?>">正常</a>
    <a href="<?php echo $this->uri->setUrl(['status' => 0], ['p']); ?>"
       class="tab <?php echo $status == 0 ? 'active' : ''; ?>">系统删除</a>
</div>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:80px'>店铺id</th>
        <th>商品名称</th>
        <th>所属店铺</th>
        <th style='width:180px'>店主</th>
        <!--  <th style='width:50px'>云信群ID</th>-->
        <!-- <th style='width:36px'>批量</th>-->

        <th style='width:120px'>商品价格</th>
        <th style='width:200px'>商品简介</th>
        <th style='width:200px'>商品外部链接</th>
        <!--    <th style='width:300px'>审核</th>-->
        <th style='width:200px'>商品图片</th>
        <th style='width:90px'>状态</th>
        <th style='width:150px'>创建时间</th>
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
                <?php echo $item['name'] ? $item['name'] : $item['default_name']; ?>
            </td>
            <td>
                【<?php echo $item['shop_id'] ?>】<?php echo $shops[$item['shop_id']]['name']; ?>
            </td>
            <!--    <th class='name'><?php /*echo $item['yx_gid']; */ ?></th>-->
            <!--     <td class="center"><input type="checkbox" class="chk" data-id="<?php /*echo $item['id']; */ ?>"/></td>-->
            <td><a href="javascript:;" class="newTarget" data-title="用户详情"
                   data-href="/panel/users/detail/?user_id=<?php echo $item['user_id'] ?>"><?php echo $item['user_id'] . "【" . $users[$item['user_id']]['username'] . "】"; ?></a>
            </td>

            <td>￥<?php echo sprintf('%.2f', $item['price'] / 100) ?>/<?php echo $item['unit'] ?></td>
            <td>
                <?php echo $item['brief'] ?>
            </td>

            <td>
                <?php if ($item['url']) { ?>
                    <a class="btn btn-xs btn-purple newTarget" href="javascript:;"
                       data-href="<?php echo $item['url'] ?>"
                       data-title="<?php echo $item['name'] ?>【<?php echo $item['url'] ?>】"><i class="fa fa-link"></i>点击查看</a>
                <?php } ?>
            </td>
            <td>
                <?php if ($item['images'] != '') {
                    $images = explode(',', $item['images']);
                    ?>
                    <ul class="ace-thumbnails" data-id="<?php echo $item['id']; ?>">
                        <?php foreach ($images as $k=>$img) { ?>
                            <li style="width: 50px;height: 50px;<?php echo $k > 0 ? 'display:none' : '' ?>">
                                <a href="<?php echo $img; ?>" data-rel="<?php echo $item['id']; ?>">
                                    <img alt="50x50" style="width: 50px; height: 50px"
                                         src="<?php echo $img; ?>?x-oss-process=image/resize,m_fill,h_160,w_160"/>
                                    <div class="text">
                                        <div class="inner">点击查看大图</div>
                                    </div>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                    <label class="badge badge-purple"><?php echo count($images); ?></label>
                <?php } ?>
            </td>

            <td class="center">
                <?php echo \Services\Shop\GoodManager::$status[$item['status']] ?>
            </td>
            <td><?php echo date('Y-m-d H:i', $item['created']); ?></td>

            <td>
                <?php if ($item['status'] == 1) { ?>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-danger btn-sm delBtn"><i class="fa fa-remove"></i> 移除</a>
                <?php } else if ($item['status'] == 0) { ?>
                    <a href="JavaScript:;" data-id="<?php echo $item['id']; ?>"
                       class="btn btn-success btn-sm recoveryBtn">恢复正常</a>
                <?php } ?>
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
                <h4 class="modal-title">移除商品</h4>
            </div>
            <div class="modal-body" style="overflow:hidden;">
                <div>
                    <div class="form-group">
                        <label for="form-field-11">审核失败原因:</label>
                        <select name="reason_list" class="reason_list">
                            <option value="0">无</option>
                            <option value="1">涉嫌假货</option>
                            <option value="2">质量不合格</option>
                            <option value="3">涉嫌盗版</option>
                            <option value="4">价格不合理</option>
                            <option value="5">涉嫌违禁品</option>

                        </select>
                    </div>
                    <textarea id="reason" placeholder="请填写具体原因" class=" form-control"
                              style="overflow: hidden; word-wrap: break-word; resize: horizontal; height: 100px;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" value="0" id="group_id"/>
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
        seajs.use('app/panel/shop/good', function (api) {
            api.del();
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

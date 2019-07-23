<style>
    .anchorBL {
        display: none
    }
</style>
<form action="" method="get" style="border-bottom: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px;">
    <label for="name">用户id</label>
    <input name="user_id" type="text" id="user_id" placeholder="区主id" value="<?php echo $user_id ? $user_id : ''; ?>">
    &nbsp;
    <label for="name">社区名称</label>
    <input name="key" type="text" id="key" placeholder="社区名称" value="<?php echo $key; ?>">
    &nbsp;
    <label for="name">开通时间:</label>
    <input type="text" id="start" value="<?php echo $start; ?>" placeholder="开始时间" name="start"
           data-date-format="yyyy-mm-dd hh:ii"/>
    - <input type="text" id="end" value="<?php echo $end; ?>" placeholder="结束时间" name="end"
             data-date-format="yyyy-mm-dd hh:ii"/>
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
</form>
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:100px'>社区ID</th>
        <th style='width:100px'>社区头像</th>
        <th style='width:200px'>社区名称</th>
        <th style='width:300px'>社区简介</th>

        <th style='width:80px'>区主UID</th>
        <th style='width:150px'>区主昵称</th>
        <th style='width:120px'>社区关注人数</th>
        <th style='width:80px'>社区动态</th>
        <th style='width:100px'>社群数</th>
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
            <!--            <th class='name'>--><?php //echo $item['id']; ?><!--</th>-->
            <td>
                <?php echo $item['id']; ?>
            </td>
            <td>
                <a href="<?php echo $item['cover']; ?>" data-lightbox="roadtrip"><img
                        src="<?php echo $item['cover']; ?>?x-oss-process=image/resize,m_fill,h_160,w_160"
                        style="width: 40px; height: 40px;"/></a>
            </td>
            <td>
                <?php echo $item['name']; ?>
            </td>
            <td>
                <?php echo $item['brief']; ?>
            </td>
            <td><a href="javascript:;" class="newTarget" data-title="用户详情"
                   data-href="/panel/users/detail/?user_id=<?php echo $item['user_id'] ?>"><?php echo $item['user_id']; ?></a>
            </td>
            <td>
                <?php echo $users[$item['user_id']]['username']; ?>
            </td>
            <td><?php echo $item['attention_cnt'] ?></td>
            <td><?php echo $item['discuss_cnt'] ?></td>
            <td><a href="javascript:;" class="newTarget" data-title="社群列表【<?php echo $item['id'] ?>】"
                   data-href="/panel/community/groupList?comm_id=<?php echo $item['id'] ?>"><?php echo $item['group_cnt'] ?></a>
            </td>

            <td class="center">
                <?php if ($item['status'] == 0) {
                    echo "<label class='badge badge-danger'>已被系统封杀</label>";
                } else if ($item['status'] == 1) {
                    echo "<label class='badge badge-success'>正常</label>";
                } ?>
            </td>
            <td><?php echo date('Y-m-d H:i', $item['created']); ?></td>

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

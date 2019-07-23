<link rel="stylesheet" type="text/css" href="/srv/static/panel/js/tools/datetimepicker/bootstrap-datetimepicker.min.css">
<script src="/srv/static/panel/js/tools/Url.js"></script>
<label for="kw">搜索：</label>
<input type="text" id="kw" placeholder="请输入关键词搜索" value="<?= $kw ?>">
<a href="javascript:;" class="btn btn-xs btn-primary" style="padding:3px 10px 3px;margin-left:10px" id="search">搜索</a>
<form action="javascript:;" method="get" class="right">
    <a class="btn btn-primary btn-sm " href="/package/adsAdd"><i class="fa fa-plus"></i> 添加广告</a>
</form>
<div class="clearfix"></div>
<hr class="hr-10">
<table id="article-list" class=' list'>
    <thead>
    <tr class="head">
        <th style='width:36px'>ID</th>
        <!--  <th style='width:56px'>批量</th>-->
        <th style='width:550px'>广告文本内容</th>
        <th style='width:500px'> 广告图片</th>
        <th style='width:80px'> 投放次数</th>
        <th style='width:200px'> 添加时间</th>
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
            <!--   <td class="center"><input type="checkbox" class="chk" data-id="<?php /*echo $item['id']; */ ?>"/>
            </td>-->
            <td><?php echo $item['content']; ?></td>
            <td style="width: 200px;word-break: break-all;">
                <?php if ($item['media'] != '') {
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
                <?php } ?>

            </td>


            <!--   <td>
                <?php /*echo \Services\Site\AdvertiseManager::$_type_name[$item['content_type']] */ ?>
            </td>-->
            <td>
                <?php echo $item['send_count'] ?>
            </td>
            <td>
                <?php echo date('Y年m月d日 H时i分', $item['created']) ?>
            </td>
            <td>
                <a href="javascript:;" data-href="/panel/package/adsAdd?id=<?php echo $item['id'] ?>" data-title="广告详情"
                   class="btn btn-success up_btn btn-sm newTarget"><i class="fa fa-edit"></i>编辑</a>
                <a href="javascript:;" class="btn del_btn btn-sm btnRemove" data-id="<?php echo $item['id'] ?>"><i
                        class="fa fa-trash"></i> 删除</a>
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
    //搜索
    $('#search').on('click',function () {
        var kw = $('#kw').val();
        var url = new Url();
        url.rmArgs(['p']);
        url.setArgs({'kw':kw});
        location.href = url.getUrl();
    });
</script>
<link rel="stylesheet" href="/srv/static/panel/css/lightbox/lightbox.css"/>

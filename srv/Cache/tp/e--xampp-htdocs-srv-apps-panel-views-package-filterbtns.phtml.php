<link rel="stylesheet" href="/static/ace/css/jquery-ui-1.10.3.custom.min.css">
<script src="/static/ace/js/jquery-ui-1.10.3.custom.min.js"></script>
<style>
    #shop_top_category_chosen > ul > li > input{
        height:34px
    }
    #shop_top_category_chosen > ul{
        -webkit-border-radius: 4px;
        -moz-border-radius: 4px;
        border-radius: 3px;
        border:1px solid #ddd;
        background-image:none;
    }
    #shop_top_category_chosen > div
    {
        border:1px solid #ddd;
    }
    #shop_top_category_chosen > ul > li.search-choice{
        border-radius: 2px;
        background-color: #4aa4ce;
    }
    #items .draggable-placeholder{
        border:2px dotted #eee !important;
    }
    #items {
        margin-left:0
    }
    #items > li{
        border:1px solid #eee;padding: 0 10px 10px 10px;margin-bottom:10px;
        cursor:pointer;
    }
/*http://avatorimg.klgwl.com/13/13937_s_50x48.png*/
</style>
<?php
    $color = ['blue','green','orange','purple','red'];
?>
<div class="width-60" >
    <div class="widget-box">
        <div class="widget-header widget-header-flat">
            <h5>地图上筛选店铺按钮动态配置：</h5>

        </div>

        <div class="widget-body" style="padding:10px 50px 10px 50px">
            <div class="widget-main">
                    <div class="" style="margin-bottom:15px">
                        <div class="alert alert-info">
                            <button type="button" class="close" data-dismiss="alert">
                                <i class="icon-remove"></i>
                            </button>
                            1、将某个店铺分类（比如：吃）添加到某个按钮（比如：美食）下即表示：当客户端用户点击《美食》按钮时，只显示店铺分类为“吃”的店铺
                            <br>
                            2、当前页按钮出现的顺序即为客户端筛选按钮的顺序；拖拽按钮可调整按钮排序，<span class="red">排好序请点击《保存》按钮保存排序</span>
                            <br>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                <h3 class="row header smaller lighter blue" style="padding-bottom:15px">
											<span class="">
												<i class="fa fa-filter"></i>
												按钮列表
											</span>
                    <div class="right">
                        <a href="javascript:;" class="btn btn-sm btn-success" id="addBtn">添加按钮</a>
                        <a href="javascript:;" class="btn btn-sm btn-primary" id="saveBtn">保存</a>
                    </div>
                </h3>
                    <ul id="items" >
                        <?php foreach( $btns as $btn) :?>
                            <li style="" data-id="<?= $btn['id'] ?>" data-name="<?= $btn['name'] ?>" data-icon="<?= $btn['icon'] ?>" data-shop_cids="<?= $btn['shop_cids'] ?>">
                                <div class="item">
                                    <h4 class="header smaller lighter <?php echo $color[($btn['id'] -1) % 5]; ?>" style="padding-bottom:10px">
                                        <div class="btn-icon" style="float:left;background: url(<?php echo $btn['icon']?>) no-repeat;background-size: cover;width:30px;height:30px;margin:0 10px 5px 0"></div>
                                        <span><?= $btn['name'] ?></span>
                                        <div class="visible-md visible-lg hidden-sm hidden-xs action-buttons right">
                                            <a class="blue" href="#">
                                                <i class="fa fa-pencil  edit-btn" style="font-size:20px;"></i>
                                            </a>
                                            <a class="red" href="#">
                                                <i class="fa fa-times-circle-o  close-btn" style="font-size:20px;"></i>
                                            </a>
                                        </div>

                                    </h4>
                                    <div>
                                        店铺类别：
                                        <select multiple="" style="width:300px;height:34px" class="chosen-select tag-input-style" id="shop-top-category" data-placeholder="请选择店铺分类">
                                            <?php foreach( $shop_cids as $shop_cid) : ?>
                                                <option value="<?= $shop_cid['id'] ?>"><?= $shop_cid['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>



            </div>
        </div>
    </div>

</div>
<!--添加按钮-->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <input type="hidden" id="action-type">
    <input type="hidden" id="btn-id">
    <div class="modal-dialog" >
        <div class="modal-content" style="-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">添加按钮</h4>
            </div>
            <div class="modal-body form-horizontal">
                <div class="form-group">
                    <label class="col-sm-3 control-label no-padding-right" for="btn-name"> 按钮名称： </label>

                    <div class="col-sm-9">
                        <input type="text" id="btn-name" placeholder="请输入按钮名称" class="col-xs-10 col-sm-5">
                    </div>
                </div>
                <div class="space-4"></div>
                <div class="form-group">
                    <label class="col-sm-3 control-label no-padding-right" for="btn-icon"> 按钮icon： </label>

                    <div class="col-sm-9">
                        <a href="javascript:;" class="btn btn-xs btn-primary " id="select-icon">选择</a>
                        <img src="" alt="" style="width:30px;height:30px;display:" id="icon-preview">
                        <input type="hidden" id="btn-icon"  class="col-xs-10 col-sm-5">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" id="confirm-btn">确定</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script>
    $(".chosen-select").chosen();
    //select多选框数据初始化
    $('#items').children('li').each(function () {
        var shop_cids = $(this).attr('data-shop_cids').split(',');
        $(this).find('.chosen-select').val(shop_cids).trigger('chosen:updated');
    });
    $('#items').sortable({
            opacity:0.3,
            revert:false,
            forceHelperSize:true,
            placeholder: 'draggable-placeholder',
            forcePlaceholderSize:true,
            tolerance:'pointer',
            stop: function( event, ui ) {//just for Chrome!!!! so that dropdowns on items don't appear below other items after being moved
                $(ui.item).css('z-index', 'auto');
            }
        }
    );
    //显示关闭按钮
    /*$('#items').on('mouseover','li',function () {
        $(this).find('.close-btn').show();
    }).on('mouseout','li',function () {
        $(this).find('.close-btn').hide();
    });*/
    //点击关闭按钮
    $('#items').on('click','.close-btn',function () {
        $(this).closest('li').fadeOut('normal',function () {
            $(this).closest('li').remove()
        });
        ;
    });
    seajs.use(['app/panel/panel.base','app/panel/panel.storage'],function (api,storage) {
        //添加‘按钮’
        $('#addBtn').on('click',function () {
            $('#action-type').val('add');
            $('#btn-name').val('');
            $('#icon-preview').attr('src','');
            $('#btn-icon').val('');
            storage.getImg('#select-icon',function (res) {
                $('#btn-icon').val(res.url);
                $('#icon-preview').attr('src',res.url);
            })
            $('#myModal').modal('show');
        });
        //编辑‘按钮’
        $('.edit-btn').on('click',function () {
            var name = $(this).closest('li').data('name');
            var icon = $(this).closest('li').data('icon');
            $('#btn-id').val($(this).closest('li').data('id'));
            $('#action-type').val('edit');
            $('#btn-name').val(name);
            $('#icon-preview').attr('src',icon);
            $('#btn-icon').val(icon);
            storage.getImg('#select-icon',function (res) {
                $('#btn-icon').val(res.url);
                $('#icon-preview').attr('src',res.url);
            })
            $('#myModal').modal('show');

        });
        //modal 确认按钮
        $('#confirm-btn').on('click',function(){
            var name = $('#btn-name').val();
            var icon = $('#btn-icon').val();
            if( name === '')
            {
                tip.showTip('err','请输入按钮名称',2000);
                return;
            }
            if( icon === '')
            {

                tip.showTip('err','请选择按钮icon',2000);
                return;
            }
            if( $('#action-type').val() === 'add')
            {
                var $new_btn = $('#items').find('li:first').clone();
                $new_btn.find('.chosen-container').remove();
                var btn_ids = [];
                $('#items').children('li').each(function () {
                    btn_ids.push($(this).data('id'));
                });
                var max_btn_id = Math.max.apply(null, btn_ids);
                if( isNaN(max_btn_id) )
                {
                    tip.showTip('err','获取按钮id出错');
                    return;
                }
                $new_btn.attr('data-id',(max_btn_id + 1));
                $new_btn.attr('data-name',name);
                $new_btn.attr('data-icon',icon);
                $new_btn.find('.item > h4 > span').text(name);
                $new_btn.find('.item > h4 > div.btn-icon').css('background-image','url(' + icon + ')');
                $new_btn.appendTo($('#items'));
                $('#items').find('li:last').find('.chosen-select').chosen();
            }else if( $('#action-type').val() === 'edit')
            {
                var id = $('#btn-id').val();
                var $target = $('li[data-id="' + id + '"]');
                $target.attr('data-name',name);
                $target.attr('data-icon',icon);
                $target.find('.btn-icon').css('background-image','url(' + icon + ')');
                $target.find('.btn-icon').next('span').text(name);
            }

            $('#myModal').modal('hide');
        });
        //保存按钮列表
        $('#saveBtn').on('click',function () {
            var btn_list = [];
            $('#items').children('li').each(function () {
                var btn = {};
                btn.id = parseInt($(this).attr('data-id'));
                btn.name = $(this).attr('data-name');
                btn.icon = $(this).attr('data-icon');
                btn.shop_cids = $(this).find('.chosen-select').val().join(',');
                btn_list.push(btn);
            });
            api.requestApi('/api/package/filterBtns',{data:JSON.stringify(btn_list)},function (res) {
                if( res.result === 1)
                {
                    tip.showTip('ok','保存成功',1000,function () {
                        location.reload()
                    });
                }
            });
        });
    });


</script>

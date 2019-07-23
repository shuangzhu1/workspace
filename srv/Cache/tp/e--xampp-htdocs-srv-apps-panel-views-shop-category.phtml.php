<script src="/static/ace/js/jquery.nestable.min.js"></script>
<style>


    .box.box-success {
        border-top-color: #00a65a;
    }
    .box {
        position: relative;
        border-radius: 3px;
        background: #ffffff;
        border-top: 3px solid #d2d6de;
        margin-bottom: 20px;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }
    .box-header.with-border {
        border-bottom: 1px solid #f4f4f4;
    }
    .box-header {
        color: #444;
        display: block;
        padding: 3px;
        position: relative;
    }
    .box-title{
        padding-left:10px;
        margin:10px
    }
    .box-body{
        padding:10px 0 10px 0;
    }
</style>
<?php /*echo "<pre>";var_dump($categorys);*/ ?>
<div class="row">
    <div class="col-sm-6 ">
        <div class="widget-box" style="border-top:3px solid #428bca;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">
            <div class="widget-header widget-header-flat" style="background:white">
                <h4 class="smaller">店铺分类列表：</h4>
                <div class="right">

                <a href="javascript:;" style="position:relative;top:5px;right:10px;" class="btn btn-xs btn-primary " id="saveTree">保存树结构</a>
                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    <div style="text-align: center;margin-bottom:10px;font-weight:bold" class="red" >拖拽条目空白处，可调整分类排序和父子级关系</div>
                    <div class="dd" id="nestable" style="margin:0 auto">
                        <?php $this->partial('shop/partial/category'); ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-sm-6">
        <div class="box box-success" style="">
            <div class="box-header with-border">
                <h4 class="box-title pull-left">
                    添加/修改分类
                </h4>
                <div class="box-tools pull-left" style="margin-top:5px;margin-left:10px;display:none" id="tip" >
                    <div class="alert alert-block alert-danger" style="margin-bottom:0;border-radius:3px;padding:3px">
                        正在编辑分类信息，点击《清空》可添加分类
                    </div>
                </div>
                <div class="clearfix"></div>
                <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class="box-body" style="display: block;">


                <div class="clearfix"></div>
                <form class="form-horizontal">
                    <input type="hidden" name="id">
                    <div class="box-body fields-group">

                        <div class="form-group  ">
                            <label for="parent_id" class="col-sm-2 control-label" >父类：</label>
                            <div class="col-sm-8">
                                <select class="form-control" style="width: 100%;" name="parent_id" >
                                    <option value="0">顶级</option>
                                    <?php $this->partial('shop/partial/option'); ?>
                                </select>

                            </div>
                        </div>

                        <div class="form-group  ">
                            <label for="title" class="col-sm-2 control-label">分类名称：</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-pencil"></i></span>
                                    <input type="text"  name="name" value="" class="form-control title" placeholder="请输入分类名称">
                                </div>
                            </div>
                        </div>

                        <div class="form-group  ">
                            <label for="roles" class="col-sm-2 control-label">分类描述：</label>
                            <div class="col-sm-8">

                                <textarea name="desc" style="width:100%;height:40px;" placeholder="请输入分类描述"></textarea>
                            </div>
                        </div>
                        <div class="form-group  ">
                            <div class="col-sm-10 center">
                                <a href="javascript:;" class="btn btn-sm btn-success " id="addCategory">提交</a>
                                <a style="margin-left:50px;" href="javascript:;" class="btn btn-sm btn-danger " id="clearAll">清空</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- /.box-body -->
        </div>

    </div>

</div>



<script>
    //分类展示
    $('#nestable').nestable();
    $('.dd-handle a,.dd-handle span').on('mousedown', function(e){
        e.stopPropagation();
    });

    //编辑分类
    $(".editBtn").on('click',function () {
        var $li = $(this).closest('li');
        var id = $li.data('id');
        var name = $li.data('name');
        var desc = $li.data('desc');
        var pid = $li.data('pid');
        $('input[name="id"]').val(id);
        $('select[name="parent_id"]').val(pid);
        $('input[name="name"]').val(name);
        $('textarea[name="desc"]').val(desc);
        $('html,body').animate({scrollTop:0}, 300);
        $('#tip').fadeIn();
    });

    //清空表单
    $('#clearAll').on('click',function () {
        $('input[name="id"]').val('');
        $('select[name="parent_id"]').val(0);
        $('input[name="name"]').val('');
        $('textarea[name="desc"]').val('');
        $('#tip').fadeOut();
    });
    seajs.use('app/panel/panel.base',function (api) {
        //保存分类树结构
        $('#saveTree').on('click',function () {
            var data = $('#nestable').nestable('serialize');
            api.requestApi('/api/shop/saveTree',{data:data},function (res) {
                if( res.result === 1 )
                {
                    tip.showTip('ok','保存成功',800,function () {
                        window.location.reload();
                    });
                }
            })
        });
        //保存单个分类
        $('#addCategory').on('click',function () {
            var id = $('input[name="id"]').val();
            var parent_id = $('select[name="parent_id"]').val();
            var name = $('input[name="name"]').val();
            var desc = $('textarea[name="desc"]').val();
            api.requestApi('/api/shop/addCategory',{id:id,parent_id:parent_id,name:name,desc:desc},function (res) {
                if( res.result === 1 )
                {
                    tip.showTip('ok','操作成功',800,function () {
                        window.location.reload();
                    })

                }
            })
        });
        //删除单个分类
        $(".delBtn").on('click',function () {
            var _this = this;
            var id = $(_this).closest('li').data('id');
            $(_this).confirm("确认删除该分类?", {
                ok: function () {
                    api.requestApi('/api/shop/delCategory',{id:id},function (res) {
                        if( res.result === 1 )
                        {
                            tip.showTip('ok','操作成功',800,function () {
                                $(_this).closest('li').fadeOut('speed',function () {
                                    $(_this).remove();
                                });
                            })

                        }
                    })

                },
                cancel: function () {
                    return false;
                }
            });


        });

    });




</script>
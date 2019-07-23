$(document).ready(function() {
    BUI.use(['bui/tree','bui/data','bui/toolbar','bui/editor'],function (Tree,Data,Toolbar,Editor) {
        var cats = $('#menuClickMessages').val();
        if(cats) {
            var data = JSON.parse(cats);
        }
        else {
            var data = [];
        }

        var tree = new Tree.TreeList({
            render : '#t1',
            showLine : true,
            height:300,
            nodes : data
        });
        tree.render();

        var store = tree.get('store'),
            editor = new Editor.DialogEditor({
                contentId:'menuInfoModal',
                width : 500,
                mask : true,
                title : '行业编辑',
                form : {
                    srcNode : '#J_Form'
                },
                //mask : false,
                success : function(){
                    var edtor = this,
                        form = edtor.get('form'),
                        url = '/admin/industry/add';

                    //检验
                    form.valid();
                    if(form.isValid()){
                        form.ajaxSubmit({ //表单异步提交
                            url : url,
                            type: 'post',
                            success : function(data){

                                //将a 改成 1 测试一下显示错误
                                if(data.code){ //返回的数据是 {hasError : fasle,error : 'xxxx',field : 'xxx'},也可以是任意格式的数据如 ： {success : false,'error' : 'xxxxx'}
                                    var field = data.field;
                                    form.getField(field).showErrors([data.message]); //也可以多个字段的错误信息 例如 errors : [{field : 'a',error: 'addd'},{field : 'a',error: 'addd'}]
                                }else{
                                    var type = editor.get('editType');
                                    if(type == 'add') {
                                        var parentNode = editor.get('parentNode');
                                        var formData = form.toObject();
                                        formData.id = data.result;
                                        var newNode = store.add(formData,parentNode);
                                        tree.expandNode(parentNode);
                                    }
                                    else {
                                        store.update(form.toObject());
                                    }
                                    edtor.accept();
                                }
                            },
                            error : function(){
                                //do something
                                editor.cancel();
                            }
                        });
                    }
                }
            });
        editor.render();

        //显示编辑器
        function showEditor(node){
            var element = tree.findElement(node);
            editor.setValue(node);
            editor.set('record',node);

            editor.set('curNode',node); //缓存当前编辑的记录
            editor.set('align',{ //设置对齐
                node : $(element).find('.x-tree-icon-wraper'),
                points : ['tr','tl']
            });
            editor.show();
            editor.focus(); //获取焦点

        }
        //双击编辑
        tree.on('itemdblclick',function(ev){
            var node = ev.item;
            showEditor(node);
            editor.set('editType', 'edit');
        });

        var bar = new Toolbar.Bar({
            render : '#tbar',
            elCls : 'button-group',
            children : [
                {

                    elCls : 'button button-small',
                    content : '添加',
                    handler : function(){
                        var selectedNode = tree.getSelected();
                        var pid = 0;
                        if(selectedNode) {
                            pid = selectedNode.id;
                        }
                        var newNode = {pid: pid, text : '新增行业', desc: ''};
                        if(selectedNode && selectedNode.pid > 0) {
                            alert("暂时只支持两级分类！");
                            return false;
                        }
                        editor.set('parentNode', selectedNode);
                        editor.set('editType', 'add');
                        showEditor(newNode);
                    }
                },
                {

                    elCls : 'button button-small',
                    content : '删除',
                    handler : function(){
                        var selectedNode = tree.getSelected();
                        if(selectedNode){
                            if(confirm("确定删除？")) {
                                $.ajax({
                                    url: '/admin/industry/remove',
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        cid: selectedNode.id
                                    }
                                }).done(function(data) {
                                    if(data.code > 0) {
                                        alert("删除失败!" + data.message);
                                    }
                                    else {
                                        alert("删除成功");
                                        store.remove(selectedNode);
                                    }

                                });
                            }
                        }
                    }
                }
            ]
        });
        bar.render();
    });

    var Form = BUI.Form;

    new Form.Form({
        srcNode : '#J_Form'
    }).render();
});
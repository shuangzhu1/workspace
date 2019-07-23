<form action="" method="get"
      style="border: 1px solid #e4e4e4;padding: 8px;margin: 0 0 8px;border-radius: 5px;line-height: 50px;">
    <label for="name">用户ID:</label>
    <input name="user_id" type="text" style="width: 200px" id="user_id" value="<?php echo $user_id ? $user_id : ''; ?>">
    <input type="submit" class="btn btn-primary btn-sm" value="搜索">
    <span class="btn btn-inverse btn-sm btnAdd"><i class="fa fa-plus"></i> 添加用户</span>
</form>
<table class="list " style="width: 60%">
    <thead>
    <tr class="head">
        <th>用户ID</th>
        <th>用户头像</th>
        <th>用户名</th>
        <th>真实姓名</th>
        <th>加入黑名单时间</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody class="listData">
    <?php if ($list) { ?>
        <?php foreach ($list as $k => $item) {
            $item = json_decode($item, true);
            ?>
            <tr class="item item_<?php echo $k ?>">
                <td><?php echo $k ?></td>
                <td>
                    <img
                        src="<?php echo $users[$k]['avatar'] ?>?x-oss-process=image/resize,m_fill,h_160,w_160"
                        width="40"
                        height="40" style="border-radius: 3px"/>
                </td>
                <td><?php echo $users[$k]['username'] ?></td>
                <td><?php echo $users[$k]['true_name'] ?></td>
                <td><?php echo date('Y-m-d H:i', $item['time']) ?></td>
                <td><span class="btn btn-xs btn-primary btnRemove" data-key="<?php echo $k ?>"
                          data-id="<?php echo $k ?>">移除黑名单</span></td>
            </tr>

        <?php } ?>
    <?php } ?>
    </tbody>

</table>
<script>
    seajs.use('app/panel/panel.base', function (api) {
        $(".btnRemove").on('click', function () {
            var user_id = $(this).data('id');
            api.requestApi('/api/package/removeBlacklist', {user_id: user_id}, function (res) {
                    if (res.result == 1) {
                        api.showTip('ok', "删除成功", 1000, function () {
                            $(".item_" + user_id).remove();
                        })
                    }
                }
            )
        });
        $(".btnAdd").on('click', function () {
            var user_id = $("#user_id").val();
            if (!user_id || !/^(\d){5,}$/.test(user_id)) {
                api.showTip("err", "请输入正确的用户ID", 1000);
                return;
            }
            api.requestApi('/api/package/addBlacklist', {user_id: user_id}, function (res) {
                    if (res.result == 1) {
                        api.showTip('ok', "添加成功", 1000, function () {
                            window.location.reload();
                        })
                    }
                }
            )
        })
    })
</script>
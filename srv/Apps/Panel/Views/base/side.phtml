<!--<div style="padding-left:15px;padding-bottom:7px">
<input type="text" id="kw" placeholder="请输入菜单名称" style="width:110px;background-color:#eee;">
    <a href="javascript:;" class="btn btn-xs btn-danger" id="kw-ac" style="margin-left:5px;padding:3px 8px">搜索</a>
</div>-->
<div class="input-group" style="padding:12px;background-color:#404e5f" id="search-box">
    <input type="text" id="kw" class="form-control" placeholder="输入关键词  > 回车" style="border-bottom-left-radius: 3px !important;border-top-left-radius: 3px !important;">
    <span class="input-group-btn">
        <button class="btn btn-xs btn-default" id="kw-ac" type="button" style="padding:3px 5px">Go!</button>
      </span>
</div>
<?php
if ($menus_tree) {
    ?>
    <?php
    foreach ($menus_tree as $menus_first) {
        ?>
        <li class="<?php echo $curMenu['cid_parendId'] == $menus_first['id'] ? 'active' : ''; ?>">

            <a href="javascript:;" class="dropdown-toggle">
                <i class="fa <?php echo $menus_first['icon']; ?>"></i>
                <span class="menu-text"><?php echo $menus_first['title'] ?></span>
                <span class="arrow fa  fa-angle-down"></span>
            </a>
            <ul class="submenu <?php echo 'active open'; ?>">
                <?php if (!empty($menus_first['list'])) { ?>
                    <?php foreach ($menus_first['list'] as $menus_second) {
                        if ($menus_second['parent_id'] == $menus_first['id']) {
                            ?>
                            <li class="<?php echo $curMenu['cid'] == $menus_second['id'] ? 'active' : ''; ?>"
                                style="<?php echo (isset($sec['hide']) && $sec['hide']) ? 'display:none' : ''; ?>">
                                <a href="javascript:;<?php /*echo $sec['url']; */ ?>" class="dropdown-toggle <?php if(empty($menus_second['list'])) echo 'hide'?>">
                                    <i class="fa <?php echo $menus_second['icon'] ?>"></i>
                                    <span><?php echo $menus_second['title'] ?></span>
                                    <!--  <span class="menu-text"><?php /*echo $cat['title'] */ ?></span>-->
                                    <span class="arrow fa  fa-angle-down"></span>
                                </a>
                                <?php if (!empty($menus_second['list'])) {
                                    ?>

                                    <ul class="submenu <?php echo 'active open'; ?>">
                                        <?php if (!empty($menus_second['list'])) { ?>
                                            <?php foreach ($menus_second['list'] as $thd) {
                                                ?>
                                                <li class="<?php echo $curMenu['id'] == $thd['id'] ? 'active' : ''; ?> subItem"
                                                    style="<?php echo (isset($sec['hide']) && $sec['hide']) ? 'display:none' : ''; ?>">
                                                    <a href="javascript:;"
                                                       data-href="/srv/<?php echo $thd['module'] . '/' . $thd['controller'] . '/' . $thd['action'] ?>"
                                                       data-title="<?php echo $thd['title'] ?>"
                                                       data-id="<?php echo $thd['id'] ?>"
                                                       class="dropdown-toggle">

                                                        <i class="fa fa-angle-double-right"></i>
                                                        <span><?php echo $thd['title'] ?></span>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                            ?>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </li>
                            <?php
                        }
                    }
                    ?>
                <?php } ?>
            </ul>
        </li>
    <?php }
    ?>
<?php } ?>

<script>
    function showBtn(){
        //显示一级菜单
        $('.nav-list > li').css('display','block');
        //折叠二级菜单
        $('.nav-list > li >ul').css('display','none');
        //去除关键词效果
        $('.nav-list').find('.dropdown-toggle span').removeClass('blue');
        //显示所有二级条目
        $('.nav-list').find('.submenu > li').css('display','block');
        //折叠三级条目
        $('.nav-list').find('.submenu').css('display','none');
        //显示所有三级条目
        $('.nav-list').find('.subItem').css('display','block');
        var kw = $('#kw').val();
        if( $.trim(kw) !== '')
        {

            var reg = new RegExp(kw);
            $('.nav-list .dropdown-toggle').each(function () {
                var menu_name = $.trim($(this).find('span').text());
                $(this).find('span').removeClass('blue');
                if(menu_name.match(reg))
                {
                    $(this).closest('li').siblings('li').each(function(){
                        if( !$(this).find('.dropdown-toggle').find('span').text().match(reg) )
                        {
                            $(this).css('display','none');
                        }else
                        {
                            $(this).css('display','block');
                        }
                    });
                    $(this).closest('ul').closest('li').siblings('li').each(function () {
                        if( !$(this).find('.dropdown-toggle').find('span').text().match(reg) )
                        {
                            $(this).css('display','none');
                        }else
                        {
                            $(this).css('display','block');
                        }
                    });

                    $(this).closest('ul').css('display','block');
                    $(this).closest('ul').closest('li').closest('ul').css('display','block');
                    $(this).find('span').addClass('blue');
                }
            });
        }else
        {
            $(this).find('span').removeClass('blue');
        }
    }

    $('#kw-ac').on('click',showBtn);
    //绑定enter键
    $('#kw').on('keydown',function (e) {
        if( e.keyCode !== 13 )
            return true;
        showBtn();
    }).on('keyup',function(){
        showBtn();
    });
    $('#sidebar-collapse').on('click',function () {
        $('#search-box').css("visibility",'hidden');
    });


</script>



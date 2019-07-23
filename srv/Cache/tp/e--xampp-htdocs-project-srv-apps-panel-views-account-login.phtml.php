<div class="content">
    <?php if (\Util\Ajax::isMobile()) { ?>
        <style>
            .main .content {
                width: 90%;
                padding: 0;
                margin: 30% auto;
            }
            .loginForm{
                width: 100%;
                margin: 0;
                padding: 0;
                overflow: hidden;
            }
            .loginForm .formHead.head{
                text-align: center;
                margin: 0 0 30px;
            }
            .loginForm .field{
                margin: 15px 20px 15px 30px;
            }
        </style>
    <?php } else { ?>
        <div class="loginSide">
            <img src="/srv/static/panel/images/admin/login_page_bg.jpg" alt="" style="border-radius:8px;"/>
        </div>
    <?php } ?>


    <form class="loginForm form" action="javascript:;">
        <h2 class="head formHead">
            管理登陆
        </h2>

        <p class="field">
            <label for="">
                账号：
            </label>
            <input type="text" name="account" id="account" placeholder="用户名或邮箱"/>
            <span class="red">*</span>
        </p>

        <p class="field">
            <label for="">
                密码：
            </label>
            <input type="password" name="password" id="password" placeholder="密码" data-tip="长度为6-16位"/>
            <span class="red">*</span>
        </p>

        <p class="field">
            <label for=""></label>
            <input class="miBtn btn-green loginBtn" type="submit" value="登 陆"/>
        </p>
    </form>

    <div class="clear"></div>
</div>
<script>
    seajs.use('app/panel/account/account.init', function (api) {
        api.login();
        $(".main").height($(document).height()-$(".header").height());

    });
</script>

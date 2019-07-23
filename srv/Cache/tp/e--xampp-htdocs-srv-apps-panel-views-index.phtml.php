<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="description" content="运营平台">
    <link rel="icon" href="/srv/static/panel/images/admin/favicon.ico"/>
    <meta name="author" content="Estt Team">
    <script type="text/javascript">
        var explorer = window.navigator.userAgent;
        if (explorer.indexOf("Firefox") >= 0) {//Firefox
            browser = 'Firefox';
        } else if (explorer.indexOf("Chrome") >= 0) {//Chrome
            browser = 'Chrome';
        } else if (explorer.indexOf("Opera") >= 0) {//Opera
            browser = 'Opera';
        } else if (explorer.indexOf("Safari") >= 0) {//Safari
            browser = 'Safari';
//        } else if (explorer.indexOf("Trident/7.0") >= 0) {//IE11
//            browser = 'IE:10.0以上';
        } else {
            if (explorer.indexOf("MSIE") >= 0) {//ie10及以下
                var b_name = navigator.appName;
                var b_version = navigator.appVersion;
                var version = b_version.split(";");
                version = version[1].replace(/[ ]/g, "");
                version = version.split('MSIE')[1];
                browser = 'IE:' + version;
                if (version < 9) {
                    alert("很抱歉给您带来不便，但是您的浏览器不支持CSS3技术，请下载使用firefox或谷歌chrome浏览器~！");
                    window.close();
                    setInterval(function () {
                        window.close();
                    }, 1000)
                }
            } else {
                alert("很抱歉给您带来不便，但是您的浏览器不支持CSS3技术，请下载使用firefox或谷歌chrome浏览器~！");
                window.close();
                setInterval(function () {
                    window.close();
                }, 1000)
            }
        }
    </script>
    <?= $this->tag->getTitle() ?>
    <?= $this->tag->stylesheetLink('static/ace/css/bootstrap.min.css') ?>
    <?= $this->tag->stylesheetLink('static/ace/css/font-awesome.min.css') ?>
    <?= $this->tag->stylesheetLink('static/fonts/font-awesome/css/font-awesome.min.css') ?>
    <?= $this->tag->stylesheetLink('static/ace/css/colorbox.css') ?>
    <?= $this->tag->stylesheetLink('static/ace/css/ace-skins.min.css') ?>
    <?= $this->tag->stylesheetLink('static/ace/css/ace.min.css?v=1.2') ?>
    <?= $this->tag->stylesheetLink('static/panel/css/ace-estt-panel.css?v=1.2') ?>

    <!--    <?= $this->tag->stylesheetLink('static/icomoon/style.css') ?>-->

    <?php foreach ($cssFiles as $cssFile) { ?>
    <?= $this->tag->stylesheetLink($cssFile) ?>
    <?php } ?>
    <?php $this->assets->outputCss() ?>
    <?= $this->tag->javascriptInclude('static/panel/js/seajs.js') ?>
    <?= $this->tag->javascriptInclude('static/panel/js/jquery/jquery-3.0.2.min.js') ?>
    <!--    <?= $this->tag->javascriptInclude('static/panel/js/jquery/jquery.last.js') ?>
    -->
    <?= $this->tag->javascriptInclude('static/ace/js/jquery.slimscroll.min.js') ?>
    <?= $this->tag->javascriptInclude('static/ace/js/ace-extra.min.js') ?>
    <?= $this->tag->javascriptInclude('static/ace/js/bootstrap.min.js') ?>
    <?= $this->tag->javascriptInclude('static/ace/js/typeahead-bs2.min.js') ?>
    <script src="/srv/static/ace/js/ace-elements.min.js"></script>
    <script src="/srv/static/ace/js/ace.min.js"></script>
    <script src="/srv/static/panel/js/jquery/jquery.confirm.js"></script>

    <?= $this->tag->javascriptInclude('static/bootstrap/js/jquery.bootstrap.dialog.js') ?>
    <?= $this->tag->javascriptInclude('static/bootstrap/js/jquery.bootstrap.validation.js') ?>
    <?php foreach ($jsFiles as $jsFile) { ?>
    <?= $this->tag->javascriptInclude($jsFile) ?>
    <?php } ?>

    <script>
        seajs.config({
            base: '/srv/static/panel/js',
            charset: 'utf-8',
            timeout: 10000
        });
        var app = app || {};
        app.site_url = '<?php echo $this->uri->baseUri('/panel'); ?>';
        $(function () {
            // scrollables
            $('.slim-scroll').each(function () {
                var $this = $(this);
                $this.slimScroll({
                    height: $this.data('height') || 100,
                    railVisible: true
                });
            });
        })
    </script>
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <?= $this->tag->javascriptInclude('static/ace/js/html5shiv.js') ?>
    <?= $this->tag->javascriptInclude('static/ace/js/respond.min.js') ?>
    <![endif]-->
    <!--    -->
    <!--</head>-->
    <?php $this->assets->outputJs() ?>
<body class="skin-1">

<div class="navbar navbar-default" id="navbar">
    <script type="text/javascript">
        try {
            ace.settings.check('navbar', 'fixed')
        } catch (e) {

        }
    </script>

    <div class="navbar-container" id="navbar-container">
        <div class="navbar-header pull-left" style="font-family: 'Microsoft YaHei';min-width: 180px;">
            <a href="<?php echo $this->uri->moduleUrl(); ?>" class="navbar-brand">
                <small>
                    <img src="srv/static/panel/images/13915.png" style="width: 25px; height: 25px;border-radius: 100%;"/>
                    <i class="icon-laptop" style="color: #63f13a;"></i>
                    <?php echo HOST_BRAND; ?>
                    <span
                            style="font-size: 12px;color: #63f13a;">- 运营平台</span>
                </small>
            </a><!-- /.brand -->
        </div>

        <div class="right" role="navigation" style="margin-right: 30px">
            <?php $this->partial('base/topNav'); ?>
        </div>
    </div>
</div>

<div class="main-container" id="main-container">
    <script type="text/javascript">
        try {
            ace.settings.check('main-container', 'fixed')
        } catch (e) {
        }
    </script>

    <div class="main-container-inner">
        <a class="menu-toggler" id="menu-toggler" href="#">
            <span class="menu-text"></span>
        </a>

        <div class="sidebar" id="sidebar" style="margin-top:-40px">
            <script type="text/javascript">
                try {
                    ace.settings.check('sidebar', 'fixed')
                } catch (e) {
                }
            </script>

            <ul class="nav nav-list">
                <?php $this->partial('base/side'); ?>
            </ul>
            <!-- /.nav-list -->
            <div class="sidebar-collapse" id="sidebar-collapse">
                <i class="fa fa-angle-double-left"
                   style="display: inline-block;position: relative;font-size: 15px;cursor: pointer"
                   data-icon1="fa fa-angle-double-left"
                   data-icon2="fa fa-angle-double-right"></i>
            </div>

            <script type="text/javascript">
                try {
                    ace.settings.check('sidebar', 'collapsed')
                } catch (e) {
                }
            </script>
        </div>

        <div class="main-content" style="position: relative">
            <?php $this->partial('base/showPath'); ?>

            <div class="page-content" style="height: auto;overflow: hidden">
                <?= $this->getContent() ?>
                <?php if (\Util\Ajax::isMobile() && $this->dispatcher->getControllerName() == 'index' && $this->dispatcher->getActionName() == 'index') {
                    $this->partial('index/welcome')
                    ?>

                <?php } else {
                    if (\Util\Ajax::isMobile()) {

                    } else { ?>
                        <div id="iframe_box" style="width: 100%;min-height: 800px;">
                            <!-- <div class="show_iframe">
                                 <div style="display: none" class="loading">
                                     <img src="/static/panel/images/admin/loading2.gif"/>
                                 </div>
                                 <iframe scrolling="yes" allowfullscreen="true" frameborder="0"
                                         src="/panel/index/welcome"></iframe>
                             </div>-->
                        </div>
                    <?php }
                } ?>

            </div>
        </div>
        <!-- /.main-content -->

    </div>

</div>
<div id="ajaxStatus">
    <p id='ajaxTip' class="wait">加载中...</p>
</div>
<!-- /.main-container -->


<?php $this->partial('base/widget/storage') ?>
<script>
    $(function () {
        //iframe 最小高度
        $('#iframe_box').css('min-height',($(document).height()-132) + 'px');
        var browser = {
            versions: function () {
                var u = navigator.userAgent, app = navigator.appVersion;
                return {//移动终端浏览器版本信息
                    trident: u.indexOf('Trident') > -1, //IE内核
                    presto: u.indexOf('Presto') > -1, //opera内核
                    webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
                    gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
                    mobile: !!u.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
                    ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
                    android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
                    iPhone: u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
                    iPad: u.indexOf('iPad') > -1, //是否iPad
                    webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
                };
            }()
        };
        //                pc
        if (browser.versions.mobile) {
            $("#main-container").css({width: '1800px'});
            $(".navbar-container").css({width: '1800px'});

            $(".navbar").css({width: '1800px',position:'absolute'});
            $(".inner-nav").css({position:'absolute',top:'-40px'});


        }
    })
</script>
</body>

</html>
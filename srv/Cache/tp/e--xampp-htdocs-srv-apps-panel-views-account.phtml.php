<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="description" content="商户入驻">
    <meta name="author" content="Estt Team">
    <?= $this->tag->getTitle() ?>
    <link rel="stylesheet" href="/srv/static/panel/css/customer.join.css?v=1.0"/>
    <?= $this->tag->stylesheetLink('static/fonts/font-awesome/css/font-awesome.min.css') ?>
    <?= $this->tag->javascriptInclude('static/panel/js/seajs.js') ?>
    <?= $this->tag->javascriptInclude('static/panel/js/jquery.min.js') ?>
    <script>
        seajs.config({
            base: '/srv/static/panel/js',
            charset: 'utf-8',
            timeout: 10000
        });
    </script>
    <!--[if lt IE 9]>
    <?= $this->tag->javascriptInclude('static/ace/js/html5shiv.js') ?>
    <?= $this->tag->javascriptInclude('static/ace/js/respond.min.js') ?>
    <![endif]-->
</head>
<body>
<main class="wrap" style="width: 100%;height: 100%">
    <header class="header">

        <div style="<?php echo \Util\Ajax::isMobile() ? '' : ' width: 1040px; ' ?> margin: 0 auto;">
            <p class="panel-tit">
                <?php echo \Util\Ajax::isMobile() ? '' : '<span class="right tit">管理登陆</span> <span>Control</span>
                <span style="color: #FFC600;">Panel</span>' ?>

                <span class="name"> - <span style="color: #FFC600;"><?php echo HOST_BRAND; ?></span>运营平台</span>
            </p>
        </div>
    </header>
    <section class="main">
        <?php
        echo $this->getContent();
        ?>
    </section>
    <footer>
    </footer>
</main>
<div id="ajaxStatus">
    <p id='ajaxTip' class="wait">加载中...</p>
</div>
</body>
</html>
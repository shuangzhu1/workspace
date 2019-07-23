<div class="page-header">
    <h1>
        <i class="fa fa-gear"></i> 基本设置
        <small>

        </small>
    </h1>
</div>
<style>
    .form-group {
        min-height: 30px;
        overflow: hidden;
    }

    .form-group input[type='number'] {
        width: 100px;
    }

    .form-group .control-label {
        text-align: right;
    }

    .form-group select {
        margin: 0;
    }

    .disabled {
        background-color: #e4e4e4;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .discuss_recommend_wrap, .share_wrap {
        padding-top: 10px;
        margin-bottom: 10px;
        width: 99%;
    }

    .disabled .form-group input {
        background-color: #f5f5f5;
    }

    .minus_btn {
        box-sizing: content-box;
        width: 20px;
        height: 20px;
        float: left;
        text-align: center;
        overflow: hidden;
        display: inline-block;
        border: 1px solid #e4e4e4;
        border-radius: 100%;
        margin-right: 5px;
        margin-top: 5px;
        cursor: pointer;
    }

    .list .head {
        background-color: #f5f5f5;
    }

    .start, .end, .rate {
        width: 80px;
    }

    .predict_start, .predict_end {
        width: 80px;
    }

    .section-group {
        width: 100%;
        height: auto;
        overflow: hidden;
        margin-bottom: 10px;
        line-height: 30px;

    }
</style>
<main class="tab-content" style="border:none;border-bottom: 1px solid #e4e4e4">
    <section id="home" class="tab-pane in active">
        <div class="">

            <div class="share_wrap">
                <p style="font-size: 16px;border-bottom: 1px solid #e4e4e4;padding: 10px;margin-bottom: 10px">有邀请码：</p>
                <div class="form-group" style="">
                    <label for="logo" class="col-sm-2 control-label">开店服务费:</label>
                    <div class="col-sm-9">
                        <input type="number" class="has_code_money" min="0"
                               value="<?php echo ($setting && !empty($setting['has_code'] / 100)) ? $setting['has_code'] / 100 : 0 ?>">
                        元
                    </div>
                </div>
                <div class="form-group" style="">
                    <label for="logo" class="col-sm-2 control-label">平台佣金:</label>
                    <div class="col-sm-9">
                        <input type="number" class="platform_money" min="0"
                               value="<?php echo ($setting && !empty($setting['platform'] / 100)) ? $setting['platform'] / 100 : 0 ?>">
                        元
                    </div>
                </div>
                <div class="form-group" style="">
                    <label for="logo" class="col-sm-2 control-label">一级邀请人:</label>
                    <br>
                    <div class="col-sm-8 form-group"
                         style="border: 1px solid #e4e4e4;padding: 10px;border-radius: 3px;margin-left: 10px">
                        <section class="section-group">
                            <label for="logo" class="col-sm-2 control-label">基础提成:</label>
                            <div class="col-sm-9">
                                <input type="number" class="base_money" min="0"
                                       value="<?php echo ($setting && !empty($setting['base'] / 100)) ? $setting['base'] / 100 : 0 ?>">
                                元(<b class="red">开店成功，立刻到账</b>)
                            </div>
                        </section>
                        <section class="section-group">
                            <label for="logo" class="col-sm-2 control-label">平台奖励基数:</label>
                            <div class="col-sm-10">
                                <input type="number" class="radices" min="0" readonly
                                       value="<?php
                                       $money = $setting['reward_radices'];
                                       echo $money / 100 ?>">
                                元
                            </div>
                        </section>
                        <section class="section-group">
                            <label for="logo" class="col-sm-2 control-label">平台奖励配置:</label>
                            <div class="col-sm-8">
                                <p style="line-height: 40px"><b class="red">月初结算上个月的</b></p>
                                <table class="list">
                                    <tr class="head">
                                        <th>起始人数【个】</th>
                                        <th>结束人数【个】</th>
                                        <th>奖金比例【百分比】</th>
                                        <th>预计收益【元】</th>
                                    </tr>
                                    <?php if ($setting['limit']) { ?>
                                        <?php foreach ($setting['limit'] as $k => $item) {
                                            $predict_start = sprintf("%.2f", ((($money * $item['rate'] / 100) + $setting['base']) * $item['start']) / 100);
                                            $predict_end = sprintf("%.2f", ((($money * $item['rate'] / 100) + $setting['base']) * $item['end']) / 100);

                                            ?>
                                            <tr class="params_item">
                                                <td>
                                                    <?php if ($k != 0) { ?>
                                                        <span class="minus_btn" style=""><i
                                                                class="fa fa-minus"></i></span>
                                                    <?php } ?>
                                                    <input type="number" min="0" max="100" name="start[]" class="start"
                                                           value="<?php echo $item['start'] ?>"/></td>
                                                <td><input type="number" min="0" max="100" name="end[]" class="end"
                                                           value="<?php echo $item['end'] ?>"/></td>
                                                <td><input type="number" min="0" max="100" name="rate[]" class="rate"
                                                           value="<?php echo $item['rate'] ?>"/></td>
                                                <td>
                                                    <input type="text" readonly class="predict_start"
                                                           value="<?php echo $predict_start ?>"/>-
                                                    <input type="text" readonly class="predict_end"
                                                           value="<?php echo $predict_end ?>"/>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr class="params_item">
                                            <td><input type="number" min="0" max="100" name="start[]" class="start"
                                                       value=""/>
                                            </td>
                                            <td><input type="number" min="0" max="100" name="end[]" class="end"
                                                       value=""/>
                                            </td>
                                            <td><input type="number" min="0" max="100" name="rate[]" class="rate"
                                                       value=""/>
                                            </td>
                                            <td>
                                                <input type="text" readonly class="predict_start"/> -
                                                <input type="text" readonly class="predict_end"/>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                </table>
                            </div>
                        </section>
                    </div>
                    <br/>

                </div>
                <div class="form-group" style="">
                    <label for="logo" class="col-sm-2 control-label">二级邀请人提成:</label>
                    <div class="col-sm-9">
                        <input type="number" class="second_base_money" min="0"
                               value="<?php echo ($setting && !empty($setting['second_base'] / 100)) ? $setting['second_base'] / 100 : 0 ?>">
                        元(<b class="red">开店成功，立刻到账</b>)
                    </div>
                </div>
                <div class="form-group" style="">
                    <label for="logo" class="col-sm-2 control-label">三级邀请人提成:</label>
                    <div class="col-sm-9">
                        <input type="number" class="third_base_money" min="0"
                               value="<?php echo ($setting && !empty($setting['third_base'] / 100)) ? $setting['third_base'] / 100 : 0 ?>">
                        元(<b class="red">开店成功，立刻到账</b>)
                    </div>
                </div>
                <div class="form-group" style="">

                </div>

                <p style="font-size: 16px;border-bottom:1px solid #e4e4e4;border-top:1px solid #e4e4e4;padding: 10px;margin-bottom: 10px">
                    没有邀请码：</p>
                <div class="form-group" style="">
                    <label for="logo" class="col-sm-2 control-label">总价格:</label>
                    <div class="col-sm-9">
                        <input type="number" class="no_code_money" min="0"
                               value="<?php echo ($setting && !empty($setting['no_code'] / 100)) ? $setting['no_code'] / 100 : 0 ?>">
                        元
                    </div>
                </div>

                <p class="alert alert-danger error-widget" style="display: none"><i class="fa fa-warning"></i>
                    <span class="error_msg"></span>
                </p>
            </div>

        </div>
        </div>
        <div
            style="width: 100%;height: auto;overflow: hidden;border: 1px solid #f4f4f4;padding:10px 0 0 0;border-top: none">
            <div class="form-group">
                <label for="name" class="col-sm-1 control-label"></label>

                <div class="col-sm-10" style="padding: 0 0 0 30px;">
                    <button type="submit" class="btn btn-primary btn-large btnSave" name="btn-base">
                        <i class="fa fa-paper-plane"></i>立即保存
                    </button>
                </div>
            </div>
        </div>
    </section>

</main>
<script>
    seajs.use('app/panel/panel.base', function (api) {

        $(".btnSave").on('click', function () {
            var has_code_money = $(".has_code_money").val();
            var no_code_money = $(".no_code_money").val();
            var base_money = $(".base_money").val();
            var second_base_money = $(".second_base_money").val();
            var third_base_money = $(".third_base_money").val();

            var platform_money = $(".platform_money").val();

            var reward = [];
            $(".list").find(".params_item").each(function () {
                var start = $(this).find('.start').val();
                var end = $(this).find('.end').val();
                var rate = $(this).find('.rate').val();

                if (start != '' && end != '' && rate != '') {
                    reward.push({'start': start, 'end': end, 'rate': rate})
                }
            });
            var data = {
                has_code_money: has_code_money,
                no_code_money: no_code_money,
                base_money: base_money,
                second_base_money: second_base_money,
                third_base_money: third_base_money,
                platform_money: platform_money,
                reward: reward
            };

            api.requestApi('/api/shop/setting', data, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", '保存成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })

        })
    });
    function error(msg) {
        tip.showTip("err", msg, 1000);
        $(".error-widget .error_msg").html(msg);
        $(".error-widget").show();
    }
    $(".list").on('keyup', '.rate', function (event) {
        if (event.keyCode == "13") {
            var html = $(this).closest("tr").clone();
            if ($(".list").find("tr").length == 2 || $(".minus_btn").length == 0) {
                html.find("td").eq(0).append('<span class="minus_btn" style=""><i class="fa fa-minus"></i></span>');
            }
            $(".list").append(html);
            html.find(".rate").focus();
        } else if (event.keyCode === 8) {
            if ($(".list").find("tr").length > 2) {
                if ($(this).val() == '' && $(this).closest("tr").find('.start').val() == '') {
                    $(this).closest("tr").prev().find('.rate').focus();
                    $(this).closest("tr").remove();
                }
            }

        }
    }).on("click", '.minus_btn', function () {
        if ($(".list").find("tr").length > 2) {
            $(this).closest("tr").remove();
        }
    });
    //    }).on('keyup', '.start', function (event) {
    //        if (event.keyCode === 8) {
    //            if ($(".list").find("tr").length > 2) {
    //                if ($(this).val() == '' && $(this).closest("tr").find('.start').val() == '') {
    //                    $(this).closest("tr").prev().find('.rate').focus();
    //                    $(this).closest("tr").remove();
    //                }
    //            }
    //
    //        }
    //    });

    $(".platform_money").on('keyup', function () {
        changeRadices();
        $(".start").each(function () {
            var tr = $(this).closest("tr");
            changeReward(tr);
        });
    });
    $(".base_money").on('keyup', function () {
        changeRadices();
        $(".start").each(function () {
            var tr = $(this).closest("tr");
            changeReward(tr);
        });
    });
    $(".second_base_money").on('keyup', function () {
        changeRadices();
        $(".start").each(function () {
            var tr = $(this).closest("tr");
            changeReward(tr);
        });
    });
    $(".third_base_money").on('keyup', function () {
        changeRadices();
        $(".start").each(function () {
            var tr = $(this).closest("tr");
            changeReward(tr);
        });
    });
    $(".has_code_money").on('keyup', function () {
        changeRadices();
        $(".start").each(function () {
            var tr = $(this).closest("tr");
            changeReward(tr);
        });
    });
    $(".list").on('keyup', '.start', function () {
        var tr = $(this).closest("tr");
        changeReward(tr);
    }).on('keyup', '.end', function () {
        var tr = $(this).closest("tr");
        changeReward(tr);
    }).on('keyup', '.rate', function () {
        var tr = $(this).closest("tr");
        changeReward(tr);
    });

    //更新奖励配置
    function changeReward(tr) {
        var start = tr.find(".start").val();
        var end = tr.find(".end").val();
        var rate = tr.find(".rate").val();
        var radices = $(".radices").val();
        var base = $(".base_money").val();
        if (checkRate(radices) && checkRate(start) && checkRate(end) && checkRate(base)) {
            tr.find(".predict_start").val(((parseFloat(base) + parseFloat(radices * rate / 100)) * start).toFixed(2))
            tr.find(".predict_end").val(((parseFloat(base) + parseFloat(radices * rate / 100)) * end).toFixed(2))
        }
    }
    //更新平台奖励基数
    function changeRadices() {
        var total = $(".has_code_money").val();
        var platform = $(".platform_money").val();
        var base = $(".base_money").val();
        var second_base = $(".second_base_money").val();
        var third_base = $(".third_base_money").val();

        if (checkRate(total) && checkRate(platform) && checkRate(base) && checkRate(second_base) && checkRate(third_base)) {
            var redices = total - platform - base - second_base - third_base;
            redices = redices > 0 ? redices : 0;
            $(".radices").val(redices.toFixed(2));
        }
    }

    function checkRate(input) {
        var re = /^[0-9]+.?[0-9]*$/; //判断字符串是否为数字 //判断正整数 /^[1-9]+[0-9]*]*$/
        if (!re.test(input)) {
            return false;
        }
        return true;
    }
</script>

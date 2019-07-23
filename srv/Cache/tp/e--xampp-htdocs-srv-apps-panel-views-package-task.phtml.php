<div class="page-header">
    <h1>红包广场任务规则</h1>
</div>

<?php
$data = $this->view->data;
$new_add = $this->view->new_add;
$behaviorNameMap = $this->view->behaviorNameMap;
$termNameMap = $this->view->termNameMap;
$pointTypeMap = $this->view->pointTypeMap;
?>
<script>
    seajs.use('app/panel/package/task.js?v=1.0', function (api) {
        api.setRule();
    });
</script>

<style>
    .ruleForm .item a {
    }

    .item {
        margin: 8px 0;
        padding: 5px;
        border-bottom: 1px solid #f5f5f5;
    }

    .item:before, .item:after {
        display: none;
    }

    .ruleForm .tab {
        padding: 2px 3px;
        margin: 0 3px 0 0;
        line-height: 24px;
        display: inline-block;
        /*border: 1px solid #fff;*/
    }

    .ruleForm .tab .selc {
        height: 20px;
    }

    .ruleForm .tab .txt {
        height: 14px;
        padding: 2px 5px;
    }

    .ruleForm .item:hover {
        background: #f6f6f6;
    }

    .item-edit {
        display: none;
    }

    .ruleForm .tab-num {
        width: 40px;
        background: #FEFCEF;
        border: 1px solid #DEEFFB;
        color: #f60;
        text-align: center;
    }

    .ruleForm .tab-name {
        width: 200px;
    }

    .ruleForm .tab-space {
        width: 60px;
        text-align: center;

    }

    .ruleForm .tab-edit {
        padding: 2px 10px;
    }

    .ruleForm .tab-edit .editRowBtn {
        display: none;
    }

    .ruleForm .tab-action {
        width: 100px;
        text-align: center;
    }

    .ruleForm .tab-quantity {
        width: 100px;
        text-align: center;
    }

    .ruleForm .tab-permanent {
        width: 100px;
        text-align: center;
    }

    .ruleForm .tab-enable {
        width: 100px;
        text-align: center;
    }

    .ruleForm .tab-term {
        width: 200px;
    }

    .ruleForm .tab-limit {
        width: 200px;
    }

    .item-head .tab {
        background: #FEFCEF;
        border: 1px solid #DEEFFB;
        font-size: 14px;
        line-height: 200%;
    }
</style>
<section style="width: 1200px; border:  1px solid #e4e4e4;padding: 10px 5px;border-radius: 5px;">
    <header
        style="line-height: 36px;background: #FEFCEF;padding: 0 12px;  border: 1px solid #DEEFFB;font-size: 14px;margin: 0 0 8px;">
        红包广场任务规则设置 [ <a href="javascript:;" class="editRuleBtn  btn btn-success btn-minier" style="color: #666;">编辑</a>
        ]
        <input type="submit" value="保存" class="saveRuleBtn btn-light" data-plate="" style="display: none"/>

    </header>

    <form action="javascript:;" class="ruleForm" id="ruleForm" data-type="<?php echo $type; ?>">
        <p class="item-head">
            <!--            <span class="tab tab-num">编号</span>-->
            <span class="tab tab-name">动作名称</span>
            <!--            <span class="tab tab-space">--</span>-->
            <!--            <span class="tab tab-action">--</span>-->
            <span class="tab tab-permanent">增加方式</span>
            <span class="tab tab-quantity">增加次数</span>
            <span class="tab tab-term">执行方式</span>
            <span class="tab tab-limit">每天执行的最大次数</span>
            <span class="tab tab-enable">状态</span>
            <span class="tab tab-edit">操作</span>
        </p>
        <?php if ($data) {
            foreach ($data as $item) {
                if (!isset($behaviorNameMap[$item['behavior']])) {
                    continue;
                }
                ?>
                <p class="item item-show" data-behavior="<?php echo $item['behavior']; ?>">
                    <!--                    <span class="tab tab-num">-->
                    <?php //echo $item['behavior']; ?><!--</span>-->
                    <span class="tab tab-name"><?php echo $behaviorNameMap[$item['behavior']]; ?></span>

                    <span class="tab tab-permanent"><?php echo $item['is_permanent'] == 1 ? '永久' : '当日'; ?></span>
                    <span class="tab tab-quantity"><?php echo $item['add_count']; ?></span>
                    <span class="tab tab-term"><?php echo $termNameMap[$item['term']]; ?></span>
                    <span class="tab tab-limit"><?php echo $item['limit_count']; ?></span>
                    <span class="tab tab-enable"><?php echo $item['enable'] == 1 ? '有效' : '禁用'; ?></span>
                    <span class="tab tab-edit"><a href="javascript:;" data-behavior="<?php echo $item['behavior']; ?>"
                                                  class="editRowBtn btn btn-minier btn-purple"
                                                  style="color: #666;">编辑</a></span>
                </p>
                <p class="item item-edit" data-behavior="<?php echo $item['behavior']; ?>"
                   data-id="<?php echo $item['id']; ?>">
                    <!--                    <span class="tab tab-num">-->
                    <?php //echo $item['behavior']; ?><!--</span>-->
                    <span class="tab tab-name"><?php echo $behaviorNameMap[$item['behavior']]; ?></span>
                      <span class="tab tab-permanent">
                          <select name="permanent" class="permanent">
                              <option value="0" <?php echo $item['is_permanent'] == 0 ? 'selected' : '' ?>>当日</option>
                              <option value="1" <?php echo $item['is_permanent'] == 1 ? 'selected' : '' ?>>永久</option>
                          </select>
                    </span>
                    <span class="tab tab-point">
                        <input class="txt quantity" type="text" value="<?php echo $item['add_count']; ?>"
                               style="width: 60px;"/>
                    </span>
                    <span class="tab tab-term">
                        <select name="" id="" class="selc term">
                            <?php foreach ($termNameMap as $k => $v) { ?>
                                <option
                                    value="<?php echo $k; ?>" <?php echo $item['term'] == $k ? 'selected="selected"' : ''; ?>><?php echo $v; ?></option>
                            <?php } ?>
                        </select>
                    </span>


                       <span class="tab tab-limit">
                        <input class="txt limit" type="text" value="<?php echo $item['limit_count']; ?>"
                               style="width: 60px;"/>
                    </span>
                    <span class="tab tab-enable">
                        <select name="enable" class="enable">
                            <option value="0" <?php echo $item['enable'] == 0 ? 'selected' : '' ?>>禁用</option>
                            <option value="1" <?php echo $item['enable'] == 1 ? 'selected' : '' ?>>有效</option>
                        </select>
                    </span>

                    <span class="tab tab-edit"> <a href="javascript:;" data-id="<?php echo $item['id']; ?>"
                                                   data-behavior="<?php echo $item['behavior']; ?>"
                                                   class="saveRowBtn  btn btn-minier btn-success" style="color: #666;">保存</a>
                    </span>
                </p>
                <?php
            }
        } ?>

        <?php if ($new_add) {
            foreach ($new_add as $k => $v) {
                ?>
                <p class="item item-show" data-behavior="<?php echo $k; ?>">
                    <span class="tab tab-num"><?php echo $k; ?></span>
                    <span class="tab tab-name"><?php echo $v; ?></span>
                    <span class="tab tab-point"><?php echo 0; ?></span>
                    <span class="tab tab-term"></span>
                    <span class="tab tab-limit"><?php echo 0; ?></span>
                    <span class="tab tab-edit"><a href="javascript:;" class="editRowBtn btn btn-minier btn-purple"
                                                  data-behavior="<?php echo $k; ?>"
                                                  style="color: #666;"
                        >编辑</a></span>
                </p>
                <p class="item item-edit" data-behavior="<?php echo $k; ?>">
                    <span class="tab tab-num"><?php echo $k; ?></span>
                    <span class="tab tab-name"><?php echo $v ?></span>
                    <span class="tab tab-point">
                        <input class="txt quantity" type="text" value="<?php echo 0; ?>" style="width: 60px;"/>
                    </span>
                    <span class="tab tab-term">
                        <select name="" id="" class="selc term">
                            <?php foreach ($termNameMap as $key => $val) { ?>
                                <option
                                    value="<?php echo $key; ?>"><?php echo $val; ?></option>
                            <?php } ?>
                        </select>
                    </span>
                      <span class="tab tab-limit">
                        <input class="txt limit" type="text" value="<?php echo 0; ?>" style="width: 60px;"/>
                    </span>
                    <span class="tab tab-edit"> <a href="javascript:;" data-behavior="<?php echo $k; ?>"
                                                   class="saveRowBtn  btn btn-minier btn-success" style="color: #666;">保存</a></span>
                </p>
                <?php
            }
        } ?>

    </form>
</section>
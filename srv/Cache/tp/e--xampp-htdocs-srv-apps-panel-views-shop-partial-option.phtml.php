<?php foreach( $categorys as $category) {?>
<option value="<?= $category['id'] ?>"><?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',$category['level']) ?><?= $category['name'] ?></option>
    <?php
        if( isset($category['children']) )
            $this->partial('shop/partial/option',['categorys' => $category['children']]);
    ?>
<?php } ?>
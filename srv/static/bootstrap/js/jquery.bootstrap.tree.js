/**
 * Created by wgwang on 14-4-4.
 */
$(document).ready(function () {
    $('.tree > ul').attr('role', 'tree').find('ul').attr('role', 'group');
    $('.tree').find('li:has(ul)').addClass('parent_item').attr('role', 'treeitem').find(' > span').attr('title', 'Collapse this branch');
    $('.tree').delegate('.tree-title', 'click', function (e) {
        var isShown = $(this).attr('data-show-subs');
        if (isShown > 0) {
            $(this).attr('data-show-subs', 0);
            $(this).parent().find('ul[role=group]').hide('slow');
        }
        else {
            $('.tree ul[role=group]').hide('slow');
            $(this).parent().find('ul[role=group]').show('slow');
            $('.tree .tree-title').attr('data-show-subs', 0);
            $(this).attr('data-show-subs', 1);
        }
        /*
         var children = $(this).parent().find(' > ul > li');
         if (children.is(':visible')) {
         children.hide('fast');
         $(this).attr('title', 'Expand this branch').find(' > i').addClass('icon-plus-sign').removeClass('icon-minus-sign');
         }
         else {
         children.show('fast');
         $(this).attr('title', 'Collapse this branch').find(' > i').addClass('icon-minus-sign').removeClass('icon-plus-sign');
         }
         */
        e.stopPropagation();
    });
    $('.tree ul[role=group]').hide(); //hide sub items by default
});

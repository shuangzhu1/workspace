<?php
namespace Util;


/**
 * Class Pagination
 * @package Library\Util
 *
 */
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\View;

/**
 * 用法：
 * 1. fst step
 * in xxxAction:
 * get param
 * $page = $this->request->get('p', 0);
 * $curpage = $page <= 0 ? 0 : $page - 1;
 * $limit = 20;
 * $count // get total count
 * Pagination::instance($this->view)->showPage($page, $count, $limit);
 * 2. sec step
 * in view xxx.php file
 * <?php \App\Admin\Lib\Paged::display($this);?>
 */
class Pagination extends Plugin
{
    protected static $view = null;


    public function __construct(View $view)
    {
        self::$view = $view;
    }

    public static function instance(View $view)
    {
        static $obj;
        if (!$obj) $obj = new self($view);
        return $obj;
    }


    // set pages to view

    public function showPage($page, $count, $limit = 14, $range = 6)
    {
        $total = ceil($count / $limit);
        // 总页数
        $page = $page > $total ? $total : $page;
        $page = $page <= 0 ? 1 : $page;
        // 上一页
        if ($page > 1) {
            self::$view->previous = $page - 1;
            self::$view->first = 1;
        }
        // 下一页
        if ($total > $page) {
            self::$view->next = $page + 1;
            self::$view->last = $total;
        }
        self::$view->current = $page;
        // $range表示显示条数的一半-1
        if ($page <= $range) {
            if ($total > $range * 2) {
                $pagesInRange = $this->getPagesInRange(1, $range * 2);
            } else {
                $pagesInRange = $this->getPagesInRange(1, $total);
            }

        } elseif ($total - $page < $range) {
            $pagesInRange = $this->getPagesInRange($total - $range * 2, $total);
        } else {
            $pagesInRange = $this->getPagesInRange($page - $range, $page + $range);
        }

        self::$view->pagesInRange = $pagesInRange;
        self::$view->total = $total;
        self::$view->page = $page;
        self::$view->perpage = $limit;
        self::$view->pageCount = $count;
    }

    // page range
    private function getPagesInRange($lowerBound, $upperBound)
    {
        $pages = array();
        for ($pageNumber = $lowerBound; $pageNumber <= $upperBound; $pageNumber++) {
            $pages [$pageNumber] = $pageNumber;
        }
        return $pages;
    }

    // display
    public final function display(View $view, $noMore = true)
    {

        $str = '<div class="pagination_wrap">';
        if (isset($view->total)) {

            if ($view->total && $noMore) {
                $str .= '<span style="float: right;" class="desc">总' . $view->pageCount . '条记录,每页<input class="page_limit" type="text" id="limit" value="' . $view->perpage . '"/>条记录,总' . $view->total . '页,
      当前<input type="text" id="page" value="' . $view->page . '" class="page_limit"/>' . '/' . $view->total . '页</span>';
            }

            // first
            if (isset($view->previous)) {
                $str .= '<a href="' . $this->uri->setUrl('?p=' . $view->first) . '"> 首页 </a>';
            } else {
                $str .= '<span class="disabled paginate_button"><a href="javascript:;">首页</a></span>';
            }

            // previous
            if (isset($view->previous)) {
                $str .= '<a href="' . $this->uri->setUrl('?p=' . $view->previous) . '">上一页</a>';
            } else {
                $str .= '<span class="disabled"><a href="javascript:;">上一页</a></span>';
            }

            // Numbered p links
            foreach ($view->pagesInRange as $page) {
                if ($page > 0) {
                    if ($page != $view->current) {
                        $str .= '<a href="' . $this->uri->setUrl('?p=' . $page) . '">' . $page . '</a>';
                    } else {
                        $str .= '<a href="javascript:;" class="disabled current">' . $view->current . '</a>';
                    }
                };
            }


            // next
            if (isset($view->next)) {
                $str .= '<a href="' . $this->uri->setUrl('?p=' . $view->next) . '">下一页 </a>';
            } else {
                $str .= '<span class="disabled"><a href="javascript:">下一页</a></span>';
            }

            // last
            if (isset($view->next)) {
                $str .= '<a class="last" href="' . $this->uri->setUrl('?p=' . $view->last) . '">尾页 </a>';
            } else {
                $str .= '<span class="disabled last"><a href="javascript:;" class="disabled">尾页</a></span>';
            }

            echo $str, '</div>';

        }
    }

    public static function getAjaxPageBar($count, $page, $limit, $show_limit = 5)
    {
        $res = "";
        if ($count == 0) {
            return $res;
        }
        $total_page = ceil($count / $limit);

        $res = '<ul class="pagination">';
        //----上一页
        if ($page == 1) {
            $res .= '<li class="disabled"> <a href="javascript:;" data-id="1">上一页</a></li>';
        } else {
            $res .= '<li> <a href="javascript:;" data-id="' . ($page - 1) . '">上一页</a></li>';
        }
        //----中间部分

        //后面部分够显示，从当前页往后推
        if (($page + $show_limit) <= $total_page) {
            $i = 0;
            while ($i < $show_limit) {
                $res .= '<li ' . (($i + $page) == $page ? "class='active'" : '') . '> <a href="javascript:;" data-id="' . ($i + $page) . '">' . ($i + $page) . '</a></li>';
                $i += 1;
            }
        } //后面部分不够显示,
        else {
            $left_page = $show_limit - ($total_page - $page);//前面还需要显示的页数
            $start_page = $page - $left_page + 1 > 0 ? $page - $left_page + 1 : 1;
            for ($i = $start_page; $i <= $total_page; $i++) {
                $res .= '<li ' . ($i == $page ? "class='active'" : '') . '> <a href="javascript:;" data-id="' . ($i) . '">' . ($i) . '</a></li>';
            }
        }
        //----下一页
        if ($page == $total_page) {
            $res .= '<li class="disabled"> <a href="javascript:;" data-id="' . $total_page . '">下一页</a></li>';
        } else {
            $res .= '<li> <a href="javascript:;" data-id="' . ($page + 1) . '">下一页</a></li>';
        }
        $res .= '<li  class="disabled"><a href="javascript:;">共' . $total_page . '页</a></li>';
        $res .= '<li  class="disabled"><a href="javascript:;">共' . $count . '条数据</a></li>';

        $res .= '</ul>';
        return $res;
    }

    public static function getAjaxListPageBar($count, $page, $limit, $show_limit = 5)
    {
        $res = "";
        if ($count == 0) {
            return $res;
        }
        $total_page = ceil($count / $limit);

        $res = '<ul class="pagination list_pagination">';
        //----上一页
        if ($page == 1) {
            $res .= '<li class="disabled"> <a href="javascript:;" data-id="1">首页</a></li>';
            $res .= '<li class="disabled"> <a href="javascript:;" data-id="1">上一页</a></li>';
        } else {
            $res .= '<li> <a href="javascript:;" data-id="1">首页</a></li>';
            $res .= '<li> <a href="javascript:;" data-id="' . ($page - 1) . '">上一页</a></li>';
        }
        //----中间部分

        //后面部分够显示，从当前页往后推
        if (($page + $show_limit) <= $total_page) {
            $i = 0;
            while ($i < $show_limit) {
                $res .= '<li ' . (($i + $page) == $page ? "class='active'" : '') . '> <a href="javascript:;" data-id="' . ($i + $page) . '">' . ($i + $page) . '</a></li>';
                $i += 1;
            }
        } //后面部分不够显示,
        else {
            $left_page = $show_limit - ($total_page - $page);//前面还需要显示的页数
            $start_page = $page - $left_page + 1 > 0 ? $page - $left_page + 1 : 1;
            for ($i = $start_page; $i <= $total_page; $i++) {
                $res .= '<li ' . ($i == $page ? "class='active'" : '') . '> <a href="javascript:;" data-id="' . ($i) . '">' . ($i) . '</a></li>';
            }
        }
        //----下一页
        if ($page == $total_page) {
            $res .= '<li class="disabled"> <a href="javascript:;" data-id="' . $total_page . '">下一页</a></li>';
            $res .= '<li class="disabled"> <a href="javascript:;" data-id="' . $total_page . '">尾页</a></li>';

        } else {
            $res .= '<li> <a href="javascript:;" data-id="' . ($page + 1) . '">下一页</a></li>';
            $res .= '<li> <a href="javascript:;" data-id="' . $total_page . '">尾页</a></li>';
        }
        $res .= '<li  class="disabled"><a href="javascript:;">共' . $total_page . '页</a></li>';
        $res .= '<li  class="disabled"><a href="javascript:;">共' . $count . '条数据</a></li>';
        $res .= '</ul>';
        $res .= '<ul class="limitBar">当前第<input type="text" class="page" data-limit="'.$total_page.'" size="3" value="' . $page . '"/>页/每页<input class="page_limit" type="text" size="3" value="' . $limit . '">条数据</ul>';

        return $res;
    }

}
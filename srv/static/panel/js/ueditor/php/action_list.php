<?php
$start = $this->request->get('start');
$size = 20;
$page = ($start + $size) / $size;
$page = $page ? $page : 1;
$count  = \Models\Site\SiteStorage::dataCount(['folder = 1 and type = "img"']);
$items = \Models\Site\SiteStorage::findList(['folder = 1 and type = "img"','limit' => $size,'offset' => ($page-1)*$size ,'order' => 'created desc']);
$items = \Models\Site\SiteStorage::findList(['folder = 1 and type = "img"','limit' => $size,'offset' => ($page-1)*$size ,'order' => 'created desc']);
$list = [];
if( $items )
{

    foreach( $items as $k => $item)
    {
        $list[$k]['mtime'] = $item['created'];
        $list[$k]['url'] = "http://circleimg.klgwl.com/" . $item['name'];
    }
}
$result = json_encode(array(
    "state" => "SUCCESS",
    "list" => $list,
    "start" => $start,
    "total" => $count
));
echo $result;exit;
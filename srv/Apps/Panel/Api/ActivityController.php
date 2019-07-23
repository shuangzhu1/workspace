<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/10
 * Time: 18:15
 */

namespace Multiple\Panel\Api;


use Util\Ajax;
use Util\LatLng;

class activityController extends ApiBase
{
    public function summaryAction()
    {
        $type = 'activity';
        $begin = $this->request->get('begin');
        $end = $this->request->get('end');
        $data = $this->postApi('forms/detail',['type' => $type,'begin' => $begin,'end' => $end]);
        Ajax::init()->outRight($data);
    }

    public function trendAction()
    {
        $type = $this->request->get('type','string','');
        //默认统计时间段为一周
        $start = $this->request->get('start','string',date('Ymd',strtotime('-7 day')));
        $end = $this->request->get('end','string',date('Ymd',strtotime('-1 day')));
        //$activitys = $this->postApi('forms/detail',['type' => 'activity','begin' => $start , 'end' => $end])['form']['activitys'];
        $sql = "select DATE_FORMAT(created,'%Y/%m/%d') as created,data from activity_form where created >= " . date('Ymd',strtotime($start)) . " and created <= " . date('Ymd',strtotime($end));
        $activities = $this->forms->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if( $activities )
        {
            $data_total = $data_redbag = $data_qa = [];
            foreach( $activities as $v)
            {
                $data_total[$v['created']] = json_decode($v['data'],JSON_UNESCAPED_UNICODE)['form']['activitys'][0];//所有活动统计数据
                $data_redbag[$v['created']] = json_decode($v['data'],JSON_UNESCAPED_UNICODE)['form']['activitys'][1];//红包雨活动统计数据
                $data_qa[$v['created']] = json_decode($v['data'],JSON_UNESCAPED_UNICODE)['form']['activitys'][2];//红包雨活动统计数据
            }

            $res = [];
            switch( $type )
            {
                case 'join_user':
                    $res['xAxis'] = array_keys($data_total);
                    $res['total']['value'] = array_column(array_column($data_total,'user_detail'),'user_count');
                    $res['redbag']['value'] = array_column(array_column($data_redbag,'user_detail'),'user_count');
                    $res['qa']['value'] = array_column(array_column($data_qa,'user_detail'),'user_count');
                    break;
                case 'platform_income':
                    $res['xAxis'] = array_keys($data_total);
                    foreach( ['total' => 'data_total','redbag' => 'data_redbag','qa' => 'data_qa'] as $k => $v )
                    {
                        $tmp1 = array_column(array_column($$v,'money_detail'),'official_take_user');
                        $tmp2 = array_column(array_column($$v,'money_detail'),'user_take_official');
                        array_walk($tmp1,function(&$v,$k,$tmp2){
                            $v = ($v - $tmp2[$k]) / 100;
                        },$tmp2);
                        $res[$k]['value'] = $tmp1;
                    }
                    break;
                case 'ac_count':
                    $res['xAxis'] = array_keys($data_total);

                    foreach( ['total' => 'data_total','redbag' => 'data_redbag','qa' => 'data_qa'] as $k => $v )
                    {
                        $tmp = array_column($$v,'user_launch');
                        array_walk($tmp,function(&$v){
                            $v = array_sum($v);
                        });
                        $res[$k]['value'] = $tmp;
                    }
                    break;
                case 'ac_money':
                    $res['xAxis'] = array_keys($data_total);
                    foreach( ['total' => 'data_total','redbag' => 'data_redbag','qa' => 'data_qa'] as $k => $v )
                    {
                        $tmp = array_column(array_column($$v,'money_detail'),'user_money');
                        array_walk($tmp,function(&$v){
                            $v = $v / 100;
                        });
                        $res[$k]['value'] = $tmp;
                    }
                    break;
                case 'ac_count_platform':
                    $res['xAxis'] = array_keys($data_total);

                    foreach( ['total' => 'data_total','redbag' => 'data_redbag','qa' => 'data_qa'] as $k => $v )
                    {
                        $tmp = array_column($$v,'official_launch');
                        array_walk($tmp,function(&$v){
                            $v = array_sum($v);
                        });
                        $res[$k]['value'] = $tmp;
                    }
                    break;
                case 'ac_money_platform':
                    $res['xAxis'] = array_keys($data_total);
                    foreach( ['total' => 'data_total','redbag' => 'data_redbag','qa' => 'data_qa'] as $k => $v )
                    {
                        $tmp = array_column(array_column($$v,'money_detail'),'official_money');
                        array_walk($tmp,function(&$v){
                            $v = $v / 100;
                        });
                        $res[$k]['value'] = $tmp;
                    }
                    break;
                default:
                    Ajax::init()->outError(Ajax::INVALID_PARAM,'数据类型错误');
            }
        }


        Ajax::init()->outRight($res);
    }

    public function coordinateAction()
    {
        $lng = $this->request->get('lng');
        $lat = $this->request->get('lat');
        $res = LatLng::getAddress($lng,$lat,'gaode');
        $data['province'] = is_string($res['province']) ? $res['province'] : '未知';
        $data['city'] = is_string($res['city']) ? $res['city'] : '未知';
        $data['district'] = is_string($res['district']) ? $res['district'] : '未知';
        Ajax::outRight($data);
    }
}
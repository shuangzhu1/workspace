<?php
namespace Download;

use Phalcon\Mvc\User\Plugin;

/*批量导出csv文件*/

class Csv extends Plugin
{
    public $filename;//文件名称
    public $header; //csv头部字段名称
    public $result; //显示的数据

    public function  __construct()
    {

    }

    public static function init()
    {
        return new self();
    }

    public function  export_csv($header, $result, $filename)
    {
        if ($filename == '') {
            $this->filename = time() . '.csv';
        } else {
            $this->filename = $filename . '.csv';
        }
        if ($header == '' || $result == '') {
            echo "illegal params";
            exit;
        }
        $this->header = $this->data_iconv($header);
        $this->result = $result;
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $this->filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $this->array_to_string();
    }

    public function array_to_string()
    {
        if (empty($this->result)) {
            return $this->data_iconv("没有符合您要求的数据！^_^");
        }
        $data = $this->header . "\n"; //栏目名称
        //$size_result = sizeof($result);
        foreach ($this->result as $k => $v) {
            $data .= $this->data_iconv(implode(',', $v)) . "\n";
        }
        return $data;
    }

    public function data_iconv($strInput)
    {
        return $strInput;
    //    return iconv('utf-8', 'gb2312', $strInput);//页面编码为utf-8时使用，否则导出的中文为乱码
    }

}


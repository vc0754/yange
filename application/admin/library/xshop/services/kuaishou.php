<?php

namespace app\admin\library\xshop\services;

use fast\Http;
use \think\Exception;

class kuaishou
{
    protected $config = [];

    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    public function getHot()
    {

        $html = $this->sendCmd($this->config['url']);
        $table = $this->getTable($html);
        $date = date("Y-m-d");
        foreach ($table as $key=>$value){
            $table[$key]['_id'] = 'ks'.trim($value[1]).$date;
            $table[$key]['word'] = trim($value[1]);
            $table[$key]['hot_value'] = trim($value[2]);
            $table[$key]['top_ranking'] = $key+1;
            $table[$key]['date'] = $date;
            $table[$key]['platform'] = $this->config['id'];
        }
        return $table;
    }

    /**
     * 发起请求
     * @param  string $url 请求地址
     * @param  string $data 请求数据包
     * @return   string      请求返回数据
     */
    public function sendCmd($url)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检测
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //解决数据包大不能提交
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $tmpInfo; // 返回数据
    }


    public function getTable($html)
    {
        preg_match_all("/<tbody>(.*)<\/tbody>/is", $html, $table);

        $table = $table[0][0];
        $table = preg_replace("'<table[^>]*?>'si", "", $table);
        $table = preg_replace("'<tr[^>]*?>'si", "", $table);
        $table = preg_replace("'<td[^>]*?>'si", "", $table);
        $table = str_replace("</tr>", "{tr}", $table);
        $table = str_replace("</td>", "{td}", $table);
        //去掉 HTML 标记
        $table = preg_replace("'<[/!]*?[^<>]*?>'si", "", $table);
        //去掉空白字符
        $table = preg_replace("'([rn])[s]+'", "", $table);
        $table = str_replace(" ", "", $table);
        $table = str_replace(" ", "", $table);
        $table = explode('{tr}', $table);
        array_pop($table);
        $td_array = [];
        foreach ($table as $key => $tr) {
            if($key<50){
                // 自己可添加对应的替换
                $tr = str_replace("\n\n", "", $tr);
                $td = explode('{td}', $tr);
                array_pop($td);

                $td_array[] = $td;
            }

        }
        return $td_array;
    }
}
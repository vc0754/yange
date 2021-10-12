<?php

namespace app\admin\library\xshop\services;

use fast\Http;
use phpQuery;
use \think\Exception;

class weibo
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

        $html = $this->sendCmd($this->config['url'], array());
        var_dump('1230');
        $table = $this->getTable($html);
        $table = array_slice($table, 2); # 把前面多余部分截掉
        $date = date("Y-m-d");
        foreach ($table as $key=>$value){
            $table[$key]['_id'] = 'wb'.(explode("\n",$value[1])[0]).$date;
            $table[$key]['word'] = (explode("\n",$value[1])[0]);
            $table[$key]['hot_value'] = (explode("\n",$value[1])[1]);
            $table[$key]['top_ranking'] = $value[0]-1;
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
    public function sendCmd($url, $data)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检测
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //解决数据包大不能提交
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
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

    public function getUrlContent($url)
    {//通过url获取html内容
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1 )");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function getTable($html)
    {
        preg_match_all("/<tbody>[\s\S]*?<\/tbody>/i", $html, $table);
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
            // 自己可添加对应的替换
            $tr = str_replace("\n\n", "", $tr);
            $td = explode('{td}', $tr);
            array_pop($td);

            $td_array[] = $td;
        }
        return $td_array;
    }
}
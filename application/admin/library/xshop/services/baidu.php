<?php

namespace app\admin\library\xshop\services;

use fast\Http;
use \think\Exception;

class baidu
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
        foreach ($table as $key => $value) {
            $table[$key]['_id'] = 'bd' . $value['word'] . $date;
            $table[$key]['word'] = $value['word'];
            $table[$key]['hot_value'] = $value['hot_value'];
            $table[$key]['top_ranking'] = $key + 1;
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
//        $tmpInfo = mb_convert_encoding($tmpInfo, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
        if (curl_errno($curl)) {
            echo 'Errno' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $tmpInfo; // 返回数据
    }

    public function getTable($html)
    {
        preg_match_all("/<table>(.*)<\/table>/is", $html, $table);
        $table = $table[0][0];
        $templateRss = str_replace("&", "&amp;", $table);
        $templateRss = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $templateRss;

        $xml = simplexml_load_String($templateRss);

        foreach ($xml->tbody->tr as $key => $temp) {

            if (!empty ($temp->td->a)) {
                $newData = [
                    'word' => trim(($temp->td->a)),
                    'hot_value' => trim(($temp->td[1])),
                ];
                $td_array [] = $newData;
            }

        }
        return $td_array;
    }
}
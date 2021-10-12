<?php

namespace app\admin\library\xshop\services;

use fast\Http;
use \think\Exception;

class toutiao
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
        $data = $this->sendCmd($this->config['url']);
        $data = json_decode($data, true);
        $data = $data['data'];
        $date = date("Y-m-d");

        foreach ($data as $key=>$value){
            $data[$key]['_id'] = 'tt'.$value['Title'].$date;
            $data[$key]['word'] = $value['Title'];
            $data[$key]['hot_value'] = $value['HotValue'];
            $data[$key]['top_ranking'] = $key+1;
            $data[$key]['date'] = $date;
            $data[$key]['platform'] = $this->config['id'];
        }
        return $data;
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
}
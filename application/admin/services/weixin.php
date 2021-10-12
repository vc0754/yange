<?php
namespace app\admin\services;

use Error;
use ErrorException;
use Exception;
use phpQuery;
use think\exception\ErrorException as ExceptionErrorException;

class weixin {
  
  private $appID = 'wx2ab50c0c62047eff';
  private $appSecret = '30c08e53e1d0750a91e7853ac3897f5a';
  private $url = 'http://mi6.tv/addons/xshop/weixin';
  private $token = '888';
  private $encodingAESKey = 'OynBwR2NJfVbZaEjYX75lyMTys4TOtL6Gzai8pfD6JP';

  public function __construct() {
  }

  public function notice($data) {
    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $this->getAccessToken();
    $this->http_request('POST', $url, json_encode($data, JSON_UNESCAPED_UNICODE));

    // $res = 
    // dump($res);
  }

  #
  private function getAccessToken() {
    $token = file_get_contents('weixin_accesstoken.txt');
    $token = json_decode($token);
    if (isset($token->expires) && time() <= $token->expires) {
      return $token->access_token;
    } else {
      $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appID . '&secret=' . $this->appSecret;
      $token = $this->http_request('GET', $url);
      $token = json_decode($token, true);
      if (isset($token['errcode'])) throw new Error($token['errmsg']);
      $token['expires'] = time() + 7000;
      file_put_contents("weixin_accesstoken.txt", json_encode($token));
      return $token['access_token'];
    }
  }

  private function http_request($method = 'POST', $url, $data = '') {
    $ch = curl_init(); 
    // dump($data);
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 

    $info = curl_exec($ch);

    if (curl_errno($ch)) echo 'Errno'.curl_error($ch);

    curl_close($ch);

    return $info;
  }
}
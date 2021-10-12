<?php

namespace addons\xshop\controller;

use addons\xshop\model\VendorModel;
use Error;

use function GuzzleHttp\json_decode;

/**
 * 微信公众号
 */
class Weixin extends Base {
  
  private $appID = 'wx2ab50c0c62047eff';
  private $appSecret = '30c08e53e1d0750a91e7853ac3897f5a';
  private $url = 'http://mi6.tv/addons/xshop/weixin';
  private $token = '888';
  private $encodingAESKey = 'OynBwR2NJfVbZaEjYX75lyMTys4TOtL6Gzai8pfD6JP';

  # 关注/取消关注事件
  # https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Receiving_event_pushes.html
  public function index() {
    $get = $this->request->get();
    
    // file_put_contents("tmp.txt", json_encode($get));

    if ($this->checkFromWeixin()) {
      if (isset($get['echostr'])) {
        echo htmlspecialchars($get['echostr']);
      } else {
        // $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
        $postStr = file_get_contents('php://input');
        file_put_contents('tmp.txt', $postStr);

        if (!empty($postStr)) {
          $xml = simplexml_load_string($postStr);
          
          switch($xml->MsgType) {
            case 'event':
              switch($xml->Event) {
                case 'subscribe':
                  $this->subscribe($xml);
                  break;
                case 'unsubscribe':
                  // file_put_contents('tmp.txt', '取消订阅');
                  echo('success');
                  break;
              }
              break;
            case 'text':
              $this->keywordReply($xml);
              break;
          }
        }

        // $res = $this->getAccessToken();
        // dump($res);

        // $menus = $this->getMenu();
        // dump($menus);

        // $menus = $this->createMenu();
        // dump($menus);
      }
    };


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

  private function checkFromWeixin() {
    $get = $this->request->get();

    $signature = isset($get['signature']) ? $get['signature'] : '';
    $timestamp = isset($get['timestamp']) ? $get['timestamp'] : '';
    $nonce = isset($get['nonce']) ? $get['nonce'] : '';

    $tmpArr = [ $this->token, $timestamp, $nonce ];
    sort($tmpArr, SORT_STRING);

    $tmpStr = implode($tmpArr);
    $tmpStr = sha1($tmpStr);

    return $tmpStr == $signature ? true : false;
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

  # 订阅
  private function subscribe($xml) {
    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $this->getAccessToken() . '&openid=' . $xml->FromUserName . '&lang=zh_CN';
    $user = $this->http_request('GET', $url);
    
    $user = json_decode($user);
    $data = [
      'vendor'  => 'Weixin',
      'unionid' => $user->unionid,
      'openid'  => $user->openid,
      'user_id' => '',
      'nickname'=> $user->nickname,
      'sex'     => $user->sex,
      'headimgurl'  => $user->headimgurl,
      'platform'=> 'WechatH5'
    ];
    $exist = VendorModel::where([
      'openid' => $user->openid,
      'unionid'=> $user->unionid,
      'platform'=> 'WechatH5'
    ])->find();
    if (!$exist) VendorModel::create($data);
    
    $this->attachMessage($xml);
  }
  
  # 获取菜单
  private function getMenu() {
    $url = 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=' . $this->getAccessToken();
    $res = $this->http_request('GET', $url);
    file_put_contents("weixin_menu.txt", $res);
    return json_decode($res, true);
  }

  # 创建菜单
  private function createMenu() {
    $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessToken();

    $data = '{
      "button":[
        {
          "name":"镜头里",
          "sub_button":[
            {	
              "type":"view",
              "name":"影",
              "url":"https://mp.weixin.qq.com/mp/homepage?__biz=MzUzNTA0NDE3NQ==&hid=6&sn=811c437d7d4d324ebf81d44925a0eead&scene=18"
            },
            {	
              "type":"view",
              "name":"剧",
              "url":"https://mp.weixin.qq.com/mp/homepage?__biz=MzUzNTA0NDE3NQ==&hid=7&sn=32cbe0dd9aca72f437c5aab1b5ec6448&scene=18"
            },
            {	
              "type":"view",
              "name":"综",
              "url":"https://mp.weixin.qq.com/mp/homepage?__biz=MzUzNTA0NDE3NQ==&hid=8&sn=84fc1fc666f440eccda873324c8a9097&scene=18"
            }
          ]
        },
        {
          "name":"镜头外",
          "sub_button":[
            {	
              "type":"view",
              "name":"娱宣传",
              "url":"https://docs.qq.com/sheet/DSHNCZ1R4V01xWEVo"
            },
            {	
              "type":"view",
              "name":"需定制",
              "url":"https://docs.qq.com/form/page/DSGJ2VmFWVnlXQUZa?_w_tencentdocx_form=1"
            },
            {	
              "type":"view",
              "name":"新资源",
              "url":"http://mp.weixin.qq.com/s?__biz=MzUzNTA0NDE3NQ==&mid=2247487700&idx=2&sn=b49dcc967d7f93b8c60834d60f571d10&chksm=fa8ad9f4cdfd50e20621c310dd47663f7dce329da2c6631d63f51d1998c9f569af04bccc13e9&scene=18#wechat_redirect"
            }
          ]
        },
        {
          "name":"镜头后",
          "sub_button":[
            // 报错
            {
              "type":"img",
              "name":"谈合作",
              "value":"fqsvFwnxe7Pp1eXMtC773uaqrxHUtAQf2f2pGRSI4Y3fjXaA0nTUJHzDdYqrAIt6"
            },
            {	
              "type":"view",
              "name":"聘人才",
              "url":"http://www.stzp.cn/ent/1892414.html"
            },
            {	
              "type":"view",
              "name":"坐落于",
              "url":"https://map.baidu.com/poi/通发大厦/@12996593.585,2660719.9850000003,19z?uid=638e72cd27264fd8a2046491&primaryUid=10162780305354920956&ugc_type=3&ugc_ver=1&device_ratio=1&compat=1&querytype=detailConInfo&da_src=shareurl"
            },
            {	
              "type":"view",
              "name":"案例展",
              "url":"https://mp.weixin.qq.com/mp/homepage?__biz=MzUzNTA0NDE3NQ==&hid=9&sn=1bcd9fac8673e00399b76b30bbc5ed01&scene=18"
            }
          ]
        }
      ]
    }';
    
    return $this->http_request('POST', $url, $data);
  }

  # 删除菜单
  private function deleteMenu() {}

  # 关注时回复
  private function attachMessage($xml) {
    echo('<xml>
      <ToUserName><![CDATA[' . $xml->FromUserName . ']]></ToUserName>
      <FromUserName><![CDATA[' . $xml->ToUserName . ']]></FromUserName>
      <CreateTime>' . time() . '</CreateTime>
      <MsgType><![CDATA[text]]></MsgType>
      <Content><![CDATA[你好，这里是东晟传媒，感谢你的关注！
了解东晟传媒更多相关信息，请点击下方菜单栏哦噢~
回复“招聘启事”了解更多招聘信息~]]></Content>
    </xml>');
  }

  # 关键词回复
  private function keywordReply($xml) {
    if ($xml->Content == '招聘启事') {
      echo('<xml>
        <ToUserName><![CDATA[' . $xml->FromUserName . ']]></ToUserName>
        <FromUserName><![CDATA[' . $xml->ToUserName . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>1</ArticleCount>
        <Articles>
          <item>
            <Title><![CDATA[招聘启事]]></Title>
            <Description><![CDATA[东晟传媒喊你一起搞事情！]]></Description>
            <PicUrl><![CDATA[picurl]]></PicUrl>
            <Url><![CDATA[url]]></Url>
          </item>
        </Articles>
      </xml>');
    } else {
      echo('success');
    }
  }


  # 消息通知测试
  public function notice() {
    $get = $this->request->get();
    $openid = $get['openid'];
    $template_ids = [
      'kbZQH5ALskLTLfWdyzvbEeeGcVRW5WZqmBp1QuuhvxY',  // 订单处理完成通知
      'ATSN8Y_BGumPAeV2e3xlmD6Xw_pLlZQumVJ96fipMlg',  // 底片下载
      'R2O5s_v-zk74L9c1wT8QerNGbSk9kCS1Zimh27HOgV0',  // 下单成功
      '9Lrxea45JsTOKci06YrgIcprcRG9-sdiyUdHR-GWeqc',  // 提现成功
    ];
    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $this->getAccessToken();
    $data = '{
      "touser":"' . $openid . '",
      "template_id":"' . $template_ids[0] . '",
      "url":"http://weixin.qq.com/download",
      "miniprogram":{
        "appid":"wx081e2d63a5efba6c",
        "pagepath":"pages/mission/index"
      },          
      "data":{
        "first": {
          "value":"您的订单已处理完成"
        },
        "keyword1":{
          "value":"《#》"
        },
        "keyword2": {
          "value":"2021年9月1日"
        },
        "keyword3": {
          "value":"已完成"
        },
        "keyword4": {
          "value":"2021年9月14日"
        },
        "remark":{
          "value":"请查看详情并确认完成"
        }
      }
    }';
    $res = $this->http_request('POST', $url, $data);

    dump($res);
    
    // 模版ID
    // kbZQH5ALskLTLfWdyzvbEeeGcVRW5WZqmBp1QuuhvxY
    // 开发者调用模版消息接口时需提供模版ID
    // 标题
    // 订单处理完成通知
    // 行业
    // 文体娱乐 - 文化|传媒
    // 详细内容
    // {{first.DATA}}
    // 订单名称：{{keyword1.DATA}}
    // 下单时间：{{keyword2.DATA}}
    // 订单状态：{{keyword3.DATA}}
    // 完成时间：{{keyword4.DATA}}
    // {{remark.DATA}}

    // 模版ID
    // R2O5s_v-zk74L9c1wT8QerNGbSk9kCS1Zimh27HOgV0
    // 开发者调用模版消息接口时需提供模版ID
    // 标题
    // 下单成功通知
    // 行业
    // 文体娱乐 - 文化|传媒
    // 详细内容
    // {{first.DATA}}
    // 订单号：{{keyword1.DATA}}
    // 商品信息：{{keyword2.DATA}}
    // 订单金额：{{keyword3.DATA}}
    // {{remark.DATA}}

    // 模版ID
    // ATSN8Y_BGumPAeV2e3xlmD6Xw_pLlZQumVJ96fipMlg
    // 开发者调用模版消息接口时需提供模版ID
    // 标题
    // 底片下载通知
    // 行业
    // 文体娱乐 - 文化|传媒
    // 详细内容
    // {{first.DATA}}
    // 订单编号：{{keyword1.DATA}}
    // 完成时间：{{keyword2.DATA}}
    // {{remark.DATA}}

    // 模版ID
    // 9Lrxea45JsTOKci06YrgIcprcRG9-sdiyUdHR-GWeqc
    // 开发者调用模版消息接口时需提供模版ID
    // 标题
    // 提现成功通知
    // 行业
    // 文体娱乐 - 文化|传媒
    // 详细内容
    // {{first.DATA}}
    // 提现金额：{{keyword1.DATA}}
    // 回执单号：{{keyword2.DATA}}
    // {{remark.DATA}}
    // 在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
  }
}

// 附 - 微信通知格式预览
// 关注
// <xml><ToUserName><![CDATA[gh_90981bdf2a82]]></ToUserName>
// <FromUserName><![CDATA[oT3600hn-2KBfrR7CqF_97RzRMwE]]></FromUserName>
// <CreateTime>1631587452</CreateTime>
// <MsgType><![CDATA[event]]></MsgType>
// <Event><![CDATA[subscribe]]></Event>
// <EventKey><![CDATA[]]></EventKey>
// </xml>

// 取消关注
// <xml><ToUserName><![CDATA[gh_90981bdf2a82]]></ToUserName>
// <FromUserName><![CDATA[oT3600hn-2KBfrR7CqF_97RzRMwE]]></FromUserName>
// <CreateTime>1631587392</CreateTime>
// <MsgType><![CDATA[event]]></MsgType>
// <Event><![CDATA[unsubscribe]]></Event>
// <EventKey><![CDATA[]]></EventKey>
// </xml>

// 发送招聘
// <xml><ToUserName><![CDATA[gh_90981bdf2a82]]></ToUserName>
// <FromUserName><![CDATA[oT3600hn-2KBfrR7CqF_97RzRMwE]]></FromUserName>
// <CreateTime>1631589239</CreateTime>
// <MsgType><![CDATA[text]]></MsgType>
// <Content><![CDATA[招聘]]></Content>
// <MsgId>23358738769930624</MsgId>
// </xml>

// 自定义菜单点击后事件
// {
//   "type":"click",
//   "name":"镜头里",
//   "key":"V1001_TODAY_MUSIC"
// },
// <xml><ToUserName><![CDATA[gh_90981bdf2a82]]></ToUserName>
// <FromUserName><![CDATA[oT3600hn-2KBfrR7CqF_97RzRMwE]]></FromUserName>
// <CreateTime>1631588519</CreateTime>
// <MsgType><![CDATA[event]]></MsgType>
// <Event><![CDATA[CLICK]]></Event>
// <EventKey><![CDATA[V1001_TODAY_MUSIC]]></EventKey>
// </xml>

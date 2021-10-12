<?php
namespace app\admin\services;

use Error;
use ErrorException;
use Exception;
use phpQuery;
use Nesk\Puphpeteer\Puppeteer;
use think\exception\ErrorException as ExceptionErrorException;

class crawler {
  protected $url;

  public function __construct($url) {
    $this->url = $url;
  }

  public function parse() {
    $method = '';

    if (strrpos($this->url, 'zhihu.com/')) {
      $method = 'zhihu';
    } else if (strrpos($this->url, 'douyin.com/')) {
      $method = 'douyin';
    } else if (strrpos($this->url, 'douban.com/')) {
      $method = 'douban';
    } else if (strrpos($this->url, 'b23.tv/')) {
      $method = 'bilibili';
    } else if (strrpos($this->url, 'zjurl.cn/')) {
      // $method = 'toutiao';
    } else if (strrpos($this->url, 'weibo.com/')) {
      // $method = 'weibo';
    } else if (strrpos($this->url, 'kuaishou.com/')) {
      // $method = 'kuaishou';
    }

    if (empty($method)) throw new Error('路径不支持');
    
    try {
      $data = $this->$method();
    } catch (ExceptionErrorException $e) {
      throw new Error('解析失败');
    }

    return $data;
  }

  public function parse_post() {
    $method = '';

    if (strrpos($this->url, 'zhihu.com/')) {
      // $method = 'zhihu';
    } else if (strrpos($this->url, 'douyin.com/')) {
      $method = 'm_douyin';
    } else if (strrpos($this->url, 'douban.com/')) {
      // $method = 'douban';
    } else if (strrpos($this->url, 'b23.tv/')) {
      // $method = 'bilibili';
    } else if (strrpos($this->url, 'zjurl.cn/')) {
      // $method = 'toutiao';
    } else if (strrpos($this->url, 'm.weibo.cn/')) {
      $method = 'm_weibo';
    } else if (strrpos($this->url, 'kuaishou.com/')) {
      // $method = 'kuaishou';
    }

    if (empty($method)) throw new Error('路径不支持');
    
    try {
      $data = $this->$method();
    } catch (ExceptionErrorException $e) {
      throw new Error('解析失败');
    }

    return $data;
  }
  
  public function zhihu() {
    require_once('phpQuery/phpQuery.php');
    // phpQuery::$debug = true;

    $result = phpQuery::newDocumentFile($this->url);
    
    $nickname = pq('.ProfileHeader-name', $result)->contents()->not('style, span')->html();
    $avatar = pq('img', $result)->attr('src');
    $authTitle = '';

    $authInfo = pq('.Profile-sideColumn .Card:eq(0) div:not(.Card-header) div:eq(0) div:eq(1) a', $result)->html();
    if ($authInfo === '认证信息') {
        $authTitle = pq('.Profile-sideColumn .Card:eq(0) div:not(.Card-header) div:eq(0) div:eq(1) div', $result)->html();
    }

    $fans = pq('.NumberBoard-itemValue', $result)->filter(function($index){
        return $index === 1;
    })->html();

    return [
      'from'      => '知乎',
      'nickname'  => $nickname,
      'avatar'    => $avatar,
      'fans'      => $fans,
      'authTitle' => $authTitle,
      'date'      => date('Y-m-d H:i:s')
    ];
  }
  
  public function douyin() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $this->url);
    $result = curl_exec($ch);
    curl_close($ch);
    
    require_once('phpQuery/phpQuery.php');
    // phpQuery::$debug = true;
    
    $result = phpQuery::newDocument($result);

    $link = pq('a', $result)->attr('href');
    $link = parse_url($link);

    $queries = explode('&', $link['query']);
    $sec_uid = '';
    foreach($queries as $query) {
      $q = explode('=', $query);
      if ($q[0] === 'sec_uid') $sec_uid = $query;
    }
    
    $return = [
      'from'      => '抖音',
      'nickname'  => '',
      'avatar'    => '',
      'fans'      => '',
      'authTitle' => '',
      'date'      => ''
    ];

    $api1 = 'https://www.iesdouyin.com/web/api/v2/user/info/?' . $sec_uid;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $api1);
    $result = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($result, true);

    $return['nickname'] = $result['user_info']['nickname'];
    $return['avatar'] = $result['user_info']['avatar_thumb']['url_list'][0];
    $return['fans'] = $result['user_info']['follower_count'];
    $return['date'] = date('Y-m-d H:i:s');

    return $return;
  }

  public function douban() {
    $uri = explode('/', $this->url);
    $curl_url = 'https://m.douban.com/rexxar/api/v2/user/' . $uri[4]. '?ck=&for_mobile=1';
    $referer = 'https://m.douban.com/people/' . $uri[4]. '/?dt_dapp=1';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Host: m.douban.com',
      'Content-Type: application/json',
      'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
    ]);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_URL, $curl_url);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $res = json_decode($result, true);

    return [
      'from'      => '豆瓣',
      'nickname'  => $res['name'],
      'avatar'    => $res['avatar'],
      'fans'      => $res['followers_count'],
      'authTitle' => '',
      'date'      => date('Y-m-d H:i:s')
    ];
  }

  public function bilibili() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $this->url);
    $result = curl_exec($ch);
    curl_close($ch);
    
    require_once('phpQuery/phpQuery.php');
    // phpQuery::$debug = true;
    
    $result = phpQuery::newDocument($result);

    $link = pq('a', $result)->attr('href');
    $link = parse_url($link);
    $id = substr($link['path'], 1);
    
    $return = [
      'from'      => '哔哩哔哩',
      'nickname'  => '',
      'avatar'    => '',
      'fans'      => '',
      'authTitle' => '',
      'date'      => ''
    ];

    $api1 = 'https://api.bilibili.com/x/space/acc/info?mid=' . $id . '&jsonp=jsonp';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $api1);
    $result = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($result, true);

    $return['nickname'] = $result['data']['name'];
    $return['avatar'] = $result['data']['face'];

    $api2 = 'https://api.bilibili.com/x/relation/stat?vmid=' . $id . '&jsonp=jsonp';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $api2);
    $result = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($result, true);
    $return['fans'] = $result['data']['follower'];

    $return['date'] = date('Y-m-d H:i:s');

    return $return;
  }

  public function kuaishou() {
    // https://v.kuaishou.com/dIknt5
    // https://live.kuaishou.com/profile/3xtmru44hwgbgdi?fid=2004631118&cc=share_copylink&followRefer=151&shareMethod=TOKEN&kpn=KUAISHOU&subBiz=PROFILE&shareId=16427222598835&shareToken=X-3EFZsYF1ByA3C4&shareMode=APP&originShareId=16427222598835&appType=1&shareObjectId=2004631118&shareUrlOpened=0&timestamp=1630641035467

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Host: id.kuaishou.com',
      'Origin: https://live.kuaishou.com',
      'Content-Type: application/x-www-form-urlencoded',
      // 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
    ]);    
    curl_setopt($ch, CURLOPT_REFERER, 'https://live.kuaishou.com/profile/3xtmru44hwgbgdi?fid=2004631118&cc=share_copylink&followRefer=151&shareMethod=TOKEN&kpn=KUAISHOU&subBiz=PROFILE&shareId=16427222598835&shareToken=X-3EFZsYF1ByA3C4&shareMode=APP&originShareId=16427222598835&appType=1&shareObjectId=2004631118&shareUrlOpened=0&timestamp=1630641035467');
    // curl_setopt($ch, CURLOPT_REFERER, 'https://live.kuaishou.com/profile/3xtmru44hwgbgdi');
    curl_setopt($ch, CURLOPT_URL, $this->url);
    $result = curl_exec($ch);
    curl_close($ch);

    dump($result);
  }

  public function toutiao() {
    // https://profile.zjurl.cn/rogue/ugc/profile/?user_id=92502589876
    require_once('phpQuery/phpQuery.php');
    // phpQuery::$debug = true;
    
    $result = phpQuery::newDocumentFile($this->url);
    
    dump(htmlentities($result, ENT_QUOTES, 'UTF-8'));
    // dump($result);
    die();

    $nickname = pq('.username', $result)->html();
    $avatar = pq('.photo_wrap img.photo', $result)->attr('src');
    $authTitle = '';

    $authInfo = pq('.Profile-sideColumn .Card:eq(0) div:not(.Card-header) div:eq(0) div:eq(1) a', $result)->html();
    if ($authInfo === '认证信息') {
        $authTitle = pq('.Profile-sideColumn .Card:eq(0) div:not(.Card-header) div:eq(0) div:eq(1) div', $result)->html();
    }

    $fans = pq('table.tb_counter tbody tr td:eq(1)', $result)->html();

    return [
        'nickname'  => $nickname,
        'avatar'    => $avatar,
        'fans'      => $fans,
        'authTitle' => $authTitle,
        'date'      => date('Y-m-d H:i:s')
    ];

  }

  public function weibo() {
    require_once('phpQuery/phpQuery.php');
    // phpQuery::$debug = true;
    
    $result = phpQuery::newDocumentFile($this->url);
    
    dump(htmlentities($result, ENT_QUOTES, 'UTF-8'));
    // dump($result);
    die();

    $nickname = pq('.username', $result)->html();
    $avatar = pq('.photo_wrap img.photo', $result)->attr('src');
    $authTitle = '';

    $authInfo = pq('.Profile-sideColumn .Card:eq(0) div:not(.Card-header) div:eq(0) div:eq(1) a', $result)->html();
    if ($authInfo === '认证信息') {
        $authTitle = pq('.Profile-sideColumn .Card:eq(0) div:not(.Card-header) div:eq(0) div:eq(1) div', $result)->html();
    }

    $fans = pq('table.tb_counter tbody tr td:eq(1)', $result)->html();

    return [
        'nickname'  => $nickname,
        'avatar'    => $avatar,
        'fans'      => $fans,
        'authTitle' => $authTitle,
        'date'      => date('Y-m-d H:i:s')
    ];
  }

  public function m_weibo() {
    
    require_once('phpQuery/phpQuery.php');
    // phpQuery::$debug = true;
    
    $result = phpQuery::newDocumentFile($this->url);
    
    // dump(htmlentities($result, ENT_QUOTES, 'UTF-8'));

    $script = pq('script', $result)->getString();
    

    preg_match('/\$render_data = \[\{([\s\S]*)\}\]/', $script[1], $match);
    
    $data = json_decode('{' . $match[1] . '}', true);

    // text 描述
    // reposts_count: 96 转发
    // comments_count: 188 评论
    // attitudes_count: 5089 点赞
    
    return [
      'text' => strip_tags($data['status']['text']),
      'reposts_count' => $data['status']['reposts_count'],
      'comments_count' => $data['status']['comments_count'],
      'attitudes_count' => $data['status']['attitudes_count'],
    ];
  }

  public function m_douyin() {
    $puppeteer = new Puppeteer;
    $browser = $puppeteer->launch();

    $page = $browser->newPage();
    $page->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1');
    $page->goto($this->url);
    // $page->screenshot(['path' => 'example.png']);
    $browser->close();

    $target_url = $page->url();
    $target_uri = parse_url($target_url);
    $target_uri_path = explode('/', $target_uri['path']);
    $item_id = $target_uri_path[count($target_uri_path) - 2];

    if ($item_id) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_URL, 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=' . $item_id);
      $result = curl_exec($ch);
      curl_close($ch);

      $result = json_decode($result, true);
    
      // text 描述
      // reposts_count: 96 转发
      // comments_count: 188 评论
      // attitudes_count: 5089 点赞
      
      return [
        'text' => strip_tags($result['item_list'][0]['desc']),
        'reposts_count' => $result['item_list'][0]['statistics']['share_count'],
        'comments_count' => $result['item_list'][0]['statistics']['comment_count'],
        'attitudes_count' => $result['item_list'][0]['statistics']['digg_count'],
      ];
    }
  }
}
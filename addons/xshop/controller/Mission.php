<?php

namespace addons\xshop\controller;

use addons\xshop\model\UserModel;
use addons\xshop\traits\LoginTrait;

use addons\xshop\model\ArticleModel;
use addons\xshop\model\OrderModel;
use addons\xshop\model\VendorModel;
use app\admin\model\User;
use app\admin\model\vc\MoneyLog;
use app\admin\model\vc\UserAccount;
use app\admin\model\xshop\Order;
use app\admin\model\xshop\OrderProducts;
use app\admin\model\xshop\Product;
use app\admin\services\crawler;
use app\admin\services\weixin;
use Error;
use Exception;
use think\addons\Controller;
use think\Db;

use function GuzzleHttp\json_decode;

/**
 * 任务
 * @ApiWeigh (10)
 */
class Mission extends Base {
  protected $beforeActionList = [
    '__NeedLogin'
  ];

  protected function __contruct() {
    parent::__contruct();
  }

  use LoginTrait;

  public function index() {
    // $get = $this->request->get();
    $user = UserModel::info();
    
    $account_ids = UserAccount::where([
      'user_id' => $user->id
    ])->column('id');
    
    $product_ids = Product::where('create_user', 'IN', $account_ids)->column('id');
    // dump($product_ids);
    
    $where = [];
    $where['is_pay'] = '1';
    $res = Order::where($where)->where('find_in_set(:id, creater_id)', ['id' => $user->id])->select();
    foreach($res as $k => $v) {
      $res[$k]['skus'] = OrderProducts::where([
        'order_id' => $v->id
      ])->where('product_id', 'IN', $product_ids)->select();
    }

    return $this->success('', $res);
  }

  # 接任务
  public function take() {
    $user = UserModel::info();
    $id = $this->request->post('id');

    $exist = OrderProducts::find($id);
    $exist->status = 'progress';
    $exist->save();

    $order = Order::find($exist->order_id);
    $order->is_delivery = 1;
    $order->save();

    return $this->success('success');
  }
  
  # 解析任务
  public function applyinfo() {
    $post = $this->request->post();
        
    $service = new crawler($post['url']);
    try {
        $res = $service->parse_post();
        
    } catch (Error $e) {
        return $this->error($e->getMessage());
    }

    return $this->success('', $res);
  }

  # 提交任务预览文件
  public function preview() {
    $user = UserModel::info();
    $post = $this->request->post();

    Db::startTrans();
    try {
      $exist = OrderProducts::find($post['id']);
      $exist->preview_url = $post['url'];
      $exist->preview_psw = $post['password'];
      $exist->status = 'preview';
      $exist->save();
      
      $order = Order::find($exist->order_id);

      # 发送微信推送给上游
      $WechatVender = VendorModel::where([
          'user_id'   => $order->user_id,
          'platform'  => 'WechatMp'
      ])->find();
      $WeixinVender = VendorModel::where([
          'unionid'   => $WechatVender->unionid,
          'platform'  => 'WechatH5'
      ])->find();
      if ($WeixinVender) {
        $data = [
            'touser'        => $WeixinVender->openid,
            'template_id'   => 'IZiELiEGY-6jZjp94ca3-eUvZ194Rq0UMu1ZICC_omw',
            'miniprogram'   => [
                'appid'     => 'wx081e2d63a5efba6c',
                'pagepath'  => 'pages/order/orderDetail?order_sn=' . $order->order_sn
            ],
            'data'          => [
                'first'     => [
                    'value' => '您的订单已更新！请点击查看'
                ],
                'keyword1'  => [
                    'value' => '『' . $exist->title . '』 ' . $exist->attributes . '服务'
                ],
                'keyword2'  => [
                    'value' => $order->order_sn
                ],
                'keyword3'  => [
                    'value' => '提交预览'
                ],
                'remark'    => [
                    'value' => '请点击查看完成情况。'
                ],
            ]
        ];

        $WeixinH5Service = new weixin();
        $WeixinH5Service->notice($data);
      }
      Db::commit();
    } catch (\think\PDOException $e) {
      Db::rollback();
      throw new Exception($e->getMessage());
    } catch (\think\Exception $e) {
      Db::rollback();
      throw new Exception($e->getMessage());
    }
    return $this->success('success');
  }

  # 提交任务
  public function apply() {
    $user = UserModel::info();
    $post = $this->request->post();

    Db::startTrans();
    try {
      $exist = OrderProducts::find($post['id']);
      $exist->post_url = $post['url'];
      $exist->status = 'submit';
      $exist->save();

      $other_OP = OrderProducts::where([
        'order_id'  => $exist->order_id,
        'status'    => 'pending'
      ])->count();      

      $order = Order::find($exist->order_id);
      if ($other_OP === 0) $order->status = 9;
      $order->save();

      # 发送微信推送给上游
      $WechatVender = VendorModel::where([
          'user_id'   => $order->user_id,
          'platform'  => 'WechatMp'
      ])->find();
      $WeixinVender = VendorModel::where([
          'unionid'   => $WechatVender->unionid,
          'platform'  => 'WechatH5'
      ])->find();
      if ($WeixinVender) {
        $data = [
            'touser'        => $WeixinVender->openid,
            'template_id'   => 'IZiELiEGY-6jZjp94ca3-eUvZ194Rq0UMu1ZICC_omw',
            'miniprogram'   => [
                'appid'     => 'wx081e2d63a5efba6c',
                'pagepath'  => 'pages/order/orderDetail?order_sn=' . $order->order_sn
            ],
            'data'          => [
                'first'     => [
                    'value' => '您的订单已更新！请点击查看'
                ],
                'keyword1'  => [
                    'value' => '『' . $exist->title . '』 ' . $exist->attributes . '服务'
                ],
                'keyword2'  => [
                    'value' => $order->order_sn
                ],
                'keyword3'  => [
                    'value' => '已发布'
                ],
                'remark'    => [
                    'value' => '请点击查看完成情况。'
                ],
            ]
        ];

        $WeixinH5Service = new weixin();
        $WeixinH5Service->notice($data);
      }
      Db::commit();
    } catch (\think\PDOException $e) {
      Db::rollback();
      throw new Exception($e->getMessage());
    } catch (\think\Exception $e) {
      Db::rollback();
      throw new Exception($e->getMessage());
    }
    return $this->success('success');
  }

  # 任务完成
  public function complete() {
    $user = UserModel::info();
    $post = $this->request->post();
    
    Db::startTrans();
    try {
      $exist = OrderModel::with([ 'products' ])->find($post['id']);
      if ($exist->status !== 9) return;

      // dump(json_decode(json_encode($exist)));
      // die();

      $exist->status = 2;
      $exist->save();

      foreach($exist->products as $OP) {
        if ($OP->status !== 'submit') continue;
        $Product = Product::find($OP->product_id);
        $UAccount = UserAccount::find($Product->create_user);
        $User = User::find($UAccount->user_id);
        
        $money_cale = bcadd($User->money, $OP->product_price, 2);

        MoneyLog::create([
          'user_id'     => $UAccount->user_id,
          'money'       => $OP->product_price,
          'before'      => $User->money,
          'after'       => $money_cale,
          'memo'        => '订单：' . $exist->order_sn . '结款',
          'createtime'  => time()
        ]);
  
        $User->money = $money_cale;
        $User->save();

        # 通知入账
        $WechatVender = VendorModel::where([
          'user_id'   => $UAccount->user_id,
          'platform'  => 'WechatMp'
        ])->find();
        $WeixinVender = VendorModel::where([
            'unionid'   => $WechatVender->unionid,
            'platform'  => 'WechatH5'
        ])->find();
        if ($WeixinVender) {
          $data = [
              'touser'        => $WeixinVender->openid,
              'template_id'   => '40vNZ2954uOZMRrazmyOb9CKbUGE_MISUM4t09c3i-A',
              'miniprogram'   => [
                  'appid'     => 'wx081e2d63a5efba6c',
                  'pagepath'  => 'pages/wallet/transition'
              ],
              'data'          => [
                  'first'     => [
                      'value' => '您的订单已结款'
                  ],
                  'keyword1'  => [
                    'value' => $exist->order_sn
                  ],
                  'keyword2'  => [
                    'value' => '『' . $OP->title . '』 ' . $OP->attributes . '服务'
                  ],
                  'keyword3'  => [
                      'value' => '已结款'
                  ],
                  'keyword4'  => [
                      'value' => date('Y年m月d日', time())
                  ],
                  'remark'    => [
                      'value' => $OP->product_price . '已入账，请点击查看完成情况。'
                  ],
              ]
          ];

          $WeixinH5Service = new weixin();
          $WeixinH5Service->notice($data);
        }

      }
      
      // throw new Error('::::: DEBUG :::::');
      
      Db::commit();
    } catch (\think\Exception $e) {
        Db::rollback();
        throw new Exception($e->getMessage());
    }

    return $this->success('success');
  }

  # 单个任务完成
  public function complete_one() {
    $user = UserModel::info();
    $post = $this->request->post();
    
    Db::startTrans();
    try {
      $OP = OrderProducts::find($post['id']);
      $Order = Order::find($OP->order_id);
      
      $OtherOP = OrderProducts::where([
        'order_id' => $OP->order_id,
        'status'  => ['NEQ', 'complete'],
        'id'      => ['NEQ', $OP->id]
      ])->count();
      // dump($OtherOP);
      // die();

      if ($OtherOP === 0) {
        if ($Order->status !== 9) return;
        $Order->status = 2;
        $Order->save();
      }

      $OP->status = 'complete';
      $OP->save();
      
      $Product = Product::find($OP->product_id);
      $UAccount = UserAccount::find($Product->create_user);
      $User = User::find($UAccount->user_id);
      $money_cale = bcadd($User->money, $OP->product_price, 2);

      MoneyLog::create([
        'user_id'     => $UAccount->user_id,
        'money'       => $OP->product_price,
        'before'      => $User->money,
        'after'       => $money_cale,
        'memo'        => '订单：' . $Order->order_sn . '结款',
        'createtime'  => time()
      ]);

      $User->money = $money_cale;
      $User->save();

      # 通知入账
      $WechatVender = VendorModel::where([
        'user_id'   => $UAccount->user_id,
        'platform'  => 'WechatMp'
      ])->find();
      $WeixinVender = VendorModel::where([
          'unionid'   => $WechatVender->unionid,
          'platform'  => 'WechatH5'
      ])->find();
      if ($WeixinVender) {
        $data = [
            'touser'        => $WeixinVender->openid,
            'template_id'   => '40vNZ2954uOZMRrazmyOb9CKbUGE_MISUM4t09c3i-A',
            'miniprogram'   => [
                'appid'     => 'wx081e2d63a5efba6c',
                'pagepath'  => 'pages/wallet/transition'
            ],
            'data'          => [
                'first'     => [
                    'value' => '您的订单已结款'
                ],
                'keyword1'  => [
                  'value' => $Order->order_sn
                ],
                'keyword2'  => [
                  'value' => '『' . $OP->title . '』 ' . $OP->attributes . '服务'
                ],
                'keyword3'  => [
                    'value' => '已结款'
                ],
                'keyword4'  => [
                    'value' => date('Y年m月d日', time())
                ],
                'remark'    => [
                    'value' => $OP->product_price . '已入账，请点击查看完成情况。'
                ],
            ]
        ];

        $WeixinH5Service = new weixin();
        $WeixinH5Service->notice($data);
      }
      
      Db::commit();
    } catch (\think\Exception $e) {
        Db::rollback();
        throw new Exception($e->getMessage());
    }

    return $this->success('success');
  }
}
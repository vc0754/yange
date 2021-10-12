<?php

namespace addons\xshop\controller;

use addons\xshop\model\UserModel;
use addons\xshop\traits\LoginTrait;
use app\admin\model\vc\Activity as VcActivity;
use app\admin\model\vc\ActivityPlayer;
use app\admin\model\vc\UserAccount;
use app\admin\services\crawler;
use Error;

/**
 * 活动
 */
class Activity extends Base {
  protected $beforeActionList = [
    '__NeedLogin'
  ];

  protected function __contruct() {
    parent::__contruct();
  }

  use LoginTrait;
  
  // 活动列表
  public function index() {

    $get = $this->request->get();
    if (isset($get['id'])) {
      $res = VcActivity::with([ 'players' ])->find($get['id']);
    } else {
      $params = array_merge([
        'size' => 15,
        'page' => 1
      ], $get);
  
      $res = VcActivity::order('create_at', 'DESC')->paginate($params['size'], false, [
        'page' => $params['page']
      ]);
    }

    return $this->success('', $res);
  }

  // 参赛者列表
  public function players() {
    $get = $this->request->get();
    $res = ActivityPlayer::where([
      'activity_id'         => $get['id'],
    ])->order('zan DESC')->limit(100)->select();
    return $this->success('', $res);
  }

  // 是否参赛者
  public function is_player() {
    $post = $this->request->post();

    $user = UserModel::info();
    $exist = ActivityPlayer::field([
      'user_account_id', 'user_account_name'
    ])->where([
      'user_id'     => $user->id,
      'activity_id' => $post['id']
    ])->select();

    return $this->success('', $exist);
  }

  // 活动报名
  public function app() {
    $post = $this->request->post();
    
    $user = UserModel::info();
    $account = UserAccount::where([
      'id'      => $post['account_id'],
      'user_id' => $user->id
    ])->find();

    $active = VcActivity::where([
      'id' => $post['id']
    ])->find();
    if (!$active) return $this->error('活动不存在');
    if ($active->player_current == $active->player_max) return $this->error('报名人数已满');
    
    ActivityPlayer::create([
      'activity_id'         => $post['id'],
      'user_id'             => $user->id,
      'user_account_id'     => $account->id,
      'user_account_name'   => $account->account,
      'user_account_avatar' => $account->account_avatar,
      'create_at' => date('Y-m-d H:i:s')
    ]);
    
    $active->player_current = $active->player_current + 1;
    $active->save();
    
    return $this->success('success');
  }

  // 我参加的活动
  public function my() {
    $get = $this->request->get();

    $user = UserModel::info();
    $res = ActivityPlayer::with([
      'activity'
    ])->where([
      'user_id' => $user->id
    ])->select();

    return $this->success('', $res);
  }

  // 检查待提交URL
  public function urlcheck() {
    $post = $this->request->post();
    
    $service = new crawler($post['url']);
    try {
      $res = $service->parse_post();
    } catch (Error $e) {
      return $this->error($e->getMessage());
    }

    return $this->success('', $res);
  }

  // 提交URL
  public function urlsubmit() {
    $post = $this->request->post();

    $exist = ActivityPlayer::find($post['id']);
    $exist->post_url = $post['url'];
    $exist->save();
    
    return $this->success('', $exist);
  }
}
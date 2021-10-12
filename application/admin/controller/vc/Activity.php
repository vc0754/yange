<?php

namespace app\admin\controller\vc;

use app\admin\model\User;
use app\admin\model\vc\Activity as VcActivity;
use app\admin\model\vc\ActivityPlayer;
use app\admin\model\vc\Coupon as VcCoupon;
use app\admin\model\vc\UserAccount;
use app\admin\model\xshop\Category;
use app\admin\model\xshop\Product;
use app\common\controller\Backend;

/**
 * 活动管理
 *
 * @icon   fa fa-list
 * @remark 
 */
class Activity extends Backend {

  // 活动列表
  public function index($size = 15, $page = 1) {
    $params = array_merge([
      'size' => $size,
      'page' => $page
    ]);
    
    $res = VcActivity::order('create_at', 'DESC')->paginate($params['size'], false, [
      'page' => $params['page']
    ]);
    $this->assign('list', $res);
    $this->assign('time', date('Y-m-d H:i:s'));

    return $this->view->fetch();
  }

  // 新增活动
  public function add() {
    if ($this->request->isPost()) {
      $post = $this->request->post();

      $exist = VcActivity::where([
        'name' => $post['name']
      ])->find();
      if ($exist) $this->error('活动已存在');

      $user = User::find($post['sponsor_id']);
      if (!$user) $this->error('用户不存在');

      if (
        $post['time_start'] > $post['time_app_end'] || 
        $post['time_app_end'] > $post['time_verify_end'] || 
        $post['time_verify_end'] > $post['time_end']
      ) $this->error('时间有误');
      
      $data = [
        'sponsor_id'      => $post['sponsor_id'],
        'name'            => $post['name'],
        'description'     => $post['description'],
        'imgs'            => $post['imgs'],
        'time_start'      => $post['time_start'],
        'time_app_end'    => $post['time_app_end'],
        'time_verify_end' => $post['time_verify_end'],
        'time_end'        => $post['time_end'],
        // 'player_current' => $post['player_current'],
        'player_max'      => $post['player_max'],
        'price_total'     => $post['price_total'],
        'price_rule'      => $post['price_rule'],
        'create_at'       => date('Y-m-d H:i:s'),
        // 'update_at'       => $post['update_at'],
        // 'delete_at'       => $post['delete_at']
      ];
      VcActivity::create($data);
      
      $this->success('添加成功', 'index');
    }
    
    return $this->view->fetch();
  }

  // 编辑活动
  public function edit($id = null) {
    $res = VcActivity::find($id);

    if ($this->request->isPost()) {
      if ($res) {
        if ($res->player_current > 0) return $this->error('已经有人报名，无法修改');

        $post = $this->request->post();
        
        if (
          $post['time_start'] > $post['time_app_end'] || 
          $post['time_app_end'] > $post['time_verify_end'] || 
          $post['time_verify_end'] > $post['time_end']
        ) $this->error('时间有误');
        
        // $res->sponsor_id = $post['sponsor_id'];
        $res->name = $post['name'];
        $res->description = $post['description'];
        $res->imgs = $post['imgs'];
        $res->time_start = $post['time_start'];
        $res->time_app_end = $post['time_app_end'];
        $res->time_verify_end = $post['time_verify_end'];
        $res->time_end = $post['time_end'];
        $res->player_max = $post['player_max'];
        $res->price_total = $post['price_total'];
        $res->price_rule = $post['price_rule'];
        $res->save();
        
        $this->success('更新更改', 'index');
      }
    }
    
    $this->assign('res', $res);
    $this->assign('id', $id);
    return $this->view->fetch();
  }

  // 删除
  public function delete($ids = null) {}

  // 报名列表
  public function players($id, $size = 15, $page = 1) {
    $params = array_merge([
      'size' => $size,
      'page' => $page
    ]);
    $res = ActivityPlayer::where([
      'activity_id' => $id
    ])->order('create_at', 'DESC')->paginate($params['size'], false, [
      'page' => $params['page']
    ]);
    
    $this->assign('res', $res);
    $this->assign('id', $id);
    return $this->view->fetch();
  }

  // 报名通过
  public function approved($id) {
    $exist = ActivityPlayer::find($id);
    $activity = VcActivity::find($exist->activity_id);
    
    if ($activity->player_approved === $activity->player_approved_max) return $this->error('参数作品已满');
    
    $exist->status = 'approved';
    $exist->save();

    $activity->player_approved = bcadd($activity->player_approved, 1, 0);
    $activity->save();

    $this->success('已通过', url('/admin/vc/activity/players', ['id' => $exist->activity_id]));
  }

  // 报名驳回
  public function unapproved($id) {
    $exist = ActivityPlayer::find($id);
    $exist->status = 'unapproved';
    $exist->save();
    $this->success('已驳回', url('/admin/vc/activity/players', ['id' => $exist->activity_id]));
  }

  // 奖金派发
  public function payout($id) {
    $exist = VcActivity::find($id);

    # 增加活动时间、状态验证 和 user验证
    
    // 总金额
    $total_bonus = $exist->price_total;
    $left_bonus = $total_bonus;

    // 分配规则
    $rules = explode(PHP_EOL, $exist->price_rule);

    foreach($rules as $rule) {
      if (strpos($rule, ' ')) {
        $price = explode(' ', $rule);
        $left_bonus = bcsub($left_bonus, $price[1], 2);
      }
    }

    $per_bonus = bcdiv($left_bonus, $exist->player_approved, 2);

    dump($rules);
    dump($left_bonus);
    dump($per_bonus);
  }
}
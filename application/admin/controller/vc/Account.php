<?php

namespace app\admin\controller\vc;

use app\admin\model\vc\Coupon as VcCoupon;
use app\admin\model\vc\UserAccount;
use app\admin\model\xshop\Category;
use app\admin\model\xshop\Product;
use app\common\controller\Backend;

/**
 * 社交账号管理
 *
 * @icon   fa fa-list
 * @remark 
 */
class Account extends Backend {

  // 列表
  public function index($size = 15, $page = 1) {
    $params = array_merge([
      'size' => $size,
      'page' => $page
    ]);
    $res = UserAccount::order('create_at', 'DESC')->paginate($params['size'], false, [
      'page' => $params['page']
    ]);
    $this->assign('list', $res);
    // dump($res);

    return $this->view->fetch();
  }

  // 编辑
  public function edit($id = null) {
    $res = UserAccount::find($id);

    if ($this->request->isPost()) {
      if ($res) {
        $post = $this->request->post();

        if ($post['status'] === 'pending' && $res->status !== 'pending') return $this->error('更新失败');

        $res->status = $post['status'];
        $res->reason = $post['status'] === 'reject' ? $post['reason'] : '';
        $res->save();
        

        # 通过审核
        if ($post['status'] === 'normal') {
          $c = Category::where([
            'name' => $res->account_from
          ])->find();

          $product = Product::where([
            'title' => $res->account
          ])->find();
          if (!$product) {
            $tip = '<p>1、请至少提前2小时下单</p><p>2、请提供完整的素材</p><p>3、如推广内容存在违规行为（涉黄、涉政治、暴力等），我方有权利拒单</p><p>4、我方不对推广内容做任何自然流量的保底承诺，如需增加自然流量，请另行做推广计划</p>';
            $data = [
              'category_id'         => $c->id,
              'title'               => $res->account,
              'description'         => '',
              'fans'                => $res->account_fans,
              'content'             => '',
              'tips'                => $tip,
              'image'               => $res->account_avatar,
              'on_sale'             => 0,
              // 'rating'           => '5.00',
              'sold_count'          => 0,
              'unit_id'             => 1,
              'favorite_count'      => 0,
              'review_count'        => 0,
              'service_tags'        => '',
              'trems_ids'           => '',
              'notice_ids'          => '',
              'home_recommend'      => 0,
              'category_recommend'  => 0,
              'price'               => '0.00',
              // 'sold_time_start'     => '',
              // 'sold_time_end'       => '',
              // 'sold_time_tips'      => '',
              'create_time'         => time(),
              'update_time'         => time(),
              'create_user'         => $res->id,
              'delivery_tpl_id'     => 1
            ];
            Product::create($data);
          }
        }
        
        // $this->success('更新更改', 'index');
      }
    }
    
    $this->assign('user', $res);
    return $this->view->fetch();
  }

  // 删除
  public function delete($ids = null) {}
}
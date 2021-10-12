<?php

namespace app\admin\controller\vc;

use app\admin\model\vc\Coupon as VcCoupon;
use app\common\controller\Backend;

/**
 * 优惠券管理
 *
 * @icon   fa fa-list
 * @remark 
 */
class Coupon extends Backend {

  // 列表
  public function index($size = 15, $page = 1) {
    $params = array_merge([
      'size' => $size,
      'page' => $page
    ]);
    $res = VcCoupon::order('create_at', 'DESC')->paginate($params['size'], false, [
      'page' => $params['page']
    ]);
    $this->assign('list', $res);
    // dump($res);

    return $this->view->fetch();
  }

  // 添加
  public function add() {
    if ($this->request->isPost()) {
      $post = $this->request->post();
      $post['create_at'] = date('Y-m-d H:i:s');
      $post['update_at'] = date('Y-m-d H:i:s');

      $exist = VcCoupon::where([
        'code' => $post['code']
      ])->find();
      if ($exist) $this->error('编码为' . $post['code'] . '的优惠券已存在');

      $coupon = VcCoupon::create($post);
      
      if (!$coupon) $this->error('新增失败，请重试');
      $this->success('新增成功', 'index');
    }
    return $this->view->fetch();
  }

  // 编辑
  public function edit($ids = null) {

    return $this->view->fetch();
  }

  // 删除
  public function delete($ids = null) {}
}
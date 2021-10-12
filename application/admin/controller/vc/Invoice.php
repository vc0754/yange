<?php

namespace app\admin\controller\vc;

use app\admin\model\vc\Invoice as VcInvoice;
use app\common\controller\Backend;

/**
 * 发票管理
 *
 * @icon   fa fa-list
 * @remark 
 */
class Invoice extends Backend {

  // 列表
  public function index($size = 15, $page = 1) {
    $params = array_merge([
      'size' => $size,
      'page' => $page
    ]);
    $res = VcInvoice::where([
      'status'    => ['NEQ', 'pending'],
      'delete_at' => null
    ])->order('create_at', 'DESC')->paginate($params['size'], false, [
      'page' => $params['page']
    ]);
    $this->assign('list', $res);
    // dump($res);

    return $this->view->fetch();
  }

  // 添加
  public function add() {}

  // 编辑
  public function edit($ids = null) {
    $res = VcInvoice::find($ids);

    if ($this->request->isPost()) {
      if ($res) {
        $POST = $this->request->post();
        $res->status = $POST['status'];
        $res->save();
        $this->success('更新更改', 'index');
      }
    }

    $this->assign('res', $res);
    return $this->view->fetch();
  }

  // 删除
  public function delete($ids = null) {}
}
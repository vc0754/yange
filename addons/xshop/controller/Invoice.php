<?php

namespace addons\xshop\controller;

use addons\xshop\validate\OrderValidate;
use addons\xshop\validate\OrderProductValidate;
use addons\xshop\model\OrderModel;
use addons\xshop\model\OrderProductModel;
use addons\xshop\model\UserModel;
use addons\xshop\traits\LoginTrait;
use app\admin\model\vc\Invoice as VcInvoice;
use Error;
use Exception;
use think\Db;

/**
 * 发票
 */
class Invoice extends Base
{
    protected $beforeActionList = [
        '__NeedLogin'
    ];
    protected function __contruct() {
        parent::__contruct();
    }

    use LoginTrait;

    /**
     * 获取发票
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="state", type="array", required=false, description="发票状态")
     */
    public function index() {
        $user = UserModel::info();
        $params = array_merge([
          'size' => 15,
          'page' => 1
        ], $this->request->get());

        // $result = $this->validate($params, OrderValidate::class . '.state');
        // if (true !== $result) {
        //     return $this->error($result);
        // }

        $res = VcInvoice::field([
            'id', 'type', 'status', 'order_id', 'no',
            'total', 'total_amount', 'total_tax',
            'buyer_type', 'buyer_name', 'buyer_tax_code',
            'buyer_email', 'buyer_mobile', 'buyer_address',
            'buyer_bank', 'buyer_bank_account'
        ])->where([
            'user_id'   => $user->id,
            'delete_at' => null
        ])->where('status', '<>', 'pending')->order('create_at', 'DESC')->paginate($params['size'], false, [
            'page' => $params['page']
        ]);

        return $this->success('', $res);
    }

    /**
     * 获取发票信息
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="order_sn", type="array", required=true, description="发票sn")
     */
    public function info() {
        $params = $this->request->get();
        $result = $this->validate($params, OrderValidate::class . '.info');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', OrderModel::info($params));
    }
    
    /**
     * 创建发票
     * @ApiSummary 有两种创建发票方式，1、立即购买，传入sku_id、quantity参数，2、购物车结算
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="address_id", type="integer", required=true, description="收货地址Id")
     * @ApiParams (name="coupon_id", type="integer", required=false, description="使用优惠券Id")
     * @ApiParams (name="remark", type="integer", required=false, description="备注")
     * @ApiParams (name=sku_id, type=integer, require=false, description=商品sku，立即购买传入)
     * @ApiParams (name=quantity, type=integer, require=false, description=数量，立即购买传入)
     */
    public function add() {
        $params = $this->request->post();      
        $user = UserModel::info();

        Db::startTrans();
        try {
            if (isset($params['invoice'])) {
                $params['tax_price'] = bcsub($params['invoice']['cale'], $params['invoice']['price'], 2);
            }

            if (!empty($params['sku_id'])) { // 立即购买
                $result = $this->validate($params, OrderValidate::class . '.addOne');
                if (true !== $result) return $this->error($result);
                
                $order = OrderModel::addOne($params);
            } else {
                $result = $this->validate($params, OrderValidate::class . '.add');
                if (true !== $result) return $this->error($result);
                
                $order = OrderModel::add($params);
            }

            // 发票信息
            if (isset($params['invoice'])) {
                // 记录开票默认填写数据
                if ($params['invoice']['set_default'] === '1') {}

                $invoice_data = [
                    'type'                  => $params['invoice']['type'],
                    'status'                => 'pending',
                    'order_id'              => $order,
                    'user_id'               => $user->id,
                    'no'                    => 'INV.' . date('YmdHis'),
                    'buyer_type'            => $params['invoice']['buyer_type'],
                    'buyer_name'            => $params['invoice']['name'],
                    'buyer_tax_code'        => $params['invoice']['tax_code'],
                    'buyer_email'           => $params['invoice']['email'],
                    'buyer_address'         => $params['invoice']['companyAddress'],
                    'buyer_mobile'          => $params['invoice']['mobile'],
                    'buyer_bank'            => $params['invoice']['bankName'],
                    'buyer_bank_account'    => $params['invoice']['bankAccount'],
                    'seller_name'           => '',
                    'seller_tax_code'       => '',
                    'seller_address'        => '',
                    'seller_mobile'         => '',
                    'seller_bank'           => '',
                    'seller_bank_account'   => '',
                    'shoukuanren'           => '',
                    'fuheren'               => '',
                    'kaipiaoren'            => '',
                    'total_amount'          => $params['invoice']['price'],
                    'total_tax'             => bcsub($params['invoice']['cale'], $params['invoice']['price'], 2),
                    'total'                 => $params['invoice']['cale'],
                    'remark'                => '',
                    // 'kaipiaoriqi'           => '',
                    'create_at'             => date('Y-m-d H:i:s'),
                    'update_at'             => date('Y-m-d H:i:s'),
                ];
                VcInvoice::create($invoice_data);
            }
            
            // throw new Error('::::: DEBUG :::::');

            Db::commit();
        } catch (\think\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }

        return $this->success('', $order);
    }

    /**
     * 发票收货
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="order_sn", type="integer", required=true, description="发票号")
     */
    public function receive() {
        $params = $this->request->post();
        $result = $this->validate($params, OrderValidate::class . '.sn');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', OrderModel::receive($params));
    }

    /**
     * 发票取消
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="order_sn", type="integer", required=true, description="发票号")
     */
    public function cancel() {
        $params = $this->request->post();
        $result = $this->validate($params, OrderValidate::class . '.sn');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', OrderModel::cancel($params));
    }

    /**
     * 发票删除
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="order_sn", type="integer", required=true, description="发票号")
     */
    public function del() {
        $params = $this->request->post();
        $result = $this->validate($params, OrderValidate::class . '.sn');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', OrderModel::del($params));
    }

    /**
     * 评价发票商品
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="id", type="integer", required=true, description="发票商品ID")
     * @ApiParams (name="star", type="integer", required=true, description="评价星级")
     * @ApiParams (name="content", type="string", required=false, description="评价内容")
     */
    public function review() {
        $params = $this->request->post();
        $result = $this->validate($params, OrderProductValidate::class . '.review');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', OrderProductModel::review($params));
    }


    /**
     * 获取发票价格
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="address_id", type="string", required=true, description="收货地址")
     * @ApiParams (name="sku_id", type="integer", required=false, description="商品Sku")
     * @ApiParams (name="quantity", type="integer", required=false, description="商品数量")
     */
    public function getPrice() {
        $params = $this->request->post();
        $rules = [
            'address_id' => 'require'
        ];
        $msg = [
            'address_id' => '请选择收货地址'
        ];
        $result = $this->validate($params, $rules, $msg);
        if ($result !== true) {
            return $this->error($result);
        }
        return $this->success('', OrderModel::getPrice($params));
    }

    /**
     * 申请退款
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="order_sn", type="string", required=true, description="发票sn")
     * @ApiParams (name="remark", type="string", required=false, description="备注")
     */
    public function applyRefund()
    {
        $params = $this->request->post();
        $rules = [
            'order_sn' => 'require',
            'remark|备注' => 'max:200'
        ];
        $result = $this->validate($params, $rules);
        if ($result !== true) {
            return $this->error($result);
        }
        return $this->success('', OrderModel::applyRefund($params));
    }

    /**
     * 查询物流
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name="order_sn", type="string", required=true, description="发票sn")
     */
    public function getExpressInfo()
    {
        $params = $this->request->post();
        $rules = [
            'order_sn' => 'require',
        ];
        $result = $this->validate($params, $rules);
        if ($result !== true) {
            return $this->error($result);
        }
        $data = \addons\xshop\logic\ExpressLogic::getInfo($params['order_sn']);
        if ($data['code'] == 1) return $this->success('', $data['data']);
        return $this->error($data['msg'], null, 9999);
    }
}
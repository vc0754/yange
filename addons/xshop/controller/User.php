<?php

namespace addons\xshop\controller;

use think\addons\Controller;
use think\Config;
use app\common\library\Auth;
use addons\xshop\validate\UserValidate;
use addons\xshop\model\AddressModel;
use addons\xshop\traits\LoginTrait;
use addons\xshop\model\HistoryModel;
use addons\xshop\model\FavoriteModel;
use addons\xshop\model\UserModel;
use addons\xshop\model\VendorModel;
use app\admin\library\xshop\services\weibo;
use app\admin\library\xshop\services\zhihu;
use app\admin\model\vc\FavGroup;
use app\admin\model\vc\MoneyLog;
use app\admin\model\vc\UserAccount;
use app\admin\model\xshop\Product;
use app\admin\model\xshop\ProductSku;
use app\admin\model\xshop\ServiceTag;
use app\admin\services\crawler;
use Error;
use Exception;
use think\Db;

/**
 * 用户
 * @ApiWeigh (10)
 */
class User extends Base
{
    protected $beforeActionList = [
        '__NeedLogin'
    ];

    use LoginTrait;
    /**
     * 获取用户信息
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     */
    public function index()
    {
        $user = UserModel::getUserInfo();
        $user['account'] = UserAccount::where([
            'user_id' => $user['id']
        ])->select();
        
        
        $WechatVender = VendorModel::where([
            'user_id'   => $user['id'],
            'platform'  => 'WechatMp'
        ])->find();
        $WeixinVender = VendorModel::where([
            'unionid'   => $WechatVender->unionid,
            'platform'  => 'WechatH5'
        ])->find();
        $user['is_attach_weixin'] = $WeixinVender ? true : false;

        \think\Hook::listen('xshop_get_userinfo', $user);
        return $this->success('', $user);
    }

    /**
     * 修改资料
     * @ApiMethod (POST)
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     */
    public function editInfo()
    {
        $this->request->filter(['strip_tags']);
        $params = $this->request->post();
        $result = $this->validate($params, UserValidate::class . '.editInfo');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', UserModel::editInfo($params));
    }

    /**
     * 新增或修改地址
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name=address_id, type=integer, required=false, description="address_id")
     * @ApiParams (name=address, type=string, required=true, description="送货地址 省 市 区")
     * @ApiParams (name=street, type=string, required=true, description="街道")
     * @ApiParams (name=is_default, type=integer, required=false, description="是否默认")
     * @ApiParams (name=contactor_name, type=string, required=true, description="联系人")
     * @ApiParams (name=phone, type=string, required=true, description="联系电话")
     */
    public function editAddress()
    {
        $params = $this->request->post();
        $result = $this->validate($params, UserValidate::class . '.edit');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', AddressModel::edit($params));
    }

    /**
     * 删除地址
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     * @ApiParams (name=address_id, type=integer, required=false, description="地址Id")
     */
    public function delAddress()
    {
        $params = $this->request->post();
        $result = $this->validate($params, UserValidate::class . '.deleteAddress');
        if (true !== $result) {
            return $this->error($result);
        }
        return $this->success('', AddressModel::del($params));
    }

    /**
     * 获取地址信息
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     */
    public function getAddress()
    {
        return $this->success('', AddressModel::getList());
    }

    /**
     * 获取浏览历史
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     */
    public function viewList()
    {
        return $this->success('', HistoryModel::getList());
    }
    /**
     * 获取收藏
     * @ApiHeaders (name=Xshop-Token, type=string, required=true, description="请求的Token")
     */
    public function favorite()
    {
        return $this->success('', FavoriteModel::getList());
    }

    /**
     * 获取收藏分组
     */
    public function favorite_group() {
        $user = UserModel::info();
        $res = FavGroup::where([
            'user_id' => $user->id
        ])->select();
        return $this->success('', $res);
    }

    /**
     * 添加收藏分组
     */
    public function add_favorite_group() {
        $post = request()->post();
        $user = UserModel::info();
        $res = FavGroup::create([
            'user_id' => $user->id,
            'group_name' => $post['group_name']
        ]);
        return $this->success('', $res);
    }

    /**
     * 删除收藏分组
     */
    public function remove_favorite_group() {
        $user = UserModel::info();
        $post = request()->post();
        $res = FavGroup::destroy($post['id']);

        $fav = new FavoriteModel();
        $fav->save([
            'group_id' => '0'
        ], [
            'user_id' => $user->id,
            'group_id' => $post['id']
        ]);

        return $this->success('', $res);
    }


    # 资金明细
    public function transition() {
        $user = UserModel::info();
        $res = MoneyLog::where([
            'user_id' => $user->id
        ])->select();
        if ($res) {
            foreach($res as $k => $item) {
                $item['createtime'] = date('m月d日 H:i', strtotime($item->createtime));
                $res[$k] = $item;
            }
        }
        return $this->success('', $res);
    }


    # 账号列表
    public function account() {
        $user = UserModel::info();
        $res = UserAccount::where([
            'user_id' => $user->id
        ])->select();
        return $this->success('', $res);
    }

    # 检查账号
    public function account_check() {
        $post = request()->post();
        
        $service = new crawler($post['url']);
        try {
            $res = $service->parse();
            
        } catch (Error $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('', $res);

    }

    # 创建新的账号
    public function account_create() {
        $user = UserModel::info();
        $post = request()->post();
        
        // dump($post);

        // array(3) {
        //     ["url"] => string(21) "https://b23.tv/GY9yKQ"
        //     ["src"] => string(59) "http://tmp/5CQ65N0HBfVz7402a42edc0f0e59cd839d4600f45c2e.jpg"
        //     ["account"] => array(5) {
        //       ["nickname"] => string(11) "超级up猪"
        //       ["avatar"] => string(73) "http://i1.hdslb.com/bfs/face/24bb8b6ac664369fbf4021f20f7af897fc5e4b7c.jpg"
        //       ["fans"] => string(1) "0"
        //       ["authTitle"] => string(0) ""
        //       ["date"] => string(19) "2021-09-03 15:58:31"
        //     }
        //   }

        $post['account']['fans'] = str_replace(',', '', $post['account']['fans']);
        if (strrpos('w', $post['account']['fans'])) {
            $post['account']['fans'] = str_replace('w', '', $post['account']['fans']);
            $post['account']['fans'] = bcmul($post['account']['fans'], 1000, 0);
        }

        $res = UserAccount::create([
            'user_id'               => $user->id,
            'account_from'          => $post['account']['from'],
            'account'               => $post['account']['nickname'],
            'account_avatar'        => $post['account']['avatar'],
            'account_fans'          => $post['account']['fans'],
            'account_remark'        => $post['account']['authTitle'],
            'account_url'           => $post['url'],
            'account_screenshot'    => $post['src'],
            'status'                => 'pending',
            'create_at'             => date('Y-m-d H:i:s')
        ]);
        
        return $this->success('', $res);
    }

    # 图片上传
    public function upload() {
        $user = UserModel::info();

        $file = request()->file('file');
        
        $info = $file->move( './uploads');
        if ($info) {

            // 输出 jpg
            // echo $info->getExtension();

            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            // echo $info->getSaveName();

            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            // echo $info->getFilename();
            
            return $this->success('', str_replace('\\', '/', $info->getSaveName()));
        } else {
            // echo $file->getError();
            $this->error($file->getError());
        }
    }


    # 获取账号信息
    public function account_info() {
        $id = $this->request->get('id');

        $res = UserAccount::find($id);

        $this->success('', $res);
    }

    # 获取账号关联产品信息
    public function account_product() {
        $id = $this->request->get('id');
        
        // $res = UserAccount::find($id);
        $res = Product::where([
            'create_user' => $id
        ])->find();
        
        $sku = ProductSku::where([
            'product_id' => $res->id
        ])->select();
        if (!empty($sku)) {
            $res['sku'] = $sku;
        } else {
            $res['sku'] = [];
        }

        $this->success('', $res);
    }

    # 更新产品信息
    public function account_product_edit() {
        $post = $this->request->post();

        $res = Product::find($post['id']);

        Db::startTrans();
        try {
            $min_price = 999999999;

            ProductSku::destroy(function($query) use ($res) {
                $query->where('product_id', '=', $res->id);
            });

            $sku = new ProductSku;
            $list = [];
            foreach($post['sku'] as $v) {
                if (floatval($v['value']) > 0) {
                    if ($v['value'] < $min_price) $min_price = $v['value'];

                    $list[] = [
                        'product_id'    =>$res->id,
                        'keys'          => '类型',
                        'value'         => $v['label'],
                        'price'         => $v['value'],
                        'stock'         => 99999,
                        'weight'        => 1,
                        'sn'            => '1',
                        'market_price'  => $v['value'],
                        'sold_count'    => 0,
                        'image'         => ''
                    ];
                }
            }
            $sku->saveAll($list);

            $res->on_sale = $post['on_sale'];
            $res->price = $min_price;
            $res->service_tags = implode(',', $post['tags']);
            $res->save();

            // throw new Error('::::: DEBUG :::::');
            
            Db::commit();
        } catch (\think\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
        
        $this->success('修改成功');
    }

    # 产品标签
    public function account_product_service_tags() {
        $res = ServiceTag::select();
        $this->success('', $res);
    }
    
}

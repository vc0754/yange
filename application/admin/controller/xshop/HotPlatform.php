<?php

namespace app\admin\controller\xshop;

use app\common\controller\Backend;
use app\admin\library\xshop\Account;
use app\admin\model\xshop\Hot;
use think\Exception;
/**
 * 热搜平台
 *
 * @icon fa fa-circle-o
 */
class HotPlatform extends Backend
{
    
    /**
     * HotPlatform模型对象
     * @var \app\admin\model\xshop\HotPlatform
     */
    protected $model = null;
    protected $hotModel = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\xshop\HotPlatform;

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function getHot(){



        $date = date("Y-m-d");
        $platform = collection($this->model->select())->toArray() ;
//        $account = Account::register($platform[1]['nickname'], $platform[1]);
//
//        try {
//            $hot = $account->getHot();
//            var_dump($hot);
////            $asd = Hot::createOrUpdate($hot,$date,$platform[4]['id']);
////            var_dump($asd);
//        } catch (\think\Exception $e) {
//            throw new Exception($e->getMessage());
//        }

        foreach ($platform as $key=>$value){
            $account = Account::register($value['nickname'], $value);
            try {
                var_dump('222');
                $hot = $account->getHot();
                var_dump('111');
                Hot::createOrUpdate($hot,$date,$value['id']);
            } catch (\think\Exception $e) {
                throw new Exception($e->getMessage());
            }
        }
    }
    

}

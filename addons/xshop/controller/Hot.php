<?php

namespace addons\xshop\controller;

use addons\xshop\model\HotPlatformModel;
use addons\xshop\model\HotModel;
use addons\xshop\model\HotMonitorModel;
class Hot extends Base
{

    public function getNav()
    {
        $this->success('', HotPlatformModel::getlist());
    }

    public function index()
    {
        $params = $this->request->post();
        $this->success('', HotModel::getlist($params));
    }

    public function getMonitorList(){
        $params = $this->request->post();
        $this->success('', HotMonitorModel::getlist($params));
    }

    public function getMonitorItem(){
        $params = $this->request->post();
        $this->success('', HotMonitorModel::getItem($params));
    }

    public function monitor(){
        $params = $this->request->post();

        $params['kw'] = implode(",",$params['kw_list']);
        $params['platform'] = implode(",",$params['platform_list']);
        $params['createtime'] = time();

        $add = HotMonitorModel::addOrUpdate($params);

        if($add){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }




}
<?php

namespace addons\xshop\controller;

use addons\xshop\model\HotModel;
use think\addons\Controller;

class Image extends Controller
{


    public function createimage()
    {
        $params = $this->request->get();

        $this->view->assign('list', HotModel::getDate($params));
        $this->view->assign('data', $params);
        return $this->view->fetch();
    }
}
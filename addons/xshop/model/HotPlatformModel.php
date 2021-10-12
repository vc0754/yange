<?php
/**
 * Created by PhpStorm.
 * User: su
 * Date: 2021-5-4
 * Time: 14:38
 */

namespace addons\xshop\model;


class HotPlatformModel extends Model
{





    // 表名
    protected $name = 'xshop_hot_platform';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'page','loading_type','order_list','kw'
    ];


    public static function getlist(){
        return collection(self::select())->toArray();
    }

    public function getPageAttr($value, $data)
    {
        return 1;
    }
    public function getLoadingTypeAttr($value, $data)
    {
        return 'more';
    }
    public function getOrderListAttr($value, $data)
    {
        return [];
    }

    public function getKwAttr($value, $data)
    {
        return '';
    }

    protected function setKwAttr($value)
    {
        return '';
    }

    protected function setPageAttr($value)
    {
        return 1;
    }

    protected function setLoadingTypeAttr($value)
    {
        return 'more';
    }

    protected function setOrderListAttr($value)
    {
        return [];
    }







}
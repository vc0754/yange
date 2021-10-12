<?php

namespace addons\xshop\model;

class ServiceTagModel extends Model
{

    // 表名
    protected $name = 'xshop_service_tag';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'value'
    ];

    public static function getList(){
        return self::select();
    }

    public function getValueAttr($value, $data)
    {
        return $data['title'];
    }
    

    







}

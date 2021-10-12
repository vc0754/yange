<?php
/**
 * Created by PhpStorm.
 * User: su
 * Date: 2021-5-4
 * Time: 14:38
 */

namespace addons\xshop\model;


class HotMonitorModel extends Model
{





    // 表名
    protected $name = 'xshop_hot_monitor';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'kw_list','platform_list'
    ];

    public static function addOrUpdate($data){
        $list[] = $data;
        $monitor = new HotMonitorModel;
        return $monitor->allowField(true)->saveAll($list);
    }

    public static function getItem($params){
        extract($params);
        return self::where('id',$id)->find();
    }

    public static function getlist($params){
        extract($params);
        $model =self::where('user_id',$user_id);
        return $model->paginate(10);
    }

    public function getKwListAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['kw']) ? $data['kw'] : '');
        return explode(",",$value);
    }

    public function getPlatformListAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['platform']) ? $data['platform'] : '');
        return explode(",",$value);
    }









}
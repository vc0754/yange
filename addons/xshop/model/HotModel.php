<?php

namespace addons\xshop\model;



class HotModel extends Model
{
    // 表名
    protected $name = 'xshop_hot';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'first_time_text'
    ];

    public static function getlist($attributes)
    {
        extract($attributes);
        $model = self::where('date', $date)->where('platform', $platform);

        if (!empty($kw)) {
            $model->where(function ($query) use ($kw) {
                return $query->where('word', 'like', "%$kw%");
            });
        }

        $model->orderRaw('convert(hot_value,SIGNED) DESC');
        return $model->paginate(10);
    }

    public static function getDate($attributes)
    {
        extract($attributes);
        $model = self::where('date', $date)->where('platform', $platform);
        if (!empty($kw)) {
            $model->where(function ($query) use ($kw) {
                return $query->where('word', 'like', "%$kw%");
            });
        }
        $model->orderRaw('convert(hot_value,SIGNED) DESC');
        return collection($model->limit(50)->select())->toArray();
    }

    public function getFirstTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['first_time']) ? $data['first_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFirstTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

}
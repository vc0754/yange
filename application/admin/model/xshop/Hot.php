<?php

namespace app\admin\model\xshop;

use think\Model;

use app\admin\library\xshop\Tools;
use think\Exception;
use think\Db;
class Hot extends Model
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


    public static function createOrUpdate($attrs, $date, $platform)
    {
        $oldList = collection(self::where('date', $date)->where('platform', $platform)->select())->toArray();
        $list = [];
        $result = [
            'insert' => 0,
            'update' => []
        ];
        foreach ($attrs as $i => $value) {
            unset($attrs[$i]['id']);
            $index = Tools::find_rows($oldList, ['_id' => $value['_id'], 'word' => $value['word'], 'date' => $value['date']]);
            if ($index > -1) {
                $attrs[$i]['top_ranking'] = $attrs[$i]['top_ranking'] < $oldList[$index]['top_ranking'] ? $attrs[$i]['top_ranking'] : $oldList[$index]['top_ranking'];
                $list[] = array_merge($value, ['id' => $oldList[$index]['id']]);
                $result['update'][] = $oldList[$index]['id'];
            } else {
                $list[] = $value;
                $result['insert'] += 1;
            }
        }

        $Hot = new Hot;
        if (!empty($list)) $Hot->allowField(true)->saveAll($list);

        return $result;
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

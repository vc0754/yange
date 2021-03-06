<?php

namespace addons\xshop\model;

use addons\xshop\exception\Exception;
use addons\xshop\exception\NotFoundException;
use addons\xshop\exception\NotLoginException;
use app\admin\library\xshop\Tools;

class AppSourceModel extends Model
{
    protected $name = 'xshop_app_update';
    protected $visible = [
    ];
    
    /**
     * 获取APP更新包
     * @param String $platform android ios
     * @param String $version 客户端当前版本
     */
    public static function checkUpdate($attributes)
    {
        extract($attributes);
        $model = self::where('platform', $platform)->where('status', 1)->order('id', 'DESC')->find();
        if (empty($model) || $model->version == $version) {
            return [
                'update' => 0
            ];
        }
        return [
            'update' => 1,
            'description' => $model->description,
            'wgtUrl' => cdnurl($model->source_file, true)
        ];
    }

    public static function checkUpdate_v2($attributes)
    {
        extract($attributes);
        $q = function($query) use ($platform) {
            $query->where('platform', 'IN', [$platform, 'all'])->where('status', 1);
        };
        $currentVersion = self::where('platform', 'IN', [$platform, 'all'])->where(['version' => $version])->order('id', 'DESC')->find();
        if (empty($currentVersion)) return [
            'update' => 0
        ];
        // 增量更新不可跨大版本
        // 获取最新的一个大版本 先更新大版本
        $model = self::where($q)->where(['version_type' => 1])->where('id', '>', $currentVersion->id)->order('id', 'DESC')->find();
        if (!empty($model)) { // 有大版本
            return [
                'update' => 1,
                'type' => 1,
                'silent' => $model->silent,
                'version' => $model->version,
                'description' => $model->description,
                'wgtUrl' => cdnurl($model->source_file, true)
            ];
        }
        $model = self::where($q)->order('id', 'DESC')->find();
        if (\addons\xshop\library\Str::compareVersion($model->version, $version)) {
            return [
                'update' => 1,
                'type' => 0,
                'silent' => $model->silent,
                'version' => $model->version,
                'description' => $model->description,
                'wgtUrl' => cdnurl($model->source_file, true)
            ];
        }
        return [
            'update' => 0
        ];
    }
}

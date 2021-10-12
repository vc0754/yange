<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'connector' => 'Redis',         // 'Sync

    'expire'    => 60,
    'default'   => 'default',
    'host'      => 'localhost',
    'port'      => 6379,
    'password'  => '',
    'select'    => 0,
    'timeout'   => 0,
    'persistent'=> false
];

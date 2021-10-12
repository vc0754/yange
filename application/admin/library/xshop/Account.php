<?php

namespace app\admin\library\xshop;

class Account
{
    public static function register($name, $config = [])
    {
        $classname = __NAMESPACE__ . "\\services\\$name";
        return new $classname($config);
    }

    public static function getRefClass($name)
    {
        return new \ReflectionClass(self::register($name));
    }

    public static function getConstant($name, $key)
    {
        return self::getRefClass($name)->getConstants()[$key];
    }

}
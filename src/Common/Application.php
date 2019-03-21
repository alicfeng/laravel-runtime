<?php
/**
 * Created by PhpStorm   User: AlicFeng   DateTime: 19-3-21 下午5:02
 */

namespace AlicFeng\Runtime\Common;


class Application
{
    /**
     * @functionName   get application sign
     * @description    redis pre of key ...
     * @author         Alicfeng
     * @datetime       19-3-21 下午9:55
     * @return string
     */
    public static function getSign()
    {
        return md5(config('app.name'));
    }

    /**
     * @functionName   log dir path
     * @description    runtime
     * @author         Alicfeng
     * @datetime       19-3-21 下午9:56
     * @return string
     */
    public static function getRuntimeLogPath()
    {
        return storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR;
    }
}
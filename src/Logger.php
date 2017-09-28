<?php
/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 2017/9/28
 * Time: 13:25
 */

namespace Beauty;

Interface Logger
{
    /**
     * 记录日志
     *
     * @param $platform
     * @param $message
     * @return mixed
     */
    public function log($platform, $message);
}
<?php
/**
 * serverlog的队列记录
 * User: tigerkim
 * Date: 2017/9/27
 * Time: 16:36
 */

namespace Beauty;

class QueueLogger implements Logger
{
    static $_producer;

    function __construct($host, $port)
    {
        self::$_producer = new HttpSQS($host, $port);
    }

    /**
     * 将日志写入到队列
     *
     * @param    string $platform 平台
     * @param    string $message 日志消息内容
     * @return    bool
     */
    public function log($platform = 'www', $message = '')
    {
        if ($message == '') {
            return false;
        }

        if ($platform == '') {
            $platform = 'other';
        }

        $platform = strtolower($platform);

        self::$_producer->put("serverlog", json_encode([
            $platform => $message
        ]));

        return TRUE;
    }
}
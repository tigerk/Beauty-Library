<?php
/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 2017/9/27
 * Time: 16:36
 */

namespace Beauty\ServerLog;

class KafkaLogger implements Logger
{
    static $_producer;

    function __construct($hosts)
    {
        $conf = new \RdKafka\Conf();
        $conf->set('socket.timeout.ms', 50); // or socket.blocking.max.ms, depending on librdkafka version
        if (function_exists('pcntl_sigprocmask')) {
            pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
            $conf->set('internal.termination.signal', SIGIO);
        } else {
            $conf->set('queue.buffering.max.ms', 1);
        }

        if (is_null(self::$_producer)) {
            self::$_producer = new \RdKafka\Producer($conf);
        };

        $lostrhost = implode(",", $hosts);
        self::$_producer->addBrokers($lostrhost);

    }

    /**
     * 将日志写入到文件
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

        $topic = self::$_producer->newTopic($platform);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message, JSON_UNESCAPED_UNICODE));
        self::$_producer->poll(1);

        return TRUE;
    }
}
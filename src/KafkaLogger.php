<?php
/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 2017/9/27
 * Time: 16:36
 */

namespace Beauty;

class KafkaLogger extends Logger
{
    private $_producer;

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

        $this->_producer = new \RdKafka\Producer($conf);

        $lostrhost = implode(",", $hosts);
        $this->_producer->addBrokers($lostrhost);

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

        $topic = $this->_producer->newTopic($platform);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message, JSON_UNESCAPED_UNICODE));

        while ($this->_producer->getOutQLen() > 0) {
            $this->_producer->poll(1);
        }

        return TRUE;
    }
}
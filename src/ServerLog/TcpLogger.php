<?php
/**
 * 使用socket发送日志，timeout设置在10ms
 */

namespace Beauty\ServerLog;


class TcpLogger implements Logger
{
    private $host;
    private $port;

    function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * 将日志发送到Http服务器
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

        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        $socket  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0.10, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0.01, 'usec' => 0));
        @socket_connect($socket, $this->host, $this->port);
        @socket_write($socket, $message, strlen($message));
        socket_close($socket);

        return true;
    }
}
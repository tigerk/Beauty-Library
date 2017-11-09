<?php
/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 2017/9/27
 * Time: 12:02
 */

namespace Beauty\ServerLog;


class HttpLogger implements Logger
{
    private $_url;

    function __construct($server)
    {
        $this->_url = $server;
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
        if ($platform == '') {
            $platform = 'other';
        }
        $platform     = strtolower($platform);
        $loarrContent = array(
            'type' => $platform,
            'slog' => $message
        );

        $data = http_build_query($loarrContent);
        $ch   = curl_init($this->_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10);
        curl_exec($ch);
        $errNo      = curl_errno($ch);
        $errMessage = curl_error($ch);
        if ($errNo > 0) {
            $filepath = "/tmp/serverLog_{$platform}_error_" . date('YmdH') . '.log';
            error_log("{$errNo}={$errMessage}\n", 3, $filepath);

            return false;
        }

        return true;
    }
}
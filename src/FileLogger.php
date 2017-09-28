<?php
/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 2017/9/27
 * Time: 12:02
 */

namespace Beauty;


class FileLogger implements Logger
{
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
        $platform     = strtolower($platform);
        $logfile_path = "/tmp/";
        $filepath     = $logfile_path . 'sys_log_' . $platform . '_' . date('YmdH') . '.log';

        if ($platform == 'api') {
            $starttime = microtime();
            $start     = explode(" ", $starttime);
            error_log(json_encode($message, JSON_UNESCAPED_UNICODE) . "\n", 3, $filepath);
            $endtime = microtime();
            $end     = explode(" ", $endtime);
            $runtime = sprintf("%f", ($end[0] - $start[0]) + ($end[1] - $start[1]));
            error_log("[" . date('Y-m-d H:i:s') . "]" . $message['req']['logid'] . "\t" . $runtime . "\n", 3, "/tmp/que" . date("md") . ".log");
        } else {
            error_log(json_encode($message, JSON_UNESCAPED_UNICODE) . "\n", 3, $filepath);
        }

        return TRUE;
    }
}
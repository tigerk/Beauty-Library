<?php

namespace Beauty\ServerLog;

/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 2017/9/8
 * Time: 17:36
 */
class DgServerLog
{
    protected $_enabled = TRUE;
    protected $_method  = "file";
    /**
     * 设置项目名称
     *
     * @var null|string
     */
    protected $_projectName = "api";

    protected $_logger;

    /**
     * DgServerLog constructor.
     * @param null $project
     */
    function __construct(Logger $logger)
    {
        $this->_logger = $logger;
    }

    function setProjectName($project)
    {
        $this->_projectName = $project;
    }

    function diffTime($start_time = array(), $end_time = array())
    {
        $time = 0;
        if (!is_numeric($start_time['sec']) || !is_numeric($start_time['usec'])) {
            return $time;
        }
        if (!is_numeric($end_time['sec']) || !is_numeric($end_time['usec'])) {
            $end_time = gettimeofday();
        }

        $diff_sec  = $end_time['sec'] - $start_time['sec'];
        $diff_usec = $end_time['usec'] - $start_time['usec'];
        $time      = ($diff_sec + $diff_usec / 1000000) * 1000;

        return (int)$time;
    }

    /**
     * write2log 用于统计日志
     * @author kimhwawoon
     * @date 2017-02-22
     *
     * @param string $file 文件
     * @param string $class 类
     * @param string $function 方法
     * @param int $line 行
     * @param array $other 其他参数
     * @return mixed
     */
    function log($file = '', $class = '', $function = '', $line = 0, $other = array())
    {
        /**
         * num: 请求数量
         * resnum: 返回结果数量
         * srctype: 统计来源
         * type: 日志类型
         */
        $user_id         = isset($other['user_id']) ? $other['user_id'] : 0;
        $type            = isset($other['type']) ? $other['type'] : '';
        $obj             = isset($other['obj']) ? $other['obj'] : 0;
        $num             = isset($other['num']) ? $other['num'] : 0;
        $resnum          = isset($other['resnum']) ? $other['resnum'] : 0;
        $comment         = isset($other['comment']) ? $other['comment'] : '';
        $ispv            = isset($other['ispv']) ? $other['ispv'] : 1;
        $uri             = isset($other['uri']) ? $other['uri'] : '';
        $qtype_sub       = isset($other['qtype_sub']) ? $other['qtype_sub'] : '';
        $page_num        = isset($other['page_num']) ? $other['page_num'] : '';
        $app_key         = isset($other['app_key']) ? $other['app_key'] : '';
        $login_type      = isset($other['login_type']) ? $other['login_type'] : '';
        $login_method    = isset($other['login_method']) ? $other['login_method'] : '';
        $register_type   = isset($other['register_type']) ? $other['register_type'] : '';
        $register_method = isset($other['register_method']) ? $other['register_method'] : '';

        /**
         * 所有的api请求都会有ss请求参数
         * 搜索来源，用于统计 ss = search source
         */
        $sourcetype = isset($_POST['_vs']) ? $_POST['_vs'] : (isset($_POST['ss']) ? $_POST['ss'] : '');
        // 记录用户登录的agentid
        $agentid = isset($_REQUEST['agent_id']) ? $_REQUEST['agent_id'] : "";
        // 记录搜索路径
        $ext = isset($_POST['_ext']) ? $_POST['_ext'] : "";

        $logconf        = array(
            "log_start_time" => gettimeofday(),
            "log_read_time"  => array(),
        );
        $log_start_time = $logconf['log_start_time'];
        $log_read_time  = $logconf['log_read_time'];

        //整理变量
        $msg                           = array();
        $msg['ispv']                   = (int)(bool)$ispv;
        $msg['uid']                    = (is_numeric($user_id) && $user_id > 0) ? $user_id : 0;
        $msg['read']                   = (is_array($log_read_time) && !empty($log_read_time)) ? json_encode($log_read_time) : '';
        $msg['file']                   = basename($file) . ':' . $line;
        $msg['total']                  = $this->diffTime($log_start_time);
        $msg['type']                   = $type;
        $msg['uri']                    = $uri != '' ? $uri : strtolower($class) . '/' . strtolower($function);
        $msg['obj']                    = $obj;
        $msg['num']                    = $num;
        $msg['resnum']                 = $resnum;
        $msg['comment']                = $comment;
        $msg['qtype_sub']              = $qtype_sub;
        $msg['srctype']                = $sourcetype;
        $msg['agentid']                = $agentid;
        $msg['sessionid']              = isset($_POST['_session']) ? $_POST['_session'] : "";
        $msg['page_num']               = $page_num;
        $msg['ext']                    = $ext;
        $msg['req']['souc']            = $app_key;
        $msg['req']['login_type']      = $login_type;    // 登录方式
        $msg['req']['login_method']    = $login_method; // 登录类型
        $msg['req']['register_type']   = $register_type;  // 注册方式
        $msg['req']['register_method'] = $register_method;  // 注册类型


        return $this->logData('NOTICE', $this->_projectName, $msg);
    }

    /**
     * 记录日志
     *
     * @param    string $level the log level
     * @param    string $plat enum(www\api\wap)
     * @param array|string $msg the log message
     * @return    bool
     */
    public function logData($level = 'error', $plat = 'www', $msg = array())
    {
        if ($this->_enabled === FALSE || empty($msg)) {
            return FALSE;
        }

        $level   = strtolower($level);
        $msgdata = $this->_genLogData($level, $msg, $plat);

        $this->_logger->log($plat, $msgdata);

        return TRUE;
    }

    /**
     * 生成日志信息
     *
     * @param string $level 错误级别
     * @param array|string $msg 日志消息内容
     * @param $plat
     * @return array
     */
    private function _genLogData($level = '', $msg = array(), $plat = 'www')
    {
        $data                      = [];
        $now_time                  = date("Y-m-d H:i:s");
        $data['level']             = $level;
        $data['time']              = $now_time;
        $data['file']              = isset($msg['file']) ? $msg['file'] : '0';
        $data['ispv']              = isset($msg['ispv']) ? (string)$msg['ispv'] : '0';
        $data['optime']['read']    = isset($msg['read']) ? $msg['read'] : '0';
        $data['optime']['total']   = isset($msg['total']) ? (string)$msg['total'] : '0';
        $data['req']['logid']      = isset($_SERVER['HTTP_X_DGS_RID']) ? (string)$_SERVER['HTTP_X_DGS_RID'] : '';
        $data['req']['souc']       = isset($_SERVER['HTTP_CLIENT']) ? (string)$_SERVER['HTTP_CLIENT'] : '';
        $data['req']['devi']       = isset($_SERVER['HTTP_DEVICE']) ? addslashes($_SERVER['HTTP_DEVICE']) : '';
        $data['req']['sys']        = isset($_SERVER['HTTP_SDK']) ? (string)$_SERVER['HTTP_SDK'] : '';
        $data['req']['dname']      = isset($_SERVER['HTTP_USER_AGENT']) ? addslashes($_SERVER['HTTP_USER_AGENT']) : '';
        $data['req']['chan']       = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : '';
        $data['req']['vers']       = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        $data['req']['mac']        = isset($_SERVER['HTTP_MAC']) ? $_SERVER['HTTP_MAC'] : '';
        $data['req']['imei']       = isset($_SERVER['HTTP_IMEI']) ? $_SERVER['HTTP_IMEI'] : '';
        $data['req']['ifa']        = isset($_SERVER['HTTP_IFA']) ? $_SERVER['HTTP_IFA'] : '';
        $data['req']['city_id']    = isset($_SERVER['HTTP_CID']) ? $_SERVER['HTTP_CID'] : '';
        $data['req']['reso']       = isset($_SERVER['HTTP_RESOLUTION']) ? addslashes($_SERVER['HTTP_RESOLUTION']) : '';
        $data['req']['euid']       = md5($data['req']['imei'] . '_' . $data['req']['mac']);
        $data['req']['dpi']        = isset($_SERVER['HTTP_DPI']) ? addslashes($_SERVER['HTTP_DPI']) : '';
        $data['req']['host']       = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] : '';
        $data['req']['ip']         = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '';
        $data['req']['uid']        = isset($msg['uid']) ? (string)$msg['uid'] : '0';
        $data['req']['qtype']      = isset($msg['type']) ? $msg['type'] : '';
        $data['req']['qtype_sub']  = isset($msg['qtype_sub']) ? $msg['qtype_sub'] : '';
        $data['req']['obj']        = isset($msg['obj']) ? (string)$msg['obj'] : '';
        $data['req']['uri']        = isset($msg['uri']) ? $msg['uri'] : '';
        $data['req']['qnum']       = isset($msg['num']) ? (string)$msg['num'] : '0';
        $data['req']['refer']      = isset($_SERVER['HTTP_REF']) ? addslashes($_SERVER['HTTP_REF']) : (isset($_SERVER['HTTP_REFER']) ? addslashes($_SERVER['HTTP_REFER']) : '');
        $data['req']['lat']        = isset($_SERVER['HTTP_LAT']) ? addslashes($_SERVER['HTTP_LAT']) : '';
        $data['req']['lon']        = isset($_SERVER['HTTP_LON']) ? addslashes($_SERVER['HTTP_LON']) : '';
        $data['req']['srctype']    = isset($msg['srctype']) ? $msg['srctype'] : '';
        $data['req']['sessionid']  = isset($msg['sessionid']) ? $msg['sessionid'] : '';
        $data['req']['agentid']    = isset($msg['agentid']) ? $msg['agentid'] : '';
        $data['req']['ext']        = isset($msg['ext']) ? $msg['ext'] : '';
        $data['req']['page_num']   = isset($msg['page_num']) ? $msg['page_num'] : '';
        $data['resnum']            = isset($msg['resnum']) ? (string)$msg['resnum'] : '0';
        $data['cmmt']              = isset($msg['comment']) ? $msg['comment'] : '';
        $data['req']['android_id'] = isset($_SERVER['android_id']) ? $_SERVER['android_id'] : '';
        $data['req']['pseudo_id']  = isset($_SERVER['pseudo_id']) ? $_SERVER['pseudo_id'] : '';
        $data['req']['rext']       = isset($msg['rext']) ? $msg['rext'] : '';
        /**
         * 652版本开始添加的header信息
         */
        $data['req']['brand']    = isset($_SERVER['HTTP_BRAND']) ? $_SERVER['HTTP_BRAND'] : '';
        $data['req']['scale']    = isset($_SERVER['HTTP_SCALE']) ? $_SERVER['HTTP_SCALE'] : '';
        $data['req']['timezone'] = isset($_SERVER['HTTP_TIMEZONE']) ? $_SERVER['HTTP_TIMEZONE'] : '';
        $data['req']['carrier']  = isset($_SERVER['HTTP_CARRIER']) ? $_SERVER['HTTP_CARRIER'] : '';
        $data['req']['language'] = isset($_SERVER['HTTP_LANGUAGE']) ? $_SERVER['HTTP_LANGUAGE'] : '';
        $data['req']['cns']      = isset($_SERVER['HTTP_CNS']) ? $_SERVER['HTTP_CNS'] : '';
        $data['req']['reach']    = isset($_SERVER['HTTP_REACH']) ? $_SERVER['HTTP_REACH'] : '';
        $data['req']['imsi']     = isset($_SERVER['HTTP_IMSI']) ? $_SERVER['HTTP_IMSI'] : '';

        $data['plat'] = strtoupper($plat);
        // APP中的HTML5页面访问统计
//        if (isset($msg['req'])) {
//            $data['req']['login_type']      = isset($msg['req']['login_type']) ? $msg['req']['login_type'] : '';    // 登录方式
//            $data['req']['login_method']    = isset($msg['req']['login_method']) ? $msg['req']['login_method'] : ''; // 登录类型
//            $data['req']['register_type']   = isset($msg['req']['register_type']) ? $msg['req']['register_type'] : '';  // 注册方式
//            $data['req']['register_method'] = isset($msg['req']['register_method']) ? $msg['req']['register_method'] : '';  // 注册类型
//        }

        return $data;
    }
}
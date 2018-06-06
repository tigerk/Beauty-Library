<?php

namespace Beauty\Lib;

use Beauty\Core\App;
use Beauty\Cache\RedisClient;
use Beauty\Log\DLog;

/**
 * 微信发送消息通知
 * User: wxq
 * Date: 2018/05/31
 * Time: 16:14
 */
class WxMessage
{
    // 微信appid
    private $appkey                 = "";
    // 微信secret
    private $appsecret              = "";
    // 抽奖服务通知的模板
    private $templateId             = "bYd2tEMuk9X4X6UmbnvNoVKwh7mg3g1bRoAZDUgD57c";
    // 获取accesstoken的url
    private $getWxTokenUrl          = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=";
    // 获取模板的url
    private $getWxTemplateUrl       = "https://api.weixin.qq.com/cgi-bin/wxopen/template/list?access_token=";
    // 发送服务通知的模板
    private $sendWxTemplateMsgUrl   = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=";

    private $accessToken            = "";
    private $accessTokenCacheKey    = "dg_wx_accesstoken_key_%s";

    public function __construct($config = [])
    {
        $this->appkey           = $config['appkey'];
        $this->appsecret        = $config['appsecret'];
        $this->_init();
    }

    public function _init()
    {
        $this->getAccessToken();

        if (!$this->accessToken) {
            throw new \Exception("get wx access token error", 1);
        }
    }

    /**
     * 设置模板id
     * @param string $id
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
    }

    /**
     * 获取模板id
     * @return mixed|string
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * 发送微信服务通知
     * @param string $openId  接收消息的openid(必须是小程序的openid）
     * @param string $form_id  提交的formid或者支付的replayid
     * @param string $url  跳转的小程序的链接
     * @param string $name  通知的名字
     * @param string $award 通知的内容
     * @return bool
     */
    function sendMsg($openId, $formId, $url = "", $name="", $award="")
    {

        if (!$openId || !$formId || !$name || !$award) {
            return false;
        }
        $post['touser']                     = $openId;
        $post['template_id']                = $this->templateId;
        $post['form_id']                    = $formId;
        $url && $post['page'] = $url;
        $post['data']['keyword1']['value']  = $name;
        $post['data']['keyword2']['value']  = $award;

        $url = $this->sendWxTemplateMsgUrl . $this->accessToken;
        $ret = $this->http($url, 'POST', $post);
        $list = json_decode($ret,true);

        return $list['errcode'] == 0 ? true : false;
    }

    /**
     * 获取微信模板列表
     * return array
     */
    function getWxTemplates()
    {
        $url            = $this->getWxTemplateUrl . $this->accessToken;
        $post['offset'] = 0;
        $post['count']  = 20;
        $data           = $this->http($url, 'POST', $post);
        return $data;
    }

    /**
     * 获取access_token
     */
    function getAccessToken()
    {
        $cache_key   = sprintf($this->accessTokenCacheKey, $this->appkey);
        $redisClient = new RedisClient();
        $result      = $redisClient->instance()->remember($cache_key, 7000, function () {
            $url     = $this->getWxTokenUrl . $this->appkey . "&secret=" .$this->appsecret;
            // 如果是企业号用以下URL获取access_token
            $res          = json_decode($this->http($url, 'GET'));
            $access_token = $res->access_token;
            $data         = [];
            if ($access_token) {
                $data['expire_time']  = time() + 7000;
                $data['access_token'] = $access_token;
                $this->accessToken    = $access_token;
            }
            DLog::notice('getwxaccesstoken', 0, ['url' => $this->getWxTokenUrl, 'res' => $res]);
            return $data;
        });
        $this->accessToken  = $result['access_token'];
    }

    /**
     * 发送curl请求
     * @param $url
     * @param $method
     * @param array $postfields
     * @return int|mixed
     */
    private function  http($url, $method, $postfields = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (strtoupper($method) == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret = curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
    }
}
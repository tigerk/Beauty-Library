<?php

/************************************************************
 * RC4加密解密算法
 * Add by kimhwawoon
 ************************************************************/

namespace Beauty\Lib;

class Rc4
{
    /**
     * 根据需求修改加密Key值
     */
    const ENCRYPT_KEY = 'D0uguo$TK#hwawo0n';

    public function __construct()
    {
    }

    /**
     * 解密
     * @param string $data 加密后的数据
     * @param string $oriStr 密钥原始串
     * @return string
     */
    public static function decryptKey($data, $oriStr)
    {
        //decrypt
        $key     = self::getEncryptKey($oriStr);
        $content = base64_decode($data);
        $decrypt = self::rc4($key, $content);

        return $decrypt;
    }

    /**
     * 加密
     * @param string $data 加密前的数据
     * @param string $oriStr 密钥原始串
     * @return string
     */
    public static function encryptKey($data, $oriStr)
    {
        //decrypt
        $key     = self::getEncryptKey($oriStr);
        $encrypt = self::rc4($key, $data);
        $content = base64_encode($encrypt);

        return $content;
    }

    /*
     * 获取密钥
     */
    public static function getEncryptKey($uid)
    {
        $salt1       = substr($uid, 20, 1) . substr($uid, 14, 1);
        $salt2       = substr($uid, -1) . substr($uid, -8, 1);
        $key_encrypt = hash('sha256', $salt1 . self::ENCRYPT_KEY . $salt2);

        return $key_encrypt;
    }

    /*
     * rc4加密算法
     * $pwd 密钥
     * $data 要加密的数据
     */
    public static $state_length = 256;

    public static function rc4($pwd, $data) //$pwd密钥　$data需加密字符串
    {
        $key[]       = "";
        $box[]       = "";
        $cipher      = "";
        $pwd_length  = strlen($pwd);
        $data_length = strlen($data);
        for ($i = 0; $i < self::$state_length; $i++) {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < self::$state_length; $i++) {
            $j       = ($j + $box[$i] + $key[$i]) % self::$state_length;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a       = ($a + 1) % self::$state_length;
            $j       = ($j + $box[$a]) % self::$state_length;
            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k       = $box[(($box[$a] + $box[$j]) % self::$state_length)];
            $cipher  .= chr(ord($data[$i]) ^ $k);
        }

        return $cipher;
    }

}

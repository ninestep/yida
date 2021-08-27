<?php

namespace Shenhou\Dingtalk;
// 应用公共文件
//该公共方法获取和全局缓存js-sdk需要使用的access_token
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\InvalidArgumentException;

class common extends YiDa
{
    /**
     * 获取配置
     * @param null|string $key 如果为空则返回全部配置否则返回对应配置内容
     * @return mixed|null
     * @throws YiDaException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public static function config($key = null)
    {
        if (Cache::has('config')) {
            $config = Cache::get('config');
        } else {
            throw new YiDaException('配置信息为空');
        }
        if ($key != null) {
            if (empty($config[$key])) {
                return null;
            }
            return $config[$key];
        } else {
            return $config;
        }
    }

    private static function nicInfo()
    {
        $cmd = '/sbin/ifconfig eth0|/usr/bin/head -2';
        $output = `$cmd`;
        if (!$output) {
            return false;
        }
        $lines = explode("\n", $output);
        $ret = array();
        foreach ($lines as $line) {
            $tmp = array();
            if (preg_match('/HWaddr ((?:[0-9A-Fa-f]{2}:)+[0-9A-Fa-f]{2})/', $line, $tmp)) {
                $ret['mac'] = $tmp[1];
                continue;
            }
            if (preg_match('/inet addr:((?:[0-9]{1,3}\.)+[0-9]{1,3})/', $line, $tmp)) {
                $ret['ip'] = $tmp[1];
                continue;
            }
        }
        return $ret;
    }

    private static function headers($api, $method, $params)
    {
        $timestamp = time();
        // 注意请求epaas必须加8个小时，否则与epaas时间匹配不上
        $timestamp += 28800;
        if (!$api || !$method || !$params) {
            return false;
        }
        $init = self::config();

        $formatTime = strftime('%Y-%m-%dT%H:%M:%S.000+08:00', $timestamp);
        $nonce = sprintf('%d000%d', $timestamp, rand(1000, 9999));

        ksort($params, SORT_STRING);
        $ret = array();
        foreach ($params as $k => $v) {
            $ret[] = sprintf('%s=%s', $k, $v);
        }
        $sig = self::signature($method, $formatTime, $nonce, $api, implode('&', $ret));

        return array(
            'X-Hmac-Auth-Timestamp' => $formatTime,
            'X-Hmac-Auth-Version' => '1.0',
            'X-Hmac-Auth-Nonce' => $nonce,
            'apiKey' => $init['api_key'],
            'X-Hmac-Auth-Signature' => $sig
        );
    }

    private static function signature($method, $timestamp, $nonce, $uri, $params)
    {
        $api_secret = self::config('api_secret');

        $bytes = sprintf("%s\n%s\n%s\n%s\n%s", $method, $timestamp, $nonce, $uri, $params);
        $hash = hash_hmac('sha256', $bytes, $api_secret, true);
        return base64_encode($hash);
    }

    /**
     * 以POST方式请求epaas
     * @param $url
     * @param array $params
     * @param int $timeout
     * @param array $headerAry
     * @param bool $onlyReturnContent 是否只返回结果中的content
     * @return mixed
     */
    public static function curlPost($uri, $params = array(), $timeout = 30, $headerAry = array(), $onlyReturnContent = true)
    {
        $url = 'https://s-api.alibaba-inc.com' . $uri;
        $params['appType'] = self::config('app_type');
        $params['systemToken'] = self::config('system_token');
        $params['userId'] = self::config('user_id');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $headerAry = self::headers($uri, 'POST', $params);

        if ($headerAry) {
            $tmp = array();
            foreach ($headerAry as $k => $v) {
                $tmp[] = sprintf('%s: %s', $k, $v);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $tmp);
        }
        $data = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            $msg = "epaasCurlPost|curl error: " . $error . "|" . $url . "|";
            error_log($msg);
        }
        curl_close($ch);
        $ret = json_decode($data, true);
        if (!$ret['success']) {
            $msg = "epaasCurlPost|result not success: " . $data . "|" . $url . "|";
            error_log($msg);
        }

        if ($onlyReturnContent) {
            return $ret['content'];
        } else {
            return $ret;
        }

    }

    /**
     * get请求
     * @param string $uri 地址
     * @param array $data 请求参数
     * @return array 返回值
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function requestGet($uri, $data)
    {
        $client = new Client([
            'base_uri' => 'https://s-api.alibaba-inc.com',
            'timeout' => 30,
            'allow_redirects' => false,
        ]);
        $data['appType'] = self::config('appType');
        $data['systemToken'] = self::config('systemToken');
        $data['userId'] = self::config('userId');
        $data['language'] = self::config('language');
        $headers = self::headers($uri, 'GET', $data);
        try {
            $res = $client->request('GET', $uri,
                [
                    'headers' => $headers,
                    'query' => $data
                ]);
        } catch (GuzzleException $e) {
            throw new YiDaException($e->getMessage());
        }
        $data = json_decode($res->getBody()->getContents(), true);
        if ($data['success']) {
            return $data;
        } else {
            throw new YiDaException($data['errorMsg'], $data['errorCode']);
        }
    }
}



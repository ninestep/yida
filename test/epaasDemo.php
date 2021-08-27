<?php
/**
 * epaas示例
 */

// php调用alifocus应用提供的epaas服务判断内容中的敏感词
$content = '这里是待检测的文本内容';
$result = getFilterWords($content);
// 输出 $content中包含的敏感词
print_r($result);
die;


function configs()
{
    /**
     * 阿里味儿日常epaas配置信息
     */
    $configs['epaas'] = array(
        'api_server' => 'https://s-api.alibaba-inc.com',
        'api_version' => '1.0',
        // 超时时间，单位秒
        'api_timeout' => 3,
        'api_nic' => 'eth0',
        'api_key' => 'shanxishengsenlingonganju-Db7K2Xw5KOR4k4E0q7OAidPJ8bl2t89qURRjeu',
        'api_secret' => '31T4F37jG6ga1k5Z0kN5V2GPoTkACtFfShO27D46',
    );
    return $configs;
}

function epaasNicInfo()
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

function epaasSignature($method, $timestamp, $nonce, $uri, $params)
{
    $init = configs();

    $bytes = sprintf("%s\n%s\n%s\n%s\n%s", $method, $timestamp, $nonce, $uri, $params);
    $hash = hash_hmac('sha256', $bytes, $init['epaas']['api_secret'], true);
    return base64_encode($hash);
}

function epaasHeaders($api, $method, $params)
{
    $timestamp=time();
    // 注意请求epaas必须加8个小时，否则与epaas时间匹配不上
    $timestamp += 28800;
    if (!$api || !$method || !$params) {
        return false;
    }
//    $addr = epaasNicInfo();
//    if (!$addr) {
//        return false;
//    }
    $init = configs();

    $formatTime = strftime('%Y-%m-%dT%H:%M:%S.000+08:00', $timestamp);
    $nonce = sprintf('%d000%d', $timestamp, rand(1000, 9999));

    ksort($params, SORT_STRING);
    $ret = array();
    foreach ($params as $k => $v) {
        $ret[] = sprintf('%s=%s', $k, $v);
    }
    $sig = epaasSignature($method, $formatTime, $nonce, $api, implode('&', $ret));

    return array(
        'X-Hmac-Auth-Timestamp' => $formatTime,
        'X-Hmac-Auth-Version' => $init['epaas']['api_version'],
        'X-Hmac-Auth-Nonce' => $nonce,
        'apiKey' => $init['epaas']['api_key'],
        'X-Hmac-Auth-Signature' => $sig
    );
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
function epaasCurlPost($url, $params = array(), $timeout = 1, $headerAry = array(), $onlyReturnContent = true)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
 * 获取$content中的敏感词
 *
 * @param $content
 */
function getFilterWords($content)
{
    if (!$content) {
        return;
    }

    try {
        $api = "/yida_vpc/process/getInstanceIds.json";
        $init = configs();

        $url = sprintf('%s%s', $init['epaas']['api_server'], $api);
        $params = array(
            'appType' => 'APP_G0ZO8DFXSC4XAYKR5W80',
            'systemToken' => 'TS766QC1PCASIZL7W03W146GVN8V1MX81QORK8',
            'userId' => 'yida_pub_account',
            'formUuid' => 'FORM-5L6664810Y9SNEHN538WRBRJHIMD2UY81QORKY1',
        );
        $headers = epaasHeaders($api, 'POST', $params);
        $ret = epaasCurlPost($url, $params, $init['epaas']['api_timeout'], $headers);
        return $ret;
    } catch (Exception $e) {
        $msg = "getFilterWords|err, code: " . $e->getCode() . "|message: " . $e->getMessage();
        error_log($msg);
        return;
    }
}
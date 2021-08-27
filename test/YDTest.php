<?php

namespace Shenhou\Tests;

use Shenhou\Dingtalk\YiDa;

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__.'/ENV.php';
class YDTest
{
    private $dingtalk;

    public function __construct()
    {
        ENV::loadFile('.env');
        $config = [
            'api_key' => ENV::get('yida.api_key'),
            'api_secret' => ENV::get('yida.api_secret'),
            'app_type' => ENV::get('yida.app_type'),
            'system_token' => ENV::get('yida.system_token'),
            'user_id' => ENV::get('yida.user_id'),
            'language' => ENV::get('yida.language'),
        ];
        try {
            $this->dingtalk = new YiDa($config);
        } catch (\Shenhou\Dingtalk\YiDaException $e) {
        }
    }

    public function getInstances()
    {
        $process = $this->dingtalk->process();
        $res = $process->getInstances('FORM-5L6664810Y9SNEHN538WRBRJHIMD2UY81QORKY1', []);
    }

}

$d = new YDTest();
$d->getInstances();
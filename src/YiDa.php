<?php


namespace Shenhou\Dingtalk;

/**
 * Class DingTalk
 * @package Shenhou\Dingtalk
 */
class YiDa
{
    protected static $config = [];

    /**
     * DingTalk constructor.
     * @param array $config
     *         $config = [
     *             'api_key' => 'aliway-php-lhrUU07DI433gBzUf6r',
     *             'api_secret' => '6C52MVi2OSe03PJL907Z858GnkDTl3922z0TgD12',
     *             'appType'=>'',
     *              'systemToken'=>'',
     *              'userId'=>'',
     *              'language'=>'zh_CN'
     *      ];
     */
    public function __construct($config = [])
    {
        if (empty($config)) {
            $config = Cache::get('config');
        } else {
            if (empty($config['language'])) {
                $config['language'] = 'zh_CN';
            }
            Cache::set('config', $config);
        }
        if (empty($config)) {
            throw new YiDaException('配置信息错误');
        }
        self::$config = $config;
    }

    public function process()
    {
        return new Process();
    }
}
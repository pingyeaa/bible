<?php
/**
 * 签名
 * User: enoch
 * Date: 16-1-11
 * Time: 下午2:35
 */
namespace frontend\components\sign;
use yii;
use yii\base\Component;

class Sign extends Component
{
    /**
     * 检测签名正确性
     * @param array $params 　参数
     * @param string $sign 签名
     * @param string $secretKey 签名密钥
     * @return bool
     */
    public function validate($params, $sign, $secretKey)
    {
        ksort($params);
        $url = urldecode(http_build_query($params) . ':' . $secretKey);
        if(sha1($url) !== $sign) {
            yii::info(var_export($params) ,'sign');
            yii::info('url: ' . $url ,'sign');
            yii::info(sprintf("origin_sign: %s \n new_sign: %s", sha1($url), $url) ,'sign');
            return false;
        }

        return true;
    }
}
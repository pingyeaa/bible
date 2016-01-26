<?php
/**
 * 七牛
 * User: enoch
 * Date: 16-1-26
 * Time: 下午2:44
 */

namespace frontend\components\qiniu;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use yii;

class Qiniu extends yii\base\Component
{
    public $accessKey;
    public $secretKey;
    public $bucket;

    /**
     * 上传文件
     * @param string $filePath 本地文件路径
     * @param string $key 保存到七牛的文件名称
     * @return bool
     */
    public function upload($filePath, $key)
    {
        $auth = new Auth($this->accessKey, $this->secretKey);
        $token = $auth->uploadToken($this->bucket);
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            var_dump($err);
        } else {
            var_dump($ret);
        }
        return true;
    }

}
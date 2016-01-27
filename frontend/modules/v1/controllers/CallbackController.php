<?php

namespace app\modules\v1\controllers;

use yii;
use common\models\NickList;
use common\models\Portrait;
use common\models\SmsRegisterBinding;
use common\models\User;
use common\models\UserNickBinding;
use yii\web\Controller;
use yii\base\Exception;

class CallbackController extends Controller
{
    public function actionQiniu()
    {
        yii::info('test', 'qiniu-callback');
//        file_put_contents('/home/enoch/文档/qiniu.log', var_export($_SERVER, true), FILE_APPEND);
//        file_put_contents('/home/enoch/文档/qiniu.log', var_export($_SERVER, true), FILE_APPEND);
//        file_put_contents('/home/enoch/文档/qiniu.log', var_export($_REQUEST, true), FILE_APPEND);
        echo 'ok';
    }

    protected function code($status = 200, $message = '', $data = [])
    {
        $response = yii::$app->getResponse();
        $response->setStatusCode($status);
        if(200 == $status) {
            $response->data = $data;
        }else {
            $response->data = [
                $message,
            ];
        }
        yii::$app->end(0, $response);
    }
}

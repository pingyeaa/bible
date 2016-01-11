<?php

namespace app\modules\v1\controllers;

use yii;
use yii\web\Controller;

class ApiController extends Controller
{
    public function beforeAction($action)
    {
        parent::beforeAction($action);

        $sign = $_REQUEST['sign'];
        unset($_REQUEST['sign']);
        $secretKey = yii::$app->params['Authorization']['sign']['secret_key'];
        if(!yii::$app->sign->validate($_REQUEST, $sign, $secretKey))
            $this->code(412, -1, '签名错误');
        return true;
    }

    public function actionUserLogin()
    {

        $response = yii::$app->getResponse();
        $response->data = [
            'code' => 2,
            'data' => [
                'user_id' => 1,
                'user_name' => 'Enoch',
                'sex' => 1,
            ],
        ];
        return $response;
    }

    protected function code($status = 200, $code = 0, $message = '', $data = [])
    {
        $response = yii::$app->getResponse();
        $response->setStatusCode($status);
        $response->data = [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
        yii::$app->end(0, $response);
    }
}

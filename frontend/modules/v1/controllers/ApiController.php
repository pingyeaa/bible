<?php

namespace app\modules\v1\controllers;

use yii;
use yii\web\Controller;

class ApiController extends Controller
{
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
}

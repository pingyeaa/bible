<?php

namespace app\modules\v1\controllers;

use yii\web\Controller;

class ApiController extends Controller
{
    public function actionUserLogin()
    {
        echo 'Hello World!';exit;
        return $this->render('index');
    }
}

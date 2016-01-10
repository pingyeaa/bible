<?php

namespace app\modules\v1;

use yii\filters\auth\HttpBasicAuth;
use yii\helpers\ArrayHelper;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\v1\controllers';

    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),[
                'authenticator' => [
                    'class' => HttpBasicAuth::className(),
                    'auth' => 'app\models\BasicAuth::auth',
                ]
            ]
        );
    }

    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}

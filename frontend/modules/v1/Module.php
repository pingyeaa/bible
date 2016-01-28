<?php

namespace app\modules\v1;

use yii;
use yii\filters\auth\HttpBasicAuth;
use yii\helpers\ArrayHelper;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\v1\controllers';

    public function behaviors()
    {
        //指定ip不需签名
        if(in_array(yii::$app->request->getUserIP(), yii::$app->params['WithoutVerifyIP'])){
            return parent::behaviors();
        }
        return ArrayHelper::merge(
            parent::behaviors(),[
                'authenticator' => [
                    'class' => HttpBasicAuth::className(),
                    'auth' => 'common\models\User::findByPasswordResetToken',
                    'except' => ['api/qiniu-callback']
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

<?php
namespace backend\controllers;

use common\models\Admin;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use common\models\LoginForm;
use yii\filters\VerbFilter;

/**
 * Site controller
 */
class SiteController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * 登录
     * @return string|\yii\web\Response
     */
    public function actionLogin()
    {
        if (isset($_SESSION['admin_id'])) {
            return $this->goHome();
        }

        if($post = Yii::$app->request->post()) {
            $result = Admin::validateIdentify($post['username'], md5($post['password']));
            if(!$result) {
                return $this->render('login', ['message' => '账号或密码错误']);
            }
            $_SESSION['admin_id'] = $result['id'];
            return $this->goBack();
        }
        return $this->render('login', ['message' => '']);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}

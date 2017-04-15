<?php
namespace backend\controllers;

use yii;
use yii\web\Controller;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        parent::beforeAction($action);
        $controllerName = strtolower(Yii::$app->controller->id);
        $actionName = strtolower(Yii::$app->controller->action->id);
        $withoutVerify = [
            'site' => ['login', 'error'],
        ];
        if(!isset($withoutVerify[$controllerName][$actionName])) {
            if(!isset($_SESSION['admin_id'])) {
                return $this->redirect('index.php/site/login');
            }
        }
        return true;
    }
}
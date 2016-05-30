<?php
namespace backend\controllers;

use yii\web\Controller;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;

    public function init()
    {

    }
}
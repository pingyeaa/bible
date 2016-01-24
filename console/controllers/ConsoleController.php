<?php

namespace console\controllers;

use common\models\NickList;
use yii;

class ConsoleController extends yii\console\Controller
{
    /**
     * 批量生成用户标识
     * @param integer $number 生成数量
     * @return bool
     */
    public function actionGenerateNick($number)
    {
        $nick = new NickList();
        return $nick->generate($number);
    }
}
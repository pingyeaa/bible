<?php

namespace console\controllers;

use common\models\AskedDaily;
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

    /**
     * 批量导入`每日一问`
     */
    public function actionImportQuestion()
    {
        $filePath = yii::$app->params['questionExcelPath'];
        $data = yii::$app->excel->import($filePath);
        foreach($data as $k => $v) {
            $title = $v['title'];
            $answer = $v['answer'];
            $url = $v['url'];
            foreach($v as $answerK => $answerV) {
                if(!empty($answerV) && $answerK != 'answer' && $answerK != 'question' && $answerK != 'title' && $answerK != '作者' && $answerK != 'url') {
                    $answer = $answerK . $answerV . "\n" . $answer;
                }
            }
            if($title) {
                $ask = new AskedDaily();
                $ask->add([
                    'title' => $title,
                    'content' => trim($answer),
                    'url' => $url,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'status' => 0,
                ]);
            }
        }
    }
}
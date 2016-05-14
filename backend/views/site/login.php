<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '登录';
?>
<div class="site-login">
    <div class="row">
        <div class="col-lg-5">
            <form action="" method="post" id="login-form">
                <label style="color: red"><?php echo $message;?></label><br>
                <label>用户名</label>
                <input type="text" name="username" class="form-control" /><br>
                <label>密码</label>
                <input type="password" name="password" class="form-control" /><br>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">提交</button>
                </div>
            </form>
        </div>
    </div>
</div>

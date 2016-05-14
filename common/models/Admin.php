<?php
namespace common\models;

use yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class Admin extends ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin.admin';
    }

    public function rules()
    {
        return [
            [['username', 'password', 'created_at', 'updated_at'], 'required'],
            [['status', 'last_login_time', 'last_login_ip'], 'safe'],
        ];
    }

    public static function validateIdentify($username, $password)
    {
        return self::find()->where(['username' => $username, 'password' => $password])->one();
    }

}

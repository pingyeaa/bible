<?php
namespace common\models;

use yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $created_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'public.user';
    }

    public function rules()
    {
        return [
            [['created_at', 'nation_code', 'username', 'password'], 'required'],
            [['nickname', 'gender', 'birthday', 'believe_date', 'status', 'last_login_at', 'province_name', 'city_name', 'province_id', 'city_id', 'openid', 'platform_id', 'union_id', 'portrait'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @param $nation_code
     * @return static|null
     */
    public static function findByUsernameAndNationCode($username, $nation_code)
    {
        return static::findOne(['username' => $username, 'nation_code' => $nation_code]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $username
     * @param string $password
     * @internal param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($username = '', $password = '')
    {
        $username_o = yii::$app->params['Authorization']['username'];
        $password_o = yii::$app->params['Authorization']['password'];

        if($username !== $username_o || $password_o !== $password) {
            yii::info(sprintf('Authorization-username: %s, password: %s', $username_o, $password_o), 'basic-auth');
            return null;
        }

        return new User();
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * 创建用户
     * @param array $data 用户数据
     * @return bool
     */
    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        if(!$this->save())
            return false;
        return $this->id;
    }

    /**
     * 查询用户是否存在
     * @param $nationCode
     * @param $phone
     * @return null|static
     */
    public function isExists($nationCode, $phone)
    {
        return $this->find()->where('nation_code = :nation_code and username = :phone', ['nation_code' => $nationCode, 'phone' => $phone])->one();
    }

    /**
     * 修改资料
     * @param $attributes
     * @param $userId
     * @return int
     */
    public static function mod($attributes, $userId)
    {
        return self::updateAll($attributes, 'id = :id', ['id' => $userId]);
    }

    /**
     * 根据国家码、电话、密码查询用户信息
     * @param $nationCode
     * @param $phone
     * @param $password
     * @return null|static
     */
    public static function findByPhoneAndPassword($nationCode, $phone, $password)
    {
        return self::findOne(['nation_code' => $nationCode, 'username' => $phone, 'password' => $password]);
    }

    /**
     * @param $userId
     * @return yii\db\DataReader
     */
    public static function getUserInfoAndAvastar($userId)
    {
        $sql = "
            select * from public.user a
            left join public.portrait b on a.id = b.user_id
            where a.id = %d order by b.id desc limit 1
        ";
        $sql = sprintf($sql, $userId);
        return self::getDb()->createCommand($sql)->queryOne();
    }

    /**
     * @param $openid
     * @return array|null|ActiveRecord
     */
    public static function getByWeChatOpenId($openid)
    {
        return self::find()->where(['openid' => $openid, 'platform_id' => 1])->one();
    }
}

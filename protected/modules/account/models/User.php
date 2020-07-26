<?php

namespace app\modules\account\models;

use app\behaviors\IpBehavior;
use app\modules\account\components\TokenType;
use app\modules\account\components\UserStatus;
use Yii;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\web\UnauthorizedHttpException;

/**
 * Database fields:
 * @property integer $id
 * @property string $username [varchar(32)]
 * @property string $email [varchar(255)]
 * @property boolean $email_confirmed
 * @property string $auth_key [varchar(32)]
 * @property string $password_hash [varchar(255)]
 * @property integer $status
 * @property integer $created_at
 * @property string $created_ip [varchar(45)]
 * @property integer $updated_at
 * @property string $updated_ip [varchar(45)]
 *
 * Fields:
 * @property-write string $password
 * @property-read string $authKey
 */
class User extends ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => UserStatus::ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = TokenType::API_AUTH)
    {
        $tokenModel = UserToken::findByCode($token, $type);

        if (!$tokenModel) {
            throw new UnauthorizedHttpException(Yii::t('app', 'Token not found!'));
        }

        if ($tokenModel->isExpired()) {
            $tokenModel->delete();
            throw new UnauthorizedHttpException(Yii::t('app', 'Token expired!'));
        }

        return static::findUserActive($tokenModel->user_id);
    }

    /**
     * @param $id
     * @return User|null
     */
    public static function findUserActive($id)
    {
        return static::findOne(['id' => $id, 'status' => UserStatus::ACTIVE]);
    }

    /**
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => UserStatus::ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            IpBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'in', 'range' => UserStatus::getRange()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * @param string $password
     * @throws Exception
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * @throws Exception
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'E-Mail'),
            'email_confirmed' => Yii::t('app', 'E-Mail Confirmed'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'created_ip' => Yii::t('app', 'Created IP'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'updated_ip' => Yii::t('app', 'Updated IP'),
        ];
    }
}

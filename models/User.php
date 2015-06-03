<?php

namespace app\models;

class User extends \yii\base\Object implements \yii\web\IdentityInterface
{
    const USERS_CSV_PATH = 'data/users.csv';
    const FAIL_CSV_PATH = 'data/failedauth.csv';

    public $id;
    public $username;
    public $password;
    public $authKey;
    public $accessToken;

    private static $users;
    private static $failAuth;

    protected static function getUsers()
    {
        if (!static::$users) {
            if (($handle = fopen(\Yii::$app->getBasePath() . DIRECTORY_SEPARATOR . self::USERS_CSV_PATH, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    static::$users[$data[0]] = array_combine(array(
                        'id', 'username', 'password', 'authKey', 'accessToken'
                    ), $data);
                }
                fclose($handle);
            }
        }

        return self::$users;
    }

    protected static function geFailAuthData()
    {
        if (!static::$failAuth) {
            if (($handle = fopen(\Yii::$app->getBasePath() . DIRECTORY_SEPARATOR . self::FAIL_CSV_PATH, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    static::$failAuth[(int)$data[0]] = $data;
                }
                fclose($handle);
            }
        }

        return self::$failAuth;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        $users = self::getUsers();
        return isset($users[$id]) ? new static($users[$id]) : null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::getUsers() as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        foreach (self::getUsers() as $user) {
            if (strcasecmp($user['username'], $username) === 0) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }

    public function getFailData()
    {
        $fails_data = self::geFailAuthData();
        return isset($fails_data[$this -> id]) ? array_combine(array('id', 'tries', 'timestamp'), $fails_data[$this -> id]) : NULL;
    }

    public function addFailTry()
    {
        self::geFailAuthData();

        $tries = 0;

        if (isset(self::$failAuth[$this -> id])) {
            $tries = self::$failAuth[$this -> id][1];
        }

        self::$failAuth[$this -> id] = array($this -> id, ++$tries, time());

        self::writeTries();
    }

    public function tryResetBan()
    {
        $fail = $this -> getFailData();

        if ($fail && ($fail['tries'] >= 3 && ($fail['timestamp'] + 300) < time())) {
            self::$failAuth[$this -> id] = array($this -> id, 0, time());
            self::writeTries();
        }
    }
    protected static function writeTries()
    {
        $handle = fopen(\Yii::$app->getBasePath() . DIRECTORY_SEPARATOR . self::FAIL_CSV_PATH, "w");
        foreach (array_values(self::$failAuth) as $fields) {
            fputcsv($handle, $fields, ';');
        }
        fclose($handle);
    }
}

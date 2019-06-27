<?php
trait UsersRecovery
{
    /**
     * @var string
     * Set this to enable password recovery functions, must contain user_id(int), token(varchar 50) and added (timestamp)
     */
    protected $usersRecoveryTableName = null;

    /**
     * @var int
     * The time for which a user can use a token to recover his password
     */
    protected $usersRecoveryTokenExpireTime = 3600;

    /**
     * @var string
     * The name of the controller which handles the tokens for user password recovery
     */
    protected $usersRecoveryControllerName = 'recovery';

    /**
     * Allows a user to recover his username with his email
     * Sends an email to the user with his username
     * Throws BaseException if something is wrong with the credentials of the user
     * Throws Exception if something is wrong with the platform setup
     * @param string $email - the email in the user profile
     * @param string $body - the message body (optional)
     * @param string $subject - the message subject (optional)
     * @param int $typeId - the type of the user (optional)
     * @throws BaseException
     * @throws Exception
     */
    public function recoverUsername(string $email, string $body = null, string $subject = null, int $typeId = null)
    {
        global $Core;

        if ($this->registerWithEmail) {
            throw new Exception("Username recovery is not allowed when the users register with email");
        }

        $email = $Core->db->escape(trim($email));

        if (empty($email)) {
            throw new BaseException("Enter email");
        }

        $this->validateEmail($email);

        $this->validateUserType($typeId);

        $queryWhere = "`email` = '$email'";

        if ($typeId !== null) {
            $queryWhere .= " AND `type_id` = $typeId ";
        }

        $selector = new BaseSelect($this->tableName);
        $selector->addField('username');
        $selector->setWhere($queryWhere);
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $user = $selector->execute();

        if (empty($user)) {
            throw new BaseException("This user does not exist");
        }

        if ($body === null) {
            $body = $Core->Language->you_requested_a_username_recovery.' .
            <br>'.$Core->Language->your_username_is.' <b>'.$user['username'].'</b>';
        } else {
            $body = str_replace( array('%USERNAME%','%EMAIL%'), array($user['username'], $email), $body);
        }

        if ($subject === null) {
            $subject = $Core->Language->username_recovery_request;
        }

        $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subject, $email, $body, true);
    }

    /**
     * Allows a user to recover his password with his email, by requesting a password token
     * Sends an email to the user with his token
     * Returns the token
     * Throws BaseException if something is wrong with the credentials of the user
     * Throws Exception if something is wrong with the platform setup
     * @param string $email - the email in the user profile
     * @param string $body - the message body (optional)
     * @param string $subject - the message subject (optional)
     * @param int $typeId - the type of the user (optional)
     * @throws BaseException
     * @throws Exception
     * @return string
     */
    public function requestPasswordToken(string $email, string $body = null, string $subject = null, int $typeId = null)
    {
        global $Core;

        if (empty($this->usersRecoveryTableName)) {
            throw new Exception("Recovery is not possible, without setting the usersRecoveryTableName");
        }

        $email = $Core->db->escape(trim($email));

        if (empty($email)) {
            throw new BaseException("Enter email");
        }

        $this->validateEmail($email);

        $this->validateUserType($typeId);

        if ($this->registerWithEmail) {
            $queryWhere = "`username` = '$email'";
        } else {
            $queryWhere = "`email` = '$email'";
        }

        if ($typeId !== null) {
            $queryWhere .= " AND `type_id` = $typeId ";
        }

        $selector = new BaseSelect($this->tableName);
        $selector->setWhere($queryWhere);
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $user = $selector->execute();

        if (empty($user)) {
            throw new BaseException('This email does not exist');
        }

        unset($selector);

        //check for existing token
        $selector = new BaseSelect($this->usersRecoveryTableName);
        $selector->setWhere("`user_id` = '{$user['id']}'");
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $token = $selector->execute();

        if (empty($token)) {
            $token = $this->generateToken();

            $inserter = new BaseInsert($this->usersRecoveryTableName);
            $inserter->addField('user_id', $user['id']);
            $inserter->addField('token', $token);
            $inserter->execute();
        } elseif ($this->usersRecoveryTokenExpireTime > 0 && strtotime(strtotime($token['added']) < time() - $this->usersRecoveryTokenExpireTime)) {
            $updater = new BaseUpdate($this->usersRecoveryTableName);
            $updater->addField('added', $Core->GlobalFunctions->formatMysqlTime(time(), true));
            $updater->setWhere("`user_id` = '{$user['id']}'");
            $updater->execute();
        } elseif ($this->usersRecoveryTokenExpireTime > 0) { //token is expired
            $deleter = new BaseDelete($this->usersRecoveryTableName);
            $deleter->setWhere("`token` = '$token'");
            $deleter->execute();

            $token = $this->generateToken();

            $inserter = new BaseInsert($this->usersRecoveryTableName);
            $inserter->addField('user_id', $user['id']);
            $inserter->addField('token', $token);
            $inserter->execute();
        }

        if ($body === null) {
            $body =
            $Core->Language->you_requested_a_password_recovery_for.'`'.$user['username'].'.`<br><br>'.
            $Core->Language->click_the_following_link_to_change_your_password.':<br>'.
            '<a href="'.$Core->siteDomain.'/'.$this->usersRecoveryControllerName.'?token='.$token.'">'.
            $Core->siteDomain.'/'.$this->usersRecoveryControllerName.'?token='.$token.'</a><br>';

            if ($this->usersRecoveryTokenExpireTime > 0){
                $body .= $Core->Language->the_link_will_be_active_for.' ';
                $body .= $Core->GlobalFunctions->timeDifference(time() + $this->usersRecoveryTokenExpireTime).'.<br>';
            }

            $body .= '<br>'.$Core->Language->did_not_forget_password;
        } else {
            $body = str_replace(
                array('%TOKEN%','%USERNAME%','%EMAIL%','%URL%'),
                array($token, $user['username'], $email, $Core->siteDomain.'/'.$this->usersRecoveryControllerName.'?token='.$token),
                $body
            );
        }

        if ($subject === null) {
            $subject = $Core->Language->password_recovery_request;
        }

        $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subject, $email, $body, true);

        return $token;
    }

    /**
     * Allows a user to recover his password with his email, by exchanging a password token
     * Sends an email to the user with his new password
     * Returns the new password
     * Throws BaseException if something is wrong with the credentials of the user
     * Throws Exception if something is wrong with the platform setup
     * @param string $token - the token from the user's email
     * @param string $subject - the message subject (optional)
     * @param int $typeId - the type of the user (optional)
     * @throws BaseException
     * @throws Exception
     * @return string
     */
    public function recoverPassword(string $token, string $body = null, string $subject = null)
    {
        global $Core;

        if (empty($this->usersRecoveryTableName)) {
            throw new Exception("Recovery is not possible, without setting the usersRecoveryTableName");
        }

        $token = $Core->db->escape(trim($token));

        if (empty($token)) {
            throw new BaseException("Provide a token");
        }

        $queryWhere = "`token` = '$token'";
        if ($this->usersRecoveryTokenExpireTime > 0) {
            $queryWhere .= " AND `added` < NOW - {$this->usersRecoveryTokenExpireTime}";
        }

        $selector = new BaseSelect($this->usersRecoveryTableName);
        $selector->addField('user_id');
        $selector->setWhere($queryWhere);
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $userId = $selector->execute();

        if (empty($userId)) {
            throw new BaseException("Invalid token");
        }

        unset($selector);

        $selector = new BaseSelect($this->tableName);
        $selector->setWhere("`id` = {$userId['user_id']}");
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $user = $selector->execute();

        if (empty($user)) {
            throw new BaseException("Invalid token");
        }

        $newPass = strtoupper(substr(md5(time()),5,10));

        $updater = new BaseUpdate($this->tableName);
        $updater->addField('password', $this->hashPassword($newPass));
        $selector->setWhere("`id` = {$user['user_id']}");
        $selector->execute();

        $deleter = new BaseDelete($this->usersRecoveryTableName);
        $deleter->setWhere("`token` = '$token'");
        $deleter->execute();

        if ($body === null) {
            $body = $Core->Language->your_new_password_for.' '.$user['username'].' :<b>'.$newPass.'</b>';
        } else {
            $body = str_replace(
                array('%USERNAME%','%EMAIL%','%PASSWORD%'),
                array($user['username'], $email, $newPass),
                $body
            );
        }

        if ($subject === null) {
            $subject = $Core->Language->your_new_password;
        }

        $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subject, $email, $body, true);

        return $newPass;
    }
}
<?php
trait UsersConfirmRegistrationWithEmail
{
    /**
     * @var bool
     * Set to true if the user has to confirm his registration, using his email
     */
    protected $confirmRegistrationWithEmail = false;

    /**
     * @var string
     * The name of the table, used to store the tokens for user registration confirmation with email
     * Must contain user_id(int), token(varchar 50) and added (timestamp)
     */
    protected $usersRegistrationConfirmationTableName = null;

    /**
     * @var string
     * This field must exist in the table of the model and will act as a flag for user registration confirmation with email
     */
    protected $usersRegistrationConfrimationControlField = 'email_confirmed';

    /**
     * @var int
     * How much time the tokens for user registration confirmation with email are valid. 0 is infinite
     */
    protected $usersRegistrationConfrimationTokenExpireTime = 3600;

    /**
     * @var string
     * The name of the controller which handles the tokens for user registration confirmation with email
     */
    protected $usersRegistrationConfrimationControllerName = 'register_confirm';

    /**
     * @var string
     * The name of the controller which handles the login of user, in case his email is not confirmed
     */
    protected $usersRegistrationEmailNotConfirmedControllerName = 'register_not_confirmed';

    /**
     * @var string
     * The name of the controller which resets the token for user registration confirmation with email
     */
    protected $usersRegistrationConfrimationResetTokenControllerName = 'register_reset';

    /**
     * Makes sure that the database and the models are set up properly, to use user registration confirmation with email
     * @throws Exception
     */
    private function validateConfirmRegistrationWithEmailConfiguration()
    {
        if (empty($this->usersRegistrationConfirmationTableName)) {
            throw new Exception("Registration is not possible, without setting the usersRegistrationConfirmationTableName");
        }

        if (empty($this->usersRegistrationConfrimationControlField)) {
            throw new Exception("Registration is not possible, without setting the usersRegistrationConfrimationControlField");
        }

        $usersRegistrationConfirmationTable = new BaseTableFields($this->usersRegistrationConfirmationTableName);
        $fields = $usersRegistrationConfirmationTable->getFields();

        if (!isset($fields['user_id']) || $fields['user_id']['type'] !== 'int' || $fields['user_id']['field_info'] !== '10 unsigned') {
            throw new Exception(
                "Registration is not possible, field `user_id` ".
                "at `{$this->usersRegistrationConfirmationTableName}` must be int 10 unsigned"
            );
        }

        if (!isset($fields['token']) || $fields['token']['type'] !== 'varchar' || $fields['token']['field_info'] !== '50') {
            throw new Exception(
                "Registration is not possible, field `token` ".
                "at `{$this->usersRegistrationConfirmationTableName}` must be varchar 50"
            );
        }

        if (!isset($fields['added']) || $fields['added']['type'] !== 'timestamp' || $fields['added']['default'] !== 'CURRENT_TIMESTAMP') {
            throw new Exception(
                "Registration is not possible, field `added` ".
                "at `{$this->usersRegistrationConfirmationTableName}` must be timestamp default CURRENT_TIMESTAMP"
            );
        }

        unset($fields);

        $this->checkTableFields();

        $fields = $this->tableFields->getFields();

        if (!isset($fields[$this->usersRegistrationConfrimationControlField])) {
            throw new Exception(
                "Registration is not possible, field `{$this->usersRegistrationConfrimationControlField}` ".
                "must be present at `{$this->tableName}`"
            );
        }

        if (
            $fields[$this->usersRegistrationConfrimationControlField]['type'] !== 'tinyint' ||
            $fields[$this->usersRegistrationConfrimationControlField]['field_info'] !== '1 unsigned' ||
            $fields[$this->usersRegistrationConfrimationControlField]['default'] !== '0'
        ) {
            throw new Exception(
                "Registration is not possible, field `{$this->usersRegistrationConfrimationControlField}` ".
                "at `{$this->tableName}` must be tinyint 1, default 0"
            );
        }
    }

    /**
     * Makes sure that the `email` field is provided and valid when using user registration confirmation with email
     * @throws BaseException
     */
    private function getAndValidateEmailForConfirmRegistrationWithEmail(array $input)
    {
        $input['email'] = isset($input['email']) ? trim($input['email']) : '';

        $this->validateEmail($input['email']);

        $queryWhere = "`email` = '{$input['email']}'";
        if ($typeId !== null) {
            $queryWhere .= " AND `type_id` = '".($typeId)."' ";
        }

        $selector = new BaseSelect($this->tableName);
        $selector->addField('id');
        $selector->setWhere($queryWhere);
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $userId = $selector->execute();

        if (!empty($userId)) {
            throw new BaseException("This email is already taken");
        }
        unset($userId);

        return $input;
    }

    /**
     * Sends an email to the user, containing a link, which will verify his email address
     * @param array $user - an user object
     * @param strring $token - the users token
     * @param string $body - the message body (optional)
     * @param string $subject - the message subject (optional)
     */
    private function sendRegistrationConfirmationEmail(array $user, string $token, string $body = null, string $subject = null)
    {
        global $Core;

        if ($body === null) {
            $body =
            $Core->Language->thank_you_for_regitestring.'`'.$user['username'].'.`<br><br>'.
            $Core->Language->click_the_following_link_to_confirm_your_registration.':<br>'.
            '<a href="'.$Core->siteDomain.'/'.$this->usersRegistrationConfrimationControllerName.'?token='.$token.'">'.
            $Core->siteDomain.'/'.$this->usersRegistrationConfrimationControllerName.'?token='.$token.'</a><br>';

            if ($this->usersRegistrationConfrimationTokenExpireTime > 0){
                $body .= $Core->Language->the_link_will_be_active_for.' ';
                $body .= $Core->GlobalFunctions->timeDifference(time() + $this->usersRegistrationConfrimationTokenExpireTime);
                $body .= '.<br>';
            }

            $body .= '<br>'.$Core->Language->did_not_forget_password;
        } else {
            $body = str_replace(
                array(
                    '%TOKEN%',
                    '%USERNAME%',
                    '%EMAIL%',
                    '%URL%'
                ),
                array(
                    $token,
                    $user['username'],
                    $email,
                    $Core->siteDomain.'/'.$this->usersRegistrationConfrimationControllerName.'?token='.$token
                ),
                $body
            );
        }

        if ($subject === null) {
            $subject = $Core->Language->confirm_your_registration;
        }

        if ($this->registerWithEmail === true) {
            $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subject, $user['username'], $body, true);
        } else {
            $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subject, $user['email'], $body, true);
        }
    }

    /**
     * Generates a token and inserts it in the database when using user registration confirmation with email.
     * Sends email to the user, containig a link to verify his email.
     * @param array $user - an user object, from register function
     * @param string $body - the message body (optional)
     * @param string $subject - the message subject (optional)
     */
    private function getRegistrationConfirmationToken(array $user, string $body = null, string $subject = null)
    {
        global $Core;

        $token = $this->generateToken();

        $inserter = new BaseInsert($this->usersRegistrationConfirmationTableName);
        $inserter->addField('user_id', $user['id']);
        $inserter->addField('token', $token);
        $inserter->execute();

        $this->sendRegistrationConfirmationEmail($user, $token, $body, $subject);

        return $token;
    }

    /**
     * When using user registration confirmation with email, call this function to reset the token and resend the email
     * to the user (in case something went wrong durign registration).
     * Also, this could be used to verify an user's email, if it's not required to login to the site.
     * @param array $user - an user object, from the user's session
     * @param string $body - the message body (optional)
     * @param string $subject - the message subject (optional)
     */
    public function resetRegistrationConfirmationToken(array $user, string $body = null, string $subject = null)
    {
        global $Core;

        $this->validateConfirmRegistrationWithEmailConfiguration();

        //check for existing token
        $selector = new BaseSelect($this->usersRegistrationConfirmationTableName);
        $selector->setWhere("`user_id` = '{$user['id']}'");
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $token = $selector->execute();

        if (empty($token)) { //no token yet
            $token = $this->generateToken();

            $inserter = new BaseInsert($this->usersRegistrationConfirmationTableName);
            $inserter->addField('user_id', $user['id']);
            $inserter->addField('token', $token);
            $inserter->execute();
        } elseif ( //token is available, reset it
            $this->usersRegistrationConfrimationTokenExpireTime > 0 &&
            strtotime(strtotime($token['added']) < time() - $this->usersRegistrationConfrimationTokenExpireTime)
        ) {
            $updater = new BaseUpdate($this->usersRegistrationConfirmationTableName);
            $updater->addField('added', $Core->GlobalFunctions->formatMysqlTime(time(), true));
            $updater->setWhere("`user_id` = '{$user['id']}'");
            $updater->execute();
        } elseif ($this->usersRegistrationConfrimationTokenExpireTime > 0) { //token is expired
            $deleter = new BaseDelete($this->usersRegistrationConfirmationTableName);
            $deleter->setWhere("`token` = '$token'");
            $deleter->execute();

            $token = $this->generateToken();

            $inserter = new BaseInsert($this->usersRegistrationConfirmationTableName);
            $inserter->addField('user_id', $user['id']);
            $inserter->addField('token', $token);
            $inserter->execute();
        }

        $this->sendRegistrationConfirmationEmail($user, $token, $body, $subject);

        return $token;
    }

    /**
     * Verifies the token, which the user received, in order to verify his email address
     * Throws BaseException if something is wrong
     * @param string $token - the token, which the user received in his email.
     * @throws BaseException
     */
    public function confirmRegistrationEmail(string $token)
    {
        global $Core;

        $this->validateConfirmRegistrationWithEmailConfiguration();

        $token = $Core->db->real_escape_string(trim($token));

        if (empty($token)) {
            throw new BaseException("Provide a token");
        }

        $queryWhere = "`token` = '$token'";
        if ($this->usersRegistrationConfrimationTokenExpireTime > 0) {
            $queryWhere .= " AND `added` < NOW - {$this->usersRegistrationConfrimationTokenExpireTime}";
        }

        $selector = new BaseSelect($this->usersRegistrationConfirmationTableName);
        $selector->setWhere($queryWhere);
        $selector->setGlobalTemplate('fetch_assoc');
        $selector->turnOffCache();
        $tokenInfo = $selector->execute();

        if (empty($tokenInfo)) {
            throw new BaseException("Invalid token");
        }

        $this->updateById(
            $tokenInfo['user_id'],
            array(
                $this->usersRegistrationConfrimationControlField => 1
            )
        );
    }
}
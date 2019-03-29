<?php
class User extends Base
{
    const BEFORE_PASS = 'WERe]r#$%{}^JH[~ghGHJ45 #$';
    const AFTER_PASS = '9 Y{]}innv89789#$%^&';
    
    const DEFAULT_USER_ROLE     = "Nobody";
    const DEFAULT_USER_LEVEL    = 0;
    const DEFAULT_USER_LEVEL_ID = 0;
    
    /**
     * @var string
     * The key, which is used to store the session in the memcache
     */
    private $sessionKey;
    
    /**
     * The name of the cookie for the user session
     */
    private $cookieName;
    
    /**
     * @var array
     * The user data is stored here
     */
    private $data;
    
    /**
     * @var int
     * User session time in seconds; after that the user will be logged out
     */
    protected $sessionTime = 3600;
    
    //database structure properties
    
    /**
     * @var string
     * The name of the table with the users
     */
    protected $tableName = 'users';
    
    /**
     * @var string
     * The name of the table, where the user levels are stored.
     * If this is not empty, the user access levels will be considered when accessing pages
     */
    protected $usersLevelTableName;
    
    /**
     * @var string
     * The name of the table, where the additional user types are stored.
     * If this is not empty, a user account can be created with same username or email but with different type_id
     */
    protected $usersTypesTableName;
    
    /**
     * @var string
     * The name of the table, where the pages of the site are recorded
     */
    protected $pagesTableName = 'pages';
    
    /**
     * @var string
     * Use this field in the users table to record last login time.
     * If it is empty it will not record it.
     */
    protected $lastLoginField;
    
    //regitsration properties
    
    /**
     * @var string
     * Allow registration with email, instead of username
     */
    protected $registerWithEmail = false;
    
    //recovery
    protected $usersRecoveryTableName      = false;     //set this to enable password recovery functions, must contain user_id(int) and token(varchar 50)
    protected $tokenExpireTime             = 3600;      //
    protected $usersRecoveryControllerName = 'recovery'; //this is the controller, which handles the tokens
     //registration and password requirements
    protected $emailRequired               = true;
    
    protected $passMinLen                  = 5;
    protected $passMaxLen                  = 20;
    protected $userNameMinLen              = 5;
    protected $userNameMaxLen              = 20;
    protected $passNumbersRequired         = false;
    protected $passCapitalsRequired        = false;
    protected $passSymbolsRequired         = false;
    
    
    
    
    
    /**
     * Gets a property from the data of the user
     * @param string $propertyName - the name of the property
     * @return mixed
     */
    public function __get(string $propertyName)
    {
        if (isset($this->data[$propertyName])) {
            return $this->data[$propertyName];
        }
    }
    
    /**
     * Returns the data of the user
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Inits the user session and cookie
     */
    public function init()
    {
        if (!isset($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'])) {
            throw new Exception("Remote address or request uri is not set");
        }

        $this->cookieName = 'user_'.sha1($this->tableName);

        $this->setSessionKey();
        
        $this->getSessionDataFromMemcache();

        $this->checkUserAccess();
    }
    
    /**
     * Set the sessionKey property for the current user
     * If the session cookie is set, gets the unique id for the session from it
     * Always resets the session cookie to extend the session time
     */
    private function setSessionKey()
    {
        if (isset($_COOKIE[$this->cookieName])) {
            $uniqid = $_COOKIE[$this->cookieName];
        } else {
            $uniqid = uniqid();
        }
        
        setcookie($this->cookieName, $uniqid, time() + $this->sessionTime, "/");
        
        $this->sessionKey = $this->tableName.$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].$uniqid;
    }
    
    /**
     * Records the current user data in memcache using the session key
     */
    private function setSessionInMemcache()
    {
        global $Core;

        $Core->db->memcache->set($this->sessionKey, array("expire" => time() + $this->sessionTime, 'data' => $this->data));
    }
    
    /**
     * Init the user data to it's default value
     */
    private function setUserDataToDefault()
    {
        $this->data = array(
            'id'        => 0, 
            'level'     => self::DEFAULT_USER_LEVEL,
            'level_id'  => self::DEFAULT_USER_LEVEL_ID,
            'role'      => self::DEFAULT_USER_ROLE,
            'logged_in' => false, 
            'pages'     => false,
        );
    }
    
    /**
     * Gets the session data from memcache and sets the data property of the class
     */
    private function getSessionDataFromMemcache()
    { 
        global $Core;
        
        $data = $Core->db->memcache->get($this->sessionKey);

        if (isset($data['expire'], $data['data'], $data['data']['logged_in']) && $data['expire'] >= time() ? $data['data'] : false) {
            $this->data = $data['data'];
        } else {
            $this->setUserDataToDefault();
        }
    }
    
    /**
     * Resets the current user data and records it in memcache
     */
    public function resetSession()
    {
        $this->setUserDataToDefault();
        $this->setSessionInMemcache();
    }
    
    /**
     * Hashes the provided password string to the format used to store the passwords
     * @param string $password - the password to hash
     * @return string
     */
    public function hashPassword(string $password)
    {
        global $Core;

        return md5(self::BEFORE_PASS.sha1($Core->db->escape($password)).self::AFTER_PASS);
    }
    
    /**
     * Sets a property in the user data and stores in memcache
     * @param string $propertyName - the name of the property to set
     * @param mixed $propertyValue - the value of the property to set
     */
    public function setProperty(string $propertyName, $propertyValue)
    {
        $this->data[$propertyName] = $propertyValue;
        $this->setSessionInMemcache();
    }
    
    /**
     * Gets the data for the level of security, allowed for the current user
     * @return array
     */
    private function getUserLevelData()
    {
        if ($this->usersLevelTableName) {
            if (!isset($this->data['level_id'])) {
                throw new Exception("Column `level_id` does not exist in table `{$this->tableName}`");
            }
            
            $selector = new BaseSelect($this->usersLevelTableName);
            $selector->setWhere(" id = {$this->data['level_id']}");
            $selector->setGlobalTemplate('fetch_assoc');
            $data = $selector->execute();
            unset($data['id']);
            return $data;
        } else {
            return array(
                'level'    => self::DEFAULT_USER_LEVEL,
                'level_id' => self::DEFAULT_USER_LEVEL_ID,
                'role'     => self::DEFAULT_USER_ROLE,
            );
        }
    }
    
    /**
     * Gets a collection of the pages, allowed for the current user
     * @return array
     */
    private function getUserPages()
    {
        global $Core;

        if ($this->usersLevelTableName) {
            $selector = new BaseSelect($this->pagesTableName);
            $selector->addField('*', null);
            $selector->addField('level', 'level', $this->usersLevelTableName);
            $selector->addLeftJoin($this->usersLevelTableName, 'id', 'level_id');
            $selector->setWhere("(`level` > {$this->data['level']} AND `name` IS NOT NULL AND `name` != '') OR `level` = 0 ");
            $selector->setOrderBy("`{$Core->dbName}`.`{$this->pagesTableName}`.`order` ASC, `{$Core->dbName}`.`{$this->pagesTableName}`.`name` ASC");
            return $selector->execute();
        }
        
        return $base->getAll(null, "`name` IS NOT NULL AND `name` != ''", "`order` ASC, `name` ASC");
    }
    
    /**
     * Checks if the user has an access to the current page;
     * If he hasn't, it will redirect to page not found
     * If the user is not logged in, he only has access to the level 0 pages
     */
    private function checkUserAccess()
    {
        global $Core;
        
        if (!$this->usersLevelTableName) {
            return;
        }
        
        if (!isset($this->data['pages']) || empty($this->data['pages'])) {
            $selector = new BaseSelect($this->pagesTableName);
            $selector->addField('level', 'level', $this->usersLevelTableName);
            $selector->addLeftJoin($this->usersLevelTableName, 'id', 'level_id');
            $selector->setWhere("`{$Core->dbName}`.`{$this->pagesTableName}`.`url` = '".$Core->db->escape($Core->rewrite->url)."'");
            $selector->setGlobalTemplate('fetch_assoc');
            $level = $selector->execute();
            if (!empty($level) && isset($level['level']) && $level['level'] == 0) {
                return;
            }
        } else {
            foreach ($this->data['pages'] as $page) {
                if ($Core->Rewrite->url == $page['url']) {
                    return;
                }
            }
        }
        
        $Core->doOrDie();
    }

    /**
     * Loggs in a user with a username(email) and password, getting its data from the database
     * Throws BaseException if username/password is empty or they are not registered
     * Throws Exception if $typeId is provided but the model is not set up correctly
     * @param string $username
     * @param string $password
     * @param int $typeId - optional type id for the user
     * @throws Exception
     * @throws BaseException
     */
    public function login(string $username, string $password, int $typeId = null)
    {
        global $Core;
        
        $username = trim($username);
        $password = trim($password);
        
        if (empty($username)) {
            if ($this->registerWithEmail) {
                throw new BaseException($Core->language->error_enter_email);
            } else {
                throw new BaseException($Core->language->error_enter_username);
            }
        }

        if (empty($password)) {
            throw new BaseException($Core->language->error_enter_password);
        }

        $username = $Core->db->escape($username);
        $password = $this->hashPassword($password);
        
        $queryWhere = array();
        
        if ($typeId != null) {
            if(!$this->usersTypesTableName){
                throw new Exception("usersTypesTableName is not set");
            }

            if(!isset($this->getUserTypes()[$typeId])){
                throw new Exception("Invalid user type");
            }

            $queryWhere[] = "`type_id` = '".($Core->db->escape($typeId))."'";
        }
        
        if ($this->registerWithEmail) {
            $queryWhere[] = "`email` = '$username'";
        } else {
            $queryWhere[] = "`username` = '$username'";
        }
        
        $queryWhere[] = "`password` = '$password'";
         
        $this->data = $this->getAll(1, " ".implode(' AND ', $queryWhere));
        
        if (!empty($this->data)) {
            $this->data = current($this->data);
            $this->data['logged_in'] = true;
            $this->data = array_merge($this->data, $this->getUserLevelData());
            $this->data['pages'] = $this->getUserPages();
            
            $this->setSessionInMemcache();
            
            if ($this->lastLoginField) {
                $this->updateById(
                    $this->data['id'],
                    array($this->lastLoginField => $Core->globalFunctions->formatMysqlTime(time(),true))
                );
            }
        } else if ($this->registerWithEmail) {
            throw new BaseException($Core->language->error_email_addres_or_password_is_invalid);
        } else {
            throw new BaseException($Core->language->error_username_or_password_is_invalid);
        }
    }
    
    /**
     * Allows the developer to force login by user id
     * Throws Exception if the user id is not provided or the user does not exist
     * @param int $userId - the id of the user
     * @throws Exception
     */
    public function loginById(int $userId)
    {
        global $Core;

        if (empty($id)) {
            throw new Exception("Provide user id");
        }
        
        $this->data = $this->getById($userId);
        
        if (empty($this->data)) {
            throw new Exception("This user does not exist");
        } else {
            $this->data['logged_in'] = true;
            $this->data = array_merge($this->data, $this->getUserLevelData());
            $this->data['pages'] = $this->getUserPages();
            
            $this->setSessionInMemcache();
            
            if ($this->lastLoginField) {
                $this->updateById(
                    $this->data['id'],
                    array($this->lastLoginField => $Core->globalFunctions->formatMysqlTime(time(),true))
                );
            }
        }
    }
    
    /**
     * Destroys the current user sesson and redicrects to the provided url (/login by default)
     * @param string $redirectUrl - an URL to redirect to after login
     */
    public function logout(string $redirectUrl = null)
    {
        global $Core;
        
        if ($redirectUrl === null) {
            $redirectUrl = '/login';
        }

        $this->resetSession();

        if ($redirectUrl !== false && (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] != $redirectUrl)) {
            $Core->redirect($redirectUrl);
        }
    }

    /**
     * Gets a list of all user levels
     * @return array
     */
    public function getUserLevels()
    {
        global $Core;
        
        $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->usersLevelTableName}` WHERE `level` != '0'", 0, 'fillArray', $levels, 'id');
        return $levels;
    }
    
    /**
     * Gets a list of all user types
     * @return array
     */
    public function getUserTypes()
    {
        global $Core;

        $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->usersTypesTableName}`", 0, 'fillArray', $levels, 'id');
        return $levels;
    }

    //REGISTRATION FUNCTIONS

    public function validateUsername($username, $typeId = false){
        global $Core;

        if(stristr($username,' ')){
            throw new BaseException($Core->language->error_no_spaces_are_allowed_in_the_username);
        }
        if(!is_string($username) || empty($username)){
            throw new BaseException($Core->language->error_provide_username);
        }
        if(!empty($min) && strlen($username) < $min){
            throw new BaseException($Core->language->error_username_must_be_minimum.' '.$this->userNameMinLen.' '.$Core->language->symbols);
        }
        if(!empty($max) && strlen($username) > $max){
            throw new BaseException($Core->language->error_username_must_be_maximum.' '.$this->userNameMaxLen.' '.$Core->language->symbols);
        }
        //multyaccount
        if($typeId){
            $typeId = " AND `type_id` = '".($Core->db->escape($typeId))."' ";
        }

        $username = $Core->db->escape($username);

        if($Core->db->result("SELECT `id` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `username` = '$username' $typeId")){
            throw new BaseException($Core->language->error_this_username_is_already_taken);
        }

        return $username;
    }

    public function validateEmail($email, $userId = false, $typeId = false){
        global $Core;

        if(!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            throw new BaseException($Core->language->error_email_is_not_valid);
        }
        //if update
        if($userId){
            $userId = " AND `id` != '$userId'";
        }
        //multyaccount
        if($typeId){
            $typeId = " AND `type_id` = '".($Core->db->escape($typeId))."' ";
        }

        $email = $Core->db->escape($email);

        if($Core->db->result("SELECT `id` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `email` = '$email' $userId $typeId")){
            throw new BaseException($Core->language->error_this_email_is_already_taken);
        }

        return $email;
    }

    public function validatePassword($pass, $repeatPass){
        global $Core;

        if(empty($pass)){
            throw new BaseException($Core->language->error_provide_password);
        }
        if(stristr($pass,' ')){
            throw new BaseException($Core->language->error_no_spaces_are_allowed_in_the_password);
        }
        if($this->passMinLen && strlen($pass) < $this->passMinLen){
            throw new BaseException($Core->language->error_password_must_be_minimum.' '.$this->passMinLen.' '.$Core->language->symbols);
        }
        if($this->passMaxLen && strlen($pass) > $this->passMaxLen){
            throw new BaseException($Core->language->error_password_must_be_maximum.' '.$this->passMaxLen.' '.$Core->language->symbols);
        }
        if($this->passNumbersRequired && !preg_match("#[0-9]+#", $pass)){
            throw new BaseException($Core->language->error_password_must_contain_a_number);
        }
        if($this->passCapitalsRequired && !preg_match("#[A-Z]+#", $pass)){
            throw new BaseException($Core->language->error_password_must_contain_a_capital_letter);
        }
        if($this->passCapitalsRequired && !preg_match("#\W+#", $pass)){
            throw new BaseException($Core->language->error_password_must_contain_a_symbol);
        }
        if(empty($repeatPass)){
            throw new BaseException($Core->language->error_repeat_password);
        }
        if($pass !== $repeatPass){
            throw new BaseException($Core->language->error_passwords_do_not_match);
        }

        return $pass;
    }

    //set fourth parameter to check for existing password!
    public function changePassword($userId, $newPass, $repeatPass, $currentPass = false){
        global $Core;

        if($currentPass !== false && !$currentPass){
            throw new BaseException($Core->language->error_enter_current_password);
        }

        $this->validatePassword($newPass, $repeatPass);

        $chPass = $Core->db->result("SELECT `password` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `id` = $userId");
        if(empty($chPass)){
            throw new BaseException($Core->language->error_this_user_does_not_exist);
        }
        if($currentPass !== false && $this->hashPassword($currentPass) != $chPass){
            throw new BaseException($Core->language->error_current_password_is_incorrect);
        }
        unset($chPass);

        $Core->db->query("
            UPDATE `{$Core->dbName}`.`{$this->tableName}`
            SET `password` = '".$this->hashPassword($newPass)."'
            WHERE`id` = '$userId'"
        );

        return true;
    }

    public function register($username, $pass, $repeatPass, $email = false, $levelId = false, $typeId = false){
        global $Core;

        if($this->usersLevelTableName && (!$levelId || !is_numeric($levelId)) || !isset($this->getUserLevels()[$levelId])){
            throw new BaseException($Core->language->error_invalid_user_level);
        }

        if($this->usersTypesTableName && (!$typeId || !is_numeric($typeId) || !isset($this->getUserTypes()[$typeId]))){
            throw new BaseException($Core->language->error_user_type_id_is_not_valid);
        }

        if($this->usernameRequired == false && $this->emailRequired == false){
            throw new BaseException($Core->language->error_at_least_username_required_or_email_required_must_be_true);
        }

        if($this->usernameRequired){
            $this->validateUsername($username, $typeId);
        }else{
            $username = $email;
        }

        if($this->emailRequired || $email){
            $this->validateEmail($email, false, $typeId);
            $email = $Core->db->escape($email);
        }

        $this->validatePassword($pass, $repeatPass);

        $username = $Core->db->escape($username);
        $pass     = $this->hashPassword($pass);

        $q = "INSERT INTO `{$Core->dbName}`.`{$this->tableName}` (`username`, `password` ";
        if($email){
            $q .= ", `email`";
        }
        if($levelId){
            $q .= ", `level_id`";
        }
        if($typeId){
            $q .= ", `type_id`";
        }
        $q .= ") VALUES('$username', '$pass'";
        if($email){
            $q .= ", '$email'";
        }
        if($levelId){
            $q .= ", '$levelId'";
        }
        if($typeId){
            $q .= ", '$typeId'";
        }
        $q .= ")";

        $Core->db->query($q);

        return $Core->db->insert_id;
    }

    public function recoverUsername($email, $body = false, $subjet = false, $typeId = false){
        global $Core;

        if(empty($email)){
            throw new BaseException($Core->language->error_enter_email);
        }

        if($typeId){
            $typeId = " AND `type_id` = $typeId ";
        }

        $email = $Core->db->escape($email);

        $Core->db->query("SELECT `username` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `email` = '$email' $typeId",0,'fetch_assoc',$user);

        if(empty($user)){
            throw new BaseException($Core->language->error_this_user_does_not_exist);
        }

        if($body === false){
            $body = $Core->language->you_requested_a_username_recovery_.' .
            <br>'.$Core->language->your_username_is.' <b>'.$user['username'].'</b>';
        }
        else{
            $body = str_replace(
                        array('%USERNAME%','%EMAIL%'),
                        array($user['username'],$email)
                        ,$body
                    );
        }

        if($subjet === false){
            $subjet = $Core->language->username_recovery_request;
        }

        $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subjet, $email, $body, true);

        return true;
    }

    public function requestPasswordToken($email, $body = false, $subjet = false, $typeId = false){
        global $Core;

        if(empty($email)){
            throw new BaseException($Core->language->error_enter_email);
        }

        $Core->validations->validateEmail($email);

        if(empty($email)){
            throw new BaseException($Core->language->error_enter_email);
        }

        if($typeId){
            $typeId = " AND `type_id` = $typeId ";
        }

        $email = $Core->db->escape($email);

        $Core->db->query("SELECT `id`,`username` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `email` = '$email' $typeId",0,'fetch_assoc',$user);

        if(empty($user)){
            throw new BaseException($Core->language->error_this_email_does_not_exist);
        }

        //check for existing token
        $token = $Core->db->result("SELECT `token` FROM `{$Core->dbName}`.`{$this->usersRecoveryTableName}` WHERE `user_id` = '{$user['id']}'");

        if(empty($token)){
            $token = md5(uniqid());

            $Core->db->query("
                INSERT INTO `{$Core->dbName}`.`{$this->usersRecoveryTableName}` (`id`,`user_id`,`token`)
                VALUES (NULL,{$user['id']},'$token')"
            );
        }elseif($this->tokenExpireTime !== false && strtotime(strtotime($token['added']) < time() - $this->tokenExpireTime)){
            $Core->db->query("UPDATE `{$Core->dbName}`.`{$this->usersRecoveryTableName}` SET `added` = '".($Core->globalFunctions->formatMysqlTime(time(), true))."' WHERE `user_id` = '{$user['id']}'");
        }elseif($this->tokenExpireTime !== false){
            //expired
            $Core->db->query("DELETE FROM `{$Core->dbName}`.`{$this->usersRecoveryTableName}` WHERE `token` = '$token'");

            $token = md5(uniqid());

            $Core->db->query("
                INSERT INTO `{$Core->dbName}`.`{$this->usersRecoveryTableName}` (`id`,`user_id`,`token`)
                VALUES (NULL,{$user['id']},'$token')"
            );
        }

        if($body === false){
            $body =
            $Core->language->you_requested_a_password_recovery_for.'`'.$user['username'].'.`<br><br>'.
            $Core->language->click_the_following_link_to_change_your_password.':<br>'.
            '<a href="'.$Core->siteDomain.'/'.$this->usersRecoveryControllerName.'?token='.$token.'">'.
            $Core->siteDomain.'/'.$this->usersRecoveryControllerName.'?token='.$token.'</a><br>';

            if($this->tokenExpireTime !== false){
                $body .= $Core->language->the_link_will_be_active_for.' '.$Core->globalFunctions->timeDifference(time() + $this->tokenExpireTime).'.<br>';
            }

            $body .= '<br>'.$Core->language->did_not_forget_password;
        }
        else{
            $body = str_replace(
                        array('%TOKEN%','%USERNAME%','%EMAIL%','%URL%'),
                        array($token, $user['username'], $email, $Core->siteDomain.'/'.$this->usersRecoveryControllerName.'?token='.$token),
                        $body
                    );
        }

        if($subjet === false){
            $subjet = $Core->language->password_recovery_request;
        }

        $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subjet, $email, $body, true);

        return true;
    }

    public function recoverPassword($token, $body = false, $subjet = false){
        global $Core;

        if(empty($token)){
            throw new BaseException($Core->language->error_invalid_token);
        }

        $token = $Core->db->escape($token);

        if($this->tokenExpireTime !== false){
            $and = " AND `added` < NOW - {$this->tokenExpireTime} ";
        }else{
            $and = '';
        }

        $userId = $Core->db->result("SELECT `user_id` FROM `{$Core->dbName}`.`{$this->usersRecoveryTableName}` WHERE `token` = '$token' $and");

        if(empty($userId)){
            throw new BaseException($Core->language->error_invalid_token);
        }

        $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `id` = $userId",0,'fetch_assoc',$user);

        $newPass = strtoupper(substr(md5(time()),5,10));

        $Core->db->query("
            UPDATE `{$Core->dbName}`.`{$this->tableName}`
            SET `password` = '".$this->hashPassword($newPass)."'
            WHERE `id` = '{$user['id']}'"
        );

        $Core->db->query("DELETE FROM `{$Core->dbName}`.`{$this->usersRecoveryTableName}` WHERE `token` = '$token'");

        if($body === false){
            $body = $Core->language->your_new_password_for.' '.$user['username'].' :<b>'.
            $newPass.'</b>';
        }
        else{
            $body = str_replace(
                        array('%USERNAME%','%EMAIL%','%PASSWORD%'),
                        array($user['username'], $email, $newPass),
                        $body
                    );
        }

        if($subjet === false){
            $subjet = $Core->language->your_new_password;
        }

        $Core->GlobalFunctions->sendEmail($Core->mailConfig['Username'], $Core->siteName, $subjet, $email, $body, true);

        return true;
    }
}

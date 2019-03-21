<?php
class User extends Base{
    //common
    private   $sessionKey                  = false;
    private   $beforePass                  = 'WERe]r#$%{}^JH[~ghGHJ45 #$';
    private   $afterPass                   = '9 Y{]}innv89789#$%^&';
    private   $cookieName                  = '';
    protected $sessionTime                 = 3600;      //session time in seconds
    protected $lastLoginField              = false;     //set this to record last login time
    //recovery
    public    $usersRecoveryTableName      = false;     //set this to enable password recovery functions, must contain user_id(int) and token(varchar 50)
    public    $tokenExpireTime             = 3600;      //
    protected $usersRecoveryControllerName = 'recovery'; //this is the controller, which handles the tokens
     //registration and password requirements
    protected $emailRequired               = true;
    protected $usernameRequired            = true;      //if false email is used as username but must be preset in the registration(anything) and in user table
    protected $passMinLen                  = 5;
    protected $passMaxLen                  = 20;
    protected $userNameMinLen              = 5;
    protected $userNameMaxLen              = 20;
    protected $passNumbersRequired         = false;
    protected $passCapitalsRequired        = false;
    protected $passSymbolsRequired         = false;
    //user levels and types
    protected $usersLevelTableName         = false;
    protected $usersTypesTableName         = false;     //if is set a user account can be created with same username or email but with different type_id
    protected $pagesTableName              = 'pages';
    //user info
    public    $user                        = NULL;
    //current page info
    public    $pageLevel                   = false;
    public    $pageId                      = false;

    public function init(){
        global $Core;

        if(!isset($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'])){
            throw new Exception($Core->language->error_remote_addr_or_request_uri_is_not_set);
        }

        $this->cookieName = 'user_'.sha1($this->tableName);

        $this->setSessionKey();
        $this->setUser();

        if($this->usersLevelTableName){
            $this->checkAccess();
        }
    }

    private function setSessionKey(){
        if(isset($_COOKIE[$this->cookieName])){
            $uniqid = $_COOKIE[$this->cookieName];
        }else{
            $uniqid = uniqid();
        }

        setcookie($this->cookieName, $uniqid, time() + $this->sessionTime, "/");
        $this->sessionKey = $this->tableName.$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].$uniqid;
        $this->loggedIn = true;
        return true;
    }

    public function setProperty($property, $value){
        $this->user->$property = $value;
        $this->setSession($this->user);
    }

    private function setSession($user){
        global $Core;

        $Core->db->memcache->set($this->sessionKey, array("expire" => time() + $this->sessionTime,'user' => $user));
        return true;
    }

    public function resetSession(){
        global $Core;

        $this->user = (object) array('id' => 0, 'level' => 0, 'level_id' => 0, 'loggedIn' => false, 'pages' => false);
        $Core->db->memcache->set($this->sessionKey,array("expire" => time() + $this->sessionTime, 'user' => $this->user));
        return true;
    }

    private function setUser(){
        global $Core;

        $key = $Core->db->memcache->get($this->sessionKey);

        if($this->user = isset($key['expire'], $key['user'], $key['user']->loggedIn) && $key['expire'] >= time() ? $key['user'] : false){
            $this->user->id       = intval($this->user->id);
            $this->user->level    = intval($this->user->level);
            $this->user->level_id = intval($this->user->level_id);
            $this->user->loggedIn = $key['user']->loggedIn;
        }else{
            $this->user = (object) array('id' => 0, 'level' => 0, 'level_id' => 0, 'loggedIn' => false, 'pages' => false);
        }

        $this->setSession($this->user);
        return true;
    }

    public function hashPassword($pass){
        global $Core;

        return md5($this->beforePass.sha1($Core->db->escape($pass)).$this->afterPass);
    }

    public function login($username, $password, $typeId = false){
        global $Core;

        if(empty($username) || !is_string($username)){
            if($this->usernameRequired){
                throw new Error($Core->language->error_enter_username);
            }else{
                throw new Error($Core->language->error_enter_email);
            }
        }

        if(empty($password) || !is_string($password)){
            throw new Error($Core->language->error_enter_password);
        }

        $username = $Core->db->escape(trim($username));
        $password = $this->hashPassword($password);

        if($typeId){
            if(!$this->usersTypesTableName){
                throw new Error($Core->language->error_users_types_table_is_not_set);
            }

            if(!is_numeric($typeId) || !isset($this->getUserTypes()[$typeId])){
                throw new Error($Core->language->error_invalid_user_type);
            }

            $typeId = " AND `type_id` = '".($Core->db->escape($typeId))."' ";
        }

        if($userId = $Core->db->result("SELECT `id` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE (`username`='$username' OR `email` = '$username') $typeId AND `password` = '$password'")){
            $this->setSession((object) $this->getUserInfo($userId));
            $this->setUser();
            if($this->lastLoginField){
                $Core->db->query("UPDATE `{$Core->dbName}`.`{$this->tableName}`
                SET {$this->lastLoginField} = '".$Core->globalFunctions->formatMysqlTime(time(),true)."' WHERE `id` = {$this->user->id}");
            }
        }else{
            if($this->usernameRequired){
                throw new Error($Core->language->error_username_or_password_is_invalid);
            }else{
                throw new Error($Core->language->error_email_addres_or_password_is_invalid);
            }
        }
        return true;
    }

    public function logout($url = '/login'){
        global $Core;

        $this->resetSession();

        if($url !== false && (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] != $url)){
            $Core->redirect($url);
        }
        return true;
    }

    public function getUserInfo($id){
        global $Core;

        $id = intval($id);
        if(empty($id)){
            throw new exception($Core->languge->error_invalid_id);
        }

        if($this->usersLevelTableName){
            $q = "
            SELECT
                 `{$Core->dbName}`.`{$this->tableName}`.*
                ,`{$Core->dbName}`.`{$this->usersLevelTableName}`.`role` AS 'role'
                ,`{$Core->dbName}`.`{$this->usersLevelTableName}`.`level` AS 'level'
                ,'true' AS 'loggedIn'
            FROM
                `{$Core->dbName}`.`{$this->tableName}`
            LEFT JOIN
                `{$Core->dbName}`.`{$this->usersLevelTableName}`
            ON
                `{$Core->dbName}`.`{$this->tableName}`.`level_id` = `{$Core->dbName}`.`{$this->usersLevelTableName}`.`id`
            WHERE
                `{$Core->dbName}`.`{$this->tableName}`.`id`= '$id'";
        }
        else{
            $q = "SELECT *, 'true' AS 'loggedIn', '0' AS 'level' FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `{$Core->dbName}`.`{$this->tableName}`.`id`= '$id'";
        }
        if($Core->db->query($q, 0, 'fetch_assoc',$user)){
            $user['pages'] = $this->getUserPages($user['level']);
            return $user;
        }
        return false;
    }

    public function checkAccess(){
        global $Core;

        if($Core->db->query("
            SELECT
                `{$Core->dbName}`.`{$this->pagesTableName}`.`id`
                ,`{$Core->dbName}`.`{$this->usersLevelTableName}`.`level` AS 'level'
            FROM
                `{$Core->dbName}`.`{$this->pagesTableName}`
            LEFT JOIN
                `{$Core->dbName}`.`{$this->usersLevelTableName}`
            ON
                `{$Core->dbName}`.`{$this->pagesTableName}`.`level_id` = `{$Core->dbName}`.`{$this->usersLevelTableName}`.`id`
            WHERE
                `{$Core->dbName}`.`{$this->pagesTableName}`.`url` = '".$Core->db->escape($Core->rewrite->URL)."'"
            , 0,'fetch_assoc', $mainPage))
        {
            $this->pageLevel = intval($mainPage['level']);
            $this->pageId    = intval($mainPage['id']);
        }else{
            $Core->doOrDie();
        }

        /*if($mainPage = $Core->globalFunctions->arraySearch($this->user->pages, 'url', $Core->rewrite->URL)){
            $mainPage        = current($mainPage);
            $this->pageLevel = intval($mainPage['level']);
            $this->pageId    = intval($mainPage['id']);
        }
        echo '<br><br><br><br><br>';
        $Core->dump($mainPage, false);*/


        if($this->pageLevel !== 0 && ($this->user->level === 0 || $this->pageLevel < $this->user->level)){
            $Core->doOrDie();
        }
        return true;
    }

    public function getUserPages($level = false){
        global $Core;

        if(!$level){
            $level = $this->user->level;
        }

        if($this->usersLevelTableName){
            $q  = " SELECT ";
            $q .= " `{$Core->dbName}`.`{$this->pagesTableName}`.*, ";
            $q .= " `{$Core->dbName}`.`{$this->usersLevelTableName}`.`level` AS 'level'";
            $q .= " FROM `{$Core->dbName}`.`{$this->pagesTableName}` ";
            $q .= " LEFT JOIN  `{$Core->dbName}`.`{$this->usersLevelTableName}` ";
            $q .= " ON `{$Core->dbName}`.`{$this->usersLevelTableName}`.`id` = `{$Core->dbName}`.`{$this->pagesTableName}`.`level_id` ";
            $q .= " WHERE `level_id`".($level === 0 ? " = " : " >= ");
            $q .= " (SELECT `id` FROM `{$Core->dbName}`.`{$this->usersLevelTableName}` WHERE `level` = $level) ";
            $q .= " AND `name` IS NOT NULL AND `name` != '' ";
            $q .= " ORDER BY `{$Core->dbName}`.`{$this->pagesTableName}`.`order` ASC, `{$Core->dbName}`.`{$this->pagesTableName}`.`name` ASC";
        }else{
            $q = "SELECT * FROM `{$Core->dbName}`.`{$this->pagesTableName}` WHERE `name` IS NOT NULL AND `name` != '' ORDER BY `order` ASC, `name` ASC";
        }

        if($Core->db->query($q, 0, 'simpleArray', $pages)){
            return $pages;
        }
        return false;
    }

    public function getUserLevels(){
        global $Core;

        $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->usersLevelTableName}` WHERE `level` != '0'", 0, 'fillArray', $levels, 'id');
        return $levels;
    }

    public function getUserTypes(){
        global $Core;

        $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->usersTypesTableName}`", 0, 'fillArray', $levels, 'id');
        return $levels;
    }

    public function loginById($id){
        global $Core;

        if(empty($id) || !is_numeric($id)){
            throw new Error($Core->language->error_invalid_id);
        }

        $userInfo = $this->getUserInfo($id);
        if($userInfo){
            $this->setSession((object)$userInfo);
            $this->setUser();
            if($this->lastLoginField){
                $Core->db->query("UPDATE `{$Core->dbName}`.`{$this->tableName}`
                SET {$this->lastLoginField} = '".$Core->globalFunctions->formatMysqlTime(time(),true)."' WHERE `id` = {$this->user->id}");
            }
        }
        else{
            throw new Error($Core->language->error_this_id_does_not_exist);
        }

        return true;
    }

    public function validateUsername($username, $typeId = false){
        global $Core;

        if(stristr($username,' ')){
            throw new Error($Core->language->error_no_spaces_are_allowed_in_the_username);
        }
        if(!is_string($username) || empty($username)){
            throw new Error($Core->language->error_provide_username);
        }
        if(!empty($min) && strlen($username) < $min){
            throw new Error($Core->language->error_username_must_be_minimum.' '.$this->userNameMinLen.' '.$Core->language->symbols);
        }
        if(!empty($max) && strlen($username) > $max){
            throw new Error($Core->language->error_username_must_be_maximum.' '.$this->userNameMaxLen.' '.$Core->language->symbols);
        }
        //multyaccount
        if($typeId){
            $typeId = " AND `type_id` = '".($Core->db->escape($typeId))."' ";
        }

        $username = $Core->db->escape($username);

        if($Core->db->result("SELECT `id` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `username` = '$username' $typeId")){
            throw new Error($Core->language->error_this_username_is_already_taken);
        }

        return $username;
    }

    public function validateEmail($email, $userId = false, $typeId = false){
        global $Core;

        if(!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            throw new Error($Core->language->error_email_is_not_valid);
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
            throw new Error($Core->language->error_this_email_is_already_taken);
        }

        return $email;
    }

    public function validatePassword($pass, $repeatPass){
        global $Core;

        if(empty($pass)){
            throw new Error($Core->language->error_provide_password);
        }
        if(stristr($pass,' ')){
            throw new Error($Core->language->error_no_spaces_are_allowed_in_the_password);
        }
        if($this->passMinLen && strlen($pass) < $this->passMinLen){
            throw new Error($Core->language->error_password_must_be_minimum.' '.$this->passMinLen.' '.$Core->language->symbols);
        }
        if($this->passMaxLen && strlen($pass) > $this->passMaxLen){
            throw new Error($Core->language->error_password_must_be_maximum.' '.$this->passMaxLen.' '.$Core->language->symbols);
        }
        if($this->passNumbersRequired && !preg_match("#[0-9]+#", $pass)){
            throw new Error($Core->language->error_password_must_contain_a_number);
        }
        if($this->passCapitalsRequired && !preg_match("#[A-Z]+#", $pass)){
            throw new Error($Core->language->error_password_must_contain_a_capital_letter);
        }
        if($this->passCapitalsRequired && !preg_match("#\W+#", $pass)){
            throw new Error($Core->language->error_password_must_contain_a_symbol);
        }
        if(empty($repeatPass)){
            throw new Error($Core->language->error_repeat_password);
        }
        if($pass !== $repeatPass){
            throw new Error($Core->language->error_passwords_do_not_match);
        }

        return $pass;
    }

    //set fourth parameter to check for existing password!
    public function changePassword($userId, $newPass, $repeatPass, $currentPass = false){
        global $Core;

        if($currentPass !== false && !$currentPass){
            throw new Error($Core->language->error_enter_current_password);
        }

        $this->validatePassword($newPass, $repeatPass);

        $chPass = $Core->db->result("SELECT `password` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `id` = $userId");
        if(empty($chPass)){
            throw new Error($Core->language->error_this_user_does_not_exist);
        }
        if($currentPass !== false && $this->hashPassword($currentPass) != $chPass){
            throw new Error($Core->language->error_current_password_is_incorrect);
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
            throw new Error($Core->language->error_invalid_user_level);
        }

        if($this->usersTypesTableName && (!$typeId || !is_numeric($typeId) || !isset($this->getUserTypes()[$typeId]))){
            throw new Error($Core->language->error_user_type_id_is_not_valid);
        }

        if($this->usernameRequired == false && $this->emailRequired == false){
            throw new Error($Core->language->error_at_least_username_required_or_email_required_must_be_true);
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
            throw new Error($Core->language->error_enter_email);
        }

        if($typeId){
            $typeId = " AND `type_id` = $typeId ";
        }

        $email = $Core->db->escape($email);

        $Core->db->query("SELECT `username` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `email` = '$email' $typeId",0,'fetch_assoc',$user);

        if(empty($user)){
            throw new Error($Core->language->error_this_user_does_not_exist);
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
            throw new Error($Core->language->error_enter_email);
        }

        $Core->validations->validateEmail($email);

        if(empty($email)){
            throw new Error($Core->language->error_enter_email);
        }

        if($typeId){
            $typeId = " AND `type_id` = $typeId ";
        }

        $email = $Core->db->escape($email);

        $Core->db->query("SELECT `id`,`username` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `email` = '$email' $typeId",0,'fetch_assoc',$user);

        if(empty($user)){
            throw new Error($Core->language->error_this_email_does_not_exist);
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
            throw new Error($Core->language->error_invalid_token);
        }

        $token = $Core->db->escape($token);

        if($this->tokenExpireTime !== false){
            $and = " AND `added` < NOW - {$this->tokenExpireTime} ";
        }else{
            $and = '';
        }

        $userId = $Core->db->result("SELECT `user_id` FROM `{$Core->dbName}`.`{$this->usersRecoveryTableName}` WHERE `token` = '$token' $and");

        if(empty($userId)){
            throw new Error($Core->language->error_invalid_token);
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
?>
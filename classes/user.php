<?php
class User extends Base
{
    const DEFAULT_USER_ROLE     = "Nobody";
    const DEFAULT_USER_LEVEL    = 0;
    const DEFAULT_USER_LEVEL_ID = 0;

    use UsersConfirmRegistrationWithEmail;
    use UsersRecovery;

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

    /**
     * @var string
     * The domain for the session cookie; by default it's the current domain
     */
    protected $sessionCookieDomain = '';

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
    protected $userTypesTableName;

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

    /**
     * @var int
     * Set a minimum length for the username
     */
    protected $userNameMinLen = 5;

    /**
     * @var int
     * Set a maximum length for the username
     */
    protected $userNameMaxLen = 20;

    /**
     * @var int
     * Set a minimum length for the password
     */
    protected $passMinLen = 5;

    /**
     * @var int
     * Set a maximum length for the password
     */
    protected $passMaxLen = 20;

    /**
     * @var bool
     * Set to true if the password must contain at least one number
     */
    protected $passNumbersRequired = false;

    /**
     * @var bool
     * Set to true if the password must contain at least one capital letter
     */
    protected $passCapitalsRequired = false;

    /**
     * @var bool
     * Set to true if the password must contain at least one symbol
     */
    protected $passSymbolsRequired = false;

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

        setcookie($this->cookieName, $uniqid, time() + $this->sessionTime, "/", $this->sessionCookieDomain);

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
     * If the session is active, it will reset the expire timer
     */
    private function getSessionDataFromMemcache()
    {
        global $Core;

        $data = $Core->db->memcache->get($this->sessionKey);

        if (isset($data['expire'], $data['data'], $data['data']['logged_in']) && $data['expire'] >= time() ? $data['data'] : false) {
            $this->data = $data['data'];
            $this->setSessionInMemcache();
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
        return md5($Core->userModelBeforePass.sha1($password).$Core->userModelAfterPass);
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
            $selector->setWhere("`level` >= {$this->data['level']} OR `level` = 0 ");
            $selector->setOrderBy("`{$Core->dbName}`.`{$this->pagesTableName}`.`order` ASC, `{$Core->dbName}`.`{$this->pagesTableName}`.`name` ASC");
            return $selector->execute();
        }

        return $this->getAll(0, "`name` IS NOT NULL AND `name` != ''", "`order` ASC, `name` ASC");
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
            $selector->setWhere("`{$Core->dbName}`.`{$this->pagesTableName}`.`controller_path` = '".$Core->db->escape($Core->rewrite->controllerPath)."'");
            $selector->setGlobalTemplate('fetch_assoc');
            $level = $selector->execute();
            if (!empty($level) && isset($level['level']) && $level['level'] == 0) {
                return;
            }
        } else {
            foreach ($this->data['pages'] as $page) {
                if ($Core->Rewrite->controllerPath == $page['controller_path']) {
                    return;
                }
            }
        }

        $this->userAccessDenied();
    }

    /**
     * Allows to customize what happens when a user does not have an access to some page
     * By default it shows the page not found
     */
    protected function userAccessDenied()
    {
        global $Core;

        $Core->doOrDie();
    }

    /**
     * Validates the provided user type
     * Throws Exception if something is wrong
     * @param int $typeId - the type of the user
     * @throws Exception
     */
    private function validateUserType(int $typeId = null)
    {
        if ($typeId !== null) {
            if (empty($this->userTypesTableName)) {
                throw new Exception("userTypesTableName is not set");
            }

            if (!isset($this->getUserTypes()[$typeId])) {
                throw new Exception("Invalid user type");
            }
        } else if (!empty($this->userTypesTableName)) {
            throw new Exception("Type id is not set");
        }
    }

    /**
     * Loggs in a user with a username(email) and password, getting its data from the database
     * Throws BaseException if username/password is empty or they are not registered
     * Throws Exception if $typeId is provided but the model is not set up correctly
     * @param string $username
     * @param string $password
     * @param int $typeId - the type of the user (optional)
     * @throws Exception
     * @throws BaseException
     */
    public function login(string $username, string $password, int $typeId = null)
    {
        global $Core;

        $username = $Core->db->escape(trim($username));
        $password = $Core->db->escape(trim($password));

        if (empty($username)) {
            if ($this->registerWithEmail) {
                throw new BaseException('Enter email');
            } else {
                throw new BaseException('Enter username');
            }
        }

        if (empty($password)) {
            throw new BaseException('Enter password');
        }

        $this->validateUserType($typeId);

        $password = $this->hashPassword($password);

        $queryWhere = array();

        if ($typeId !== null) {
            $queryWhere[] = "`type_id` = '".($Core->db->escape($typeId))."'";
        }

        $queryWhere[] = "`username` = '$username'";

        $queryWhere[] = "`password` = '$password'";

        $this->data = $this->getAll(1, implode(' AND ', $queryWhere));

        if (!empty($this->data)) {
            $this->data = current($this->data);

            if ($this->confirmRegistrationWithEmail && empty($this->data[$this->usersRegistrationConfrimationControlField])) {
                $this->data['logged_in'] = false;
                $this->setSessionInMemcache();
                $Core->redirect($this->usersRegistrationEmailNotConfirmedControllerName);
            }

            $this->data['logged_in'] = true;
            $this->data = array_merge($this->data, $this->getUserLevelData());
            $this->data['pages'] = $this->getUserPages();

            $this->setSessionInMemcache();

            if ($this->lastLoginField) {
                $this->updateById(
                    $this->data['id'],
                    array($this->lastLoginField => $Core->globalFunctions->formatMysqlTime(time(), true))
                );
            }
        } elseif ($this->registerWithEmail) {
            throw new BaseException("Email addres or password is invalid");
        } else {
            throw new BaseException("Username or password is invalid");
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

        if ($redirectUrl !== '' && (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] != $redirectUrl)) {
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

        $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->userTypesTableName}`", 0, 'fillArray', $levels, 'id');
        return $levels;
    }

    //REGISTRATION FUNCTIONS

    /**
     * Validates the provided username (before registering or chaning)
     * It will check for empty spaces, min/max length, if these are required by the model
     * It will check for duplicate of the username. If user type is set, it will check for duplicates
     * only within this type
     * Throws Exception if something is wrong
     * @param string $username - the provided username
     * @param int $typeId - the type of the user (optional)
     * @throws Exception
     */
    private function validateUsername(string $username, int $typeId = null)
    {
        if (empty($username)) {
            throw new Exception("Provide username");
        }

        if (stristr($username,' ')) {
            throw new Exception("No spaces are allowed in the username");
        }

        if ($this->userNameMinLen && strlen($username) < $this->userNameMinLen) {
            throw new Exception("Username must be minimum %%{$this->userNameMinLen}%% symbols");
        }

        if ($this->userNameMaxLen && strlen($username) > $this->userNameMaxLen) {
            throw new Exception("Username must be maximum %%{$this->userNameMaxLen}%% symbols");
        }

        $queryWhere = "`username` = '$username'";
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
            if ($this->registerWithEmail) {
                throw new Exception("This email is already taken");
            }
            throw new Exception("This username is already taken");
        }
    }

    /**
     * Validates the provided password (before registering or chaning)
     * It will check for empty spaces, min/max length, numbers, capital letters and symbols,
     * if these are required by the model
     * Throws Exception if something is wrong
     * @param string $password - the provided password
     * @throws Exception
     */
    private function validatePassword(string $password)
    {
        if (empty($password)) {
            throw new Exception("Provide password");
        }
        if (stristr($password, ' ')) {
            throw new Exception("No spaces are allowed in the password");
        }
        if ($this->passMinLen && strlen($password) < $this->passMinLen) {
            throw new Exception("Password must be minimum %%{$this->passMinLen}%% symbols");
        }
        if ($this->passMaxLen && strlen($password) > $this->passMaxLen) {
            throw new Exception("Password must be maximum %%{$this->passMaxLen}%% symbols");
        }
        if ($this->passNumbersRequired && !preg_match("#[0-9]+#", $password)) {
            throw new Exception("Password must contain a number");
        }
        if ($this->passCapitalsRequired && !preg_match("#[A-Z]+#", $password)) {
            throw new Exception("Password must contain a capital letter");
        }
        if ($this->passCapitalsRequired && !preg_match("#\W+#", $password)) {
            throw new Exception("Password must contain a symbol");
        }
    }

    /**
     * Ensures the provided email is in the correct format
     * Throws Exception if it's not or if it's empty
     * @param string $email - the email to validate
     * @throws Exception
     */
    private function validateEmail(string $email)
    {
        if (empty($email)) {
            throw new Exception("Provide email");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email is not valid");
        }
    }

    /**
     * Validates the provided user level
     * Throws Exception if something is wrong
     * @param int $levelId - the type of the user
     * @throws Exception
     */
    private function validateUserLevel(int $levelId = null)
    {
        if ($this->usersLevelTableName && (empty($levelId) || !isset($this->getUserLevels()[$levelId]))) {
            throw new Exception("User level is not supported or invalid");
        }
    }

    /**
     * Throws BaseException if something is wrong with the credentials of the user
     * @param string $username - the user's username
     * @param string $password - the user's password
     * @param string $repeatPassword - the repeated user's password
     * @param int $levelId - the level of the user (optional)
     * @param int $typeId - the type of the user (optional)
     * @throws BaseException
     */
    public function validateRegisterationValues(
        string $username,
        string $password,
        string $repeatPassword,
        int $levelId = null,
        int $typeId = null
    )
    {
        $this->validateUserLevel($levelId);

        $this->validateUserType($typeId);

        $errorsInFields = array();

        try {
            if ($this->registerWithEmail) {
                $this->validateEmail($username);
            }
            $this->validateUsername($username, $typeId);
        } catch (Exception $ex) {
            $errorsInFields['username'] = $ex->getMessage();
        }

        try {
            $this->validatePassword($password);
        } catch (Exception $ex) {
            $errorsInFields['password'] = $ex->getMessage();
        }

        if (empty($repeatPassword)) {
            $errorsInFields['repeat_password'] = "Repeat password";
        } elseif ($password !== $repeatPassword) {
            $errorsInFields['repeat_password'] = "Passwords do not match";
        }

        if (!empty($errorsInFields)) {
            throw new BaseException("The following fields are not valid", $errorsInFields, get_class($this));
        }
    }

    /**
     * Allows the registration of users with username and password
     * Allows to set a user level id and user type id (if requried by the project)
     * Returns the registered user id
     * Throws Exception if input is empty
     * @param array $input - the user credentials; it must have the fields 'username', 'password', 'repeat_password'
     * @return int
     * @throws Exception
     */
    public function register(array $input)
    {
        global $Core;

        if ($this->confirmRegistrationWithEmail === true) {
            $this->validateConfirmRegistrationWithEmailConfiguration();
        }

        if (empty($input)) {
            throw new Exception("Provide user data");
        }

        $input['username']        = isset($input['username'])        ? trim($input['username']) : '';
        $input['password']        = isset($input['password'])        ? trim($input['password']) : '';
        $input['repeat_password'] = isset($input['repeat_password']) ? trim($input['repeat_password']) : '';
        $input['level_id']        = isset($input['level_id'])        ? intval($input['level_id']) : null;
        $input['type_id']         = isset($input['type_id'])         ? intval($input['type_id']) : null;

        $this->validateRegisterationValues(
            $input['username'],
            $input['password'],
            $input['repeat_password'],
            $input['level_id'],
            $input['type_id']
        );

        if ($this->confirmRegistrationWithEmail === true && $this->registerWithEmail === false) {
            $input = $this->getAndValidateEmailForConfirmRegistrationWithEmail($input);
        }

        $input['password'] = $this->hashPassword($input['password']);

        if ($input['level_id'] === null) {
            unset($input['level_id']);
        }

        if ($input['type_id'] === null) {
            unset($input['type_id']);
        }

        unset($input['repeat_password']);

        $userId = $this->insert($input);

        $input['id'] = $userId;

        if ($this->confirmRegistrationWithEmail === true) {
            $this->getRegistrationConfirmationToken($input);
        }

        return $userId;
    }

    /**
     * Allows to change the password of the user
     * Throws BaseException if something is wrong with the credentials of the user
     * Throws Exception if a user id is not provided
     * @param int $userId - the id of the user
     * @param string $newPassword - the new password
     * @param string $repeatPassword - repeat the new password
     * @param string $currentPassword - the current password of the user (optional)
     * @throws BaseException
     * @throws Exception
     */
    public function changePassword(int $userId, string $newPassword, string $repeatPassword, string $currentPassword = null){
        global $Core;

        if (empty($userId)) {
            throw new Exception("Provide user id");
        }

        $userData = $this->getById($userId);

        if (empty($userData)) {
            throw new BaseException("This user does not exist");
        }

        $errorsInFields = array();

        $newPassword = $Core->db->escape(trim($newPassword));
        $repeatPassword = $Core->db->escape(trim($repeatPassword));

        try {
            $this->validatePassword($newPassword);
        } catch (Exception $ex) {
            $errorsInFields['password'] = $ex->getMessage();
        }

        if (empty($repeatPassword)) {
            $errorsInFields['repeat_password'] = "Repeat password";
        } elseif ($newPassword !== $repeatPassword) {
            $errorsInFields['repeat_password'] = "Passwords do not match";
        }

        if ($currentPassword !== null){
            $currentPassword = $Core->db->escape(trim($currentPassword));
            if (empty($currentPassword)) {
                $errorsInFields['current_password'] = "Enter current password";
            } else if ($this->hashPassword($currentPassword) != $userData['password']) {
                $errorsInFields['current_password'] = "Current password is incorrect";
            }
        }

        if (!empty($errorsInFields)) {
            throw new BaseException("The following fields are not valid", $errorsInFields, get_class($this));
        }

        return $this->updateById($userId, array('password' => $this->hashPassword($newPassword)));
    }

    /**
     * Generates a random alpanumeric string, with a specific length
     * Throws exception if the provided length is negative, zero or larger than 32
     * @param int $length - the length of the password to generate (between 1 and 32 symbols)
     * @return string
     * @throws Exception
     */
    public function generatePassword(int $length)
    {
        if ($length <= 0) {
            throw new Exception("Password length must be bigger than %%0%% symbols");
        }

        if ($length > 32) {
            throw new Exception("Password length must be less than %%32%% symbols");
        }

        return strtoupper(substr(md5(time()), rand(0, (32 - $length)), $length));
    }

    /**
     * Generates an unique token
     * @return string
     */
    public function generateToken()
    {
        return md5(uniqid());
    }
}

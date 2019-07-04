<?php
class RESTAPI extends Base
{
    const MAX_ITEMS_PER_PAGE = 100;
    
    const DEFAULT_ITEMS_PER_PAGE = 12;
    
    const DEFAULT_PAGE = 1;
    
    /**
     * The result to output
     * @var mixed
     */
    private $result;
    
    /**
     * How many are the total results
     * @var int
     */
    private $total;
    
    protected $allowedRequestMethods = array(
        'GET',
        'POST',
        'DELETE',
        'PUT',
        'PATCH'
    );
    
    /**
     * Shows a 404 header
     */
    protected final function showHeaderNotFound()
    {
        header('HTTP/1.0 404 Not Found', true, 404);
    }
    
    /**
     * Shows a 400 header
     */
    protected final function showHeaderBadRequest()
    {
        header("HTTP/1.1 400 Bad Request", true, 400);
    }
    
    /**
     * Gets the request method type
     * @return string
     */
    private function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Parses a POST query to the API
     * It has two options: 
     * - insert query (POST /users # add new user; returns the user)
     * - updateById query (POST /users/:id # update user data; returns the updated user)
     * Throws Exception if the $_POST array is empty
     * @throws Exception
     */
    private function parsePost()
    {
        global $Core;
        
        if (empty($_POST)) {
            throw new Exception("Cannot insert or update an empty object");
        }
        
        if (isset($Core->Rewrite->urlBreakdown[1]) && !empty($Core->Rewrite->urlBreakdown[1])) {
            $this->parseUpdateById();
        } else {
            $objectId = $this->insert($Core->db->escape($_POST));
            $this->result = $this->getById($objectId);
            $this->total = 1;
            $Core->Rewrite->setCurrentPage(1);
            $Core->setItemsPerPage(1);
        }
    }
    
    /**
     * Parses a PUT and PATCH queries to the API
     * - PUT /users/:id # update user data; returns the updated user
     * - PATCH /users/:id # update user data fields; returns the updated user
     * Throws Exception if no object id is in the request URL
     * @throws Exception
     */
    private function parsePutAndPatch()
    {
        global $Core, $_PATCH, $_PUT;
        if (isset($Core->Rewrite->urlBreakdown[1]) && !empty($Core->Rewrite->urlBreakdown[1])) {
            $_POST = !empty($_PATCH) ? $_PATCH : $_PUT;
            
            $this->parseUpdateById();
        } else {
            throw new Exception("Provide an object id");
        }
    }
    
    /**
     * Parses an update by id query.
     * This could happen when making a POST (with id), PUT or PATCH queries to the API;
     * The result will be the updated object.
     * If the query has the $_GET parameter 'lang', it will translate the object instead;
     * In this case the result witll be the translated object
     */
    private function parseUpdateById()
    {
        global $Core;
        
        $objectId = $Core->Rewrite->urlBreakdown[1];
        if (isset($_POST['id'])) {
            unset($_POST['id']);
        }
        
        if (isset($_GET['lang']) && !empty($_GET['lang'])) {
            $this->parseTranslateById($objectId);
        } else {
            $this->updateById($objectId, $Core->db->escape($_POST));
            $this->result = $this->getById($objectId);
        }
        
        $this->total = 1;
        $Core->Rewrite->setCurrentPage(1);
        $Core->setItemsPerPage(1);
    }
    
    /**
     * Parses an update by id query.
     * This could happen when making a POST (with id), PUT or PATCH queries to the API, when the 'lang' parameter is set
     * to valid and active language.
     * Throws Exception if the language does not exist, it is not active, or it's the default language.
     * @param int $objectId - the id of the object ot translate
     * @throws Excception
     */
    private function parseTranslateById($objectId)
    {
        global $Core;
        
        $languageId = $Core->Language->getIdByName($_GET['lang']);
       
        if ($Core->Language->getDefaultLanguageId() === $languageId) {
            throw new Exception("Cannot translate to the default language");
        }
            
        if ($Core->Language->isActiveLanguage($languageId) === false) {
            throw new Exception("Language ##`{$_GET['lang']}`## is not allowed");
        }
        
        $this->translate($objectId, $languageId, $Core->db->escape($_POST));
        $this->result = $this->getTranslation($this->getById($objectId), $languageId);
    }
    
    /**
     * Parses a DELETE query to the API.
     * It has two options: 
     * - deleteById query  (DELETE /users/:id # delete user)
     * - delete query (DELETE /users?id[] # delete multiple users)
     * Throws Exception if the $_POST array is empty.
     * @throws Exception
     */
    private function parseDelete()
    {
        global $Core;
        
        if ($Core->Rewrite->urlGetPart === '') {
            $this->deleteById($Core->Rewrite->currentPage);
        } else {
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                throw new Exception("Provide a list with ids");
            }
            
            $ids = $Core->db->real_escape_string(key($_GET['id']));
            
            if (empty($ids)) {
                throw new Exception("Provide a list with ids");
            }
            
            $this->delete("`id` IN ($ids)");
        }
    }
    
    /**
     * Parses a GET query to the API.
     * It has three options: 
     * - getAll query  (GET /users?q=search&page=1&limit=10&sort=-name,age; reurns user list and pagination)
     * - getAll query  (GET /users reurns the first page of the user list and pagination)
     * - getById query (GET /users/:id # get user data; returns the user)
     */
    private function parseGet()
    {
        global $Core;
        
        if (
            count($Core->Rewrite->urlBreakdown) > 1 && 
            is_numeric(array_values(array_slice($Core->Rewrite->urlBreakdown, -1))[0])
        ) {
            $this->parseGetById();
        } else {
            $this->parseGetAll();
        }
    }
    
    /**
     * Parses a getById query to the API.
     * The result is single object.
     */
    private function parseGetById()
    {
        global $Core;
        
        $this->result = $this->getById($Core->Rewrite->currentPage);
        if (!empty($this->result)) {
            $this->total = 1;
        } else {
            $this->total = 0;
        }
        $Core->Rewrite->setCurrentPage(1);
        $Core->setItemsPerPage(1);
    }
    
    /**
     * Parses a getAll query to the API.
     * The result is an array of objects.
     * Consideres search and sort parameters of the query
     */
    private function parseGetAll()
    {
        global $Core;
        
        $this->setDefaultPagination();
        
        $additional = $this->parseSearchBy();
        
        $this->result = $this->getAll(null, $additional, $this->parseOrderBy());
        if (!empty($this->result)) {
            $this->total = $this->getCount($additional);
        } else {
            $this->total = 0;
        }
    }
    
    /**
     * Looks for a 'sort' GET parameter in the query.
     * If it's available, prepares an orderBy override for the getAll function.
     * If it's not available, it will return null.
     * Checks if all the provided fields in the 'sort' parameter exist in the model's table; if a field does not exist,
     * throws an Exception.
     * Fields should be comma separated, the '-' before the field name stands for DESC sort type, without 'i' is ASC.
     * 
     * @throws Exception
     * @return string|null
     */
    private function parseOrderBy()
    {
        if (isset($_GET['sort']) && !empty($_GET['sort'])) {
            $this->checkTableFields();
            $sort = explode(',', $_GET['sort']);
            foreach ($sort as $key => $field) {
                try {
                    $this->tableFields->getFieldType(str_replace('-', '', $field));
                } catch (Exception $ex) {
                    throw new Exception("Cannot sort by ##`{$field}`##");
                }
                
                if (strstr($field, '-')) {
                    $field = "`".str_replace('-', '', $field)."` DESC";
                } else {
                    $field = "`{$field}` ASC";
                }
                $sort[$key] = $field;
            }
            return implode(', ', $sort);
        }
        
        return null;
    }
    
    /**
     * Looks for additional GET parameters in the query. Additional parameter is considered any parameter, except the
     * reserved ones - 'page', 'items_per_page', 'limit' and 'sort'.
     * If such parameter is found, it is considered a parameter to filter by, in the manner of <fieldName>=<value>.
     * All search parameters are forming the 'additional' parameter for the getAll function.
     * Checks if all the provided fields exist in the model's table; if a field does not exist, throws an Exception.
     * Checks if all the provided values match the fields types (for example, it's impossible to search for an 'a' in 
     * an int); If there is a mismatch - BaseException.
     * Numeric fields are searched for exact match, while text fields are searched with a double LIKE.
     * Additional fields are added with AND to the query.
     * 
     * @throws BaseException
     * @throws Exception
     * @return string|null
     */
    private function parseSearchBy()
    {
        global $Core;
        
        $get = $_GET;
        unset($get['page'], $get['items_per_page'], $get['limit'], $get['sort']);
        
        if (!empty($get)) {
            $this->checkTableFields();
            
            $this->validateInputValues($get);
            
            $additional = array();
            
            foreach ($get as $fieldName => $value) {
                try {
                    $fieldType = $this->tableFields->getFieldType(str_replace('-', '', $fieldName));
                } catch (Exception $ex) {
                    throw new Exception("Cannot search by ##`{$fieldName}`##");
                }
                
                $value = $Core->db->real_escape_string($value);
                
                if ((strstr($fieldType, 'int') || $fieldType === 'double' || $fieldType === 'float')) {
                    $additional[] = "`$fieldName` = '{$value}'";
                } else {
                    $additional[] = "`$fieldName` LIKE '%{$value}%'";
                }
                
                unset($value, $fieldType);
            }
            
            return implode(" AND ", $additional);
        }
        
        return null;
    }
    
    /**
     * Sets the currentPage variable of the Rewrite class using the 'page' key in the $_REQUEST array.
     * If it's not set, uses the DEFAULT_PAGE constant of the class.
     * Sets the Core itemsPerPage variable, according to the 'items_per_page' or 'limit' keys in the $_REQUEST array.
     * If both keys are present, the 'limit' key is considered.
     * If none are present it uses the DEFAULT_ITEMS_PER_PAGE constant of the class.
     * Throws Exception if the Core items per page variable is bigger than MAX_ITEMS_PER_PAGE constant.
     * @throws Exception
     */
    private function setDefaultPagination()
    {
        global $Core;

        $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? $_REQUEST['page'] : self::DEFAULT_PAGE;
        
        if (!is_numeric($page)) {
            throw new Exception("Invalid page"); 
        }
        
        if (isset($_REQUEST['items_per_page']) && !empty($_REQUEST['items_per_page'])) {
            $itemsPerPage = intval($_REQUEST['items_per_page']);
        } elseif (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) {
            $itemsPerPage = intval($_REQUEST['limit']);
        } else {
            $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;
        }
        
        if (!is_numeric($itemsPerPage) || empty($itemsPerPage)) {
            throw new Exception("Invalid limits"); 
        }
        
        if ($itemsPerPage > self::MAX_ITEMS_PER_PAGE) {
            throw new Exception("Maximum ##".self::MAX_ITEMS_PER_PAGE."## items per page");
        }
        
        $Core->Rewrite->setCurrentPage($page);
        $Core->setItemsPerPage($itemsPerPage);
    }
    
    /**
     * Creates an instance of the RESTAPI class.
     * It will parse the request, considering which type it is (GET, POST, DELETE, PUT or PATCH).
     * The result is JSON encoded array.
     * Handles any exceptions, thrown during the parsing.
     */
    public function __construct()
    {   
        global $Core;
        
        try {
            $method = $this->getRequestMethod();
            
            if (!empty($this->allowedRequestMethods) && !in_array($method, $this->allowedRequestMethods)) {
                throw new UnauthorizedException("Unauthorized");
            }
            
            switch ($method) {
                case 'POST': 
                    $this->parsePost();
                    break;
                case 'DELETE':
                    $this->parseDelete();
                    break;
                case 'PUT':
                case 'PATCH':
                    $this->parsePutAndPatch();
                    break;
                default:
                    $this->parseGet();
                    break;
            }
            
            $Core->RestAPIResult->showResult($this->result, $this->total);
        } catch (UnauthorizedException $ex) {
            $Core->RestAPIResult->showError($ex->getMessage(), $ex->getData());
        } catch (BaseException $ex) {
            $this->showHeaderBadRequest();
            $Core->RestAPIResult->showError($ex->getMessage(), $ex->getData());
        } catch (Error $ex) {
            $this->showHeaderBadRequest();
            $Core->RestAPIResult->showError($ex->getMessage(), array());
        } catch (Exception $ex) {
            $this->showHeaderBadRequest();
            $Core->RestAPIResult->showError($ex->getMessage(), array());
        }
    }
}

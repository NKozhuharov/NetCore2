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
    
    /**
     * Gets the request method type
     * @return string
     */
    private function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Gets the current model name, according to the request URL
     * Throws Error if the model does not exist
     * @throws Error
     */
    private function setModelNameFromRequest()
    {
        global $Core;
        
        $this->modelName = ucfirst($Core->Rewrite->controller);
        
        if ($this === false) {
            $this->modelName .= 's';
        }
        
        if ($this === false) {
            throw new Error("This object does not exist!");
        }
    }
    
    /**
     * Parses a POST query to the API
     * It has two options: 
     * - insert query  (POST /users # add new user; returns the user)
     * - updateById query (POST /users/:id # update user data; returns the updated user)
     * Throws Error if the $_POST array is empty
     * @throws Error
     */
    private function parsePost()
    {
        global $Core;
        
        if (empty($_POST)) {
            throw new Error("Cannot insert/update an empty object!");
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
     * Throws Error if no object id is in the request URL
     * @throws Error
     */
    private function parsePutAndPatch()
    {
        global $Core, $_PATCH, $_PUT;
        if (isset($Core->Rewrite->urlBreakdown[1]) && !empty($Core->Rewrite->urlBreakdown[1])) {
            $_POST = !empty($_PATCH) ? $_PATCH : $_PUT;
            
            $this->parseUpdateById();
        } else {
            throw new Error("Provide an object id");
        }
    }
    
    /**
     * Parses an update by id query
     * This could happen when making a POST (with id), PUT or PATCH queries to the API
     */
    private function parseUpdateById()
    {
        global $Core;
        
        $objectId = $Core->Rewrite->urlBreakdown[1];
        if (isset($_POST['id'])) {
            unset($_POST['id']);
        }
        $this->updateById($objectId, $Core->db->escape($_POST));
        
        $this->result = $this->getById($objectId);
        $this->total = 1;
        $Core->Rewrite->setCurrentPage(1);
        $Core->setItemsPerPage(1);
    }
    
    /**
     * Parses a DELETE query to the API
     * It has two options: 
     * - deleteById query  (DELETE /users/:id # delete user)
     * - delete query (DELETE /users?id[] # delete multiple users)
     * Throws Error if the $_POST array is empty
     * @throws Error
     */
    private function parseDelete()
    {
        global $Core;
        
        if ($Core->Rewrite->urlGetPart === '') {
            $this->deleteById($Core->Rewrite->currentPage);
        } else {
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                throw new Error("Provide a list with ids");
            }
            
            $ids = $Core->db->real_escape_string(key($_GET['id']));
            
            if (empty($ids)) {
                throw new Error("Provide a list with ids");
            }
            
            $this->delete("`id` IN ($ids)");
        }
    }
    
    /**
     * Parses a GET query to the API
     * It has two options: 
     * - getAll query  (GET /users?q=search&page=1&limit=10&sort=-name,age; reurns user list and pagination)
     * - getById query (GET /users/:id # get user data; returns the user)
     */
    private function parseGet()
    {
        global $Core;
        
        if ($Core->Rewrite->urlGetPart === '') {
            $this->parseGetById();
        } else {
            $this->parseGetAll();
        }
    }
    
    /**
     * Parses a getById query to the API
     * The result is single object
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
     * Parses a getAll query to the API
     * The result is an array of objects
     * @todo - order and search
     */
    private function parseGetAll()
    {
        global $Core;
        
        $this->setDefaultPagination();
        $this->result = $this->getAll(null);
        if (!empty($this->result)) {
            $this->total = $this->getCount(null);
        } else {
            $this->total = 0;
        }
    }
    
    /**
     * Sets the currentPage variable of the Rewrite class using the 'page' key in the $_REQUEST array
     * If it's not set, uses the DEFAULT_PAGE constan of the class
     * Sets the Core itemsPerPage variable, according to the 'items_per_page' or 'limit' keys in the $_REQUEST array
     * If both keys are present, the 'limit' key is considered
     * If none are present it uses the DEFAULT_ITEMS_PER_PAGE constan of the class
     * Throws Error if the Core items per page variable is bigger than MAX_ITEMS_PER_PAGE constant
     * @throws Error
     */
    private function setDefaultPagination()
    {
        global $Core;

        $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? $_REQUEST['page'] : self::DEFAULT_PAGE;
        
        if (!is_numeric($page)) {
            throw new Error("Invalid page!"); 
        }
        
        if (isset($_REQUEST['items_per_page']) && !empty($_REQUEST['items_per_page'])) {
            $itemsPerPage = intval($_REQUEST['items_per_page']);
        } elseif (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) {
            $itemsPerPage = intval($_REQUEST['limit']);
        } else {
            $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;
        }
        
        if (!is_numeric($itemsPerPage) || empty($itemsPerPage)) {
            throw new Error("Invalid limits!"); 
        }
        
        if ($itemsPerPage > self::MAX_ITEMS_PER_PAGE) {
            throw new Error("Maximum ".self::MAX_ITEMS_PER_PAGE." items per page!");
        }
        
        $Core->Rewrite->setCurrentPage($page);
        $Core->setItemsPerPage($itemsPerPage);
    }
    
    private function returnResponse()
    {
        global $Core;

        header('Content-Type: application/json');

        exit(
            json_encode(
                array(
                    'info'  => $this->result,
                    'total' => intval($this->total),
                    'page'  => intval($Core->Rewrite->currentPage),
                    'limit' => intval($Core->itemsPerPage),
                )
            )
        );
    }
    
    public function __construct()
    {   
        global $Core;
        
        try {
            switch ($this->getRequestMethod()) {
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
            
            $this->returnResponse();
        } catch (BaseException $ex) {
            $Core->dump($ex);
        } catch (Error $ex) {
            $Core->dump($ex);
        }
    }
}
<?php
/**
 * Base class for all the classes in the query builde
 * @todo some kind of global checker
 */
abstract class BaseQuery
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';
    const ORDER_FIELD = 'FIELD';

    /**
     * @var string
     * The body of the query
     */
    protected $query;

    /**
     * @var string
     * The name of the databse
     */
    protected $dbName;

    /**
     * @var string
     * The name of the table
     */
    protected $tableName;

    /**
     * Builds the query from all of it's parts
     * @return string
     */
    public abstract function build();

    /**
     * Creates a new instance of the BaseQuery class
     * Throws Error if table name is empty
     * @param string $tableName - the name for the table
     * @throw Error
     */
    public function __construct(string $tableName)
    {
        global $Core;

        if (empty($tableName)) {
            throw new Error("Provide a table name");
        }

        $this->tableName = $tableName;

        $this->dbName = $Core->dbName;
    }

    /**
     * Allows the user to get the query string
     * Throws Error if another property is requested
     * @param string $varName - only 'qeury' is allowed
     * @return string
     * @throws Error
     */
    public function __get(string $varName)
    {
        if (mb_strtolower($varName) === 'query') {
            if (empty($this->query)) {
                $this->build();
            }
            return $this->query;
        }

        throw new Error("Only 'query' is allowed");
    }

    /**
     * Set the name of the table for the query
     * Throws Error if table name is empty
     * @param string $tableName - the name for the table
     * @throw Error
     */
    public function setTableName(string $tableName)
    {
        if (empty($tableName)) {
            throw new Error("Provide a table name");
        }
        $this->tableName = $tableName;
    }

    /**
     * Set the name of the database for the query
     * Throws Error if databse name is empty
     * @param string $dbName - the name for the database
     * @throw Error
     */
    public function setDbName(string $dbName)
    {
        if (empty($dbName)) {
            throw new Error("Provide a database name");
        }
        $this->dbName = $dbName;
    }

    /**
     * Dumps the body of the query.
     * By default, it dies after the dump
     * @param bool @die - if set to false, the script will not die after the dump
     */
    public function dump(bool $die = null)
    {
        global $Core;
        $Core->dump(
            $this->build(),
            ($die === false) ? false : true
        );
    }

    /**
     * Echoes the body of the query.
     * By default, it dies after the echo
     * @param bool @die - if set to false, the script will not die after the echo
     */
    public function dumpString(bool $die = null)
    {
        echo $this->build().PHP_EOL;
        if ($die !== false) {
            die;
        }
    }

    /**
     * Returns the body of the query
     * @return string
     */
    public function get()
    {
        return $this->build();
    }

    /**
     * Executes the query and returns the result
     * @return mixed
     */
    public function execute()
    {
        global $Core;

        return $Core->db->query($this->build());
    }
}

<?php
trait QueryExecuters
{
    /**
     * Executes a delete query statment and parses any thrown exceptions
     * @param BaseDelete $deleter - the deleter instance of the BaseDelete class
     * @return int
     */
    protected function executeDeleteQuery(BaseDelete $deleter)
    {
        try {
            return $deleter->execute();
        } catch (Exception $ex) {
            $this->parseDeleteMySQLError($ex);
        }
    }
    
    /**
     * Executes a insert query statment and parses any thrown exceptions
     * @param BaseInsert $inserter - the deleter instance of the BaseInsert class
     * @return int
    */
    protected function executeInsertQuery(BaseInsert $inserter)
    {
        try {
            return $inserter->execute();
        } catch (Exception $ex) {
            $this->parseInsertMysqlError($ex);
        }
    }
    
    /**
     * Executes a update query statment and parses any thrown exceptions
     * @param BaseUpdate $updater - the updater instance of the BaseUpdate class
     * @return int
    */
    protected function executeUpdateQuery(BaseUpdate $updater)
    {
        try {
            return $updater->execute();
        } catch (Exception $ex) {
            $this->parseUpdateMysqlError($ex);
        }
    }

    /**
     * Parses a MySQL Error, occured when using any of the delete functions
     * Looks for foreign key constraint fails and returns a human readble error with BaseException
     * It will throw the same Exception if it wasn't parsed
     * @param Exception $ex - an Exception thrown when executing a delete query
     * @throws BaseException
     * @throws Exception
     */
    protected function parseDeleteMySQLError(Exception $ex)
    {
        global $Core;

        $message = $ex->getMessage();

        if (stristr($message, 'Mysql Error: Cannot delete or update a parent row: a foreign key constraint fails')) {
            $modelName = strtolower(get_class($this));
            $referencedTableName = substr($message, strpos($message, 'constraint fails (') + strlen('constraint fails ('));
            $referencedTableName = str_replace("`{$Core->dbName}`.", "", $referencedTableName);
            $referencedTableName = str_replace("`", "", $referencedTableName);
            $referencedTableName = substr($referencedTableName, 0, strpos($referencedTableName, ','));

            throw new BaseException(
                "You cannot delete from %%{$modelName}%% if there are %%$referencedTableName%% attached to it",
                null,
                get_class($this)
            );
        }

        throw new Exception($ex->getMessage());
    }
    
    /**
     * Parses a MySQL Error, occured when using any of the insert functions
     * It will throw the same Exception if it wasn't parsed
     * @param Exception $ex - an Exception thrown when executing a insert query
     * @throws Exception
     */
    protected function parseInsertMysqlError(Exception $ex)
    {
        $message = $ex->getMessage();
        
        $this->parseDuplicateKeyMysqlError($message);
        $this->parseForeignKeyMysqlError($message);
        
        throw new Exception($ex->getMessage());
    }
    
    /**
     * Parses a MySQL Error, occured when using any of the update functions
     * It will throw the same Exception if it wasn't parsed
     * @param Exception $ex - an Exception thrown when executing a update query
     * @throws Exception
     */
    protected function parseUpdateMysqlError(Exception $ex)
    {
        $message = $ex->getMessage();
        
        $this->parseDuplicateKeyMysqlError($message);
        $this->parseForeignKeyMysqlError($message);
        
        throw new Exception($ex->getMessage());
    }
    
    /**
     * Parses a Duplicate key MySQL Error
     * It will throw BaseException with the parsed error
     * If the error is not a Duplicate Key Error, it will not do anything
     * @param string $message - the message text of the error
     * @throws BaseException
     */
    protected function parseDuplicateKeyMysqlError(string $message)
    {
        if (strstr($message, 'Mysql Error: Duplicate entry')) {
            $message = str_replace("Mysql Error: Duplicate entry '", '', $message);
            $duplicatedValue = substr($message, 0, strpos($message, "'"));
            $duplicatedKey = str_replace("$duplicatedValue' for key", '', $message);
            $duplicatedKey = trim(str_replace("'", '', $duplicatedKey));
            
            $modelName = strtolower(get_class($this));
            
            if (substr($modelName, strlen($modelName) - 1, 1) === 's') {
                $modelName = substr($modelName, 0, -1);
            }
            
            throw new BaseException(
                "The following %%{$modelName}%% alredy exists %%`{$duplicatedValue}`%%",
                null,
                get_class($this)
            );
        }
    }
    
    /**
     * Parses a Foreign key MySQL Error
     * It will throw BaseException with the parsed error
     * If the error is not a Foreign Key Error, it will not do anything
     * @param string $message - the message text of the error
     * @throws BaseException
     */
    protected function parseForeignKeyMysqlError(string $message)
    {
        if (strstr($message, 'Mysql Error: Cannot add or update a child row: a foreign key constraint fails')) {
            $message = substr($message, strpos($message, 'FOREIGN KEY') + 12);
            $message = substr($message, 0, strpos($message, 'REFERENCES') - 1);
            $message = trim(str_replace(array(')','(','`'), '', $message));
            $message = str_replace('_id', '', $message);
            
            throw new BaseException("This %%{$message}%% does not exist", null, get_class($this));
        }
    }
}

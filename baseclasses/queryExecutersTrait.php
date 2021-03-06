<?php
trait QueryExecuters
{
    /**
     * Executes a delete query statment and parses any thrown errors
     * @param BaseDelete $deleter - the deleter instance of the BaseDelete class
     * @return int
     */
    protected function executeDeleteQuery(BaseDelete $deleter)
    {
        try {
            return $deleter->execute();
        } catch (Error $ex) {
            $this->parseDeleteMySQLError($ex);
        }
    }

    /**
     * Executes a insert query statment and parses any thrown errors
     * @param BaseInsert $inserter - the deleter instance of the BaseInsert class
     * @return int
    */
    protected function executeInsertQuery(BaseInsert $inserter)
    {
        try {
            return $inserter->execute();
        } catch (Error $ex) {
            $this->parseInsertMysqlError($ex);
        }
    }

    /**
     * Executes a update query statment and parses any thrown errors
     * @param BaseUpdate $updater - the updater instance of the BaseUpdate class
     * @return int
    */
    protected function executeUpdateQuery(BaseUpdate $updater)
    {
        try {
            return $updater->execute();
        } catch (Error $ex) {
            $this->parseUpdateMysqlError($ex);
        }
    }

    /**
     * Parses a MySQL Error, occured when using any of the delete functions
     * Looks for foreign key constraint fails and returns a human readble error with BaseException
     * It will throw the same Error if it wasn't parsed
     * @param Error $ex - an Error thrown when executing a delete query
     * @throws BaseException
     * @throws Error
     */
    protected function parseDeleteMySQLError(Error $ex)
    {
        global $Core;

        $message = $ex->getMessage();

        if (mb_stristr($message, 'Mysql Error: Cannot delete or update a parent row: a foreign key constraint fails')) {
            $modelName = mb_strtolower(get_class($this));
            $referencedTableName = mb_substr($message, mb_strpos($message, 'constraint fails (') + mb_strlen('constraint fails ('));
            $referencedTableName = str_replace("`{$Core->dbName}`.", "", $referencedTableName);
            $referencedTableName = str_replace("`", "", $referencedTableName);
            $referencedTableName = mb_substr($referencedTableName, 0, mb_strpos($referencedTableName, ','));

            throw new BaseException(
                "You cannot delete from %%{$modelName}%% if there are %%$referencedTableName%% attached to it",
                null,
                get_class($this)
            );
        }

        throw new Error($ex->getMessage());
    }

    /**
     * Parses a MySQL Error, occured when using any of the insert functions
     * It will throw the same Error if it wasn't parsed
     * @param Error $ex - an Error thrown when executing a insert query
     * @throws Error
     */
    protected function parseInsertMysqlError(Error $ex)
    {
        $message = $ex->getMessage();

        $this->parseDuplicateKeyMysqlError($message);
        $this->parseForeignKeyMysqlError($message);

        throw new Error($ex->getMessage());
    }

    /**
     * Parses a MySQL Error, occured when using any of the update functions
     * It will throw the same Error if it wasn't parsed
     * @param Error $ex - an Error thrown when executing a update query
     * @throws Error
     */
    protected function parseUpdateMysqlError(Error $ex)
    {
        $message = $ex->getMessage();

        $this->parseDuplicateKeyMysqlError($message);
        $this->parseForeignKeyMysqlError($message);

        throw new Error($ex->getMessage());
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
        if (mb_strstr($message, 'Mysql Error: Duplicate entry')) {
            $message = str_replace("Mysql Error: Duplicate entry '", '', $message);
            $duplicatedValue = mb_substr($message, 0, mb_strpos($message, "'"));
            $duplicatedKey = str_replace("$duplicatedValue' for key", '', $message);
            $duplicatedKey = trim(str_replace("'", '', $duplicatedKey));

            $modelName = mb_strtolower(get_class($this));

            if (mb_substr($modelName, mb_strlen($modelName) - 1, 1) === 's') {
                $modelName = mb_substr($modelName, 0, -1);
            }

            throw new BaseException(
                "The following %%{$modelName}%% already exists %%`{$duplicatedValue}`%%",
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
        if (mb_strstr($message, 'Mysql Error: Cannot add or update a child row: a foreign key constraint fails')) {
            $message = mb_substr($message, mb_strpos($message, 'FOREIGN KEY') + 12);
            $message = mb_substr($message, 0, mb_strpos($message, 'REFERENCES') - 1);
            $message = trim(str_replace(array(')','(','`'), '', $message));
            $message = str_replace('_id', '', $message);

            throw new BaseException("This %%{$message}%% does not exist", null, get_class($this));
        }
    }
}

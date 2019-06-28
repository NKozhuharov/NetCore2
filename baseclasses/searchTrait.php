<?php
define ("SEARCH_TYPE_LEFT", 0);
define ("SEARCH_TYPE_RIGHT", 1);
define ("SEARCH_TYPE_FULL", 2);

trait SearchTrait
{
    /**
     * The name of the field to search at
     * @var string
     */
    protected $searchByField;

    /**
     * Builds the WHERE clause for the search query, using LIKE.
     * Requires the searchByField to be set, throws Exception if it doesn't.
     * Supports left, right and full searches.
     * Allows the $additional condition to start with OR/AND. If it doesn't, it will apply it with AND.
     * @param string $phrase - the phrase to search for
     * @param int $searchType - the search type (0, 1 or 2)
     * @param string $additional - where clause additional conditions (optional)
     * @return string
     * @throws Exception
     */
    private function buildSearchQueryWhereClause(string $phrase, int $searchType, string $additional = null)
    {
        global $Core;

        if (empty($this->searchByField)) {
            throw new Exception("To use the search function, set a search by field first!");
        }

        $phrase = $Core->db->real_escape_string($phrase);

        $query = "`{$Core->dbName}`.`{$this->tableName}`.`{$this->searchByField}` LIKE '";

        if ($searchType === SEARCH_TYPE_LEFT) {
            $query .= "%{$phrase}";
        } else if ($searchType === SEARCH_TYPE_RIGHT) {
            $query .= "{$phrase}%";
        } else if ($searchType === SEARCH_TYPE_FULL) {
            $query .= "%{$phrase}%";
        } else {
            throw new Exception("Invalid search type");
        }

        $query .= "' ";
        if (!empty($additional)) {
            $additional = trim($additional);
            $additionalLogic = mb_substr($additional, 0, 3);
            if (mb_stristr($additionalLogic, 'and') || mb_stristr($additionalLogic, 'or')) {
                $query .= " {$additional}";
            } else {
                $query .= " AND ({$additional})";
            }
        }

        return $query;
    }

    /**
     * Executes the search query and returns the result in an array (info, total)
     * Uses the getCound and getAll Base functions, so uses the $limit and $orderBy parameters accordingly
     * @param string $whereCondition
     * @param int $limit - the limit override
     * @param string $orderBy - the order by override
     */
    private function search(string $whereCondition, int $limit = null, string $orderBy = null)
    {
        $count = $this->getCount($whereCondition);

        if ($count) {
            return array(
                'info' => $this->getAll($limit, $whereCondition, $orderBy),
                'total' => $count,
            );
        }

        return $this->getEmptySearchResult();
    }

    /**
     * Get a structured result, when the search query does not have results
     * All search queries should return array with keys 'info' and 'total'
     * @return result
     */
    public final function getEmptySearchResult()
    {
        return array(
            'info' => array(),
            'total' => 0,
        );
    }

    /**
     * Sets the name of the field from the table of the model, used to carry on the searches
     * The field must exist in the table of the model, otherwize it will throw Exception
     * @param string $searchByFieldName - the name of the field to search in
     * @throws Exception
     */
    public final function setSearchByField(string $searchByFieldName)
    {
        $this->checkTableFields();

        if (!array_key_exists($searchByFieldName, $this->tableFields->getFields())) {
            throw new Exception("The field `$searchByFieldName` does not exist in table `{$this->tableName}`");
        }

        $this->searchByField = $searchByFieldName;
    }

    /**
     * Executes a MySQL SELECT query to a search in the search field,
     * using left match (LIKE '%var') for the provided expression.
     * Uses the Base getAll and getCount functions.
     * Returns search structured array (info, total).
     * @param string $phrase - the phrase to search for
     * @param int $limit - the limit override
     * @param string $additional - the addition to the WHERE clause (if any)
     * @param string $orderBy - the order by override
     * @return array
     */
    public function searchLeft(string $phrase, int $limit = null, string $additional = null, string $orderBy = null)
    {
        return $this->search(
            $this->buildSearchQueryWhereClause($phrase, SEARCH_TYPE_LEFT, $additional),
            $limit,
            $orderBy
        );
    }

    /**
     * Executes a MySQL SELECT query to a search in the search field,
     * using right match (LIKE 'var%') for the provided expression.
     * Uses the Base getAll and getCount functions.
     * Returns search structured array (info, total).
     * @param string $phrase - the phrase to search for
     * @param int $limit - the limit override
     * @param string $additional - the addition to the WHERE clause (if any)
     * @param string $orderBy - the order by override
     * @return array
     */
    public function searchRight(string $phrase, int $limit = null, string $additional = null, string $orderBy = null)
    {
        return $this->search(
            $this->buildSearchQueryWhereClause($phrase, SEARCH_TYPE_RIGHT, $additional),
            $limit,
            $orderBy
        );
    }

    /**
     * Executes a MySQL SELECT query to a search in the search field,
     * using full match (LIKE '%var%') for the provided expression.
     * Uses the Base getAll and getCount functions.
     * Returns search structured array (info, total).
     * @param string $phrase - the phrase to search for
     * @param int $limit - the limit override
     * @param string $additional - the addition to the WHERE clause (if any)
     * @param string $orderBy - the order by override
     * @return array
     */
    public function searchFull(string $phrase, int $limit = null, string $additional = null, string $orderBy = null)
    {
        return $this->search(
            $this->buildSearchQueryWhereClause($phrase, SEARCH_TYPE_FULL, $additional),
            $limit,
            $orderBy
        );
    }
}
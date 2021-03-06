<?php
trait InputValidations
{
    /**
     * All validations for the field names and values for an input array for an insert/update query
     * It will return the validated and prepared input
     * @param array $input - the input for a insert/update query
     * @param bool $isUpdate - the input query is an update query
     * @return array
     */
    protected function validateAndPrepareInputArray(array $input, bool $isUpdate = null)
    {
        $this->checkTableFields();

        $this->validateInputStructure($input);

        $this->checkInputForFieldId($input);

        $this->checkForFieldsThatDoNotExistInTheTable($input);

        if (empty($isUpdate)) {
            $this->checkForMissingFields($input);
        }

        $this->checkRequiredFieldsForEmptyValues($input);

        $this->validateInputValues($input);

        return $this->parseInputExplodeFields($input);
    }

    /**
     * Validates the structure of the input array for an insert/update query
     * It will throw Error if the input is empty
     * It will throw Error if a field name is an array or object
     * It will throw Error if a field value is an array or object and the value is not an explode field
     * @param array $input - the input for a insert/update query
     * @throws Error
     */
    protected function validateInputStructure(array $input)
    {
        if (empty($input)) {
            throw new Error("Input cannot be empty");
        }

        foreach ($input as $fieldName => $value) {
            if (empty($fieldName)) {
                throw new Error("Field names cannot be empty");
            }

            if (is_array($fieldName) || is_object($fieldName)) {
                throw new Error("Field names cannot be arrays or objects");
            }

            if (is_array($value) || is_object($value)) {
                if (!in_array($fieldName, $this->explodeFields)) {
                    throw new Error("{$fieldName} must not be array or object");
                }
            }
        }
    }

    /**
     * Checks insert or update query input the field 'id'
     * Throws Error if the field is present
     * @param array $input - the input of query
     * @throws Error
     */
    protected function checkInputForFieldId(array $input)
    {
        if (isset($input['id'])) {
            throw new Error("Field `id` is not allowed", null, get_class($this));
        }
    }

    /**
     * Checks insert or update query input for missing fields
     * Throws BaseException if a missing field is found
     * @param array $input - the input of query
     * @throws BaseException
     */
    protected function checkForMissingFields(array $input)
    {
        $missingFields = array();

        foreach ($this->tableFields->getRequiredFields() as $requiredField) {
            if (!isset($input[$requiredField])) {
                $missingFields[$requiredField] = 'Is required';
            }
        }

        if (!empty($missingFields)) {
            throw new BaseException("The following fields are required", $missingFields, get_class($this));
        }
    }

    /**
     * Checks the input array for required values, which are empty
     * Throws BaseException if an empty value is found
     * @param array $input - the input of query
     * @throws BaseException
     */
    protected function checkRequiredFieldsForEmptyValues(array $input)
    {
        $emptyFields = array();

        foreach ($this->tableFields->getRequiredFields() as $requiredField) {
            if (isset($input[$requiredField])) {
                $input[$requiredField] = trim($input[$requiredField]);

                if (empty($input[$requiredField]) && $input[$requiredField] != '0') {
                    $emptyFields[$requiredField] = 'Must not be empty';
                }
            }
        }

        if (!empty($emptyFields)) {
            throw new BaseException("The following fields are not valid", $emptyFields, get_class($this));
        }
    }

    /**
     * Checks insert or update query input for fields, which do not exist in the table
     * Throws BaseException if such a field is found
     * @param array $input - the input of query
     * @throws BaseException
     */
    protected function checkForFieldsThatDoNotExistInTheTable(array $input)
    {
        $unexistingFields = array();

        foreach ($input as $field => $value) {
            if (!array_key_exists($field, $this->tableFields->getFields())) {
                $unexistingFields[] = $field;
            }
        }

        if (!empty($unexistingFields)) {
            throw new BaseException("The following fields does not exist", $unexistingFields, get_class($this));
        }
    }

    /**
     * Validates an array for an input query
     * Returns an array, field name => error text
     * @param array $input - the input for the query
     * @return array
     */
    protected function validateInputValues(array $input)
    {
        $inputErrors = array();

        foreach ($input as $fieldName => $value) {
            if (empty($value)) {
                continue;
            }

            $fieldType = $this->tableFields->getFieldType($fieldName);

            if ((mb_strstr($fieldType, 'int') || $fieldType === 'double' || $fieldType === 'float')) {
                if (!is_numeric($value)) {
                    $inputErrors[$fieldName] = "Must be a number";
                } else if (mb_strstr($fieldType, 'int') && $error = $this->validateIntegerField($fieldName, $value)) {
                    $inputErrors[$fieldName] = $error;
                }
            } else if ($fieldType === 'date' && $error = $this->validateIntegerField($value)) {
                $inputErrors[$fieldName] = $error;
            } else if ($fieldType === 'varchar') {
                if (is_array($value)) {
                    $error = $this->validateVarcharField($fieldName, implode($this->explodeDelimiter, $value));
                } else {
                    $error = $this->validateVarcharField($fieldName, $value);
                }
                if (!empty($error)) {
                    $inputErrors[$fieldName] = $error;
                }
            } else if (mb_strstr($fieldType, 'text') || mb_strstr($fieldType, 'blob')) {
                if (is_array($value)) {
                    $error = $this->validateTextOrBlobField($fieldName, implode($this->explodeDelimiter, $value));
                } else {
                    $error = $this->validateTextOrBlobField($fieldName, $value);
                }
                if (!empty($error)) {
                    $inputErrors[$fieldName] = $error;
                }
            }
            unset($fieldType);

            if (!isset($inputErrors[$fieldName]) && !empty($this->explodeFields) && in_array($fieldName, $this->explodeFields)) {
                if (is_object($value)) {
                    $value = (array)$value;
                } else if (!is_array($value)) {
                    $value = array($value);
                }

                foreach ($value as $explodeFieldKey => $explodeFieldValue) {
                    if (mb_strstr($explodeFieldValue, $this->explodeDelimiter)) {
                        $inputErrors[$fieldName] = "The following %%`{$this->explodeDelimiter}`%% is not allowed in explode field";
                    }
                }
            }
        }

        if (!empty($inputErrors)) {
            throw new BaseException("The following fields are not valid", $inputErrors, get_class($this));
        }
    }

    /**
     * Search for explode fields in the input array; if there are some, it will convert them to string,
     * using the specified explodeDelimiter of the model.
     * It will return the parsed input
     * @param array $input - the input for a insert/update query
     * @return array
     */
    protected function parseInputExplodeFields(array $input)
    {
        if (!empty($this->explodeFields)) {
            foreach ($input as $fieldName => $value) {
                if (in_array($fieldName, $this->explodeFields)) {
                    if (is_object($value)) {
                        $value = (array)$value;
                    } else if (!is_array($value)) {
                        $value = array($value);
                    }

                    $input[$fieldName] = implode($this->explodeDelimiter, $value);
                }
            }
        }
        return $input;
    }

    /**
     * Validates an integer field size for an update or insert query
     * Returns the text of the error (or empty string)
     * @param string $fieldName - the name of the field
     * @param string $value - the value of the field
     * @return string
     */
    protected function validateIntegerField(string $fieldName, string $value)
    {
        $fieldType = $this->tableFields->getFieldType($fieldName);
        $info = $this->tableFields->getFieldInfo($fieldName);

        if ($fieldType === 'int') {
            if (mb_stristr($info, 'unsigned')) {
                if (mb_strlen($value) > 10 || $value < 0 || $value > 4294967295) {
                    return 'Should be between %%0%% and %%4294967295%%';
                }
            } else {
                if (mb_strlen($value) > 11 ||$value < -2147483648 || $value > 2147483647) {
                    return 'Should be between %%-2147483648%% and %%2147483647%%';
                }
            }
        } else if ($fieldType === 'tinyint') {
            if (mb_stristr($info, 'unsigned')) {
                if (mb_strlen($value) > 3 || $value < 0 || $value > 255) {
                    return 'Should be between %%0%% and %%255%%';
                }
            } else {
                if (mb_strlen($value) > 4 || $value < -128 || $value > 127) {
                    return 'Should be between %%-128%% and %%127%%';
                }
            }
        } else if ($fieldType === 'bigint') {
            if (mb_stristr($info, 'unsigned')) {
                if (mb_strlen($value) > 20 || $value < 0 || $value > 18446744073709551615) {
                    return 'Should be between %%0%% and %%65535%%';
                }
            } else {
                if (mb_strlen($value) > 20 || $value < -9223372036854775808 || $value > 9223372036854775807) {
                    return 'Should be between %%-9223372036854775808%% and %%9223372036854775807%%';
                }
            }
        }  else if ($fieldType === 'smallint') {
            if (mb_stristr($info, 'unsigned')) {
                if (mb_strlen($value) > 6 || $value < 0 || $value > 65535) {
                    return 'Should be between %%0%% and %%65535%%';
                }
            } else {
                if (mb_strlen($value) > 6 || $value < -32768 || $value > 32767) {
                    return 'Should be between %%-32768%% and %%32767%%';
                }
            }
        } else if ($fieldType === 'mediumint') {
            if (mb_stristr($info, 'unsigned')) {
                if (mb_strlen($value) > 8 || $value < 0 || $value > 16777215) {
                    return 'Should be between %%0%% and %%16777215%%';
                }
            } else {
                if (mb_strlen($value) > 8 || $value < -8388608 || $value > 8388607) {
                    return 'Should be between %%-8388608%% and %%8388607%%';
                }
            }
        }

        return '';
    }

    /**
     * Validates a varchar field length for an update or insert query
     * Returns the text of the error (or empty string)
     * @param string $fieldName - the name of the field
     * @param string $value - the value of the field
     * @return string
     */
    protected function validateVarcharField(string $fieldName, string $value)
    {
        $info = $this->tableFields->getFieldInfo($fieldName);

        if (mb_strlen($value) > $info) {
            return "Must not be longer than %%{$info}%% symbols";
        }

        return '';
    }

    /**
     * Validates a text field length for an update or insert query
     * Returns the text of the error (or empty string)
     * @param string $fieldName - the name of the field
     * @param string $value - the value of the field
     * @return string
     */
    protected function validateTextOrBlobField(string $fieldName, string $value)
    {
        $fieldType = $this->tableFields->getFieldType($fieldName);
        $valueLength = mb_strlen($value);

        if (($fieldType === 'text' || $fieldType === 'blob') && $valueLength > 65535) {
            return 'Must not be longer than %%65535%% symbols';
        } else if (mb_strstr($fieldType, 'tiny') && $valueLength > 255) {
            return 'Must not be longer than %%255%% symbols';
        } else if (mb_strstr($fieldType, 'medium') && $valueLength > 16777215) {
            return 'Must not be longer than %%16777215%% symbols';
        } else if (mb_strstr($fieldType, 'long') && $valueLength > 4294967295) {
            return 'Must not be longer than %%4294967295%% symbols';
        }

        return '';
    }

    /**
     * Validates a date field for a correct format
     * @param string $fieldName - the name of the field
     * @return string
     */
    protected function validateDateField(string $value)
    {
        $t = explode('-', $value);
        if (count($t) < 3 || !checkdate($t[1], $t[2], $t[0])) {
            return "Must be a date with MySQL format";
        }

        return '';
    }

    /**
     * If there are any explode fields, it will validate the input array for a translate funtion
     * Makes sure all the parts are available and not empty
     * @param array $input - the input for a translate function
     * @param array $objectInfo - the object, which is going to be translated
     * @throws Error
     * @thorws BaseException
     */
    protected function validateTranslateExplodeFields(array $input, array $objectInfo)
    {
        if (!empty($this->explodeFields)) {
            foreach ($input as $fieldName => $value) {
                if (in_array($fieldName, $this->explodeFields)) {
                    if (count($objectInfo[$fieldName]) != count ($value)) {
                        throw new Error("Field `{$fieldName}` does not have the same number of parts as the original object in model `".get_class($this)."`");
                    }

                    $emptyFields = array();

                    foreach ($value as $valueKey => $valuePart) {
                        $valuePart = trim($valuePart);
                        if (empty($valuePart)) {
                            $emptyFields[$objectInfo[$fieldName][$valueKey]] = 'Translation missing';
                        }
                    }

                    if (!empty($emptyFields)) {
                        throw new BaseException(
                            "The following fields are not valid",
                            $emptyFields,
                            get_class($this)
                        );
                    }

                    unset($emptyFields);
                }
            }
        }
    }

    /**
     * Checks if the parent field in current model is set, it exists in the table of the model and the given value is valid
     * Throws Error if the field is not set, does not exist or it's not valid
     * @param int $value - the provided parent id
     * @throws Error
     */
    protected function validateParentField(int $value)
    {
        if (empty($this->parentField)) {
            throw new Error("Set a parent field in model `".get_class($this)."`");
        }

        if ($value <= 0) {
            throw new Error("Parent id should be bigger than 0 in model `".get_class($this)."`");
        }

        $this->checkTableFields();

        if (!array_key_exists($this->parentField, $this->tableFields->getFields())) {
            throw new Error("The field `{$this->parentField}` does not exist in table `{$this->tableName}`");
        }
    }

    /**
     * Validates the provided object id, it should be bigger than 0
     * Throws Error if it is not
     * @param int $objectId - the object id to check
     * @throws Error
     */
    protected function validateObjectId(int $objectId)
    {
        if ($objectId <= 0) {
            throw new Error("Object id must be bigger than 0 in model `".get_class($this)."`");
        }
    }

    /**
     * Throws Error if translationFields property is empty
     * @throws Error
     */
    protected function checkIfTranslationFieldsAreSet()
    {
        if (empty($this->translationFields)) {
            throw new Error("You cannot translate an object, without any translation fields in model ".get_class($this)."`");
        }
    }

    /**
     * Checks if the hidden field in current model is set and it exists in the table of the model
     * Throws Error if the field is not set or does not exist
     * @throws Error
     */
    protected function validateHiddenField()
    {
        if (empty($this->hiddenFieldName)) {
            throw new Error("Set the hiddenFieldName for the model `".get_class($this)."`");
        }

        if (!array_key_exists($this->hiddenFieldName, $this->tableFields->getFields())) {
            throw new Error("The field `{$this->hiddenFieldName}` does not exist in table `{$this->tableName}`");
        }
    }
}

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
        private function validateAndPrepareInputArray(array $input, bool $isUpdate = null)
        {
            $this->checkTableFields();

            $this->validateInputStructure($input);

            $this->checkInputForFieldId($input);
            
            $this->checkForFieldsThatDoNotExistInTheTable($input);
            
            if (empty($isUpdate)) {
                $this->checkForMissingFields($input);
            } else {
                $this->checkRequiredFieldsForEmptyValues($input);
            }
            
            $this->validateInputValues($input);

            return $this->parseInputExplodeFields($input);
        }

        /**
         * Validates the structure of the input array for an insert/update query
         * It will throw Exception if the input is empty
         * It will throw Exception if a field name is an array or object
         * It will throw Exception if a field value is an array or object and the value is not an explode field
         * @param array $input - the input for a insert/update query
         * @throws Exception
         */
        private function validateInputStructure(array $input)
        {
            if (empty($input)) {
                throw new Exception("Input cannot be empty");
            }

            foreach ($input as $fieldName => $value) {
                if (empty($fieldName)) {
                    throw new Exception("Field names cannot be empty");
                }

                if (is_array($fieldName) || is_object($fieldName)) {
                    throw new Exception("Field names cannot be arrays or objects");
                }

                if (is_array($value) || is_object($value)) {
                    if (!in_array($fieldName, $this->explodeFields)) {
                        throw new Exception("{$fieldName} must not be array or object");
                    }
                }
            }
        }

        /**
         * Checks insert or update query input the field 'id'
         * Throws Exception if the field is present
         * @param array $input - the input of query
         * @throws Exception
         */
        private function checkInputForFieldId(array $input)
        {
            if (isset($input['id'])) {
                throw new Exception("Field `id` is not allowed", null, get_class($this));
            }
        }

        /**
         * Checks insert or update query input for missing fields
         * Throws BaseException if a missing field is found
         * @param array $input - the input of query
         * @throws BaseException
         */
        private function checkForMissingFields(array $input)
        {
            $missingFields = array();
            
            foreach ($this->tableFields->getRequiredFields() as $requiredField) {
                if (!isset($input[$requiredField])) {
                    $missingFields[$requiredField] = 'Is required';
                } 
            }

            if (!empty($missingFields)) {
                throw new BaseException("The following fields are not valid", $missingFields, get_class($this));
            }
        }
        
        /**
         * Checks the input array for required values, which are empty
         * Throws BaseException if an empty value is found
         * @param array $input - the input of query
         * @throws BaseException
         */
        private function checkRequiredFieldsForEmptyValues(array $input)
        {
            $emptyFields = array();
            
            foreach ($this->tableFields->getRequiredFields() as $requiredField) {
                if (isset($input[$requiredField])) {
                    $input[$requiredField] = trim($input[$requiredField]);
                    
                    if (empty($input[$requiredField]) && $input[$requiredField] != '0') {
                        $emptyFields[] = $requiredField;
                    }
                }
            }

            if (!empty($emptyFields)) {
                throw new BaseException("The field/s must not be empty", $emptyFields, get_class($this));
            }
        }

        /**
         * Checks insert or update query input for fields, which do not exist in the table
         * Throws BaseException if such a field is found
         * @param array $input - the input of query
         * @throws BaseException
         */
        private function checkForFieldsThatDoNotExistInTheTable(array $input)
        {
            $unexistingFields = array();

            foreach ($input as $field => $value) {
                if (!array_key_exists($field, $this->tableFields->getFields())) {
                    $unexistingFields[] = $field;
                }
            }

            if (!empty($unexistingFields)) {
                throw new BaseException("The field/s does not exist", $unexistingFields, get_class($this));
            }
        }

        /**
         * Validates an array for an input query
         * Returns an array, field name => error text
         * @param array $input - the input for the query
         * @return array
         */
        private function validateInputValues(array $input)
        {
            $inputErrors = array();

            foreach ($input as $fieldName => $value) {
                if (empty($value)) {
                    continue;
                }
                
                $fieldType = $this->tableFields->getFieldType($fieldName);

                if ((strstr($fieldType, 'int') || $fieldType === 'double' || $fieldType === 'float')) {
                    if (!is_numeric($value)) {
                        $inputErrors[$fieldName] = "Must be a number";
                    } else if (strstr($fieldType, 'int') && $error = $this->validateIntegerField($fieldName, $value)) {
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
                } else if (strstr($fieldType, 'text') || strstr($fieldType, 'blob')) {
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
                        if (strstr($explodeFieldValue, $this->explodeDelimiter)) {
                            $inputErrors[$fieldName] = "The following %%{$this->explodeDelimiter}%% is not allowed for an explode field";
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
        private function parseInputExplodeFields(array $input)
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
         * @param int $value - the value of the field
         * @return string
         */
        private function validateIntegerField(string $fieldName, int $value)
        {
            $fieldType = $this->tableFields->getFieldType($fieldName);
            $info = $this->tableFields->getFieldInfo($fieldName);

            if ($fieldType === 'int') {
                if (stristr($info, 'unsigned')) {
                    if (strlen($value) > 10 || $value < 0 || $value > 4294967295) {
                        return 'Should be between 0 and 4294967295';
                    }
                } else {
                    if (strlen($value) > 11 ||$value < -2147483648 || $value > 2147483647) {
                        return 'Should be between -2147483648 and 2147483647';
                    }
                }
            } else if ($fieldType === 'tinyint') {
                if (stristr($info, 'unsigned')) {
                    if (strlen($value) > 3 || $value < 0 || $value > 255) {
                        return 'Should be between 0 and 255';
                    }
                } else {
                    if (strlen($value) > 4 || $value < -128 || $value > 127) {
                        return 'Should be between -128 and 127';
                    }
                }
            } else if ($fieldType === 'bigint') {
                if (stristr($info, 'unsigned')) {
                    if (strlen($value) > 20 || $value < 0 || $value > 18446744073709551615) {
                        return 'Should be between 0 and 65535';
                    }
                } else {
                    if (strlen($value) > 20 || $value < -9223372036854775808 || $value > 9223372036854775807) {
                        return 'Should be between -9223372036854775808 and 9223372036854775807';
                    }
                }
            }  else if ($fieldType === 'smallint') {
                if (stristr($info, 'unsigned')) {
                    if (strlen($value) > 6 || $value < 0 || $value > 65535) {
                        return 'Should be between 0 and 65535';
                    }
                } else {
                    if (strlen($value) > 6 || $value < -32768 || $value > 32767) {
                        return 'Should be between -32768 and 32767';
                    }
                }
            } else if ($fieldType === 'mediumint') {
                if (stristr($info, 'unsigned')) {
                    if (strlen($value) > 8 || $value < 0 || $value > 16777215) {
                        return 'Should be between 0 and 16777215';
                    }
                } else {
                    if (strlen($value) > 8 || $value < -8388608 || $value > 8388607) {
                        return 'Should be between -8388608 and 8388607';
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
        private function validateVarcharField(string $fieldName, string $value)
        {
            $info = $this->tableFields->getFieldInfo($fieldName);

            if (strlen($value) > $info) {
                return 'Must not be longer than '.$info.' sybmols';
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
        private function validateTextOrBlobField(string $fieldName, string $value)
        {
            $fieldType = $this->tableFields->getFieldType($fieldName);
            $valueLength = strlen($value);

            if (($fieldType === 'text' || $fieldType === 'blob') && $valueLength > 65535) {
                return 'Must not be longer than 65535 sybmols';
            } else if (strstr($fieldType, 'tiny') && $valueLength > 255) {
                return 'Must not be longer than 255 sybmols';
            } else if (strstr($fieldType, 'medium') && $valueLength > 16777215) {
                return 'Must not be longer than 16777215 sybmols';
            } else if (strstr($fieldType, 'long') && $valueLength > 4294967295) {
                return 'Must not be longer than 4294967295 sybmols';
            }

            return '';
        }

        /**
         * Validates a date field for a correct format
         * @param string $fieldName - the name of the field
         * @return string
         */
        private function validateDateField(string $value)
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
         * @throws Exception
         * @thorws BaseException
         */
        private function validateTranslateExplodeFields(array $input, array $objectInfo)
        {
            if (!empty($this->explodeFields)) {
                foreach ($input as $fieldName => $value) {
                    if (in_array($fieldName, $this->explodeFields)) {
                        if (count($objectInfo[$fieldName]) != count ($value)) {
                            throw new Exception("Field `{$fieldName}` does not have the same number of parts as the original object in model `".get_class($this)."`");
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
         * Checks if the parent field in current model is set and the given value is valid
         * Throws Exception if the field is not set or not valid
         * @param int $value - the provided parent id
         * @throws Exception
         */
        private function validateParentField(int $value)
        {
            if (empty($this->parentField)) {
                throw new Exception("Set a parent field in model `".get_class($this)."`");
            }

            if ($value <= 0) {
                throw new Exception("Parent id should be bigger than 0 in model `".get_class($this)."`");
            }

            $this->checkTableFields();

            if (!array_key_exists($this->parentField, $this->tableFields->getFields())) {
                throw new Exception("The field `{$this->parentField}` does not exist in table `{$this->tableName}`");
            }
        }
    }

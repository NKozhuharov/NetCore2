<?php
/**
*For private files, not directly accessible through web
*rewrite NGINX like this
*    location ^~ /files{
*        internal;
*        root /var/www/SITE_NAME/;
*    }
*and in php use like this
*    header('Content-Type: file/type');
*    header('X-Accel-Redirect: /files/folder/filename));
*/
class Files extends Base
{
    /**
     * Creates an instance of the Files Base Model
     * By default uses the 'files' table
     */
    public function __construct()
    {
        $this->tableName = 'files';
    }

    /**
     * Gets file info from the database, using the file hash
     * Throws Exception if an ivalid hash is provided or the file does not exist
     * @param string $hash - the hash of the file
     * @throws Exception
     * @return array
     */
    public function getByHash(string $hash)
    {
        global $Core;

        $hash = $Core->db->escape(trim($hash));
        if (empty($hash)) {
            throw new Exception('Invalid hash');
        }

        $result = $this->getAll(1, "`hash`='$hash'");

        if (empty($result)) {
            throw new Exception('File not found');
        }

        return current($result);
    }

    /**
     * Gets file info from the database, using the file src
     * Throws Exception if an ivalid src is provided or the file does not exist
     * @param string $src - the src of the file
     * @throws Exception
     * @return array
     */
    public function getBySrc(string $src)
    {
        global $Core;

        $src = $Core->db->escape(trim($src));

        if (empty($src)) {
            throw new Exception('Invalid file source');
        }

        $src   = explode('/', $src);
        $count = count($src);

        if ($count < 2) {
            throw new Exception('Invalid file source');
        }

        $hashedName = $src[$count - 1];
        $dir        = $src[$count - 2];

        $result = $this->getAll(1, "`dir` = '$dir' AND `hashed_name`='$hashedName'");

        if (empty($result)) {
            throw new Exception('File not found');
        }

        return current($result);
    }

    /**
     * Gets the src of a file
     * @param array $file - the file info from the database
     * @return string
     */
    public function getSrc(array $file)
    {
        global $Core;
        return $Core->filesWebDir.$file['dir'].'/'.$file['hashed_name'];
    }

    /**
     * Gets the unique hash for every file body
     * @param array $file - an uploaded file
     * @return string
     */
    private function getFileHash(array $file)
    {
        return md5_file($file['tmp_name']);
    }

    /**
     * Gets the unique hash for every file name
     * @param array $file - an uploaded file
     * @return string
     */
    private function getHashedName(array $file)
    {
        return sha1($file['name'].'the_file_name'.time().uniqid());
    }

    /**
     * Inserts an uploaded file. Checks for exceeding of maximum files per directory limit
     * Returns the id of the directory, where the file is uploaded
     * @param int $currentDirId - the current upload file directory id (default 1)
     * @param array $file - an uploaded file
     * @return int
     */
    private function insertFile(int $currentDirId, array $file)
    {
        global $Core;
        if (!is_dir($Core->filesDir.$currentDirId)) {
            mkdir($Core->filesDir.$currentDirId,0755);
        }

        $fi = new FilesystemIterator($Core->filesDir.$currentDirId, FilesystemIterator::SKIP_DOTS);
        $fCount = iterator_count($fi);
        if ($fCount < $Core->folderLimit) {
            move_uploaded_file($file['tmp_name'],$Core->filesDir.$currentDirId.'/'.$file['hashed_name']);
            return $currentDirId;
        }

        return $this->insertFile($currentDirId + 1, $file);
    }

    /**
     * Uploads an array of files
     * Throws Exception if something is wrong the inputted files
     * Throws Error if the upload fails
     * @param array $files - an array with files to upload
     * @param bool $insertInDb - if this is false, it will not insert a row in the database
     * @return array
     * @throws Exception
     * @throws Error
     */
    public function addFiles(array $files, bool $insertInDb = null)
    {
        global $Core;

        if (empty($files)) {
            throw new Exception('Provide a valid file');
        }

        $files = $Core->GlobalFunctions->reArrangeRequestFiles($files);

        foreach ($files as $file) {
            if (empty($file)) {
                throw new Exception('Provide a valid file');
            } elseif ($file['error']) {
                throw new Error($this->codeToMessage($file['error']));
            }

            $file['hash'] = $this->getFileHash($file);
            $file['hashed_name'] = $this->getHashedName($file);

            $currentDirId = $this->insertFile(1, $file);

            if (is_file($Core->filesDir.$currentDirId.'/'.$file['hashed_name'])) {
                $inputArr = array(
                    'name' => $file['name'],
                    'hashed_name' => $file['hashed_name'],
                    'type' => $file['type'],
                    'dir'  => $currentDirId,
                    'hash' => $file['hash'],
                    'size' => $file['size']
                );

                if ($insertInDb !== false) {
                    $this->insert($inputArr);
                    $return[] = $Core->siteDomain.'/'.$currentDirId.$Core->filesWebDir.$file['hash'];
                } else {
                    $return[] = $inputArr;
                }
            } else {
                throw new Exception($Core->generalErrorText);
            }
        }
        return $return;
    }

    /**
     * Converts a file upload error message code to human-readble text
     * @param string $code
     * @return string
     */
    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }

    /**
     * Gets the code of a file type
     * @param string $type - the type of the file
     * @param string $name - the name of the file
     * @return string
     */
    public function typeMap(string $type, string $name)
    {
        if ($type == 'application/octet-stream') {
            $name = strrev($name);
            $name = mb_substr($name,0,mb_strpos($name,'.'));
            $name = strrev($name);
            if ($name == 'zip' || $name == 'rar') {
                return $name;
            }
        }
        if (stristr($type, 'image')) {
            return 'pic';
        }
        if ($type == 'application/pdf') {
            return 'pdf';
        }
        if ($type == 'application/vnd.ms-excel') {
            return 'excel';
        }
        if ($type == 'application/vnd.openxmlformats-officedocument.pres') {
            return 'ppt';
        }
        if (stristr($type, 'application') || stristr($type, 'text')) {
            return 'doc';
        }
        if (stristr($type, 'video')) {
            return 'mov';
        }
        if (stristr($type, 'audio') || stristr($type, 'music')) {
            return 'music';
        }
        return 'unknown';
    }

    /**
     * Initiates a downloads of a file from the system
     * It works by providing the file src or the file id
     * Throws Exception if the file does not exist
     * @param mixed @fileTo
     * @throws Exception
     */
    public function download($fileTo)
    {
        global $Core;

        if (is_numeric($fileTo) && $file = $this->getById($fileTo)) {
            $fileTo = $Core->filesDir.$file['dir'].'/'.$file['hashed_name'];
        } else {
            $file = $this->getBySrc($fileTo);
            $fileTo = GLOBAL_PATH.mb_substr(SITE_PATH, 0, -1).$fileTo;
        }

        if (is_file($fileTo)) {
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Content-Transfer-Encoding: binary');
            header('Content-Disposition: attachment; filename="'.$file['name'].'";');
            header('Content-Type: '.$file['type']);
            header('Content-Length: ' .$file['size']);

            $chunkSize = 1024 * 1024;
            $handle = fopen($fileTo, 'rb');
            while (!feof($handle)) {
                $buffer = fread($handle, $chunkSize);
                echo $buffer;
                ob_flush();
                flush();
            }
            fclose($handle);
            die;
        } else {
            throw new Exception('File not found');
        }
    }

    /**
     * Gives a preview of a file from the system
     * It works by providing the file src or the file id
     * Throws Exception if the file does not exist
     * @param mixed @fileTo
     * @throws Exception
     */
    public function preview($fileTo)
    {
        global $Core;

        if (is_numeric($fileTo) && $file = $this->getById($fileTo)) {
            $fileTo = $Core->filesDir.$file['dir'].'/'.$file['hashed_name'];
        } else {
            $file = $this->getBySrc($fileTo);
            $fileTo = GLOBAL_PATH.mb_substr(SITE_PATH, 0, -1).$fileTo;
        }

        if (is_file($fileTo)) {
            header('Content-Disposition: filename="' . $file['name'] . '";');
            header('Content-Length: ' . $file['size']);
            header('Content-Type: ' . $file['type']);
            exit(file_get_contents($fileTo));
        } else {
            throw new Exception('File not found');
        }
    }

    /**
     * Deletes a file, both from the file system and the database
     * It works by providing the file src or the file id
     * Throws Exception if the file does not exist
     * @param mixed @fileTo
     * @throws Exception
     */
    public function deleteFile($fileTo)
    {
        global $Core;

        if (is_numeric($fileTo) && $file = $this->getById($fileTo)) {
            $fileTo = $Core->filesDir.$file['dir'].'/'.$file['hashed_name'];
        } else {
            $file = $this->getBySrc($fileTo);
            $fileTo = GLOBAL_PATH.mb_substr(SITE_PATH, 0, -1).$fileTo;
        }

        if (!is_file($fileTo)) {
            throw new Exception('File not found');
        }

        unlink($fileTo);
        $this->deleteById($file['id']);
    }

    /**
     * Changes the value of the is_private file property to the opposite one
     * Throws Exception if the file does not exist
     * @param int $fileId - the id of the file
     * @throws Exception
     */
    public function changePrivacyById(int $fileId)
    {
        $fileInfo = $this->getById($fileId);
        if (empty($fileInfo)) {
            throw new Exception('File not found');
        }

        if ($fileInfo['is_private'] == 0) {
            $this->updateById($fileId, array('is_private' => 1));
        } else {
            $this->updateById($fileId, array('is_private' => 0));
        }
    }
}

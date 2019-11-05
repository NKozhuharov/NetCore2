<?php
class GlobalFunctions
{
    /**
     * Cuts string by given number of symbols
     * Puts '...' at the end
     * @param string $text
     * @param int $symbols
     * @return string
     */
    public function cutText(string $text = null, int $symbols = 250)
    {
        $text = trim($text);

        if (mb_strlen($text) <= $symbols) {
            return trim($text);
        }

        $endSymbols = array('.', '!', '?');

        $text = mb_substr($text, 0, $symbols);

        if (mb_substr($text, -2) == '..' && mb_substr($text, -3) != '...') {
            $text = $text.'.';
        }

        $lastSymbol = mb_substr($text, -1);

        if (in_array($lastSymbol, $endSymbols)) {
            return $text;
        }

        if (ctype_alnum($lastSymbol)) {
            $text = trim(mb_substr($text, 0, mb_strrpos($text, ' ')));
            $lastSymbol = mb_substr($text, -1);
        }

        while (!preg_match_all('~[\p{L}\p{Nl}\p{Nd}]~u', $lastSymbol) && !in_array($lastSymbol, $endSymbols) && !empty($lastSymbol)) {
            $text = mb_substr($text, 0, mb_strlen($text) - 1);
            $lastSymbol = mb_substr($text, -1);
        }

        if (empty($text)) {
            return $text;
        }

        if (!in_array($lastSymbol, $endSymbols)) {
            $text = $text.'...';
        }

        return $text;
    }

    function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec($ch);
        $er = curl_error($ch);
        curl_close($ch);
        if ($er) {
            throw new Error($er);
        }

        return $r;
    }

    /**
     * Replaces all special characters and spaces in a string with the provided symbol
     * @param string $string - string by which to create the link
     * @param string $replaceWith - with what to replace the special characters
     * @return string
     */
    public function replaceSpecialCharactersInString(string $string, string $replaceWith = '-')
    {
        $string = mb_strtolower($string);
        $string = preg_replace('~\P{Xan}++~u', ' ', $string);
        $string = preg_replace("~\s+~", ' ', $string);

        return preg_replace("~\s+~", $replaceWith, trim($string));
    }

    /**
     * Creates an unique link from the provided string
     * @param string $string - the string to create a link from
     * @param string $table - the name of the table to check for duplicate links
     * @param string $field - the name of the field in the table
     * @param int $id - the id ot the object. Use when updating
     * @param int $limit - how many symbols to cut the link to
     * @return string
     */
    public function getHref(string $string, string $table, string $field, int $id = null, int $limit = 200)
    {
        global $Core;

        $url = $this->replaceSpecialCharactersInString($string);
        $url = $Core->db->escape(mb_substr($url, 0, $limit));
        $and = '';

        if ($id !== null && is_numeric($id)) {
            $and = " `id` != '$id' AND ";
        }

        $count = 0;
        while($Core->db->result("SELECT `id` FROM `{$Core->dbName}`.`$table` WHERE $and `$field` = '$url'")) {
            $count++;
            $postFix = mb_substr($url, mb_strripos($url, '-'));
            if ($count > 1) {
                $postFix = str_replace('-'.($count-1),'-'.$count, $postFix);
                $url = mb_substr($url, 0, mb_strripos($url, '-')).$postFix;
            } else {
                $url .= '-'.$count;
            }
        }

        return $url;
    }

    //create swiper
    public function swiper(array $imges, $pagination = true, $navigation = true)
    {
        ob_start();
        ?>
        <div class="swiper-container">
            <div class="swiper-wrapper">
            <?php
            if (!empty($imges)) {
                foreach ($imges as $img) {
                    ?>
                    <div class="swiper-slide">
                        <img src="<?php echo $img?>" class="swiper-lazy">
                        <div class="swiper-lazy-preloader swiper-lazy-preloader-white"></div>
                    </div>
                    <?php
                }
            }
            ?>
            </div>
            <?php if ($pagination) { ?>
                <div class="swiper-pagination"></div>
            <?php } ?>

            <?php if ($navigation) { ?>
                <!-- Add Arrows -->
                <div class="swiper-button-next swiper-button-white"></div>
                <div class="swiper-button-prev swiper-button-white"></div>
            <?php } ?>
            <!--Close button-->
            <i class="glyphicon glyphicon-remove-circle swiper-full-close"></i>
        </div>
        <?php
        $sw = ob_get_contents();
        ob_end_clean();

        return $sw;
    }

    //search array key for given value
    public function arraySearch($array, $key, $value)
    {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, $this->arraySearch($subarray, $key, $value));
            }
        }

        return $results;
    }

    /**
     * Sends email
     * Requires PHPMailer library to work!
     * @param $fromEmail - email of the sender
     * @param $fromName - name of the sender
     * @param $subject - email subject
     * @param string or array $addAddress - recipient/s email
     * @param string $body - email content
     * @param string $isHTML - should the email body be considered as HTML
     * @param string or array $attachment - file/s to be attached
     * @param bool $isAdnmin - if true shows real error if occurs
     * @throws BaseException if an error occurs
     * @throws Error if an error occurs and $isAdnmin = true
     * @return bool
     */
    public function sendEmail(
        string $fromEmail  = '',
        string $fromName   = '',
        string $subject    = '',
               $recipient  = false, //string or array
        string $body       = '',
        bool   $isHTML     = false,
               $attachment = false, //string or array
        bool   $isAdnmin     = false
    )
    {
        global $Core;

        require_once(GLOBAL_PATH.'platform/external/PHPMailer-master/PHPMailerAutoload.php');

        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = $Core->mailConfig['SMTPSecure'];
        $mail->Username   = $Core->mailConfig['Username'];
        $mail->Password   = $Core->mailConfig['Password'];
        $mail->Host       = $Core->mailConfig['Host'];
        $mail->Port       = $Core->mailConfig['Port'];

        if ($isHTML) {
            $mail->isHTML(true);
        }

        if ($body) {
            $mail->Body = $body;
        }

        if (is_array($attachment)) {
            foreach ($attachment as $att) {
                $mail->AddAttachment($att);
            }
        } else {
            $mail->AddAttachment($attachment);
        }

        if (is_array($recipient)) {
            foreach ($recipient as $adr) {
                $mail->addAddress($adr);
            }
        } else {
            $mail->addAddress($recipient);
        }

        $mail->CharSet = "UTF-8";
        $mail->Subject = $subject;
        $mail->From = $fromEmail;
        $mail->FromName = $fromName;

        if (!$mail->send()) {
            if ($isAdnmin) {
                throw new Error($mail->ErrorInfo);
            } else {
                throw new BaseException($Core->generalErrorText);
            }
        }

        return true;
    }

    //pagination function
    //$resultsCount - total matches
    //$url - url for the links
    //$currentPage - current page number
    //$showFirstLast - show first and last page
    //$html - html for the pagination
    //$showFirstPage- show or hide page number in the url when current page = 1
    public function drawPagination($resultsCount, $url = false, $currentPage = false, $showFirstLast = false, $html = array(), $showFirstPage= false)
    {
        global $Core;
        //get current page from the rewrite class
        if (!$currentPage) {
            $currentPage = $Core->Rewrite->currentPage;
        }
        //invalid page number
        if ($currentPage <= 0) {
            $Core->doOrDie();
        }
        //no need of pagination
        if ($resultsCount <= $Core->itemsPerPage) {
            return false;
        }
        //default html
        if (!$html) {
            $html = array(
                'ul_class'           => 'pagination',
                'current_page_class' => 'active',
                'default' => array(
                    'html'  => '',
                    'class' => ''
                ),
                'first' => array(
                    'html'  => '<i class="fa fa-angle-double-left"></i>',
                    'class' => ''
                ),
                'prev' => array(
                    'html'  => '<i class="fa fa-angle-left"></i>',
                    'class' => ''
                ),
                'last' => array(
                    'html'  => '<i class="fa fa-angle-double-right"></i>',
                    'class' => ''
                ),
                'next' => array(
                    'html'  => '<i class="fa fa-angle-right"></i>',
                    'class' => ''
                )
            );
        }

        //default url with page numper in GET parameter(page=)
        if (!$url) {
            $url = $Core->Rewrite->url.((preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))) ? '?'.(preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))).'&page=' : '?page=');
        }

        $pagesCount = ceil($resultsCount / $Core->itemsPerPage);

        if ($Core->numberOfPagesInPagination > $pagesCount) {
            $numberOfPagesInPagination = $pagesCount;
        } else {
            $numberOfPagesInPagination = $Core->numberOfPagesInPagination;
        }

        $current = $currentPage;

        $ulClass = (isset($html['ul_class']) ? ' class="'.$html['ul_class'].'"' : '');
        $currentClass = isset($html['current_page_class']) ? ' '.$html['current_page_class'] : '';

        if (!$showFirstPage && $url == '/') {
            $firstUrl = '/';
        }elseif (!$showFirstPage && mb_substr($url, -1, 1) == '/') {
            $firstUrl = mb_substr($url, 0, -1);
        }elseif (!$showFirstPage) {
            $firstUrl = $Core->Rewrite->url.((preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))) ? '?'.(preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))) : '');
        } else {
            $firstUrl = $url.'1';
        }

        //first page
        $firstClass   = isset($html['first']['class']) && $html['first']['class'] ? ' class="'.$html['first']['class'].'"' : '';
        $firstHtml    = isset($html['first']['html']) && $html['first']['html'] ? $html['first']['html'] : '1';
        //previous page
        $prevClass    = isset($html['prev']['class']) && $html['prev']['class'] ? ' class="'.$html['prev']['class'].'"' : '';
        $prevHtml     = isset($html['prev']['html']) && $html['prev']['html'] ? $html['prev']['html'] : $current - 1;
        //next page
        $nextClass    = isset($html['next']['class']) && $html['next']['class'] ? ' class="'.$html['next']['class'].'"' : '';
        $nextHtml     = isset($html['next']['html']) && $html['next']['html'] ? $html['next']['html'] : $current + 1;
        //last page
        $lastClass    = isset($html['last']['class']) && $html['last']['class'] ? ' class="'.$html['first']['class'].'"' : '';
        $lastHtml     = isset($html['last']['html']) && $html['last']['html'] ? $html['last']['html'] : $pagesCount;
        //default page
        $defaultClass = isset($html['default']['class']) && $html['default']['class'] ? $html['default']['class'] : '';
        $defaultHtml  = isset($html['default']['html']) && $html['default']['html'] ? $html['default']['html'] : '';

        echo '<ul'.$ulClass.'>';
            if ($currentPage > 1) {
                if ($showFirstLast) {
                    echo('<li'.$firstClass.'><a title="Page 1" href="'.$firstUrl.'">'.$firstHtml.'</a></li>');
                }
                echo('<li'.$prevClass.'><a href="'.($current - 1 == 1 ? $firstUrl : $url.($current - 1)).'" title="Page '.($current-1).'">'.$prevHtml.'</a></li>');
            }

            if ($Core->numberOfPagesInPagination % 2 == 0)
                $OddOrEven = 0;
            else
                $OddOrEven = 1;

            $more = 0;

            for($s = $currentPage - ceil($numberOfPagesInPagination / 2) + $OddOrEven; $s < $currentPage; $s++) {
                if ($s > 0 && $currentPage + ceil($numberOfPagesInPagination / 2) + $OddOrEven < $pagesCount + 1 + $OddOrEven) {
                    if ($s<=$pagesCount) {
                        echo (
                            '<li'.(($defaultClass || $currentPage == $s) ? ' class="'.$defaultClass.($currentPage == $s ? $currentClass : '').'"' : '').'>
                                <a title="Page '.$s.'" href="'.($s == 1 ? $firstUrl : $url.$s).'">'.($defaultHtml.$s).'</a>
                            </li>');
                    }
                    $more++;
                }
            }

            if ($currentPage + ceil($numberOfPagesInPagination / 2) >= $pagesCount + 1) {
                $currentPage = $pagesCount-$numberOfPagesInPagination + 1;

                for($s = $currentPage; $s<$numberOfPagesInPagination+$currentPage; $s++) {
                    if ($s <= $pagesCount) {
                        echo(
                            '<li'.(($defaultClass || $current == $s) ? ' class="'.$defaultClass.($current == $s ? $currentClass : '').'"' : '').'>
                            <a title="Page '.$s.'" href="'.($s == 1 ? $firstUrl : $url.$s).'">'.($defaultHtml.$s).'</a>
                            </li>');
                    }
                }
            } else {
                for($s = $currentPage; $s < $currentPage + $numberOfPagesInPagination - $more; $s++) {
                    if ($s <= $pagesCount) {
                        echo(
                            '<li'.(($defaultClass || $currentPage == $s) ? ' class="'.$defaultClass.($currentPage == $s ? $currentClass : '').'"' : '').'>
                            <a title="Page '.$s.'" href="'.($s == 1 ? $firstUrl : $url.$s).'">'.($defaultHtml.$s).'</a>
                            </li>');
                    }
                }
            }

            if ($current < $pagesCount) {
                echo('<li'.$nextClass.'><a href="'.$url.($current + 1).'" title="Page '.($current+1).'">'.$nextHtml.'</a></li>');
                if ($showFirstLast) {
                    echo('<li'.$lastClass.'><a title="Page '.$pagesCount.'" href="'.$url.$pagesCount.'">'.$lastHtml.'</a></li>');
                }
            }
        echo '</ul>';
    }

    //returns the content between 2 points of a string
    public function getBetween($content, $start, $end)
    {
        if (!mb_strpos($content,$start)) {
            return '';
        }

        $content=mb_substr($content,mb_strpos($content,$start)+mb_strlen($start));
        $content=mb_substr($content,0,mb_strpos($content,$end));

        return $content;
    }

    //returns the content between 2 points of a string
    public function getBetweenAll($content, $start, $end, $return = array())
    {
        while(stristr($content,$start)) {
            $startpos=mb_strpos($content,$start)+mb_strlen($start);
            $a=$content=mb_substr($content,$startpos);
            $endpos=mb_strpos($content,$end);
            $b[]=mb_substr($content,0,$endpos);
            $content=mb_substr($content,$endpos);
        }

        if (isset($b)) {
            return $b;
        }

        return array();
    }

    //strips the HTML tags all fields in an array
    public function stripTagsOfArray(&$fields, $donot = false)
    {
        foreach ($fields as $key => $value) {
            if ($donot) {
                if (is_array($donot) && in_array($key, $donot)) {
                    continue;
                }elseif (is_string($donot) && $key == $donot) {
                    continue;
                }
            }
            if (is_array($fields[$key])) {
                $this->stripTagsOfArray($fields[$key], $donot);
            } else {
                $fields[$key] = strip_tags($value);
            }
        }
    }

    //formats the given timestamp into ready for insert into mysql db date for field date/datetime; addHours parameter should be true for datetime fields
    public function formatMysqlTime($time,$addHours = false)
    {
        $time = intval($time);
        if (empty($time)) {
            return false;
        }

        if ($addHours) {
            return date('Y-m-d H:i:s',$time);
        }
        return date('Y-m-d',$time);
    }

    //formats date from mysql date/datetime field into timestamp
    public function mysqlTimeToTimestamp($time)
    {
        if (empty($time)) {
            return false;
        }

        $time = str_replace('T',' ',$time);
        $time = str_replace('Z','',$time);

        if (stristr($time,':')) {
            $time = str_replace(array(':',' '),'-',$time);
            $time = explode('-',$time);
            return mktime($time[3],$time[4],$time[5],$time[1],$time[2],$time[0]);
        }
        $time = explode('-',$time);
        return mktime(0,0,0,$time[1],$time[2],$time[0]);
    }

    //alias of mysqlTimeToTimestamp
    public function formatMysqlTimeToTimestamp($time)
    {
        return $this->mysqlTimeToTimestamp($time);
    }

    //formats seconds int seconds, minutes, hours, days and months; remove comment from $s and $mo to calculate seconds and months
    function formatSecondsToTime($time)
    {
        $time = intval($time);
        if (empty($time)) {
            return false;
        }

        //$s = $time%60;
        $m = floor(($time%3600)/60);
        $h = floor(($time%86400)/3600);
        $d = floor(($time%2592000)/86400);
        //$mo = floor($time/2592000);

        $r = '';
        if ($d > 0) {
            $r .= $d.'d ';
        }
        if ($h < 10) {
            $h = "0$h";
        }
        if ($m < 10) {
            $m = "0$m";
        }
        $r .= "$h:$m";
        return $r;
    }

    public function timeDifference($time)
    {
        global $Core;

        $time = intval($time);
        if (empty($time)) {
            return false;
        }

        $format = '';
        $suffix = '';

        $dtF = new \DateTime();
        $dtT = new \DateTime("@$time");

        $difference = $dtF->diff($dtT);

        if (!$difference) {
            return '0';
        }

        if ($difference->y) {
            if ($difference->y > 1) {
                $suffix = 's';
            }
            $format = '%y '.$Core->language->{'year'.$suffix};
        }elseif ($difference->m) {
            if ($difference->m > 1) {
                $suffix = 's';
            }
            $format = '%m '.$Core->language->{'month'.$suffix};
        }elseif ($difference->d) {
            if ($difference->d > 1) {
                $suffix = 's';
            }
            $format = '%d '.$Core->language->{'day'.$suffix};
        }elseif ($difference->h) {
            if ($difference->h > 1) {
                $suffix = 's';
            }
            $format = '%h '.$Core->language->{'hour'.$suffix};
        }elseif ($difference->i) {
            if ($difference->i > 1) {
                $suffix = 's';
            }
            $format = '%i '.$Core->language->{'minute'.$suffix};
        }elseif ($difference->s) {
            if ($difference->s > 1) {
                $suffix = 's';
            }
            $format = '%s '.$Core->language->{'second'.$suffix};
        }

        return $dtF->diff($dtT)->format($format);
    }

    //formats bytes into the powered values; $precision is used to set the number of decimal numbers
    public function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);

        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function formatNumberToReadable($number)
    {
        if ($number < 1000) {
            return $number;
        }
        if ($number < 1000000) {
            return number_format(($number / 1000),0).'K';
        }
        if ($number < 1000000000) {
            return number_format(($number / 1000000),0).'M';
        }
        return number_format(($number / 1000000000),0).'B';
    }

    public function getFolder($dir, $onlyCurrent = false)
    {
        global $Core;

        $mainFoldersCount = count(glob($dir.'*', GLOB_ONLYDIR));
        if ($mainFoldersCount == 0) {
            $current = 1;
            $folder  = $dir.$current.'/';
        }elseif (count(glob($dir.$mainFoldersCount.'/*')) >= $Core->folderLimit) {
            $current = $mainFoldersCount+1;
            $folder  = $dir.$current.'/';
        } else {
            $current = $mainFoldersCount;
            $folder  = $dir.$current.'/';
        }

        if ($onlyCurrent) {
            return $current;
        }
        return $folder;
    }

    public function checkIfProcessIsRunning($processName)
    {
        global $Core;
        if (empty($processName)) {
            throw new Error('Provide a process name');
        }

        exec("ps ax | grep '$processName'",$res);

        return count($res) > 2 ? true : false;
    }

    public function getProcessInstancesCount($processName)
    {
        global $Core;
        if (empty($processName)) {
            throw new Error('Provide a process name');
        }

        exec("ps ax | grep '$processName'",$res);

        return count($res) - 2;
    }

    public function reArrangeRequestFiles($files)
    {
        if(!is_array($files['name'])) {
            return array($files);
        }

        $file_array = array();
        $file_count = count($files['name']);
        $file_keys = array_keys($files);

        foreach ($files['name'] as $k => $v) {
            foreach ($file_keys as $key) {
                $file_array[$k][$key] = $files[$key][$k];
            }
        }

        return $file_array;
    }
}

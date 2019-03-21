<?php
class GlobalFunctions{
    function curl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec($ch);
        $er = curl_error($ch);
        curl_close($ch);
        if($er){
            throw new Error($er);
        }

        return $r;
    }

    public function getUrl($string){
        $string = trim(preg_replace('~\P{Xan}++~u', ' ', $string));
        $string = preg_replace("~\s+~", '-', strtolower($string));
        $string = substr($string, 0, 200);
        return $string;
    }

    public function getHref($title, $table, $field, $id = false){
        global $Core;

        $url = $this->getUrl($title);
        $url = $Core->db->escape(substr($url, 0, 200));
        $and = '';
        if($id && is_numeric($id)){
            $and = " `id` != '$id' AND ";
        }

        $count = 0;
        while($Core->db->result("SELECT `id` FROM `{$Core->dbName}`.`$table` WHERE $and `$field` = '$url'")){
            $count++;
            $postFix = substr($url, strripos($url, '-'));
            if($count > 1){
                $postFix = str_replace('-'.($count-1),'-'.$count, $postFix);
                $url = substr($url, 0, strripos($url, '-')).$postFix;
            }else{
                $url .= '-'.$count;
            }
        }
        return $url;
    }

    //create swiper
    public function swiper(array $imges, $pagination = true, $navigation = true){
        ob_start();
        ?>
        <div class="swiper-container">
            <div class="swiper-wrapper">
            <?php
            if(!empty($imges)){
                foreach($imges as $img){
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
            <?php if($pagination){ ?>
                <div class="swiper-pagination"></div>
            <?php } ?>

            <?php if($navigation){ ?>
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
    public function arraySearch($array, $key, $value){
        $results = array();

        if(is_array($array)){
            if(isset($array[$key]) && $array[$key] == $value){
                $results[] = $array;
            }

            foreach($array as $subarray){
                $results = array_merge($results, $this->arraySearch($subarray, $key, $value));
            }
        }

        return $results;
    }

    //sends email; requires PHPMailer library to work!
    public function sendEmail($from = false, $fromName = false, $subject = false, $addAddress = false, $body = false, $isHTML = false, $attachment = false, $isAdnmin = false){
        global $Core;
        require_once(GLOBAL_PATH.'platform/classes/PHPMailer-master/PHPMailerAutoload.php');
        $mail = new PHPMailer;
        $mail->isSMTP();

        if(isset($Core->mailConfig) && $Core->mailConfig){
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = $Core->mailConfig['SMTPSecure'];
            $mail->Username   = $Core->mailConfig['Username'];
            $mail->Password   = $Core->mailConfig['Password'];
            $mail->Host       = $Core->mailConfig['Host'];
            $mail->Port       = $Core->mailConfig['Port'];;
        }else{
            throw new Exception('Please set core $mailConfig variable');
        }

        if($isHTML){
            $mail->isHTML(true);
        }
        if($body){
            $mail->Body = $body;
        }

        if(is_array($attachment)){
            foreach($attachment as $att){
                $mail->AddAttachment($att);
            }
        }else{
            $mail->AddAttachment($attachment);
        }

        if(is_array($addAddress)){
            foreach($addAddress as $adr){
                $mail->addAddress($adr);
            }
        }else{
            $mail->addAddress($addAddress);
        }

        $mail->CharSet = "UTF-8";
        $mail->Subject = $subject;
        $mail->From = $from;
        $mail->FromName = $fromName;

        if(!$mail->send()){
            if($isAdnmin){
                $msg = $mail->ErrorInfo;
            }else{
                $msg = $Core->language->generalEmailError;
            }
            throw new Error($msg);
        }
    }

    //pagination function
    //$resultsCount - total matches
    //$url - url for the links
    //$currentPage - current page number
    //$showFirstLast - show first and last page
    //$html - html for the pagination
    //$showFirstPage- show or hide page number in the url when current page = 1
    public function drawPagination($resultsCount, $url = false, $currentPage = false, $showFirstLast = false, $html = array(), $showFirstPage= false){
        global $Core;
        //get current page from the rewrite class
        if(!$currentPage){
            $currentPage = $Core->rewrite->currentPage;
        }
        //invalid page number
        if($currentPage <= 0){
            $Core->doOrDie();
        }
        //no need of pagination
        if($resultsCount <= $Core->itemsPerPage){
            return false;
        }
        //default html
        if(!$html){
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
        if(!$url){
            $url = $Core->rewrite->URL.((preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))) ? '?'.(preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))).'&page=' : '?page=');
        }

        $pagesCount = ceil($resultsCount / $Core->itemsPerPage);

        if($Core->numberOfPagesInPagination > $pagesCount){
            $numberOfPagesInPagination = $pagesCount;
        }else{
            $numberOfPagesInPagination = $Core->numberOfPagesInPagination;
        }

        $current = $currentPage;

        $ulClass = (isset($html['ul_class']) ? ' class="'.$html['ul_class'].'"' : '');
        $currentClass = isset($html['current_page_class']) ? ' '.$html['current_page_class'] : '';

        if(!$showFirstPage && $url == '/'){
            $firstUrl = '/';
        }elseif(!$showFirstPage && substr($url, -1, 1) == '/'){
            $firstUrl = substr($url, 0, -1);
        }elseif(!$showFirstPage){
            $firstUrl = $Core->rewrite->URL.((preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))) ? '?'.(preg_replace("~(&|\?|)(page=)(\d+|)~", "", http_build_query($_GET))) : '');
        }else{
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
            if($currentPage > 1){
                if($showFirstLast){
                    echo('<li'.$firstClass.'><a title="Page 1" href="'.$firstUrl.'">'.$firstHtml.'</a></li>');
                }
                echo('<li'.$prevClass.'><a href="'.($current - 1 == 1 ? $firstUrl : $url.($current - 1)).'" title="Page '.($current-1).'">'.$prevHtml.'</a></li>');
            }

            if($Core->numberOfPagesInPagination % 2 == 0)
                $OddOrEven = 0;
            else
                $OddOrEven = 1;

            $more = 0;

            for($s = $currentPage - ceil($numberOfPagesInPagination / 2) + $OddOrEven; $s < $currentPage; $s++){
                if($s > 0 && $currentPage + ceil($numberOfPagesInPagination / 2) + $OddOrEven < $pagesCount + 1 + $OddOrEven){
                    if($s<=$pagesCount){
                        echo (
                            '<li'.(($defaultClass || $currentPage == $s) ? ' class="'.$defaultClass.($currentPage == $s ? $currentClass : '').'"' : '').'>
                                <a title="Page '.$s.'" href="'.($s == 1 ? $firstUrl : $url.$s).'">'.($defaultHtml.$s).'</a>
                            </li>');
                    }
                    $more++;
                }
            }

            if($currentPage + ceil($numberOfPagesInPagination / 2) >= $pagesCount + 1){
                $currentPage = $pagesCount-$numberOfPagesInPagination + 1;

                for($s = $currentPage; $s<$numberOfPagesInPagination+$currentPage; $s++){
                    if($s <= $pagesCount){
                        echo(
                            '<li'.(($defaultClass || $current == $s) ? ' class="'.$defaultClass.($current == $s ? $currentClass : '').'"' : '').'>
                            <a title="Page '.$s.'" href="'.($s == 1 ? $firstUrl : $url.$s).'">'.($defaultHtml.$s).'</a>
                            </li>');
                    }
                }
            }else{
                for($s = $currentPage; $s < $currentPage + $numberOfPagesInPagination - $more; $s++){
                    if($s <= $pagesCount){
                        echo(
                            '<li'.(($defaultClass || $currentPage == $s) ? ' class="'.$defaultClass.($currentPage == $s ? $currentClass : '').'"' : '').'>
                            <a title="Page '.$s.'" href="'.($s == 1 ? $firstUrl : $url.$s).'">'.($defaultHtml.$s).'</a>
                            </li>');
                    }
                }
            }

            if($current < $pagesCount){
                echo('<li'.$nextClass.'><a href="'.$url.($current + 1).'" title="Page '.($current+1).'">'.$nextHtml.'</a></li>');
                if($showFirstLast){
                    echo('<li'.$lastClass.'><a title="Page '.$pagesCount.'" href="'.$url.$pagesCount.'">'.$lastHtml.'</a></li>');
                }
            }
        echo '</ul>';
    }

    //returns the content between 2 points of a string
    public function getBetween($content, $start, $end){
        if(!strpos($content,$start))
            return '';
        $content=substr($content,strpos($content,$start)+strlen($start));
        $content=substr($content,0,strpos($content,$end));
        return $content;
    }

    //returns the content between 2 points of a string
    public function getBetweenAll($content, $start, $end,$return=array()){
        while(stristr($content,$start)){
            $startpos=strpos($content,$start)+strlen($start);
            $a=$content=substr($content,$startpos);
            $endpos=strpos($content,$end);
            $b[]=substr($content,0,$endpos);
            $content=substr($content,$endpos);
        }
        if(isset($b))
            return $b;
    }

    //strips the HTML tags all fields in an array
    public function stripAllFields(&$fields, $donot = false){
        foreach ($fields as $key => $value) {
            if($donot){
                if(is_array($donot) && in_array($key, $donot)){
                    continue;
                }elseif(is_string($donot) && $key == $donot){
                    continue;
                }
            }
            if(is_array($fields[$key])){
                $this->stripAllFields($fields[$key], $donot);
            }else{
                $fields[$key] = strip_tags($value);
            }
        }
    }

    //formats the given timestamp into ready for insert into mysql db date for field date/datetime; addHours parameter should be true for datetime fields
    public function formatMysqlTime($time,$addHours = false){
        $time = intval($time);
        if(empty($time)){
            return false;
        }

        if($addHours){
            return date('Y-m-d H:i:s',$time);
        }
        return date('Y-m-d',$time);
    }

    //formats date from mysql date/datetime field into timestamp
    public function mysqlTimeToTimestamp($time){
        if(empty($time)){
            return false;
        }

        $time = str_replace('T',' ',$time);
        $time = str_replace('Z','',$time);

        if(stristr($time,':')){
            $time = str_replace(array(':',' '),'-',$time);
            $time = explode('-',$time);
            return mktime($time[3],$time[4],$time[5],$time[1],$time[2],$time[0]);
        }
        $time = explode('-',$time);
        return mktime(0,0,0,$time[1],$time[2],$time[0]);
    }

    //alias of mysqlTimeToTimestamp
    public function formatMysqlTimeToTimestamp($time){
        return $this->mysqlTimeToTimestamp($time);
    }

    //formats seconds int seconds, minutes, hours, days and months; remove comment from $s and $mo to calculate seconds and months
    function formatSecondsToTime($time) {
        $time = intval($time);
        if(empty($time)){
            return false;
        }

        //$s = $time%60;
        $m = floor(($time%3600)/60);
        $h = floor(($time%86400)/3600);
        $d = floor(($time%2592000)/86400);
        //$mo = floor($time/2592000);

        $r = '';
        if($d > 0){
            $r .= $d.'d ';
        }
        if($h < 10){
            $h = "0$h";
        }
        if($m < 10){
            $m = "0$m";
        }
        $r .= "$h:$m";
        return $r;
    }

    public function timeDifference($time){
        global $Core;

        $time = intval($time);
        if(empty($time)){
            return false;
        }

        $format = '';
        $suffix = '';

        $dtF = new \DateTime();
        $dtT = new \DateTime("@$time");

        $difference = $dtF->diff($dtT);

        if(!$difference){
            return '0';
        }

        if($difference->y){
            if($difference->y > 1){
                $suffix = 's';
            }
            $format = '%y '.$Core->language->{'year'.$suffix};
        }elseif($difference->m){
            if($difference->m > 1){
                $suffix = 's';
            }
            $format = '%m '.$Core->language->{'month'.$suffix};
        }elseif($difference->d){
            if($difference->d > 1){
                $suffix = 's';
            }
            $format = '%d '.$Core->language->{'day'.$suffix};
        }elseif($difference->h){
            if($difference->h > 1){
                $suffix = 's';
            }
            $format = '%h '.$Core->language->{'hour'.$suffix};
        }elseif($difference->i){
            if($difference->i > 1){
                $suffix = 's';
            }
            $format = '%i '.$Core->language->{'minute'.$suffix};
        }elseif($difference->s){
            if($difference->s > 1){
                $suffix = 's';
            }
            $format = '%s '.$Core->language->{'second'.$suffix};
        }

        return $dtF->diff($dtT)->format($format);
    }

    //formats bytes into the powered values; $precision is used to set the number of decimal numbers
    public function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function formatNumberToReadable($number){
        if($number < 1000){
            return $number;
        }
        if($number < 1000000){
            return number_format(($number / 1000),0).'K';
        }
        if($number < 1000000000){
            return number_format(($number / 1000000),0).'M';
        }
        return number_format(($number / 1000000000),0).'B';
    }

    public function getFolder($dir, $onlyCurrent = false){
        global $Core;

        $mainFoldersCount = count(glob($dir.'*', GLOB_ONLYDIR));
        if($mainFoldersCount == 0){
            $current = 1;
            $folder  = $dir.$current.'/';
        }elseif(count(glob($dir.$mainFoldersCount.'/*')) >= $Core->folderLimit){
            $current = $mainFoldersCount+1;
            $folder  = $dir.$current.'/';
        }else{
            $current = $mainFoldersCount;
            $folder  = $dir.$current.'/';
        }

        if($onlyCurrent){
            return $current;
        }
        return $folder;
    }

    //validate an URL ($url) against a test string ($stringToCheck)
    public function validateSpecificLink($url, $pattern) {
        global $Core;
        $this->validateBasicUrl($url);
        if (!preg_match("{".$pattern."}",$url)) {
            throw new Error("{$url} ".$Core->language->is_not_valid);
        }
        return true;
    }

    //a basic check, if an URL is valid
    public function validateBasicUrl($url) {
        global $Core;
        if (!filter_var($url, FILTER_VALIDATE_URL))
            throw new Error("{$url} ".$Core->language->is_not_a_valid_link);

        if (!substr($url, 0, 7) == "http://" || !substr($url, 0, 8) == "https://") {
            throw new Error("{$url} ".$Core->language->is_not_a_valid_link);
        }

        return true;
    }

    public function checkIfProcessIsRunning($processName){
        global $Core;
        if(empty($processName)){
            throw new Error($Core->language->error_provide_a_process_name);
        }

        exec("ps ax | grep '$processName'",$res);

        return count($res) > 2 ? true : false;
    }

    public function getProcessInstancesCount($processName){
        global $Core;
        if(empty($processName)){
            throw new Error($Core->language->error_provide_a_process_name);
        }

        exec("ps ax | grep '$processName'",$res);

        return count($res) - 2;
    }

    //old not supporting file names
    /*public function reArrangeRequestFiles($files){
        $file_array = array();
        $file_count = count($files['name']);
        $file_keys = array_keys($files);

        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_array[$i][$key] = $files[$key][$i];
            }
        }

        return $file_array;
    }*/

    public function reArrangeRequestFiles($files){
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
?>
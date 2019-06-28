<?php
//error_reporting(-1);
//ini_set("display_errors",1);

//Important!!!
//NOTE: Don't forget to clean your urls before you insert them into the database so they don't break the sitemaps.
//And make them unique(better for SEO).
//Exaple: $url = urlencode(preg_replace('~\P{Xan}++~u', '-', $title));
    //$count = 0;
    //while($a = $Core->db->result("SELECT COUNT(`id`) FROM `SITEMAP_DATABASE`.`SITEMAP_SOURCE_TABLE` WHERE `url` = '$insertData[url]'") > 0){
    //    $count++;
    //    if($count>1){
    //        $insertData['url'] = str_replace('-'.($count-1),'-'.$count, $insertData['url']);
    //    }else{
    //        $insertData['url'] .= '-'.$count;
    //    }
    //}
    //unset($count);
//To reset everything delete SITEMAP_INDEX, SITEMAP_DESTINATION folder content and set SITEMAP_LAST_INDEX_FIELD to 0

//set server time zone just in case
//date_default_timezone_set('Europe/London');

//limit for count of urls in single sitemap not more than 50000 Google requirement
//define('SITEMAP_LIMIT', '45000');

//name of the site in the urls
//define('SITEDOMAIN', 'http://******.***');

//sitemaps destinations
    //sitemap.xml(index file) and robots.txt must be in the same dir as index.php
//define('SITEMAP_INDEX', '/var/www/***/***/sitemap.xml');
//define('ROBOTS_TXT', '/var/www/***/***/robots.txt');
    //create this folder or the code will do it for you
//define('SITEMAP_DESTINATION', '/var/www/***/***/sitemaps/');
//define('SITEMAP_NAME', '******');
//define('HTTP_PATH', '/sitemaps/');

//SITEMAP_INDEX_TABLE query parameters where we keep last indexed value
//define('SITEMAP_INDEX_TABLE', '******');
//define('SITEMAP_LAST_INDEX_FIELD', 'last_index');

//SITEMAP_SOURCE_TABLE parameters
//define('SITEMAP_SOURCE_TABLE', '******');
    //field to use for last_index int or timestamp
//define('INDEX_FIELD', 'id');
    //just add the url field or modify it with CONCAT or with what ever you wish
    //and don't forget the quotes(``, "");
    //exaple CONCAT("item/",`url`)
//define('SITEMAP_URL', 'CONCAT("******/", `url`)');
   //SITEMAP_LAST_MOD_FIELD must be timestamp
//define('SITEMAP_LAST_MOD_FIELD', 'last_mod');

//db params
    //SITEMAP_INDEX_TABLE and SITEMAP_SOURCE_TABLE must be in the same database
//define('SITEMAP_DATABASE', '******');
//define('SITEMAP_HOST', '******');
//define('SITEMAP_USER', '******');
//define('SITEMAP_PASS', '******');
//define('SITEMAP_CHARSET', 'utf8');

class Sitemap{
    private $db;

    public function __construct($SITEDOMAIN, $SITEMAP_DESTINATION, $SITEMAP_INDEX_DESTINATION, $SITEMAP_INDEX, $SITEMAP_SOURCE_TABLE_CONDITION, $ROBOTS_DESTINATION, $HTTP_PATH){
        //create connection
        global $Core;
        $this->db = $Core->db;
        $this->generate($SITEDOMAIN, $SITEMAP_DESTINATION, $SITEMAP_INDEX_DESTINATION, $SITEMAP_INDEX, $SITEMAP_SOURCE_TABLE_CONDITION, $ROBOTS_DESTINATION, $HTTP_PATH);
    }

    private function drawUrl($last_index, $i, $SITEDOMAIN, $SITEMAP_DESTINATION, $SITEMAP_INDEX_DESTINATION, $SITEMAP_INDEX, $SITEMAP_SOURCE_TABLE_CONDITION, $ROBOTS_DESTINATION, $HTTP_PATH){
        file_put_contents($SITEMAP_DESTINATION.SITEMAP_NAME.$i.".xml", "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">");
        $sql = $this->db->query("SELECT ".INDEX_FIELD.", ".SITEMAP_URL." AS `loc`, `".SITEMAP_LAST_MOD_FIELD."` AS `last_mod` FROM `".SITEMAP_SOURCE_TABLE."` WHERE `".INDEX_FIELD."` > '$last_index' ".$SITEMAP_SOURCE_TABLE_CONDITION." LIMIT ".SITEMAP_LIMIT."");
        if($sql->num_rows){
            while($row = $sql->fetch_assoc()){
                $last_mod = mb_substr($row['last_mod'], 0,mb_strpos($row['last_mod'], ' '));
                file_put_contents($SITEMAP_DESTINATION.SITEMAP_NAME.$i.".xml", "<url><loc>".$SITEDOMAIN."/".$row['loc']."</loc><lastmod>".$last_mod."</lastmod><priority>1.0</priority></url>", FILE_APPEND);
                $last_index_update = $row[INDEX_FIELD];
            }
            file_put_contents($SITEMAP_DESTINATION.SITEMAP_NAME.$i.".xml", "</urlset>", FILE_APPEND);
            return $last_index_update;
        }
    }

    private function generate($SITEDOMAIN, $SITEMAP_DESTINATION, $SITEMAP_INDEX_DESTINATION, $SITEMAP_INDEX, $SITEMAP_SOURCE_TABLE_CONDITION, $ROBOTS_DESTINATION, $HTTP_PATH){
        if(!is_dir($SITEMAP_INDEX_DESTINATION)){
            mkdir($SITEMAP_INDEX_DESTINATION, 0755, true);
        }
        if(!is_dir($SITEMAP_DESTINATION)){
            mkdir($SITEMAP_DESTINATION, 0755, true);
        }
        if(!is_dir($ROBOTS_DESTINATION)){
            mkdir($ROBOTS_DESTINATION, 0755, true);
        }
        if(!is_file($ROBOTS_DESTINATION.'robots.txt')){
            file_put_contents($ROBOTS_DESTINATION.'robots.txt', "User-agent: *\r\nAllow: /\r\nSitemap: ".$SITEDOMAIN.(mb_substr($SITEMAP_INDEX, mb_strrpos($SITEMAP_INDEX, '/'))));
        }

        if(!is_file($SITEMAP_INDEX)){
            $last_index = $this->db->query("SELECT `".SITEMAP_LAST_INDEX_FIELD."` FROM `".SITEMAP_INDEX_TABLE."` WHERE `site_domain` = '".$SITEDOMAIN."'")->fetch_row()[0];
            $count = $this->db->query("SELECT COUNT(*) FROM `".SITEMAP_SOURCE_TABLE."` WHERE `".INDEX_FIELD."` > '$last_index' ".$SITEMAP_SOURCE_TABLE_CONDITION)->fetch_row()[0];

            if($count){
                file_put_contents($SITEMAP_INDEX, "<?xml version=\"1.0\" encoding=\"UTF-8\"?><sitemapindex xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">");
                $count_sitemaps = ceil($count/SITEMAP_LIMIT);
                for($i=1;$i<=$count_sitemaps;$i++){
                    $last_index = $this->db->query("SELECT `".SITEMAP_LAST_INDEX_FIELD."` FROM `".SITEMAP_INDEX_TABLE."` WHERE `site_domain` = '".$SITEDOMAIN."'".$SITEMAP_SOURCE_TABLE_CONDITION)->fetch_row()[0];
                    file_put_contents($SITEMAP_INDEX , "<sitemap><loc>".$SITEDOMAIN.$HTTP_PATH.SITEMAP_NAME.$i.".xml</loc><lastmod>".date('Y-m-d')."</lastmod></sitemap>", FILE_APPEND);
                    $last_index_update = $this->drawUrl($last_index, $i, $SITEDOMAIN, $SITEMAP_DESTINATION, $SITEMAP_INDEX_DESTINATION, $SITEMAP_INDEX, $SITEMAP_SOURCE_TABLE_CONDITION, $ROBOTS_DESTINATION, $HTTP_PATH);
                    $this->db->query("UPDATE `".SITEMAP_INDEX_TABLE."` SET `".SITEMAP_LAST_INDEX_FIELD."` = '$last_index_update' WHERE `site_domain` = '".$SITEDOMAIN."'".$SITEMAP_SOURCE_TABLE_CONDITION);
                }
                file_put_contents($SITEMAP_INDEX, "</sitemapindex>", FILE_APPEND);
                unset($sql, $row, $last_index, $last_mod, $last_index_update);
            }
        }else{
            $sitemaps = simplexml_load_file($SITEMAP_INDEX);
            $sitemapsCount = count($sitemaps);
            $lastSitemap = simplexml_load_file($SITEMAP_DESTINATION.SITEMAP_NAME.$sitemapsCount.'.xml');
            $lastSitemapCount = count($lastSitemap);

            if($lastSitemapCount < SITEMAP_LIMIT){
                $countUrl = SITEMAP_LIMIT-$lastSitemapCount;
                $last_index = $this->db->query("SELECT `".SITEMAP_LAST_INDEX_FIELD."` FROM `".SITEMAP_INDEX_TABLE."` WHERE `site_domain` = '".$SITEDOMAIN."'")->fetch_row()[0];
                $items = $this->db->query("SELECT ".INDEX_FIELD.", ".SITEMAP_URL." AS `loc`, `".SITEMAP_LAST_MOD_FIELD."` AS `last_mod` FROM `".SITEMAP_SOURCE_TABLE."` WHERE `".INDEX_FIELD."` > '$last_index' ".$SITEMAP_SOURCE_TABLE_CONDITION." LIMIT $countUrl");

                if($items->num_rows){
                    while($row = $items->fetch_assoc()){
                        $url = $lastSitemap->addChild('url');
                        $url->addChild('loc', $SITEDOMAIN."/".$row['loc']);
                        $url->addChild('lastmod', mb_substr($row['last_mod'], 0,mb_strpos($row['last_mod'], ' ')));
                        $url->addChild('priority', '1.0');
                        $last_index_update = $row[INDEX_FIELD];
                    }
                    $lastSitemap->saveXML($SITEMAP_DESTINATION.SITEMAP_NAME.$sitemapsCount.'.xml');
                    $sitemaps->sitemap[$sitemapsCount-1]->lastmod = date('Y-m-d');
                    $sitemaps->saveXML($SITEMAP_INDEX);
                    $this->db->query("UPDATE `".SITEMAP_INDEX_TABLE."` SET `".SITEMAP_LAST_INDEX_FIELD."` = '$last_index_update' WHERE `site_domain` = '".$SITEDOMAIN."'");
                }
                unset($items, $row, $last_index_update, $last_index);
            }
            $last_index = $this->db->query("SELECT `".SITEMAP_LAST_INDEX_FIELD."` FROM `".SITEMAP_INDEX_TABLE."` WHERE `site_domain` = '".$SITEDOMAIN."'".$SITEMAP_SOURCE_TABLE_CONDITION)->fetch_row()[0];
            $count = $this->db->query("SELECT COUNT(*) FROM `".SITEMAP_SOURCE_TABLE."` WHERE `".INDEX_FIELD."` > '$last_index' ".$SITEMAP_SOURCE_TABLE_CONDITION)->fetch_row()[0];
            if($count){
                $count_sitemaps = ceil($count/SITEMAP_LIMIT);
                for($i=$sitemapsCount+1;$i<=$sitemapsCount+$count_sitemaps;$i++){
                    $last_index = $this->db->query("SELECT `".SITEMAP_LAST_INDEX_FIELD."` FROM `".SITEMAP_INDEX_TABLE."` WHERE `site_domain` = '".$SITEDOMAIN."'".$SITEMAP_SOURCE_TABLE_CONDITION)->fetch_row()[0];
                    $sitemap = $sitemaps->addChild('sitemap');
                    $sitemap->addChild('loc', $SITEDOMAIN.$HTTP_PATH.SITEMAP_NAME.$i.'.xml');
                    $sitemap->addChild('lastmod', date('Y-m-d'));
                    $last_index_update = $this->drawUrl($last_index, $i, $SITEDOMAIN, $SITEMAP_DESTINATION, $SITEMAP_INDEX_DESTINATION, $SITEMAP_INDEX, $SITEMAP_SOURCE_TABLE_CONDITION, $ROBOTS_DESTINATION, $HTTP_PATH);
                    $SITEDOMAIN = str_ireplace('https://', '', $SITEDOMAIN);
                    $SITEDOMAIN = str_ireplace('http://', '', $SITEDOMAIN);
                    $SITEDOMAIN = str_ireplace('//', '', $SITEDOMAIN);
                    $this->db->query("UPDATE `".SITEMAP_INDEX_TABLE."` SET `".SITEMAP_LAST_INDEX_FIELD."` = '$last_index_update' WHERE `site_domain` = '".$SITEDOMAIN."'".$SITEMAP_SOURCE_TABLE_CONDITION);
                    $sitemaps->saveXML($SITEMAP_INDEX);
                }
                unset($i, $last_index_update);
            }
            unset( $last_index);
        }
    }
}
?>
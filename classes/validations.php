<?php
class Validations{
    public function validateEmail($email){
        global $Core;

        if(!$email || !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            return false;
        }

        return true;
    }

    public function validatePhone($phone){
        global $Core;

        if(!is_string($phone) || !trim($phone) || !preg_match("~^(\+|\*|)(?! |-)[0-9 \-]+$~", $phone) || strlen($phone) < 3){
            return false;
        }

        return true;
    }

    public function validateName($name, $allowSpace = true){
        global $Core;

        if(!is_string($name) || !trim($name)){
            return false;
        }

        $letters = "çèñàâăêÍøšųČčСрпскиîكوردی–á’éńí日本語ếệ現代標準漢粵ú̍ōīüə中文简体繁體한국어조선말ភាសរខ្មែΕληνικάՀայերնქართულიбелауяъгмдонйїьالعبيةفسܐܬܘܪܝëïĳôóûÆØÅæåÄÖäöŠŽžÕÜõßÇÊÎŞÛşĂÂŢţÔŴŶÁÉÏŵŷÓÚẂÝÀÈÌÒÙẀỲËẄŸẃýìòùẁỳẅÿĈĜĤĴŜŬĉĝĥĵŝŭĞİğıÐÞðþŐŰőű·ŒœãÑ¡¿ÃẼĨŨỸ̃ẽĩũỹĄ́ĘĮŁŃąęįłǪā̄ēǫǭūŲżćśźůŻĆĐĎĚŇŘŤŮďěňřťĽĹŔľĺŕĀĒĢĪĶĻŅŌŖŪģķļņŗĖėẢẠẰẲẴẮẶẦẨẪẤẬẺẸỀỂỄẾỆỈỊỎỌỒỔỖỐỘƠỜỞỠỚỢỦỤƯỪỬỮỨỰỶỴđảạằẳẵắặầẩẫấậẻẹềểễỉịỏọồổỗốơờởỡớợủụưừửữứựỷỵꞗĕŏ᷄Asameঅআইঈউঊঋএঐওঔকখগঘঙচছজঝঞটঠডঢণতথদধনপফবভমযৰলৱশষসহ়্ৎংঃঁrbicﺍﺏﺕﺙﺝﺡﺥﺩﺫﺭﺯﺱﺵﺹﺽﻁﻅﻉﻍﻑﻕﻙﻝﻡﻥهـﻭﻱnԱԲԳԴԵԶԷԸԹԺԻԼԽԾԿՁՂՃՄՅՆՇՈՉՊՋՌՍՎՏՐՑՒՓՔՕՖSyܒܓܕܗܙܚܛܟܠܡܢܣܥܦܨܩܫ.ClАБВГДЕЖЗИІКЛМНОПРТУФХЦЧШЩЪЫЬЮЯGogბგდევზკმნოპჟსტფღყშჩცძწჭხჯჰkΑΒΓΔΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΧΨΩHwאבגדהוזחטיכלמנסעפצקרשתLtBDEFIJKMNOPQRTUVWXYZuㄱㄲㄴㄷㄸㄹㅁㅂㅃㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎㅏㅐㅑㅒㅓㅔㅕㅖㅗㅘㅙㅚㅛㅜㅝㅞㅟㅠㅡㅢㅣpfㄅㄆㄇㄈㄉㄊㄋㄌㄍㄎㄏㄐㄑㄒㄓㄔㄕㄖㄗㄘㄙㄚㄛㄜㄝㄞㄟㄠㄡㄢㄣㄤㄥㄦㄧㄨㄩㄭh ᚁᚂᚃᚄᚅᚆᚇᚈᚉᚊᚋᚌᚍᚎᚏᚐᚑᚒᚓᚔᚕᚖᚗᚘᚙᚚ᚛᚜ሀለሐመሠረሰቀበተኀነአከወዐዘየደገጠጰጸፀፈፐⴰⴱⵛⴷⴹⴻⴼⴳⵯⵀⵃⵉⵊⴽⵍⵎⵏⵓⵄⵖⵅⵇⵔⵕⵙⵚⵜⵟⵡⵢتثجحخذزشصضطظغقمنپچژگািীুূৃেৈোৌ‍র০১২৩৪৫৬৭৮৯अपआाइिईीउुऊूऋृॠॄऌॢॡॣऍॅऎॆएेऐैऑॉऒॊओोऔौकखगघङचछजझञटठडढणतथदधनफबभमयरलळवशषसह०१२३४५६७८९्ँंः़ऽਅਆਇਈਉਊਏਐਓਔਕਖਗਘਙਚਛਜਝਞਟਠਡਢਣਤਥਦਧਨਪਫਬਭਮਯਰਲ਼ਵਸਹઅઆઇઈઉઊઋઌઍએઐઑઓઔકખગઘઙચછજઝઞટઠડઢણતથદધનપફબભમયરલળવશષસહૠૡૢૣཀཁགངཅཆཇཉཏཐདནཔཕབམཙཚཛཝཞཟའཡརལཤསཧཨЙЁЭЎҐЄЇЉЊЏЈЃЌЅЋЂꙂꙀꙈꙊѠꙐѢꙖѤѦѨѪѬѮѰѲѴҀӁαβγδεζθμξοπρσςτυφχψωֿ字化圈あいうえおのアイウエオノ위키백과에ˋˊㄪㄫㄬកគឃងចឆជឈញដឋឌឍណតថទធនបផពហយលឡអវჱჲჳჴჵჶჷჸ—«»șțĲ‘´¨ċġħ¸‐~ǎ¯。、，…讠钅饣纟门语银饭纪问訁釒飠糹門銀飯紀問国会这来对开关时个书长万边东车爱儿國會這來對開關時個書長萬邊東車愛兒嘅咗咁嚟啲唔佢乜嘢既カタナひらがな「」しゃ　りゅょってシャリュョッテでずデガズぱぴパをは성១២៣៤៥៦៧៨៩ិីឹឺុូួើឿៀេៃៅំះោ។ίέήόύώϊΐϋΰᾶἀἁὰᾳῥёіўыэшчщювтјљњџѓќѕђћєґإىک‮ٹ‎ڈڑںےہள்துகிறனமழஅவரஉசல″®​ⵣⵥ";
        $symbols = "-.";

        if($allowSpace){
            $symbols .= ' ';
        }

        $l = mb_strlen($name, 'UTF-8');

        for($i = 0; $i < $l; $i++) {
            $char = mb_substr($name, $i, 1);

            if(!$char || (!mb_stristr($symbols, $char) && !mb_stristr($letters, $char))){
                return false;
            }
        }

        return true;
    }

    public function validateBirthDate($date){
        if(!is_string($date) || (!DateTime::createFromFormat('d-m-Y', $date) && !DateTime::createFromFormat('Y-m-d', $date))){
            return false;
        }

        //MYSQL format
        return date('Y-m-d H:i:s', strtotime($date));
    }

    public function validateEGN($egn){ //BG
        if(!is_numeric($egn) || strlen(trim($egn)) != 10 || preg_match_all( "/[0-9]/", $egn) != 10){
            return false;
        }

        return true;
    }

    public function validateEIK($eik){
        if(!is_string($eik)){
            return false;
        }

        if(!preg_match('/^(BG)?((\d{9})(\d{4})?)$/', $eik, $matches)){
            return false;
        }

        //1*а1+2*а2+3*а3+4*а4+5*а5+6*а6+7*а7+8*а8;
        $a = $matches[3];
        $a9 = $a[0]*1 + $a[1]*2 + $a[2]*3 + $a[3]*4 + $a[4]*5 + $a[5]*6 + $a[6]*7 + $a[7]*8;
        $a9 = $a9 % 11;

        if($a9 == 10){
            //3*а1+4*а2+5*а3+6*а4+7*а5+8*а6+9*а7+10*а8
            $a9 = $a[0]*3 + $a[1]*4 + $a[2]*5 + $a[3]*6 + $a[4]*7 + $a[5]*8 + $a[6]*9 + $a[7]*10;
            $a9 = $a9 % 11;
        }

        $a9 = $a9 == 10 ? 0 : $a9;

        if($a9 != $a[8]){
            return false;
        }

        if(!isset($matches[4])){
            return true;
        }

        //2*а9 + 7*а10 + 3*а11 +5*а12
        $a = $matches[4];
        $a13 = $a9*2 + $a[0]*7 + $a[1]*3 + $a[2]*5;
        $a13 = $a13 % 11;

        //4*а9+9*а10+5*а11+7*а12
        if ($a13 == 10){
            $a13 = $a9*4 + $a[0]*9 + $a[1]*5 + $a[2]*7;
            $a13 = $a13 % 11;
        }

        $a13 = $a13 == 10 ? 0 : $a13;

        return ($a13 == $a[3]);
    }

    public function isContainingPhone($string, $minLength = 6){
        if(preg_match_all("~(\+|\*|)(?! |-)[0-9 \-]{".$minLength.",}+~", $string)){
            return true;
        }

        return false;
    }

    public function isContainingEmail($string){
        if(preg_match_all("~[A-z0-9-_]+\@[A-z0-9-_]+\.[A-z0-9]+~", $string)){
            return true;
        }

        return false;
    }
}
?>
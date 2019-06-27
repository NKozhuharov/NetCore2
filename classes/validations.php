<?php
class Validations
{
    /**
     * Check if an email is valid
     * @param string $email - the inputted email
     * @return bool
     */
    public function validateEmail(string $email)
    {
        global $Core;

        if (!trim($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a phone is valid
     * @param string $phone - the inputted phone
     * @return bool
     */
    public function validatePhone(string $phone)
    {
        global $Core;

        if(!trim($phone) || !preg_match("~^(\+|\*|)(?! |-)[0-9 \-]+$~", $phone) || strlen($phone) < 3){
            return false;
        }

        return true;
    }

    /**
     * Check if a name is valid
     * @param string $name - the inputted name
     * @param string $allowedSymbols - a string of additional allowed symbols (like space)
     * @return bool
     */
    public function validateName(string $name, string $allowedSymbols = null)
    {
        global $Core;

        if (!trim($name)) {
            return false;
        }

        $letters = "çèñàâăêÍøšųČčСрпскиîكوردی–á’éńí日本語ếệ現代標準漢粵ú̍ōīüə中文简体繁體한국어조선말ភាសរខ្មែΕληνικάՀայերնქართულიбелауяъгмдонйїьالعبيةفسܐܬܘܪܝëïĳôóûÆØÅæåÄÖäöŠŽžÕÜõßÇÊÎŞÛşĂÂŢţÔŴŶÁÉÏŵŷÓÚẂÝÀÈÌÒÙẀỲËẄŸẃýìòùẁỳẅÿĈĜĤĴŜŬĉĝĥĵŝŭĞİğıÐÞðþŐŰőű·ŒœãÑ¡¿ÃẼĨŨỸ̃ẽĩũỹĄ́ĘĮŁŃąęįłǪā̄ēǫǭūŲżćśźůŻĆĐĎĚŇŘŤŮďěňřťĽĹŔľĺŕĀĒĢĪĶĻŅŌŖŪģķļņŗĖėẢẠẰẲẴẮẶẦẨẪẤẬẺẸỀỂỄẾỆỈỊỎỌỒỔỖỐỘƠỜỞỠỚỢỦỤƯỪỬỮỨỰỶỴđảạằẳẵắặầẩẫấậẻẹềểễỉịỏọồổỗốơờởỡớợủụưừửữứựỷỵꞗĕŏ᷄Asameঅআইঈউঊঋএঐওঔকখগঘঙচছজঝঞটঠডঢণতথদধনপফবভমযৰলৱশষসহ়্ৎংঃঁrbicﺍﺏﺕﺙﺝﺡﺥﺩﺫﺭﺯﺱﺵﺹﺽﻁﻅﻉﻍﻑﻕﻙﻝﻡﻥهـﻭﻱnԱԲԳԴԵԶԷԸԹԺԻԼԽԾԿՁՂՃՄՅՆՇՈՉՊՋՌՍՎՏՐՑՒՓՔՕՖSyܒܓܕܗܙܚܛܟܠܡܢܣܥܦܨܩܫ.ClАБВГДЕЖЗИІКЛМНОПРТУФХЦЧШЩЪЫЬЮЯGogბგდევზკმნოპჟსტფღყშჩცძწჭხჯჰkΑΒΓΔΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΧΨΩHwאבגדהוזחטיכלמנסעפצקרשתLtBDEFIJKMNOPQRTUVWXYZuㄱㄲㄴㄷㄸㄹㅁㅂㅃㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎㅏㅐㅑㅒㅓㅔㅕㅖㅗㅘㅙㅚㅛㅜㅝㅞㅟㅠㅡㅢㅣpfㄅㄆㄇㄈㄉㄊㄋㄌㄍㄎㄏㄐㄑㄒㄓㄔㄕㄖㄗㄘㄙㄚㄛㄜㄝㄞㄟㄠㄡㄢㄣㄤㄥㄦㄧㄨㄩㄭh ᚁᚂᚃᚄᚅᚆᚇᚈᚉᚊᚋᚌᚍᚎᚏᚐᚑᚒᚓᚔᚕᚖᚗᚘᚙᚚ᚛᚜ሀለሐመሠረሰቀበተኀነአከወዐዘየደገጠጰጸፀፈፐⴰⴱⵛⴷⴹⴻⴼⴳⵯⵀⵃⵉⵊⴽⵍⵎⵏⵓⵄⵖⵅⵇⵔⵕⵙⵚⵜⵟⵡⵢتثجحخذزشصضطظغقمنپچژگািীুূৃেৈোৌ‍র০১২৩৪৫৬৭৮৯अपआाइिईीउुऊूऋृॠॄऌॢॡॣऍॅऎॆएेऐैऑॉऒॊओोऔौकखगघङचछजझञटठडढणतथदधनफबभमयरलळवशषसह०१२३४५६७८९्ँंः़ऽਅਆਇਈਉਊਏਐਓਔਕਖਗਘਙਚਛਜਝਞਟਠਡਢਣਤਥਦਧਨਪਫਬਭਮਯਰਲ਼ਵਸਹઅઆઇઈઉઊઋઌઍએઐઑઓઔકખગઘઙચછજઝઞટઠડઢણતથદધનપફબભમયરલળવશષસહૠૡૢૣཀཁགངཅཆཇཉཏཐདནཔཕབམཙཚཛཝཞཟའཡརལཤསཧཨЙЁЭЎҐЄЇЉЊЏЈЃЌЅЋЂꙂꙀꙈꙊѠꙐѢꙖѤѦѨѪѬѮѰѲѴҀӁαβγδεζθμξοπρσςτυφχψωֿ字化圈あいうえおのアイウエオノ위키백과에ˋˊㄪㄫㄬកគឃងចឆជឈញដឋឌឍណតថទធនបផពហយលឡអវჱჲჳჴჵჶჷჸ—«»șțĲ‘´¨ċġħ¸‐~ǎ¯。、，…讠钅饣纟门语银饭纪问訁釒飠糹門銀飯紀問国会这来对开关时个书长万边东车爱儿國會這來對開關時個書長萬邊東車愛兒嘅咗咁嚟啲唔佢乜嘢既カタナひらがな「」しゃ　りゅょってシャリュョッテでずデガズぱぴパをは성១២៣៤៥៦៧៨៩ិីឹឺុូួើឿៀេៃៅំះោ។ίέήόύώϊΐϋΰᾶἀἁὰᾳῥёіўыэшчщювтјљњџѓќѕђћєґإىک‮ٹ‎ڈڑںےہள்துகிறனமழஅவரஉசல″®​ⵣⵥ";

        for ($i = 0; $i < mb_strlen($name, 'UTF-8'); $i++) {
            $char = mb_substr($name, $i, 1);

            if (!$char || (!mb_stristr($allowedSymbols, $char) && !mb_stristr($letters, $char))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a birth date is valid
     * Checks for d-m-Y and Y-m-d formats
     * @param string $date - the inputted birth date
     * @return bool
     */
    public function validateBirthDate(string $date)
    {
        if (!trim($date) || (!DateTime::createFromFormat('d-m-Y', $date) && !DateTime::createFromFormat('Y-m-d', $date))) {
            return false;
        }

        return true;
    }

    /**
     * Check if a bulgarian EGN is valid
     * @param string $egn - the inputted EGN
     * @return bool
     */
    public function validateBGEGN(string $egn)
    {
        if (!is_numeric($egn) || strlen(trim($egn)) != 10 || preg_match_all( "/[0-9]/", $egn) != 10) {
            return false;
        }

        return true;
    }

    /**
     * Check if a bulgarian EIK is valid
     * @param string $eik - the inputted EIK
     * @return bool
     */
    public function validateBGEIK(string $eik)
    {
        if (!preg_match('/^(BG)?((\d{9})(\d{4})?)$/', $eik, $matches)) {
            return false;
        }

        //1*а1+2*а2+3*а3+4*а4+5*а5+6*а6+7*а7+8*а8;
        $a = $matches[3];
        $a9 = $a[0]*1 + $a[1]*2 + $a[2]*3 + $a[3]*4 + $a[4]*5 + $a[5]*6 + $a[6]*7 + $a[7]*8;
        $a9 = $a9 % 11;

        if ($a9 == 10) {
            //3*а1+4*а2+5*а3+6*а4+7*а5+8*а6+9*а7+10*а8
            $a9 = $a[0]*3 + $a[1]*4 + $a[2]*5 + $a[3]*6 + $a[4]*7 + $a[5]*8 + $a[6]*9 + $a[7]*10;
            $a9 = $a9 % 11;
        }

        $a9 = $a9 == 10 ? 0 : $a9;

        if ($a9 != $a[8]) {
            return false;
        }

        if (!isset($matches[4])) {
            return true;
        }

        //2*а9 + 7*а10 + 3*а11 +5*а12
        $a = $matches[4];
        $a13 = $a9*2 + $a[0]*7 + $a[1]*3 + $a[2]*5;
        $a13 = $a13 % 11;

        //4*а9+9*а10+5*а11+7*а12
        if ($a13 == 10) {
            $a13 = $a9*4 + $a[0]*9 + $a[1]*5 + $a[2]*7;
            $a13 = $a13 % 11;
        }

        $a13 = $a13 == 10 ? 0 : $a13;

        return ($a13 == $a[3]);
    }

    /**
     * Check if a string contains phone
     * @param string $string - the inputted string
     * @param int $minLength - the minimum length of the phone number (default 6)
     * @return bool
     */
    public function isContainingPhone(string $string, int $minLength = null)
    {
        if (empty($minLength)) {
            $minLength = 6;
        }

        if (preg_match_all("~(\+|\*|)(?! |-)[0-9 \-]{".$minLength.",}+~", $string)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a string contains email
     * @param string $string - the inputted string
     * @return bool
     */
    public function isContainingEmail(string $string)
    {
        if (preg_match_all("~[A-z0-9-_]+\@[A-z0-9-_]+\.[A-z0-9]+~", $string)) {
            return true;
        }

        return false;
    }

    //validate an URL ($url) against a test string ($stringToCheck)
    public function validateSpecificLink($url, $pattern)
    {
        $this->validateBasicUrl($url);

        if (!preg_match("{".$pattern."}", $url)) {
            return false;
        }

        return true;
    }

    //a basic check, if an URL is valid
    public function validateBasicUrl($url)
    {
        if (
            !strstr($url, '.') ||
            !substr($url, 0, 2) == "//" ||
            !substr($url, 0, 7) == "http://" ||
            !substr($url, 0, 8) == "https://"
        )
        {
            return false;
        }

        return true;
    }
}

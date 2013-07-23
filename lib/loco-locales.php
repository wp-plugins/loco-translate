<?php
/**
 * Loco locale utilities
 */



/**
 * @return LocoLocale
 */
function loco_locale_resolve( $s ){
    if( preg_match('/([a-z]{2})(?:(?:-|_)([a-z]{2}))?$/i', $s, $r ) ){
        $lc = strtolower( $r[1] );
        $cc = isset($r[2]) ? strtoupper($r[2]) : '';
        return LocoLocale::init( $lc, $cc );
    }
}



/**
 * Locale object
 */ 
final class LocoLocale {

    private $lang;
    private $region;
    private $label;
    private $nplurals = 2;
    private $pluraleq = '(n != 1)';

    private function __construct( $lc, $cc ){
        $lc and $this->lang = $lc;
        $cc and $this->region = $cc;
        $this->label = Loco::__('Unknown language');
    }

    private function __import( $lc, $cc, array $raw ){
        $this->lang = $lc;
        $this->region = $cc;
        list( $this->label, $this->nplurals, $this->pluraleq ) = $raw;
    }
    
    public function export(){
        return get_object_vars($this);
    }
    
    public function __toString(){
        return $this->get_code().', '.$this->label;
    }
    
    public function get_code(){
        return $this->lang && $this->region ? $this->lang.'_'.$this->region : ( $this->lang ? $this->lang : 'zz' ) ;
    }
    
    public function get_name(){
        return $this->label;
    }
    
    public function equal_to( LocoLocale $locale ){
        return $this->get_code() === $locale->get_code();
    }
    
    public function preg( $delimiter = '/' ){
        $lc = preg_quote( $this->lang, $delimiter );
        $cc = preg_quote( $this->region, $delimiter );
        return $lc.'(?:[\-_]'.$cc.')?';
    }
    
    
    /**
     * @return LocoLocale
     */
    public static function init( $lc, $cc ){
        // pre-compiled locale data
        static $plurals = array ( 0 => '(n != 1)', 1 => 'n == 1 ? 0 : 1', 2 => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)', 3 => '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2', 4 => '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)', 5 => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 or n%100>=20) ? 1 : 2)', 6 => '(n==1 ? 0 : n==0 || ( n%100>1 && n%100<11) ? 1 : (n%100>10 && n%100<20 ) ? 2 : 3)', 7 => '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)', 8 => '(n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2)', 9 => '(n%100==1 ? 1 : n%100==2 ? 2 : n%100==3 || n%100==4 ? 3 : 0)', 10 => 0, 11 => '(n > 1)', 12 => '(n%10!=1 || n%100==11)', 13 => 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5', 14 => '(n>1)', 15 => '(n==1) ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3', 16 => 'n==1 ? 0 : n==2 ? 1 : n<7 ? 2 : n<11 ? 3 : 4', 17 => 'n!=0', 18 => '(n!=1)', 19 => 'n != 1', ), $locales = array ( 'en' => array ( 'GB' => array ( 0 => 'English (UK)', 1 => 2, 2 => 0, ), 'IE' => array ( 0 => 'English (Ireland)', 1 => 2, 2 => 0, ), 'US' => array ( 0 => 'English (USA)', 1 => 2, 2 => 0, ), 'CA' => array ( 0 => 'English (Canada)', 1 => 2, 2 => 0, ), 'AU' => array ( 0 => 'English (Australia)', 1 => 2, 2 => 0, ), 'NZ' => array ( 0 => 'English (New Zealand)', 1 => 2, 2 => 0, ), 'HK' => array ( 0 => 'English (Hong Kong)', 1 => 2, 2 => 0, ), 'SG' => array ( 0 => 'English (Singapore)', 1 => 2, 2 => 0, ), 'AE' => array ( 0 => 'English (United Arab Emirates)', 1 => 2, 2 => 0, ), 'ZA' => array ( 0 => 'English (South Africa)', 1 => 2, 2 => 0, ), 'IN' => array ( 0 => 'English (India)', 1 => 2, 2 => 0, ), ), 'fr' => array ( 'FR' => array ( 0 => 'French', 1 => 2, 2 => 1, ), 'CH' => array ( 0 => 'French (Switzerland)', 1 => 2, 2 => 1, ), 'BE' => array ( 0 => 'French (Belgium)', 1 => 2, 2 => 1, ), 'HT' => array ( 0 => 'French (Haiti)', 1 => 2, 2 => 1, ), 'CA' => array ( 0 => 'French (Canada)', 1 => 2, 2 => 1, ), ), 'it' => array ( 'IT' => array ( 0 => 'Italian', 1 => 2, 2 => 0, ), 'CH' => array ( 0 => 'Italian (Switzerland)', 1 => 2, 2 => 0, ), ), 'de' => array ( 'DE' => array ( 0 => 'German', 1 => 2, 2 => 0, ), 'CH' => array ( 0 => 'German (Switzerland)', 1 => 2, 2 => 0, ), 'AT' => array ( 0 => 'German (Austria)', 1 => 2, 2 => 0, ), ), 'es' => array ( 'ES' => array ( 0 => 'Spanish', 1 => 2, 2 => 0, ), 'MX' => array ( 0 => 'Spanish (Mexico)', 1 => 2, 2 => 0, ), 'AR' => array ( 0 => 'Spanish (Argentina)', 1 => 2, 2 => 0, ), 'BO' => array ( 0 => 'Spanish (Bolivia)', 1 => 2, 2 => 0, ), 'CL' => array ( 0 => 'Spanish (Chile)', 1 => 2, 2 => 0, ), 'CO' => array ( 0 => 'Spanish (Colombia)', 1 => 2, 2 => 0, ), 'CR' => array ( 0 => 'Spanish (Costa Rica)', 1 => 2, 2 => 0, ), 'CU' => array ( 0 => 'Spanish (Cuba)', 1 => 2, 2 => 0, ), 'DO' => array ( 0 => 'Spanish (Dominican Republic)', 1 => 2, 2 => 0, ), 'EC' => array ( 0 => 'Spanish (Ecuador)', 1 => 2, 2 => 0, ), 'SV' => array ( 0 => 'Spanish (El Salvador)', 1 => 2, 2 => 0, ), 'GT' => array ( 0 => 'Spanish (Guatemala)', 1 => 2, 2 => 0, ), 'HN' => array ( 0 => 'Spanish (Honduras)', 1 => 2, 2 => 0, ), 'NI' => array ( 0 => 'Spanish (Nicaragua)', 1 => 2, 2 => 0, ), 'PA' => array ( 0 => 'Spanish (Panama)', 1 => 2, 2 => 0, ), 'PY' => array ( 0 => 'Spanish (Paraguay)', 1 => 2, 2 => 0, ), 'PE' => array ( 0 => 'Spanish (Peru)', 1 => 2, 2 => 0, ), 'UY' => array ( 0 => 'Spanish (Uruguay)', 1 => 2, 2 => 0, ), 'VE' => array ( 0 => 'Spanish (Venezuela)', 1 => 2, 2 => 0, ), ), 'pt' => array ( 'PT' => array ( 0 => 'Portuguese', 1 => 2, 2 => 0, ), 'BR' => array ( 0 => 'Portuguese (Brazil)', 1 => 2, 2 => 0, ), ), 'ru' => array ( 'RU' => array ( 0 => 'Russian', 1 => 3, 2 => 2, ), 'UA' => array ( 0 => 'Russian (Ukraine)', 1 => 3, 2 => 2, ), ), 'sv' => array ( 'SE' => array ( 0 => 'Swedish', 1 => 2, 2 => 0, ), ), 'no' => array ( 'NO' => array ( 0 => 'Norwegian', 1 => 2, 2 => 0, ), ), 'da' => array ( 'DK' => array ( 0 => 'Danish', 1 => 2, 2 => 0, ), ), 'fi' => array ( 'FI' => array ( 0 => 'Finnish', 1 => 2, 2 => 1, ), ), 'nl' => array ( 'BE' => array ( 0 => 'Dutch (Belgium)', 1 => 2, 2 => 0, ), 'NL' => array ( 0 => 'Dutch', 1 => 2, 2 => 0, ), ), 'bg' => array ( 'BG' => array ( 0 => 'Bulgarian', 1 => 2, 2 => 0, ), ), 'cs' => array ( 'CZ' => array ( 0 => 'Czech', 1 => 3, 2 => 3, ), ), 'et' => array ( 'EE' => array ( 0 => 'Estonian', 1 => 2, 2 => 0, ), ), 'el' => array ( 'GR' => array ( 0 => 'Greek', 1 => 2, 2 => 0, ), 'CY' => array ( 0 => 'Greek (Cyprus)', 1 => 2, 2 => 0, ), ), 'hu' => array ( 'HU' => array ( 0 => 'Hungarian', 1 => 2, 2 => 0, ), ), 'lv' => array ( 'LV' => array ( 0 => 'Latvian', 1 => 3, 2 => 4, ), ), 'lt' => array ( 'LT' => array ( 0 => 'Lithuanian', 1 => 3, 2 => 5, ), ), 'lb' => array ( 'LU' => array ( 0 => 'Luxembourgish', 1 => 2, 2 => 0, ), ), 'mt' => array ( 'MT' => array ( 0 => 'Maltese', 1 => 4, 2 => 6, ), ), 'pl' => array ( 'PL' => array ( 0 => 'Polish', 1 => 3, 2 => 7, ), ), 'ro' => array ( 'RO' => array ( 0 => 'Romanian', 1 => 3, 2 => 8, ), ), 'sk' => array ( 'SK' => array ( 0 => 'Slovak', 1 => 3, 2 => 3, ), ), 'sl' => array ( 'SI' => array ( 0 => 'Slovenian', 1 => 4, 2 => 9, ), ), 'ht' => array ( 'HT' => array ( 0 => 'Haitian Creole', 1 => 2, 2 => 1, ), ), 'gn' => array ( 'PY' => array ( 0 => 'Guarani (Paraguay)', 1 => 2, 2 => 1, ), ), 'ja' => array ( 'JP' => array ( 0 => 'Japanese', 1 => 1, 2 => 10, ), ), 'zh' => array ( 'CN' => array ( 0 => 'Chinese', 1 => 2, 2 => 11, ), 'HK' => array ( 0 => 'Chinese (Hong Kong)', 1 => 2, 2 => 11, ), 'TW' => array ( 0 => 'Chinese (Taiwan)', 1 => 2, 2 => 11, ), ), 'af' => array ( 'ZA' => array ( 0 => 'Afrikaans (South Africa)', 1 => 2, 2 => 0, ), ), 'hr' => array ( 'HR' => array ( 0 => 'Croatian', 1 => 3, 2 => 2, ), ), 'is' => array ( 'IS' => array ( 0 => 'Icelandic', 1 => 2, 2 => 12, ), ), 'he' => array ( 'IL' => array ( 0 => 'Hebrew (Israel)', 1 => 2, 2 => 0, ), ), 'ar' => array ( 'IL' => array ( 0 => 'Arabic (Israel)', 1 => 6, 2 => 13, ), 'AE' => array ( 0 => 'Arabic (United Arab Emirates)', 1 => 6, 2 => 13, ), ), 'hi' => array ( 'IN' => array ( 0 => 'Hindi (India)', 1 => 2, 2 => 0, ), ), 'sr' => array ( 'RS' => array ( 0 => 'Serbian', 1 => 3, 2 => 2, ), ), 'tr' => array ( 'TR' => array ( 0 => 'Turkish', 1 => 2, 2 => 14, ), ), 'ko' => array ( 'KR' => array ( 0 => 'Korean', 1 => 1, 2 => 10, ), ), 'cy' => array ( 'GB' => array ( 0 => 'Welsh', 1 => 4, 2 => 15, ), ), 'ms' => array ( 'MY' => array ( 0 => 'Malay (Malaysia)', 1 => 1, 2 => 10, ), ), 'az' => array ( 'TR' => array ( 0 => 'Azerbaijani (Turkey)', 1 => 2, 2 => 0, ), ), 'bn' => array ( 'BD' => array ( 0 => 'Bengali (Bangladesh)', 1 => 2, 2 => 0, ), ), 'bs' => array ( 'BA' => array ( 0 => 'Bosnian (Bosnia & Herzegovina)', 1 => 3, 2 => 2, ), ), 'fa' => array ( 'AF' => array ( 0 => 'Persian (Afghanistan)', 1 => 2, 2 => 1, ), 'IR' => array ( 0 => 'Persian (Iran', 1 => 2, 2 => 1, ), ), 'fo' => array ( 'FO' => array ( 0 => 'Faroese (Faroe Islands)', 1 => 2, 2 => 1, ), 'DK' => array ( 0 => 'Faroese (Denmark)', 1 => 2, 2 => 1, ), ), 'ga' => array ( 'IE' => array ( 0 => 'Irish (Ireland)', 1 => 5, 2 => 16, ), ), 'gl' => array ( 'ES' => array ( 0 => 'Galician (Spain)', 1 => 2, 2 => 0, ), ), 'hy' => array ( 'AM' => array ( 0 => 'Armenian', 1 => 2, 2 => 0, ), ), 'id' => array ( 'ID' => array ( 0 => 'Indonesian', 1 => 1, 2 => 10, ), ), 'jv' => array ( 'ID' => array ( 0 => 'Javanese (Indonesia)', 1 => 2, 2 => 17, ), ), 'ka' => array ( 'GE' => array ( 0 => 'Georgian', 1 => 1, 2 => 10, ), ), 'kk' => array ( 'KZ' => array ( 0 => 'Kazakh', 1 => 1, 2 => 10, ), ), 'kn' => array ( 'IN' => array ( 0 => 'Kannada (India)', 1 => 2, 2 => 18, ), ), 'li' => array ( 'NL' => array ( 0 => 'Limburgish (Netherlands)', 1 => 2, 2 => 1, ), ), 'lo' => array ( 'LA' => array ( 0 => 'Lao (Laos)', 1 => 1, 2 => 10, ), ), 'mg' => array ( 'MG' => array ( 0 => 'Malagasy (Madagascar)', 1 => 2, 2 => 11, ), ), 'my' => array ( 'MM' => array ( 0 => 'Burmese (Myanmar)', 1 => 1, 2 => 10, ), ), 'nb' => array ( 'NO' => array ( 0 => 'Bokmål', 1 => 2, 2 => 0, ), ), 'nn' => array ( 'NO' => array ( 0 => 'Nynorsk', 1 => 2, 2 => 0, ), ), 'ne' => array ( 'NP' => array ( 0 => 'Nepali', 1 => 2, 2 => 0, ), ), 'os' => array ( 'TR' => array ( 0 => 'Ossetian (Turkey)', 1 => 2, 2 => 1, ), 'RU' => array ( 0 => 'Ossetian (Russia)', 1 => 2, 2 => 1, ), 'GE' => array ( 0 => 'Ossetian (Georgia)', 1 => 2, 2 => 1, ), ), 'pa' => array ( 'IN' => array ( 0 => 'Punjabi (India)', 1 => 2, 2 => 0, ), ), 'uk' => array ( 'UA' => array ( 0 => 'Ukrainian (Ukraine)', 1 => 3, 2 => 2, ), ), 'sa' => array ( 'IN' => array ( 0 => 'Sanskrit (India)', 1 => 2, 2 => 1, ), ), 'sd' => array ( 'PK' => array ( 0 => 'Sindhi (Pakistan)', 1 => 2, 2 => 0, ), ), 'si' => array ( 'LK' => array ( 0 => 'Sinhala (Sri Lanka)', 1 => 2, 2 => 0, ), ), 'so' => array ( 'SO' => array ( 0 => 'Somali', 1 => 2, 2 => 19, ), ), 'sq' => array ( 'AL' => array ( 0 => 'Albanian (Albania)', 1 => 2, 2 => 0, ), ), 'sc' => array ( 'IT' => array ( 0 => 'Sardinian (Italy)', 1 => 2, 2 => 1, ), ), 'su' => array ( 'ID' => array ( 0 => 'Sundanese (Indonesia)', 1 => 1, 2 => 10, ), ), 'sw' => array ( 'KE' => array ( 0 => 'Swahili (Kenya)', 1 => 2, 2 => 0, ), 'UG' => array ( 0 => 'Swahili (Uganda)', 1 => 2, 2 => 0, ), 'TZ' => array ( 0 => 'Swahili (Tanzania)', 1 => 2, 2 => 0, ), 'KM' => array ( 0 => 'Swahili (Comoros)', 1 => 2, 2 => 0, ), ), 'ta' => array ( 'IN' => array ( 0 => 'Tamil (India)', 1 => 2, 2 => 0, ), 'LK' => array ( 0 => 'Tamil (Sri Lanka)', 1 => 2, 2 => 0, ), ), 'te' => array ( 'IN' => array ( 0 => 'Telugu (India)', 1 => 2, 2 => 0, ), ), 'th' => array ( 'TW' => array ( 0 => 'Thai (Taiwan)', 1 => 1, 2 => 10, ), ), 'tg' => array ( 'TJ' => array ( 0 => 'Tajik (Tajikistan)', 1 => 2, 2 => 11, ), ), 'ug' => array ( 'CN' => array ( 0 => 'Uyghur (China)', 1 => 1, 2 => 10, ), ), 'ur' => array ( 'IN' => array ( 0 => 'Urdu (India)', 1 => 2, 2 => 0, ), 'PK' => array ( 0 => 'Urdu (Pakistan)', 1 => 2, 2 => 0, ), ), 'uz' => array ( 'UZ' => array ( 0 => 'Uzbek (Uzbekistan)', 1 => 2, 2 => 11, ), ), 'vi' => array ( 'VN' => array ( 0 => 'Vietnamese', 1 => 1, 2 => 10, ), ), ); 
        // end pre-compiled locale data
        $locale = new LocoLocale( $lc, $cc );
        if( isset($locales[$lc]) ){
            if( ! $cc ){
                $cc = key( $locales[$lc] );
            }
            if( isset($locales[$lc][$cc]) ){
                $raw = $locales[$lc][$cc];
                $raw[2] = $plurals[ $raw[1] ];
                $locale->__import( $lc, $cc, $raw );
            }
        }
        return $locale;
    }

}

 






 
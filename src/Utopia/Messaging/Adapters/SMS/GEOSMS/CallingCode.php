<?php

namespace Utopia\Messaging\Adapters\SMS\GEOSMS;

/*
 * List of country calling codes
 * @link https://en.wikipedia.org/wiki/List_of_country_calling_codes
 */

enum CallingCode: string
{
    case ALGERIA = '213';
    case ANDORRA = '376';
    case ANGOLA = '244';
    case ARGENTINA = '54';
    case ARMENIA = '374';
    case ARUBA = '297';
    case AUSTRALIA = '61';
    case AUSTRIA = '43';
    case AZERBAIJAN = '994';
    case BAHRAIN = '973';
    case BANGLADESH = '880';
    case BELARUS = '375';
    case BELGIUM = '32';
    case BELIZE = '501';
    case BENIN = '229';
    case BHUTAN = '975';
    case BOLIVIA = '591';
    case BOSNIA_HERZEGOVINA = '387';
    case BOTSWANA = '267';
    case BRAZIL = '55';
    case BRUNEI = '673';
    case BULGARIA = '359';
    case BURKINA_FASO = '226';
    case BURUNDI = '257';
    case CAMBODIA = '855';
    case CAMEROON = '237';
    case CAPE_VERDE_ISLANDS = '238';
    case CENTRAL_AFRICAN_REPUBLIC = '236';
    case CHILE = '56';
    case CHINA = '86';
    case COLOMBIA = '57';
    case COMOROS_AND_MAYOTTE = '269';
    case CONGO = '242';
    case COOK_ISLANDS = '682';
    case COSTA_RICA = '506';
    case CROATIA = '385';
    case CUBA = '53';
    case CYPRUS = '357';
    case CZECH_REPUBLIC = '420';
    case DENMARK = '45';
    case DJIBOUTI = '253';
    case ECUADOR = '593';
    case EGYPT = '20';
    case EL_SALVADOR = '503';
    case EQUATORIAL_GUINEA = '240';
    case ERITREA = '291';
    case ESTONIA = '372';
    case ETHIOPIA = '251';
    case FALKLAND_ISLANDS = '500';
    case FAROE_ISLANDS = '298';
    case FIJI = '679';
    case FINLAND = '358';
    case FRANCE = '33';
    case FRENCH_GUIANA = '594';
    case FRENCH_POLYNESIA = '689';
    case GABON = '241';
    case GAMBIA = '220';
    case GEORGIA = '995';
    case GERMANY = '49';
    case GHANA = '233';
    case GIBRALTAR = '350';
    case GREECE = '30';
    case GREENLAND = '299';
    case GUADELOUPE = '590';
    case GUAM = '671';
    case GUATEMALA = '502';
    case GUINEA = '224';
    case GUINEA_BISSAU = '245';
    case GUYANA = '592';
    case HAITI = '509';
    case HONDURAS = '504';
    case HONG_KONG = '852';
    case HUNGARY = '36';
    case ICELAND = '354';
    case INDIA = '91';
    case INDONESIA = '62';
    case IRAN = '98';
    case IRAQ = '964';
    case IRELAND = '353';
    case ISRAEL = '972';
    case ITALY = '39';
    case JAPAN = '81';
    case JORDAN = '962';
    case KENYA = '254';
    case KIRIBATI = '686';
    case NORTH_KOREA = '850';
    case SOUTH_KOREA = '82';
    case KUWAIT = '965';
    case KYRGYZSTAN = '996';
    case LAOS = '856';
    case LATVIA = '371';
    case LEBANON = '961';
    case LESOTHO = '266';
    case LIBERIA = '231';
    case LIBYA = '218';
    case LIECHTENSTEIN = '417';
    case LITHUANIA = '370';
    case LUXEMBOURG = '352';
    case MACAO = '853';
    case MACEDONIA = '389';
    case MADAGASCAR = '261';
    case MALAWI = '265';
    case MALAYSIA = '60';
    case MALDIVES = '960';
    case MALI = '223';
    case MALTA = '356';
    case MARSHALL_ISLANDS = '692';
    case MARTINIQUE = '596';
    case MAURITANIA = '222';
    case MEXICO = '52';
    case MICRONESIA = '691';
    case MOLDOVA = '373';
    case MONACO = '377';
    case MONGOLIA = '976';
    case MOROCCO = '212';
    case MOZAMBIQUE = '258';
    case MYANMAR = '95';
    case NAMIBIA = '264';
    case NAURU = '674';
    case NEPAL = '977';
    case NETHERLANDS = '31';
    case NEW_CALEDONIA = '687';
    case NEW_ZEALAND = '64';
    case NICARAGUA = '505';
    case NIGER = '227';
    case NIGERIA = '234';
    case NIUE = '683';
    case NORFOLK_ISLANDS = '672';
    case NORTHERN_MARIANA_ISLANDS = '670';
    case NORWAY = '47';
    case OMAN = '968';
    case PALAU = '680';
    case PANAMA = '507';
    case PAPUA_NEW_GUINEA = '675';
    case PARAGUAY = '595';
    case PERU = '51';
    case PHILIPPINES = '63';
    case POLAND = '48';
    case PORTUGAL = '351';
    case QATAR = '974';
    case REUNION = '262';
    case ROMANIA = '40';
    case RUSSIA_KAZAKHSTAN_UZBEKISTAN_TURKMENISTAN_AND_TAJIKSTAN = '7';
    case RWANDA = '250';
    case SAN_MARINO = '378';
    case SAO_TOME_AND_PRINCIPE = '239';
    case SAUDI_ARABIA = '966';
    case SENEGAL = '221';
    case SERBIA = '381';
    case SEYCHELLES = '248';
    case SIERRA_LEONE = '232';
    case SINGAPORE = '65';
    case SLOVAK_REPUBLIC = '421';
    case SLOVENIA = '386';
    case SOLOMON_ISLANDS = '677';
    case SOMALIA = '252';
    case SOUTH_AFRICA = '27';
    case SPAIN = '34';
    case SRI_LANKA = '94';
    case ST_HELENA = '290';
    case SUDAN = '249';
    case SURINAME = '597';
    case SWAZILAND = '268';
    case SWEDEN = '46';
    case SWITZERLAND = '41';
    case SYRIA = '963';
    case TAIWAN = '886';
    case THAILAND = '66';
    case TOGO = '228';
    case TONGA = '676';
    case TUNISIA = '216';
    case TURKEY = '90';
    case TUVALU = '688';
    case UGANDA = '256';
    case UKRAINE = '380';
    case UNITED_ARAB_EMIRATES = '971';
    case UNITED_KINGDOM = '44';
    case URUGUAY = '598';
    case NORTH_AMERICA = '1';
    case VANUATU = '678';
    case VENEZUELA = '58';
    case VIETNAM = '84';
    case WALLIS_AND_FUTUNA = '681';
    case YEMEN = '967';
    case ZAMBIA = '260';
    case ZANZIBAR = '255';
    case ZIMBABWE = '263';

    public static function fromPhoneNumber($number): ?CallingCode
    {
        $digits = str_replace(['+', ' ', '(', ')', '-'], '', $number);

        // International call prefix is usually 00 or 011
        // https://en.wikipedia.org/wiki/List_of_international_call_prefixes
        $digits = preg_replace('/^00|^011/', '', $digits);

        // Prefixes can be 3, 2, or 1 digits long
        // Attempt to match the longest first
        foreach ([3, 2, 1] as $length) {
            $codeScalar = substr($digits, 0, $length);
            $code = CallingCode::tryFrom($codeScalar);
            if ($code) {
                return $code;
            }
        }

        return null;
    }
}

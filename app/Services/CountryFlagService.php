<?php

namespace App\Services;

class CountryFlagService
{
    // ISO 3166-1 alpha-2 code => canonical English name.
    private const CODES = [
        'AD' => 'Andorra', 'AE' => 'United Arab Emirates', 'AF' => 'Afghanistan', 'AG' => 'Antigua and Barbuda',
        'AI' => 'Anguilla', 'AL' => 'Albania', 'AM' => 'Armenia', 'AO' => 'Angola', 'AR' => 'Argentina',
        'AT' => 'Austria', 'AU' => 'Australia', 'AW' => 'Aruba', 'AZ' => 'Azerbaijan', 'BA' => 'Bosnia and Herzegovina',
        'BB' => 'Barbados', 'BD' => 'Bangladesh', 'BE' => 'Belgium', 'BF' => 'Burkina Faso', 'BG' => 'Bulgaria',
        'BH' => 'Bahrain', 'BI' => 'Burundi', 'BJ' => 'Benin', 'BN' => 'Brunei', 'BO' => 'Bolivia', 'BR' => 'Brazil',
        'BS' => 'Bahamas', 'BT' => 'Bhutan', 'BW' => 'Botswana', 'BY' => 'Belarus', 'BZ' => 'Belize',
        'CA' => 'Canada', 'CD' => 'DR Congo', 'CF' => 'Central African Republic', 'CG' => 'Congo',
        'CH' => 'Switzerland', 'CI' => 'Ivory Coast', 'CL' => 'Chile', 'CM' => 'Cameroon', 'CN' => 'China',
        'CO' => 'Colombia', 'CR' => 'Costa Rica', 'CU' => 'Cuba', 'CV' => 'Cape Verde', 'CY' => 'Cyprus',
        'CZ' => 'Czech Republic', 'DE' => 'Germany', 'DJ' => 'Djibouti', 'DK' => 'Denmark', 'DM' => 'Dominica',
        'DO' => 'Dominican Republic', 'DZ' => 'Algeria', 'EC' => 'Ecuador', 'EE' => 'Estonia', 'EG' => 'Egypt',
        'ER' => 'Eritrea', 'ES' => 'Spain', 'ET' => 'Ethiopia', 'FI' => 'Finland', 'FJ' => 'Fiji', 'FR' => 'France',
        'GA' => 'Gabon', 'GB' => 'United Kingdom', 'GD' => 'Grenada', 'GE' => 'Georgia', 'GH' => 'Ghana',
        'GL' => 'Greenland', 'GM' => 'Gambia', 'GN' => 'Guinea', 'GQ' => 'Equatorial Guinea', 'GR' => 'Greece',
        'GG' => 'Guernsey', 'GT' => 'Guatemala', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HK' => 'Hong Kong',
        'HN' => 'Honduras', 'IM' => 'Isle of Man', 'JE' => 'Jersey',
        'HR' => 'Croatia', 'HT' => 'Haiti', 'HU' => 'Hungary', 'ID' => 'Indonesia', 'IE' => 'Ireland',
        'IL' => 'Israel', 'IN' => 'India', 'IQ' => 'Iraq', 'IR' => 'Iran', 'IS' => 'Iceland', 'IT' => 'Italy',
        'JM' => 'Jamaica', 'JO' => 'Jordan', 'JP' => 'Japan', 'KE' => 'Kenya', 'KG' => 'Kyrgyzstan',
        'KH' => 'Cambodia', 'KI' => 'Kiribati', 'KM' => 'Comoros', 'KN' => 'Saint Kitts and Nevis',
        'KP' => 'North Korea', 'KR' => 'South Korea', 'KW' => 'Kuwait', 'KZ' => 'Kazakhstan', 'LA' => 'Laos',
        'LB' => 'Lebanon', 'LC' => 'Saint Lucia', 'LI' => 'Liechtenstein', 'LK' => 'Sri Lanka', 'LR' => 'Liberia',
        'LS' => 'Lesotho', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'LV' => 'Latvia', 'LY' => 'Libya',
        'MA' => 'Morocco', 'MC' => 'Monaco', 'MD' => 'Moldova', 'ME' => 'Montenegro', 'MG' => 'Madagascar',
        'MH' => 'Marshall Islands', 'MK' => 'North Macedonia', 'ML' => 'Mali', 'MM' => 'Myanmar', 'MN' => 'Mongolia',
        'MR' => 'Mauritania', 'MT' => 'Malta', 'MU' => 'Mauritius', 'MV' => 'Maldives', 'MW' => 'Malawi',
        'MX' => 'Mexico', 'MY' => 'Malaysia', 'MZ' => 'Mozambique', 'NA' => 'Namibia', 'NE' => 'Niger',
        'NG' => 'Nigeria', 'NI' => 'Nicaragua', 'NL' => 'Netherlands', 'NO' => 'Norway', 'NP' => 'Nepal',
        'NR' => 'Nauru', 'NZ' => 'New Zealand', 'OM' => 'Oman', 'PA' => 'Panama', 'PE' => 'Peru',
        'PF' => 'French Polynesia', 'PG' => 'Papua New Guinea', 'PH' => 'Philippines', 'PK' => 'Pakistan',
        'PL' => 'Poland', 'PR' => 'Puerto Rico', 'PS' => 'Palestine', 'PT' => 'Portugal', 'PW' => 'Palau',
        'PY' => 'Paraguay', 'QA' => 'Qatar', 'RO' => 'Romania', 'RS' => 'Serbia', 'RU' => 'Russia',
        'RW' => 'Rwanda', 'SA' => 'Saudi Arabia', 'SB' => 'Solomon Islands', 'SC' => 'Seychelles',
        'SD' => 'Sudan', 'SE' => 'Sweden', 'SG' => 'Singapore', 'SI' => 'Slovenia', 'SK' => 'Slovakia',
        'SL' => 'Sierra Leone', 'SM' => 'San Marino', 'SN' => 'Senegal', 'SO' => 'Somalia', 'SR' => 'Suriname',
        'SS' => 'South Sudan', 'ST' => 'Sao Tome and Principe', 'SV' => 'El Salvador', 'SY' => 'Syria',
        'SZ' => 'Eswatini', 'TD' => 'Chad', 'TG' => 'Togo', 'TH' => 'Thailand', 'TJ' => 'Tajikistan',
        'TL' => 'Timor-Leste', 'TM' => 'Turkmenistan', 'TN' => 'Tunisia', 'TO' => 'Tonga', 'TR' => 'Turkey',
        'TT' => 'Trinidad and Tobago', 'TV' => 'Tuvalu', 'TW' => 'Taiwan', 'TZ' => 'Tanzania', 'UA' => 'Ukraine',
        'UG' => 'Uganda', 'US' => 'United States', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VA' => 'Vatican City',
        'VC' => 'Saint Vincent and the Grenadines', 'VE' => 'Venezuela', 'VN' => 'Vietnam', 'VU' => 'Vanuatu',
        'WS' => 'Samoa', 'YE' => 'Yemen', 'ZA' => 'South Africa', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
    ];

    // Alternate names/spellings/abbreviations users are likely to type, lowercased => ISO-2.
    private const ALIASES = [
        'holland' => 'NL', 'the netherlands' => 'NL', 'nederland' => 'NL',
        'uk' => 'GB', 'united kingdom' => 'GB', 'great britain' => 'GB', 'england' => 'GB',
        'scotland' => 'GB', 'wales' => 'GB', 'northern ireland' => 'GB', 'britain' => 'GB',
        'usa' => 'US', 'u.s.a.' => 'US', 'u.s.' => 'US', 'united states' => 'US',
        'united states of america' => 'US', 'america' => 'US',
        'uae' => 'AE', 'emirates' => 'AE',
        'south korea' => 'KR', 'korea' => 'KR', 'republic of korea' => 'KR',
        'north korea' => 'KP',
        'russia' => 'RU', 'russian federation' => 'RU',
        'czechia' => 'CZ', 'czech republic' => 'CZ',
        'ivory coast' => 'CI', "cote d'ivoire" => 'CI', 'côte d\'ivoire' => 'CI',
        'vatican' => 'VA', 'holy see' => 'VA',
        'macedonia' => 'MK',
        'swaziland' => 'SZ',
        'burma' => 'MM',
        'republic of ireland' => 'IE',
        'dr congo' => 'CD', 'democratic republic of congo' => 'CD', 'congo-kinshasa' => 'CD',
        'republic of the congo' => 'CG', 'congo-brazzaville' => 'CG',
        'hong kong sar' => 'HK',
        'south africa republic' => 'ZA',
        'chinese taipei' => 'TW',
        'st lucia' => 'LC', 'st kitts and nevis' => 'KN', 'st vincent and the grenadines' => 'VC',
        // Native-language spellings and common typos seen in real user input.
        'españa' => 'ES', 'espana' => 'ES',
        'sverige' => 'SE',
        'deutschland' => 'DE',
        'italia' => 'IT',
        'belgique' => 'BE', 'belgië' => 'BE', 'belgie' => 'BE',
        'polska' => 'PL',
        'brasil' => 'BR',
        'united sates' => 'US',
        'n.ireland' => 'GB', 'n ireland' => 'GB',
        'bosnia & herzegovina' => 'BA',
        'ksa' => 'SA',
    ];

    /**
     * Normalize free-text country input (name, alt spelling, or 2-letter code) to an ISO-2 code.
     */
    public static function toIso2(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') return null;

        $upper = strtoupper($input);
        if (strlen($upper) === 2 && isset(self::CODES[$upper])) {
            return $upper;
        }

        $lower = strtolower($input);
        if (isset(self::ALIASES[$lower])) {
            return self::ALIASES[$lower];
        }

        foreach (self::CODES as $code => $name) {
            if (strtolower($name) === $lower) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Render a free-text country value as a flag emoji, or '' if it can't be normalized.
     */
    public static function emoji(?string $input): string
    {
        $code = self::toIso2($input);
        if (!$code) return '';

        return mb_chr(0x1F1E6 + (ord($code[0]) - 65)) . mb_chr(0x1F1E6 + (ord($code[1]) - 65));
    }

    /**
     * Return a flagcdn.com SVG URL for the country, or the XCL fallback flag if unknown.
     */
    public static function imgUrl(?string $input): string
    {
        $code = self::toIso2($input);
        if (!$code) return asset('images/flags/XCL Flag.png');

        return 'https://flagcdn.com/' . strtolower($code) . '.svg';
    }
}

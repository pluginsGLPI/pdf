<?php

/**
 *  -------------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of PDF plugin for GLPI.
 *
 *  PDF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PDF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nelly Mahu-Lasson, Remi Collet, Teclib
 * @copyright Copyright (c) 2009-2022 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;

/**
 *  -------------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of PDF plugin for GLPI.
 *
 *  PDF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PDF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nelly Mahu-Lasson, Remi Collet, Teclib
 * @copyright Copyright (c) 2009-2022 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

class PluginPdfConfig extends CommonDBTM
{
    private static $_instance = null;
    public static $rightname  = 'config';

    public static function canCreate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canView(): bool
    {
        return Session::haveRight('config', READ);
    }

    public static function getTypeName($nb = 0)
    {
        return __s('PDF export', 'pdf');
    }

    public function getName($params = [])
    {
        return __s('PDF exportf', 'pdf');
    }

    public static function getIcon()
    {
        return "ti ti-file-type-pdf";
    }

    /**
     * Singleton for the unique config record
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
            if (!self::$_instance->getFromDB(1)) {
                self::$_instance->getEmpty();
            }
        }

        return self::$_instance;
    }

    public static function install(Migration $mig)
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = 'glpi_plugin_pdf_configs';
        if (!$DB->tableExists($table)) { //not installed
            $default_charset   = DBConnection::getDefaultCharset();
            $default_collation = DBConnection::getDefaultCollation();
            $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
            $query             = 'CREATE TABLE `' . $table . "`(
                     `id` int $default_key_sign NOT NULL,
                     `currency`  VARCHAR(15) NULL,
                     `add_text`  VARCHAR(255) NULL,
                     `use_branding_logo` BOOLEAN DEFAULT 0,
                     `date_mod` timestamp NULL DEFAULT NULL,
                     PRIMARY KEY  (`id`)
                   ) ENGINE=InnoDB  DEFAULT CHARSET= {$default_charset}
                 COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC";
            $DB->doQuery($query);

            $query = "INSERT INTO `$table`
                         (id, currency)
                   VALUES (1, 'EUR')";
            $DB->doQuery($query);
        } else {
            // 2.1.0
            if ($DB->fieldExists($table, 'date_mod')) {
                $mig->changeField($table, 'date_mod', 'date_mod', 'timestamp');
            }
            //3.0.0
            if (!$DB->fieldExists($table, 'add_text')) {
                $mig->addField($table, 'add_text', 'char(255) DEFAULT NULL', ['after' => 'currency']);
            }
            //4.0.0
            if (!$DB->fieldExists($table, 'use_branding_logo')) {
                $mig->addField($table, 'use_branding_logo', 'boolean DEFAULT 0', ['after' => 'add_text']);
            }
        }
    }

    public static function showConfigForm($item)
    {
        /** @var array $PDF_DEVICES */
        global $PDF_DEVICES;
        $config = self::getInstance();

        $config->showFormHeader();

        $is_branding_active = Plugin::isPluginActive('branding');
        $is_branding_compatible = false;

        if ($is_branding_active) {
            $branding_info = Plugin::getInfo('branding');
            if (isset($branding_info['version']) && version_compare($branding_info['version'], '3.0.0', '>=')) {
                $is_branding_compatible = true;
            }
        }

        $options = [];
        foreach ($PDF_DEVICES as $option => $value) {
            $options[$option] = $option . ' - ' . $value[0] . ' (' . $value[1] . ')';
        }

        TemplateRenderer::getInstance()->display(
            '@pdf/config.html.twig',
            [
                'currency_options'   => $options,
                'selected_currency'  => $config->fields['currency'],
                'is_branding_active' => $is_branding_active && $is_branding_compatible,
                'use_branding_logo'  => (!empty($config->fields['use_branding_logo']) && $is_branding_active && $is_branding_compatible),
                'add_text'           => $config->fields['add_text'],
            ],
        );

        $config->showFormButtons(['candel' => false]);

        return false;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Config') {
            return self::createTabEntry(self::getTypeName(), 0, $item::getType(), self::getIcon());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Config') {
            self::showConfigForm($item);
        }

        return true;
    }

    public static function currency()
    {
        //   name, symbole, currency, uniqUE
        return ['AED' => [__s('UAE Dirham', 'pdf'), 'د.إ', false],
            'AFN'     => [__s('Afghani', 'pdf'), 'Af'],
            'ALL'     => [__s('Lek', 'pdf'), 'L',  false],
            'AMD'     => [__s('Armenian Dram', 'pdf'), 'Դ'],
            'AOA'     => [__s('Kwanza', 'pdf'), 'Kz'],
            'ARS'     => [__s('Argentine Peso', 'pdf'), '$', false],
            'AUD'     => [__s('Australian Dollar', 'pdf'), '$', false],
            'AWG'     => [__s('Aruban Guilder/Florin', 'pdf'), 'ƒ'],
            'AZN'     => [__s('Azerbaijanian Manat', 'pdf'), 'ман'],
            'BAM'     => [__s('Konvertibilna Marka', 'pdf'), 'КМ'],
            'BBD'     => [__s('Barbados Dollar', 'pdf'), '$', false],
            'BDT'     => [__s('Taka', 'pdf'), '৳'],
            'BGN'     => [__s('Bulgarian Lev', 'pdf'), 'лв'],
            'BHD'     => [__s('Bahraini Dinar', 'pdf'), 'ب.د'],
            'BIF'     => [__s('Burundi Franc', 'pdf'), '₣', false],
            'BMD'     => [__s('Bermudian Dollar', 'pdf'), '$', false],
            'BND'     => [__s('Brunei Dollar', 'pdf'), '$', false],
            'BOB'     => [__s('Boliviano', 'pdf'), 'Bs.'],
            'BRL'     => [__s('Brazilian Real', 'pdf'), 'R$'],
            'BSD'     => [__s('Bahamian Dollar', 'pdf'), '$', false],
            'BTN'     => [__s('Ngultrum', 'pdf'), ''],
            'BWP'     => [__s('Pula', 'pdf'), 'P', false],
            'BYR'     => [__s('Belarussian Ruble', 'pdf'), 'Br.'],
            'BZD'     => [__s('Belize Dollar', 'pdf'), '$', false],
            'CAD'     => [__s('Canadian Dollar', 'pdf'), '$', false],
            'CDF'     => [__s('Congolese Franc', 'pdf'), 'F', false],
            'CHF'     => [__s('Swiss Franc', 'pdf'), 'F', false],
            'CLP'     => [__s('Chilean Peso', 'pdf'), '$', false],
            'CNY'     => [__s('Yuan', 'pdf'), '¥'],
            'COP'     => [__s('Colombian Peso', 'pdf'), '$', false],
            'CRC'     => [__s('Costa Rican Colon', 'pdf'), '₡'],
            'CUP'     => [__s('Cuban Peso', 'pdf'), '$', false],
            'CVE'     => [__s('Cape Verde Escudo', 'pdf'), '$', false],
            'CZK'     => [__s('Czech Koruna', 'pdf'), 'Kč'],
            'DJF'     => [__s('Djibouti Franc', 'pdf'), '₣', false],
            'DKK'     => [__s('Danish Krone', 'pdf'), 'kr', false],
            'DOP'     => [__s('Dominican Peso', 'pdf'), '$', false],
            'DZD'     => [__s('Algerian Dinar', 'pdf'), 'د.ج'],
            'EGP'     => [__s('Egyptian Pound', 'pdf'), '£', false],
            'ERN'     => [__s('Nakfa', 'pdf'), 'Nfk'],
            'ETB'     => [__s('Ethiopian Birr', 'pdf'), ''],
            'EUR'     => [__s('Euro', 'pdf'), '€'],
            'FJD'     => [__s('Fiji Dollar', 'pdf'), '$', false],
            'FKP'     => [__s('Falkland Islands Pound', 'pdf'), '£', false],
            'GBP'     => [__s('Pound Sterling', 'pdf'), '£', false],
            'GEL'     => [__s('Lari', 'pdf'), 'ლ'],
            'GHS'     => [__s('Cedi', 'pdf'), '₵'],
            'GIP'     => [__s('Gibraltar Pound', 'pdf'), '£', false],
            'GMD'     => [__s('Dalasi', 'pdf'), 'D'],
            'GNF'     => [__s('Guinea Franc', 'pdf'), '₣', false],
            'GTQ'     => [__s('Quetzal', 'pdf'), 'Q'],
            'HKD'     => [__s('Hong Kong Dollar', 'pdf'), '$', false],
            'HNL'     => [__s('Lempira', 'pdf'), 'L', false],
            'HRK'     => [__s('Croatian Kuna', 'pdf'), 'Kn'],
            'HTG'     => [__s('Gourde', 'pdf'), 'G'],
            'HUF'     => [__s('Forint', 'pdf'), 'Ft'],
            'IDR'     => [__s('Rupiah', 'pdf'), 'Rp'],
            'ILS'     => [__s('New Israeli Shekel', 'pdf'), '₪'],
            'INR'     => [__s('Indian Rupee', 'pdf'), '₨', false],
            'IQD'     => [__s('Iraqi Dinar', 'pdf'), 'ع.د'],
            'IRR'     => [__s('Iranian Rial', 'pdf'), '﷼'],
            'ISK'     => [__s('Iceland Krona', 'pdf'), 'Kr', false],
            'JMD'     => [__s('Jamaican Dollar', 'pdf'), '$', false],
            'JOD'     => [__s('Jordanian Dinar', 'pdf'), 'د.ا', false],
            'JPY'     => [__s('Yen', 'pdf'), '¥'],
            'KES'     => [__s('Kenyan Shilling', 'pdf'), 'Sh', false],
            'KGS'     => [__s('Som', 'pdf'), ''],
            'KHR'     => [__s('Riel', 'pdf'), '៛'],
            'KPW'     => [__s('North Korean Won', 'pdf'), '₩', false],
            'KRW'     => [__s('South Korean Won', 'pdf'),  '₩', false],
            'KWD'     => [__s('Kuwaiti Dinar', 'pdf'), 'د.ك'],
            'KYD'     => [__s('Cayman Islands Dollar', 'pdf'), '$', false],
            'KZT'     => [__s('Tenge', 'pdf'), '〒'],
            'LAK'     => [__s('Kip', 'pdf'), '₭'],
            'LBP'     => [__s('Lebanese Pound', 'pdf'), '£L'],
            'LKR'     => [__s('Sri Lanka Rupee', 'pdf'), 'Rs'],
            'LRD'     => [__s('Liberian Dollar', 'pdf'), '$', false],
            'LSL'     => [__s('Loti', 'pdf'), 'L', false],
            'LYD'     => [__s('Libyan Dinar', 'pdf'), 'ل.د'],
            'MAD'     => [__s('Moroccan Dirham', 'pdf'), 'د.م.'],
            'MDL'     => [__s('Moldavian Leu', 'pdf'), 'L', false],
            'MGA'     => [__s('Malagasy Ariary', 'pdf'), ''],
            'MKD'     => [__s('Denar', 'pdf'), 'ден'],
            'MMK'     => [__s('Kyat', 'pdf'), 'K', false],
            'MNT'     => [__s('Tugrik', 'pdf'), '₮'],
            'MOP'     => [__s('Pataca', 'pdf'), 'P', false],
            'MRO'     => [__s('Ouguiya', 'pdf'), 'UM'],
            'MUR'     => [__s('Mauritius Rupee', 'pdf'), '₨', false],
            'MVR'     => [__s('Rufiyaa', 'pdf'), 'ރ.'],
            'MWK'     => [__s('Kwacha', 'pdf'), 'MK'],
            'MXN'     => [__s('Mexican Peso', 'pdf'), '$', false],
            'MYR'     => [__s('Malaysian Ringgit', 'pdf'), 'RM'],
            'MZN'     => [__s('Metical', 'pdf'), 'MTn'],
            'NAD'     => [__s('Namibia Dollar', 'pdf'), '$', false],
            'NGN'     => [__s('Naira', 'pdf'), '₦'],
            'NIO'     => [__s('Cordoba Oro', 'pdf'), 'C$'],
            'NOK'     => [__s('Norwegian Krone', 'pdf'), 'kr', false],
            'NPR'     => [__s('Nepalese Rupee', 'pdf'), '₨', false],
            'NZD'     => [__s('New Zealand Dollar', 'pdf'), '$', false],
            'OMR'     => [__s('Rial Omani', 'pdf'), 'ر.ع.'],
            'PAB'     => [__s('Balboa', 'pdf'), 'B/.'],
            'PEN'     => [__s('Nuevo Sol', 'pdf'), 'S/.'],
            'PGK'     => [__s('Kina', 'pdf'), 'K', false],
            'PHP'     => [__s('Philippine Peso', 'pdf'), '₱'],
            'PKR'     => [__s('Pakistan Rupee', 'pdf'), '₨', false],
            'PLN'     => [__s('PZloty', 'pdf'), 'zł'],
            'PYG'     => [__s('Guarani', 'pdf'), '₲'],
            'QAR'     => [__s('Qatari Rial', 'pdf'), 'ر.ق'],
            'RON'     => [__s('Leu', 'pdf'), 'L', false],
            'RSD'     => [__s('Serbian Dinar', 'pdf'), 'din'],
            'RUB'     => [__s('Russian Ruble', 'pdf'), 'р.'],
            'RWF'     => [__s('Rwanda Franc', 'pdf'), 'F', false],
            'SAR'     => [__s('Saudi Riyal', 'pdf'), 'ر.س '],
            'SBD'     => [__s('Solomon Islands Dollar', 'pdf'), '$', false],
            'SCR'     => [__s('Seychelles Rupee', 'pdf'), '₨', false],
            'SDG'     => [__s('Sudanese', 'pdf'), '£', false],
            'SEK'     => [__s('Swedish Krona', 'pdf'), 'kr', false],
            'SGD'     => [__s('Singapore Dollar', 'pdf'), '$', false],
            'SHP'     => [__s('Saint Helena Pound', 'pdf'), '£', false],
            'SLL'     => [__s('leone', 'pdf'), 'Le'],
            'SOS'     => [__s('Somali Shilling', 'pdf'), 'Sh', false],
            'SRD'     => [__s('Suriname Dollar', 'pdf'), '$', false],
            'STD'     => [__s('Dobra', 'pdf'), 'Db'],
            'SYP'     => [__s('Syrian Pound', 'pdf'), 'ل.س'],
            'SZL'     => [__s('Lilangeni', 'pdf'), 'L', false],
            'THB'     => [__s('Baht', 'pdf'), '฿'],
            'TJS'     => [__s('Somoni', 'pdf'), 'ЅМ'],
            'TMT'     => [__s('Manat', 'pdf'), 'm'],
            'TND'     => [__s('Tunisian Dinar', 'pdf'), 'د.ت'],
            'TOP'     => [__s('Pa’anga', 'pdf'), 'T$'],
            'TRY'     => [__s('Turkish Lira', 'pdf'), '₤', false],
            'TTD'     => [__s('Trinidad and Tobago Dollar', 'pdf'), '$', false],
            'TWD'     => [__s('Taiwan Dollar', 'pdf'), '$', false],
            'TZS'     => [__s('Tanzanian Shilling', 'pdf'), 'Sh', false],
            'UAH'     => [__s('Hryvnia', 'pdf'), '₴'],
            'UGX'     => [__s('Uganda Shilling', 'pdf'), 'Sh', false],
            'USD'     => [__s('US Dollar', 'pdf'), '$', false],
            'UYU'     => [__s('Peso Uruguayo', 'pdf'), '$', false],
            'UZS'     => [__s('Uzbekistan Sum', 'pdf'), ''],
            'VEF'     => [__s('Bolivar Fuerte', 'pdf'), 'Bs F'],
            'VND'     => [__s('Dong', 'pdf'), '₫'],
            'VUV'     => [__s('Vatu', 'pdf'), 'Vt'],
            'WST'     => [__s('Tala', 'pdf'), 'T'],
            'XAF'     => [__s('CFA Franc BCEAO', 'pdf'), '₣', false],
            'XCD'     => [__s('East Caribbean Dollar', 'pdf'), '$', false],
            'XPF'     => [__s('CFP Franc', 'pdf'), '₣', false],
            'YER'     => [__s('Yemeni Rial', 'pdf'), '﷼'],
            'ZAR'     => [__s('Rand', 'pdf'), 'R'],
            'ZMW'     => [__s('Zambian Kwacha', 'pdf'), 'ZK'],
            'ZWL'     => [__s('Zimbabwe Dollar', 'pdf'), '$', false]];
    }

    public static function formatNumber($value)
    {
        /** @var array $PDF_DEVICES */
        global $PDF_DEVICES;


        $config = new Config();
        $language = '';
        foreach ($config->find(['context' => 'core',
            'name'                        => 'language']) as $row) {
            $language = $row['value'];
        }
        $user = new User();
        $user->getFromDB($_SESSION['glpiID']);
        if (!empty($user->fields['language'])) {
            $language = $user->fields['language'];
        }
        $currency = PluginPdfConfig::getInstance();

        $fmt = numfmt_create($language, NumberFormatter::CURRENCY);
        $val = numfmt_format_currency($fmt, (float) $value, $currency->getField('currency'));
        foreach ($PDF_DEVICES as $option => $value) {
            if ($currency->fields['currency'] == $option) {
                $sym = $value[1];

                return  preg_replace("/$option/", $sym, $val);
            }
        }
    }

    public static function currencyName()
    {
        /** @var array $PDF_DEVICES */
        global $PDF_DEVICES;

        $config = self::getInstance();
        foreach ($PDF_DEVICES as $option => $value) {
            if ($config->getField('currency') == $option && isset($value[2])) {
                return $value[0];
            }
        }
    }
}

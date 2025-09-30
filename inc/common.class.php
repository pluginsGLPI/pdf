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

abstract class PluginPdfCommon extends CommonGLPI
{
    protected $obj = null;
    protected $pdf = null;

    public static $rightname = 'plugin_pdf';

    /**
     * Constructor, should intialize $this->obj property
    **/
    public function __construct(?CommonGLPI $obj = null)
    {
        if ($obj instanceof CommonGLPI) {
            $this->obj = $obj;
        }
    }

    // cannot override because created in CommonGLPI as final
    /** @phpstan-ignore-next-line */
    public function addStandardTab($itemtype, array &$ong, array $options)
    {
        parent::addStandardTab($itemtype, $ong, $options);
        $dbu = new DbUtils();

        $withtemplate = 0;
        if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
        }

        if (!is_numeric($itemtype) && ($obj = $dbu->getItemForItemtype($itemtype)) && (method_exists($itemtype, 'displayTabContentForPDF'))) {
            $titles = $obj->getTabNameForItem($this->obj, $withtemplate);
            if (!is_array($titles)) {
                $titles = [1 => $titles];
            }
            foreach ($titles as $key => $val) {
                if (!empty($val)) {
                    $ong[$itemtype . '$' . $key] = $val;
                }
            }
        }
        return $this;
    }

    /**
     * Get the list of the printable tab for the object
     * Can be overriden to remove some unwanted tab
     *
     * @param $options Array of options
    **/
    public function defineAllTabsPDF($options = [])
    {
        $onglets = $this->obj->defineTabs();

        $othertabs = CommonGLPI::getOtherTabs($this->obj->getType());

        unset($onglets['empty']);

        // Add plugins TAB
        foreach ($othertabs as $typetab) {
            $this->addStandardTab($typetab, $onglets, $options);
        }

        return $onglets;
    }

    /**
     * Get Tab Name used for itemtype
     *
     * NB : Only called for existing object
     *      Must check right on what will be displayed + template
     *
     * @since version 0.83
     *
     * @param $item         CommonDBTM object for which the tab need to be displayed
     * @param $withtemplate boolean is a template object ?
     *
     *  @return string tab name
    **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (Session::haveRight('plugin_pdf', READ) && empty($withtemplate)) {
            return self::createTabEntry(__s('PDF export', 'pdf'), 0, $item::getType(), PluginPdfConfig::getIcon());
        }
        return '';
    }

    /**
     * export Tab content - specific content for this type
     * is run first, before displayCommonTabForPDF.
     *
     * @since version 0.83
     *
     * @param $pdf          PluginPdfSimplePDF object for output
     * @param $item         CommonGLPI object for which the tab need to be displayed
     * @param $tab   string tab number
     *
     * @return bool true if display done (else will search for another handler)
    **/
    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        return false;
    }

    /**
     * export Tab content - classic content use by various object
     *
     * @since version 0.83
     *
     * @param $pdf          PluginPdfSimplePDF object for output
     * @param $item         CommonGLPI object for which the tab need to be displayed
     * @param $tab   string tab number
     *
     * @return bool true if display done (else will search for another handler)
    **/
    final public static function displayCommonTabForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        if ($item instanceof CommonDBTM) {
            switch ($tab) {
                case $item->getType() . '$main':
                    static::pdfMain($pdf, $item); // @phpstan-ignore-line - Call to an undefined static method CommonGLPI::pdfMain()
                    break;

                case 'Notepad$1':
                    if (Session::haveRight($item::$rightname, READNOTE)) {
                        self::pdfNote($pdf, $item);
                    }
                    break;

                case 'Document_Item$1':
                    if (Session::haveRight('document', READ)) {
                        PluginPdfDocument::pdfForItem($pdf, $item);
                    }
                    break;

                case 'NetworkPort$1':
                    PluginPdfNetworkPort::pdfForItem($pdf, $item);
                    break;

                case 'Infocom$1':
                    if (Session::haveRight('infocom', READ)) {
                        PluginPdfInfocom::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Contract_Item$1':
                    if (Session::haveRight('contract', READ)) {
                        PluginPdfContract_Item::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Ticket$1':
                    if (Ticket::canView()) {
                        PluginPdfItem_Ticket::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Item_Problem$1':
                    if (Problem::canView()) {
                        PluginPdfItem_Problem::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Change_Item$1':
                    if (Change::canView()) {
                        PluginPdfChange_Item::pdfForItem($pdf, $item);
                    }
                    break;

                case 'ManualLink$1':
                    if (Session::haveRight('link', READ)) {
                        PluginPdfLink::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Reservation$1':
                    if (Session::haveRight('reservation', READ)) {
                        PluginPdfReservation::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Log$1':
                    PluginPdfLog::pdfForItem($pdf, $item);
                    break;

                case 'KnowbaseItem_Item$1':
                    if (KnowbaseItem::canView()) {
                        PluginPdfItem_Knowbaseitem::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Item_Devices$1':
                    if (Session::haveRight('device', READ)) {
                        PluginPdfItem_Device::pdfForItem($pdf, $item);
                    }
                    break;

                case 'Item_Disk$1':
                    PluginPdfItem_Disk::pdfForItem($pdf, $item);
                    break;

                case 'Computer_Item$1':
                    PluginPdfComputer_Item::pdfForItem($pdf, $item);
                    break;

                case 'Item_SoftwareVersion$1':
                    PluginPdfItem_SoftwareVersion::pdfForItem($pdf, $item);
                    break;

                case 'Domain_Item$1':
                    PluginPdfDomain_Item::pdfForItem($pdf, $item);
                    break;

                case 'Item_OperatingSystem$1':
                    PluginPdfItem_OperatingSystem::pdfForItem($pdf, $item);
                    break;

                default:
                    return false;
            }
        }

        return true;
    }

    /**
     * show Tab content
     *
     * @since version 0.83
     *
     * @param $item         CommonGLPI object for which the tab need to be displayed
     * @param $tabnum       integer tab number
     * @param $withtemplate boolean is a template object ?
     *
     * @return true
    **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $pref = new PluginPdfPreference();
        $pref->menu($item, $CFG_GLPI['root_doc'] . '/plugins/pdf/front/export.php');

        return true;
    }

    /**
     * Read the object and create header for all pages
     *
     * No HTML supported in this function
     *
     * @param $ID integer, ID of the object to print
    **/
    private function addHeader($ID)
    {
        $entity = '';
        if ($this->obj->getFromDB($ID) && $this->obj->can($ID, READ)) {
            if ($this->obj->getType()    != 'Ticket'
                && $this->obj->getType() != 'KnowbaseItem'
                && $this->obj->getField('name')) {
                $name = $this->obj->getField('name');
            } else {
                $name = sprintf(__s('%1$s %2$s'), __s('ID'), $ID);
            }

            if (Session::isMultiEntitiesMode() && $this->obj->isEntityAssign()) {
                $entity = ' (' . Dropdown::getDropdownName('glpi_entities', $this->obj->getEntityID()) . ')';
            }
            $header = sprintf(
                __s('%1$s - %2$s'),
                $this->obj->getTypeName(),
                sprintf(
                    __s('%1$s %2$s'),
                    $name,
                    $entity,
                ),
            );
            $this->pdf->setHeader($header);

            return true;
        }

        return false;
    }

    public static function pdfNote(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        $ID    = $item->getField('id');
        $notes = Notepad::getAllForItem($item);
        $rand  = mt_rand();

        $number = count($notes);

        $pdf->setColumnsSize(100);
        $title = '<b>' . _sn('Note', 'Notes', $number) . '</b>';

        if ($number === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            if ($number > $_SESSION['glpilist_limit']) {
                $title = sprintf(__s('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'] . ' / ' . $number);
            } else {
                $title = sprintf(__s('%1$s: %2$s'), $title, $number);
            }
            $pdf->displayTitle($title);

            $tot = 0;
            foreach ($notes as $note) {
                if (!empty($note['content']) && ($tot < $_SESSION['glpilist_limit'])) {
                    $id      = 'note' . $note['id'] . $rand;
                    $content = $note['content'];
                    $pdf->displayText('', $content, 5);
                    $tot++;
                }
            }
        }
        $pdf->displaySpace();
    }

    /**
     * Generate the PDF for some object
     *
     * @param $tab_id  Array   of ID of object to print
     * @param $tabs    Array   of name of tab to print
     * @param $page    Integer 1 for landscape, 0 for portrait
     * @param $render  Boolean send result if true,  return result if false
     *
     * @return string pdf output if $render is false
    **/
    final public function generatePDF($tab_id, $tabs, $page = 0, $render = true)
    {
        $dbu = new DbUtils();

        $this->pdf = new PluginPdfSimplePDF('a4', ($page ? 'landscape' : 'portrait'));

        foreach ($tab_id as $key => $id) {
            if ($this->addHeader($id)) {
                $this->pdf->newPage();
            } else {
                // Object not found or no right to read
                continue;
            }

            foreach ($tabs as $tab) {
                if (!static::displayTabContentForPDF($this->pdf, $this->obj, $tab)
                    && !static::displayCommonTabForPDF($this->pdf, $this->obj, $tab)) {
                    $data     = explode('$', $tab);
                    $itemtype = $data[0];
                    // Default set
                    $tabnum = ($data[1] ?? 1);

                    if (!is_numeric($itemtype)
                        && ($itemtype != 'empty')) {
                        if ($itemtype == 'Item_Devices') {
                            $PluginPdfComputer = new PluginPdfComputer();
                            if ($PluginPdfComputer->displayTabContentForPdf(
                                $this->pdf,
                                $this->obj,
                                $tabnum,
                            )) {
                                continue;
                            }
                        } elseif (method_exists($itemtype, 'displayTabContentForPdf')
                                   && ($obj = $dbu->getItemForItemtype($itemtype))) {
                            /** @phpstan-ignore-next-line */
                            if ($obj->displayTabContentForPdf($this->pdf, $this->obj, $tabnum)) {
                                continue;
                            }
                        }
                    }
                    Toolbox::logInFile(
                        'php-errors',
                        sprintf(__s("PDF: don't know how to display %s tab") . '\n', $tab),
                    );
                }
            }
        }
        $config = PluginPdfConfig::getInstance();
        if (!empty($config->getField('add_text'))) {
            $this->pdf->displayText('<b><i>' . $config->getField('add_text') . '</i></b>', '', 5);
        }

        if ($render) {
            $this->pdf->render();
            return '';
        } else {
            return $this->pdf->output();
        }
    }

    public static function mainTitle(PluginPdfSimplePDF $pdf, $item)
    {
        $pdf->setColumnsSize(50, 50);

        $col1 = '<b>' . sprintf(__s('%1$s %2$s'), __s('ID'), $item->fields['id']) . '</b>';
        $col2 = sprintf(
            __s('%1$s: %2$s'),
            __s('Last update'),
            Html::convDateTime($item->fields['date_mod']),
        );
        if (!empty($item->fields['template_name'])) {
            $col2 = sprintf(
                __s('%1$s (%2$s)'),
                $col2,
                sprintf(
                    __s('%1$s: %2$s'),
                    __s('Template name'),
                    $item->fields['template_name'],
                ),
            );
        }

        return $pdf->displayTitle($col1, $col2);
    }

    public static function mainLine(PluginPdfSimplePDF $pdf, $item, $field)
    {
        $dbu = new DbUtils();

        $type = Toolbox::strtolower($item->getType());
        switch ($field) {
            case 'name-status':
                return $pdf->displayLine(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Name') . '</i></b>',
                        $item->fields['name'],
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Status') . '</i></b>',
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_states',
                            $item->fields['states_id'],
                        )),
                    ),
                );

            case 'location-type':
                return $pdf->displayLine(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Location') . '</i></b>',
                        Dropdown::getDropdownName(
                            'glpi_locations',
                            $item->fields['locations_id'],
                        ),
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Type') . '</i></b>',
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_' . $type . 'types',
                            $item->fields[$type . 'types_id'],
                        )),
                    ),
                );

            case 'tech-manufacturer':
                return $pdf->displayLine(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Technician in charge of the hardware') . '</i></b>',
                        $dbu->getUserName($item->fields['users_id_tech']),
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Manufacturer') . '</i></b>',
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_manufacturers',
                            $item->fields['manufacturers_id'],
                        )),
                    ),
                );
            case 'group-model':
                return $pdf->displayLine(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Group in charge of the hardware') . '</i></b>',
                        Dropdown::getDropdownName(
                            'glpi_groups',
                            $item->fields['groups_id_tech'],
                        ),
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Model') . '</i></b>',
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_' . $type . 'models',
                            $item->fields[$type . 'models_id'],
                        )),
                    ),
                );

            case 'contactnum-serial':
                return $pdf->displayLine(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Alternate username number') . '</i></b>',
                        $item->fields['contact_num'],
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Serial number') . '</i></b>',
                        $item->fields['serial'],
                    ),
                );

            case 'contact-otherserial':
                return $pdf->displayLine(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Alternate username') . '</i></b>',
                        $item->fields['contact'],
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Inventory number') . '</i></b>',
                        $item->fields['otherserial'],
                    ),
                );

            case 'user-management':
                return $pdf->displayLine(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('User') . '</i></b>',
                        $dbu->getUserName($item->fields['users_id']),
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Management type') . '</i></b>',
                        ($item->fields['is_global'] ? __s('Global management')
                                                   : __s('Unit management')),
                    ),
                );

            case 'comment':
                return $pdf->displayText('<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Comments') . '</i></b>',
                    '',
                ), $item->fields['comment']);

            default:
                return;
        }
    }

    /**
     * @since version 0.85
    **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'DoIt':
                $opt = ['id' => 'pdfmassubmit'];
                echo Html::submit(_sx('button', 'Post'), $opt);

                return true;
        }
        //      return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @since version 0.85
    **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        switch ($ma->getAction()) {
            case 'DoIt':
                $tab_id = [];
                foreach ($ids as $key => $val) {
                    if ($val) {
                        $tab_id[] = $key;
                    }
                }
                $_SESSION['plugin_pdf']['type']   = $item->getType();
                $_SESSION['plugin_pdf']['tab_id'] = serialize($tab_id);
                echo "<script type='text/javascript'>
                      location.href='" . $CFG_GLPI['root_doc'] . "/plugins/pdf/front/export.massive.php'</script>";
                break;
        }
    }

    public static function devices()
    {
        //   name, symbole, currency, uniqUE
        return ['AED' => ['UAE Dirham', 'د.إ', false],
            'AFN'     => ['Afghani', 'Af'],
            'ALL'     => ['Lek', 'L', false],
            'AMD'     => ['Armenian Dram', 'Դ'],
            'AOA'     => ['Kwanza', 'Kz'],
            'ARS'     => ['Argentine Peso', '$', false],
            'AUD'     => ['Australian Dollar', '$', false],
            'AWG'     => ['Aruban Guilder/Florin', 'ƒ'],
            'AZN'     => ['Azerbaijanian Manat', 'ман'],
            'BAM'     => ['Konvertibilna Marka', 'КМ'],
            'BBD'     => ['Barbados Dollar', '$', false],
            'BDT'     => ['Taka', '৳'],
            'BGN'     => ['Bulgarian Lev', 'лв'],
            'BHD'     => ['Bahraini Dinar', 'ب.د'],
            'BIF'     => ['Burundi Franc', '₣', false],
            'BMD'     => ['Bermudian Dollar', '$', false],
            'BND'     => ['Brunei Dollar', '$', false],
            'BOB'     => ['Boliviano', 'Bs.'],
            'BRL'     => ['Brazilian Real', 'R$'],
            'BSD'     => ['Bahamian Dollar', '$', false],
            'BTN'     => ['Ngultrum', ''],
            'BWP'     => ['Pula', 'P', false],
            'BYR'     => ['Belarussian Ruble', 'Br'],
            'BZD'     => ['Belize Dollar', '$', false],
            'CAD'     => ['Canadian Dollar', '$', false],
            'CDF'     => ['Congolese Franc', '₣', false],
            'CHF'     => ['Swiss Franc', '₣', false],
            'CLP'     => ['Chilean Peso', '$', false],
            'CNY'     => ['Yuan', '¥'],
            'COP'     => ['Colombian Peso', '$', false],
            'CRC'     => ['Costa Rican Colon', '₡'],
            'CUP'     => ['Cuban Peso', '$', false],
            'CVE'     => ['Cape Verde Escudo', '$', false],
            'CZK'     => ['Czech Koruna', 'Kč'],
            'DJF'     => ['Djibouti Franc', '₣', false],
            'DKK'     => ['Danish Krone', 'kr', false],
            'DOP'     => ['Dominican Peso', '$', false],
            'DZD'     => ['Algerian Dinar', 'د.ج'],
            'EGP'     => ['Egyptian Pound', '£', false],
            'ERN'     => ['Nakfa', 'Nfk'],
            'ETB'     => ['Ethiopian Birr', ''],
            'EUR'     => ['Euro', '€'],
            'FJD'     => ['Fiji Dollar', '$', false],
            'FKP'     => ['Falkland Islands Pound', '£', false],
            'GBP'     => ['Pound Sterling', '£', false],
            'GEL'     => ['Lari', 'ლ'],
            'GHS'     => ['Cedi', '₵'],
            'GIP'     => ['Gibraltar Pound', '£', false],
            'GMD'     => ['Dalasi', 'D'],
            'GNF'     => ['Guinea Franc', '₣', false],
            'GTQ'     => ['Quetzal', 'Q'],
            'HKD'     => ['Hong Kong Dollar', '$', false],
            'HNL'     => ['Lempira', 'L', false],
            'HRK'     => ['Croatian Kuna', 'Kn'],
            'HTG'     => ['Gourde', 'G'],
            'HUF'     => ['Forint', 'Ft'],
            'IDR'     => ['Rupiah', 'Rp'],
            'ILS'     => ['New Israeli Shekel', '₪'],
            'INR'     => ['Indian Rupee', '₨', false],
            'IQD'     => ['Iraqi Dinar', 'ع.د'],
            'IRR'     => ['Iranian Rial', '﷼'],
            'ISK'     => ['Iceland Krona', 'Kr', false],
            'JMD'     => ['Jamaican Dollar', '$', false],
            'JOD'     => ['Jordanian Dinar', 'د.ا', false],
            'JPY'     => ['Yen', '¥'],
            'KES'     => ['Kenyan Shilling', 'Sh', false],
            'KGS'     => ['Som', ''],
            'KHR'     => ['Riel', '៛'],
            'KPW'     => ['North Korean Won', '₩', false],
            'KRW'     => ['South Korean Won',  '₩', false],
            'KWD'     => ['Kuwaiti Dinar', 'د.ك'],
            'KYD'     => ['Cayman Islands Dollar', '$', false],
            'KZT'     => ['Tenge', '〒'],
            'LAK'     => ['Kip', '₭'],
            'LBP'     => ['Lebanese Pound', 'ل.ل'],
            'LKR'     => ['Sri Lanka Rupee', 'Rs'],
            'LRD'     => ['Liberian Dollar', '$', false],
            'LSL'     => ['Loti', 'L', false],
            'LYD'     => ['Libyan Dinar', 'ل.د'],
            'MAD'     => ['Moroccan Dirham', 'د.م.'],
            'MDL'     => ['Moldavian Leu', 'L', false],
            'MGA'     => ['Malagasy Ariary', ''],
            'MKD'     => ['Denar', 'ден'],
            'MMK'     => ['Kyat', 'K', false],
            'MNT'     => ['Tugrik', '₮'],
            'MOP'     => ['Pataca', 'P', false],
            'MRO'     => ['Ouguiya', 'UM'],
            'MUR'     => ['Mauritius Rupee', '₨', false],
            'MVR'     => ['Rufiyaa', 'ރ.'],
            'MWK'     => ['Kwacha', 'MK'],
            'MXN'     => ['Mexican Peso', '$', false],
            'MYR'     => ['Malaysian Ringgit', 'RM'],
            'MZN'     => ['Metical', 'MTn'],
            'NAD'     => ['Namibia Dollar', '$', false],
            'NGN'     => ['Naira', '₦'],
            'NIO'     => ['Cordoba Oro', 'C$'],
            'NOK'     => ['Norwegian Krone', 'kr', false],
            'NPR'     => ['Nepalese Rupee', '₨', false],
            'NZD'     => ['New Zealand Dollar', '$', false],
            'OMR'     => ['Rial Omani', 'ر.ع.'],
            'PAB'     => ['Balboa', 'B/.'],
            'PEN'     => ['Nuevo Sol', 'S/.'],
            'PGK'     => ['Kina', 'K', false],
            'PHP'     => ['Philippine Peso', '₱'],
            'PKR'     => ['Pakistan Rupee', '₨', false],
            'PLN'     => ['PZloty', 'zł'],
            'PYG'     => ['Guarani', '₲'],
            'QAR'     => ['Qatari Rial', 'ر.ق'],
            'RON'     => ['Leu', 'L', false],
            'RSD'     => ['Serbian Dinar', 'din'],
            'RUB'     => ['Russian Ruble', 'р.'],
            'RWF'     => ['Rwanda Franc', 'F', false],
            'SAR'     => ['Saudi Riyal', 'ر.س '],
            'SBD'     => ['Solomon Islands Dollar', '$', false],
            'SCR'     => ['Seychelles Rupee', '₨', false],
            'SDG'     => ['Sudanese', '£', false],
            'SEK'     => ['Swedish Krona', 'kr', false],
            'SGD'     => ['Singapore Dollar', '$', false],
            'SHP'     => ['Saint Helena Pound', '£', false],
            'SLL'     => ['leone', 'Le'],
            'SOS'     => ['Somali Shilling', 'Sh', false],
            'SRD'     => ['Suriname Dollar', '$', false],
            'STD'     => ['Dobra', 'Db'],
            'SYP'     => ['Syrian Pound', 'ل.س'],
            'SZL'     => ['Lilangeni', 'L', false],
            'THB'     => ['Baht', '฿'],
            'TJS'     => ['Somoni', 'ЅМ'],
            'TMT'     => ['Manat', 'm'],
            'TND'     => ['Tunisian Dinar', 'د.ت'],
            'TOP'     => ['Pa’anga', 'T$'],
            'TRY'     => ['Turkish Lira', '₤', false],
            'TTD'     => ['Trinidad and Tobago Dollar', '$', false],
            'TWD'     => ['Taiwan Dollar', '$', false],
            'TZS'     => ['Tanzanian Shilling', 'Sh', false],
            'UAH'     => ['Hryvnia', '₴'],
            'UGX'     => ['Uganda Shilling', 'Sh', false],
            'USD'     => ['US Dollar', '$', false],
            'UYU'     => ['Peso Uruguayo', '$', false],
            'UZS'     => ['Uzbekistan Sum', ''],
            'VEF'     => ['Bolivar Fuerte', 'Bs F'],
            'VND'     => ['Dong', '₫'],
            'VUV'     => ['Vatu', 'Vt'],
            'WST'     => ['Tala', 'T'],
            'XAF'     => ['CFA Franc BCEAO', '₣', false],
            'XCD'     => ['East Caribbean Dollar', '$', false],
            'XPF'     => ['CFP Franc', '₣', false],
            'YER'     => ['Yemeni Rial', '﷼'],
            'ZAR'     => ['Rand', 'R'],
            'ZMW'     => ['Zambian Kwacha', 'ZK'],
            'ZWL'     => ['Zimbabwe Dollar', '$', false]];
    }
}

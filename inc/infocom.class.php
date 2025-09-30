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

class PluginPdfInfocom extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Infocom());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        /** @var array $CFG_GLPI */
        /** @var array $PDF_DEVICES */
        global $CFG_GLPI, $PDF_DEVICES;

        $ID = $item->getField('id');

        if (!Session::haveRight('infocom', READ)) {
            return false;
        }

        $ic = new Infocom();

        $pdf->setColumnsSize(100);
        $title = '<b>' . __s('Financial and administratives information') . '</b>';

        if (!$ic->getFromDBforDevice(get_class($item), $ID)) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            $pdf->displayTitle('<b>' . __s('Asset lifecycle') . '</b>');

            $pdf->setColumnsSize(50, 50);

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Order date') . '</i></b>',
                    Html::convDate($ic->fields['order_date']),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Date of purchase') . '</i></b>',
                    Html::convDate($ic->fields['buy_date']),
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Delivery date') . '</i></b>',
                    Html::convDate($ic->fields['delivery_date']),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Startup date') . '</i></b>',
                    Html::convDate($ic->fields['use_date']),
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Date of last physical inventory') . '</i></b>',
                    Html::convDate($ic->fields['inventory_date']),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Decommission date') . '</i></b>',
                    Html::convDate($ic->fields['decommission_date']),
                ),
            );

            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>' . __s('Financial and administrative information') . '</b>');

            $pdf->setColumnsSize(50, 50);

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Supplier') . '</i></b>',
                    Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_suppliers',
                        $ic->fields['suppliers_id'],
                    )),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Budget') . '</i></b>',
                    Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_budgets',
                        $ic->fields['budgets_id'],
                    )),
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Order number') . '</i></b>',
                    $ic->fields['order_number'],
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Immobilization number') . '</i></b>',
                    $ic->fields['immo_number'],
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Invoice number') . '</i></b>',
                    $ic->fields['bill'],
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Delivery form') . '</i></b>',
                    $ic->fields['delivery_number'],
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    _x('price', 'Value') . '</i></b>',
                    PluginPdfConfig::formatNumber($ic->fields['value']),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Warranty extension value') . '</i></b>',
                    PluginPdfConfig::formatNumber($ic->fields['warranty_value']),
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Account net value') . '</i></b>',
                    PluginPdfConfig::formatNumber(Infocom::Amort(
                        $ic->fields['sink_type'],
                        $ic->fields['value'],
                        $ic->fields['sink_time'],
                        $ic->fields['sink_coeff'],
                        $ic->fields['warranty_date'],
                        $ic->fields['use_date'],
                        $CFG_GLPI['date_tax'],
                        'n',
                    )),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Amortization duration') . '</i></b>',
                    sprintf(
                        __s('%1$s (%2$s)'),
                        sprintf(
                            _sn('%d year', '%d years', $ic->fields['sink_time']),
                            $ic->fields['sink_time'],
                        ),
                        Infocom::getAmortTypeName($ic->fields['sink_type']),
                    ),
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Amortization type') . '</i></b>',
                    Infocom::getAmortTypeName($ic->fields['sink_type']),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Amortization coefficient') . '</i></b>',
                    $ic->fields['sink_coeff'],
                ),
            );

            $currency = PluginPdfConfig::getInstance();
            $sym = '';

            foreach ($PDF_DEVICES as $option => $value) {
                if ($currency->fields['currency'] == $option) {
                    $sym = $value[1];
                }
            }
            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('TCO (value + tracking cost)') . '</i></b>',
                    sprintf(
                        __s('%1$s %2$s'),
                        Toolbox::stripTags((string) Infocom::showTco(
                            $item->getField('ticket_tco'),
                            $ic->fields['value'],
                        )),
                        $sym,
                    ),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Monthly TCO') . '</i></b>',
                    sprintf(
                        __s('%1$s %2$s'),
                        Toolbox::stripTags((string) Infocom::showTco(
                            $item->getField('ticket_tco'),
                            $ic->fields['value'],
                            $ic->fields['buy_date'],
                        )),
                        $sym,
                    ),
                ),
            );

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Business criticity') . '</i></b>',
                    Dropdown::getDropdownName(
                        'glpi_businesscriticities',
                        $ic->fields['businesscriticities_id'],
                    ),
                ),
            );

            PluginPdfCommon::mainLine($pdf, $ic, 'comment');

            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>' . __s('Warranty information') . '</b>');

            $pdf->setColumnsSize(50, 50);

            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Start date of warranty') . '</i></b>',
                    Html::convDate($ic->fields['warranty_date']),
                ),
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Warranty duration') . '</i></b>',
                    sprintf(
                        __s('%1$s - %2$s'),
                        sprintf(
                            _sn(
                                '%d month',
                                '%d months',
                                $ic->fields['warranty_duration'],
                            ),
                            $ic->fields['warranty_duration'],
                        ),
                        sprintf(
                            __s('Valid to %s'),
                            Infocom::getWarrantyExpir(
                                $ic->fields['buy_date'],
                                $ic->fields['warranty_duration'],
                            ),
                        ),
                    ),
                ),
            );

            $col1 = '<b><i>' . __s('Alarms on financial and administrative information') . '</i></b>';
            if ($ic->fields['alert'] == 0) {
                $col1 = sprintf(__s('%1$s: %2$s'), $col1, __s('No'));
            } elseif ($ic->fields['alert'] == 4) {
                $col1 = sprintf(__s('%1$s: %2$s'), $col1, __s('Warranty expiration date'));
            }
            $pdf->displayLine(
                '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Warranty information') . '</i></b>',
                    $ic->fields['warranty_info'],
                ),
                $col1,
            );
        }

        $pdf->displaySpace();
    }
}

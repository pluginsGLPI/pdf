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

class PluginPdfPrinter extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Printer());
    }

    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);
        unset($onglets['Certificate_Item$1']);
        unset($onglets['Impact$1']);
        unset($onglets['Appliance_Item$1']);
        unset($onglets['PrinterLog$0']);
        unset($onglets['Glpi\Socket$1']);

        return $onglets;
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, Printer $printer)
    {
        $dbu = new DbUtils();

        PluginPdfCommon::mainTitle($pdf, $printer);

        PluginPdfCommon::mainLine($pdf, $printer, 'name-status');
        PluginPdfCommon::mainLine($pdf, $printer, 'location-type');
        PluginPdfCommon::mainLine($pdf, $printer, 'tech-manufacturer');
        PluginPdfCommon::mainLine($pdf, $printer, 'group-model');
        PluginPdfCommon::mainLine($pdf, $printer, 'contactnum-serial');
        PluginPdfCommon::mainLine($pdf, $printer, 'contact-otherserial');
        PluginPdfCommon::mainLine($pdf, $printer, 'user-management');

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Sysdescr') . '</i></b>',
                $printer->fields['sysdescr'],
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('User') . '</i></b>',
                $dbu->getUserName($printer->fields['users_id']),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Management type') . '</i></b>',
                ($printer->fields['is_global'] ? __s('Global management')
                                              : __s('Unit management')),
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Network') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_networks',
                    $printer->fields['networks_id'],
                )),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Group') . '</i></b>',
                Dropdown::getDropdownName('glpi_groups', $printer->fields['groups_id']),
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('UUID') . '</i></b>',
                $printer->fields['uuid'],
            ),
        );


        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Memory') . '</i></b>',
                $printer->fields['memory_size'],
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Initial page counter') . '</i></b>',
                $printer->fields['init_pages_counter'],
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Current counter of pages') . '</i></b>',
                $printer->fields['last_pages_counter'],
            ),
        );

        $opts = ['have_serial' => __s('Serial'),
            'have_parallel'    => __s('Parallel'),
            'have_usb'         => __s('USB'),
            'have_ethernet'    => __s('Ethernet'),
            'have_wifi'        => __s('Wifi')];

        foreach (array_keys($opts) as $key) {
            if (!$printer->fields[$key]) {
                unset($opts[$key]);
            }
        }

        $pdf->setColumnsSize(100);
        $pdf->displayLine('<b><i>' . sprintf(
            __s('%1$s: %2$s'),
            _sn('Port', 'Ports', count($opts)) . '</i></b>',
            (count($opts) ? implode(', ', $opts) : __s('None')),
        ));

        PluginPdfCommon::mainLine($pdf, $printer, 'comment');

        $pdf->displaySpace();
    }

    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        if ($item instanceof Printer) {
            switch ($tab) {
                case 'Cartridge$1':
                    PluginPdfCartridge::pdfForPrinter($pdf, $item, false);
                    PluginPdfCartridge::pdfForPrinter($pdf, $item, true);
                    break;

                default:
                    return false;
            }
        }

        return true;
    }
}

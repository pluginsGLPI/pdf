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

class PluginPdfCartridgeItem extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new CartridgeItem());
    }

    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);

        return $onglets;
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, CartridgeItem $cartitem)
    {
        $dbu = new DbUtils();

        PluginPdfCommon::mainTitle($pdf, $cartitem);

        $pdf->displayLine(
            '<b><i>' . sprintf(__('%1$s: %2$s'), __('Name') . '</i></b>', $cartitem->fields['name']),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Location') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_locations',
                    $cartitem->fields['locations_id'],
                )),
            ),
        );
        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Type') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_cartridgeitemtypes',
                    $cartitem->fields['cartridgeitemtypes_id'],
                )),
            ),
            '<b><i>' . sprintf(__('%1$s: %2$s'), __('Reference') . '</i></b>', $cartitem->fields['ref']),
        );


        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Technician in charge of the hardware') . '</i></b>',
                $dbu->getUserName($cartitem->fields['users_id_tech']),
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Manufacturer') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_manufacturers',
                    $cartitem->fields['manufacturers_id'],
                )),
            ),
        );
        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Group in charge of the hardware') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_groups',
                    $cartitem->fields['groups_id_tech'],
                ),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Stock location') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_locations',
                    $cartitem->fields['locations_id'],
                ),
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Alert threshold') . '</i></b>',
                $cartitem->getField('alarm_threshold'),
            ),
        );

        PluginPdfCommon::mainLine($pdf, $cartitem, 'comment');

        $pdf->displaySpace();
    }

    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        if ($item instanceof CartridgeItem) {
            switch ($tab) {
                case 'Cartridge$1':
                    PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'new');
                    PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'used');
                    PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'old');
                    break;

                case 'CartridgeItem_PrinterModel$1':
                    self::pdfForPrinterModel($pdf, $item);
                    break;

                default:
                    return false;
            }
        }

        return true;
    }

    public static function pdfForPrinterModel(PluginPdfSimplePDF $pdf, CartridgeItem $item)
    {
        $instID = $item->getField('id');
        if (!$item->can($instID, READ)) {
            return false;
        }

        $iterator = CartridgeItem_PrinterModel::getListForItem($item);
        $number   = count($iterator);
        $datas    = [];

        foreach ($iterator as $data) {
            $datas[$data['linkid']] = $data;
        }

        $pdf->setColumnsSize(100);
        $title = '<b>' . _n('Printer model', 'Printer models', $number) . '</b>';
        if (!$number) {
            $pdf->displayTitle(__('No printel model associated', 'pdf'));
        } else {
            if ($number > $_SESSION['glpilist_limit']) {
                $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'] . ' / ' . $number);
            } else {
                $title = sprintf(__('%1$s: %2$s'), $title, $number);
            }
            $pdf->displayTitle($title);

            foreach ($datas as $data) {
                $pdf->displayLine($data['name']);
            }
        }
    }
}

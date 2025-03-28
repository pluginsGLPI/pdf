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

class PluginPdfSoftware extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new Software());
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, Software $software)
    {
        $dbu = new DbUtils();

        PluginPdfCommon::mainTitle($pdf, $software);

        $pdf->displayLine(
            '<b><i>' . sprintf(__('%1$s: %2$s'), __('Name') . '</i></b>', $software->fields['name']),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Publisher') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_manufacturers',
                    $software->fields['manufacturers_id'],
                )),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Location') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_locations',
                    $software->fields['locations_id'],
                ),
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Category') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_softwarecategories',
                    $software->fields['softwarecategories_id'],
                ),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Technician in charge of the hardware') . '</i></b>',
                $dbu->getUserName($software->fields['users_id_tech']),
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Associable to a ticket') . '</i></b>',
                ($software->fields['is_helpdesk_visible'] ? __('Yes') : __('No')),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Group in charge of the hardware') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_groups',
                    $software->fields['groups_id_tech'],
                ),
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('User') . '</i></b>',
                $dbu->getUserName($software->fields['users_id']),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Group') . '</i></b>',
                Dropdown::getDropdownName('glpi_groups', $software->fields['groups_id']),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('Last update on %s'),
                Html::convDateTime($software->fields['date_mod']),
            ),
        );


        if ($software->fields['softwares_id'] > 0) {
            $col2 = '<b><i> ' . __('from') . ' </i></b> ' .
                     Toolbox::stripTags(Dropdown::getDropdownName(
                         'glpi_softwares',
                         $software->fields['softwares_id'],
                     ));
        } else {
            $col2 = '';
        }

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Upgrade') . '</i></b>',
                ($software->fields['is_update'] ? __('Yes') : __('No')),
                $col2,
            ),
        );


        $pdf->setColumnsSize(100);
        PluginPdfCommon::mainLine($pdf, $software, 'comment');

        $pdf->displaySpace();
    }

    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);
        unset($onglets['Appliance_Item$1']);
        unset($onglets['Impact$1']);

        return $onglets;
    }

    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        if ($item instanceof Software) {
            switch ($tab) {
                case 'SoftwareVersion$1':
                    PluginPdfSoftwareVersion::pdfForSoftware($pdf, $item);
                    break;

                case 'SoftwareLicense$1':
                    $infocom = isset($_REQUEST['item']['Infocom$1']);
                    PluginPdfSoftwareLicense::pdfForSoftware($pdf, $item, $infocom);
                    break;

                case 'Item_SoftwareVersion$1':
                    PluginPdfItem_SoftwareVersion::pdfForSoftware($pdf, $item);
                    break;

                case 'Domain_Item$1':
                    PluginPdfDomain_Item::pdfForItem($pdf, $item);
                    break;

                default:
                    return false;
            }
        }

        return true;
    }
}

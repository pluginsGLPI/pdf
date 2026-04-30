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

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Software());
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, Software $software)
    {
        $dbu = new DbUtils();

        PluginPdfCommon::mainTitle($pdf, $software);

        $pdf->displayLine(
            '<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Name') . '</i></b>', $software->fields['name']),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Publisher') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_manufacturers',
                    $software->fields['manufacturers_id'],
                )),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Location') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_locations',
                    $software->fields['locations_id'],
                ),
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Category') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_softwarecategories',
                    $software->fields['softwarecategories_id'],
                ),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Technician in charge of the hardware') . '</i></b>',
                $dbu->getUserName($software->fields['users_id_tech']),
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Associable to a ticket') . '</i></b>',
                ($software->fields['is_helpdesk_visible'] ? __s('Yes') : __s('No')),
            ),
        );

        $group = Dropdown::getDropdownName('glpi_groups', $software->fields['groups_id']);
        $group_tech = Dropdown::getDropdownName('glpi_groups', $software->fields['groups_id_tech']);
        if (Toolbox::hasTrait($software::class, \Glpi\Features\AssignableItem::class)) {
            $group_item = new Group_Item();
            $groups = $group_item->getItemsAssociatedTo($software::class, (int) $software->fields['id']);

            $group_ids = [];
            $group_tech_ids = [];
            foreach ($groups as $group_item_link) {
                if ((int) $group_item_link->fields['type'] === Group_Item::GROUP_TYPE_NORMAL) {
                    $group_ids[] = (int) $group_item_link->fields['groups_id'];
                }
                if ((int) $group_item_link->fields['type'] === Group_Item::GROUP_TYPE_TECH) {
                    $group_tech_ids[] = (int) $group_item_link->fields['groups_id'];
                }
            }

            $group = implode(', ', array_filter(array_map(
                static fn($group_id) => Toolbox::stripTags(Dropdown::getDropdownName('glpi_groups', $group_id)),
                $group_ids,
            )));
            $group_tech = implode(', ', array_filter(array_map(
                static fn($group_id) => Toolbox::stripTags(Dropdown::getDropdownName('glpi_groups', $group_id)),
                $group_tech_ids,
            )));
        }

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Group in charge of the hardware') . '</i></b>',
                $group_tech,
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('User') . '</i></b>',
                $dbu->getUserName($software->fields['users_id']),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Group') . '</i></b>',
                $group,
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('Last update on %s'),
                Html::convDateTime($software->fields['date_mod']),
            ),
        );


        if ($software->fields['softwares_id'] > 0) {
            $col2 = '<b><i> ' . __s('from') . ' </i></b> ' .
                     Toolbox::stripTags(Dropdown::getDropdownName(
                         'glpi_softwares',
                         $software->fields['softwares_id'],
                     ));
        } else {
            $col2 = '';
        }

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Upgrade') . '</i></b>',
                ($software->fields['is_update'] ? __s('Yes') : __s('No')),
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

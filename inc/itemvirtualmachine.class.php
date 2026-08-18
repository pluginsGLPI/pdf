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

class PluginPdfItemVirtualMachine extends PluginPdfCommon
{
    public static string $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new ItemVirtualMachine());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        $dbu = new DbUtils();

        $ID = $item->getField('id');

        // From ItemVirtualMachine::showForAsset()
        $virtualmachines = $dbu->getAllDataFromTable(
            'glpi_itemvirtualmachines',
            ['WHERE' => ['itemtype' => $item->getType(),
                'items_id'         => $ID],
                'ORDER' => 'name'],
        );
        $pdf->setColumnsSize(100);
        $title = '<b>' . __s('List of virtualized environments') . '</b>';

        $number = count($virtualmachines);

        if ($number === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            if ($number > $_SESSION['glpilist_limit']) {
                $title = sprintf(__s('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'] . ' / ' . $number);
            } else {
                $title = sprintf(__s('%1$s: %2$s'), $title, $number);
            }
            $pdf->displayTitle($title);

            $pdf->setColumnsSize(19, 11, 11, 8, 20, 8, 8, 15);
            $pdf->displayTitle(
                __s('Name'),
                __s('Virtualization system'),
                __s('Virtualization model'),
                __s('State'),
                __s('UUID'),
                _x('quantity', 'Processors number'),
                sprintf(__s('%1$s (%2$s)'), __s('Memory'), __s('Mio')),
                __s('Machine'),
            );
            $pdf->setColumnsAlign('left', 'center', 'center', 'center', 'left', 'right', 'right', 'left');

            foreach ($virtualmachines as $virtualmachine) {
                $name = '';
                if ($link_item = ItemVirtualMachine::findVirtualMachine($virtualmachine)) {
                    $linked = $dbu->getItemForItemtype($virtualmachine['itemtype']);
                    if ($linked && $linked->getFromDB($link_item)) {
                        $name = $linked->getName();
                    }
                }
                $pdf->displayLine(
                    $virtualmachine['name'],
                    Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_virtualmachinetypes',
                        $virtualmachine['virtualmachinetypes_id'],
                    )),
                    Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_virtualmachinesystems',
                        $virtualmachine['virtualmachinesystems_id'],
                    )),
                    Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_virtualmachinestates',
                        $virtualmachine['virtualmachinestates_id'],
                    )),
                    $virtualmachine['uuid'],
                    $virtualmachine['vcpu'],
                    Toolbox::stripTags(Html::formatNumber($virtualmachine['ram'], false, 0)),
                    $name,
                );
            }
        }

        // From ItemVirtualMachine::showForVirtualMachine()
        // The exported item may itself be a guest: look for the host(s) declaring a virtual machine having its UUID.
        if (!empty($item->fields['uuid'])) {
            $hosts = $dbu->getAllDataFromTable(
                'glpi_itemvirtualmachines',
                ['RAW'
                 => ['LOWER(uuid)'
                     => ItemVirtualMachine::getUUIDRestrictCriteria($item->fields['uuid']),
                 ],
                ],
            );

            if (count($hosts)) {
                $pdf->setColumnsSize(100);
                $pdf->displayTitle('<b>' . __s('List of hosts') . '</b>');

                $pdf->setColumnsSize(26, 37, 37);
                $pdf->displayTitle(__s('Name'), __s('Serial number'), __s('Entity'));

                foreach ($hosts as $host) {
                    $host_item = $dbu->getItemForItemtype($host['itemtype']);
                    if ($host_item && $host_item->getFromDB($host['items_id'])) {
                        $pdf->displayLine(
                            $host_item->getName(),
                            Toolbox::stripTags((string) $host_item->getField('serial')),
                            Dropdown::getDropdownName('glpi_entities', $host_item->getEntityID()),
                        );
                    }
                }
            }
        }
        $pdf->displaySpace();
    }
}

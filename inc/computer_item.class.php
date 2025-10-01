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

use Glpi\Asset\Asset_PeripheralAsset;

class PluginPdfComputer_Item extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Asset_PeripheralAsset());
    }

    public static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $comp)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $ID = $comp->getField('id');

        $items = ['Printer' => _sn('Printer', 'Printers', 2),
            'Monitor'       => _sn('Monitor', 'Monitors', 2),
            'Peripheral'    => _sn('Device', 'Devices', 2),
            'Phone'         => _sn('Phone', 'Phones', 2)];

        $info = new Infocom();

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . __s('Direct connections') . '</b>');

        foreach (array_keys($items) as $type) {
            $item = $dbu->getItemForItemtype($type);
            if (!$item->canView()) {
                continue;
            }
            $itemTable = $dbu->getTableForItemType($type);
            $query = [
                'SELECT' => [
                    'glpi_assets_assets_peripheralassets.id AS assoc_id',
                    'glpi_assets_assets_peripheralassets.computers_id AS assoc_computers_id',
                    'glpi_assets_assets_peripheralassets.itemtype',
                    'glpi_assets_assets_peripheralassets.items_id',
                    'glpi_assets_assets_peripheralassets.is_dynamic AS assoc_is_dynamic',
                ],
                'FROM' => 'glpi_assets_assets_peripheralassets',
                'LEFT JOIN' => [
                    $itemTable => [
                        'FKEY' => [
                            $itemTable => 'id',
                            'glpi_assets_assets_peripheralassets' => 'items_id',
                        ],
                    ],
                ],
                'WHERE' => [
                    'computers_id' => $ID,
                    'itemtype' => $type,
                    'glpi_assets_assets_peripheralassets.is_deleted' => 0,
                ],
            ];

            if ($item->maybetemplate()) {
                $query['WHERE'][$itemTable . '.is_template'] = 0;
            }

            $result    = $DB->request($query);
            $resultnum = count($result);
            if ($resultnum > 0) {
                foreach ($result as $row) {
                    $tID    = $row['items_id'];
                    $connID = $row['id'];
                    $item->getFromDB($tID);
                    if (!$info->getFromDBforDevice($type, $tID)) {
                        $info->getEmpty();
                    }

                    $line1 = $item->getName();
                    if ($item->getField('serial') != null) {
                        $line1 = sprintf(
                            __s('%1$s - %2$s'),
                            $line1,
                            sprintf(
                                __s('%1$s: %2$s'),
                                __s('Serial number'),
                                $item->getField('serial'),
                            ),
                        );
                    }

                    $line1 = sprintf(
                        __s('%1$s - %2$s'),
                        $line1,
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_states',
                            $item->getField('states_id'),
                        )),
                    );

                    $line2 = '';
                    if ($item->getField('otherserial') != null) {
                        $line2 = sprintf(
                            __s('%1$s: %2$s'),
                            __s('Inventory number'),
                            $item->getField('otherserial'),
                        );
                    }
                    if ($info->fields['immo_number']) {
                        $line2 = sprintf(
                            __s('%1$s - %2$s'),
                            $line2,
                            sprintf(
                                __s('%1$s: %2$s'),
                                __s('Immobilization number'),
                                $info->fields['immo_number'],
                            ),
                        );
                    }
                    if ($line2 !== '' && $line2 !== '0') {
                        $pdf->displayText(
                            '<b>' . sprintf(__s('%1$s: %2$s'), $item->getTypeName() . '</b>', ''),
                            $line1 . "\n" . $line2,
                            2,
                        );
                    } else {
                        $pdf->displayText(
                            '<b>' . sprintf(__s('%1$s: %2$s'), $item->getTypeName() . '</b>', ''),
                            $line1,
                            1,
                        );
                    }
                }
            } else { // No row
                switch ($type) {
                    case 'Printer':
                        $pdf->displayLine(__s('No printer', 'pdf'));
                        break;

                    case 'Monitor':
                        $pdf->displayLine(__s('No monitor', 'pdf'));
                        break;

                    case 'Peripheral':
                        $pdf->displayLine(__s('No peripheral', 'pdf'));
                        break;

                    case 'Phone':
                        $pdf->displayLine(__s('No phone', 'pdf'));
                        break;
                }
            } // No row
        } // each type
        $pdf->displaySpace();
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID   = $item->getField('id');
        $type = $item->getType();

        $info = new Infocom();
        $comp = new Computer();

        $pdf->setColumnsSize(100);
        $title = '<b>' . __s('Direct connections') . '</b>';

        $result = $DB->request(
            ['FROM' => 'glpi_assets_assets_peripheralassets'] + ['items_id'    => $ID,
                'itemtype' => $type],
        );
        $resultnum = count($result);

        if ($resultnum === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            $pdf->displayTitle($title);

            foreach ($result as $row) {
                $tID    = $row['computers_id'];
                $connID = $row['id'];
                $comp->getFromDB($tID);
                if (!$info->getFromDBforDevice('Computer', $tID)) {
                    $info->getEmpty();
                }

                $line1 = ($comp->fields['name'] ?? '(' . $comp->fields['id'] . ')');
                if (isset($comp->fields['states_id'])) {
                    $line1 = sprintf(
                        __s('%1$s - %2$s'),
                        $line1,
                        sprintf(
                            __s('%1$s: %2$s'),
                            '<b>' . __s('Status') . '</b>',
                            Toolbox::stripTags(Dropdown::getDropdownName(
                                'glpi_states',
                                $comp->fields['states_id'],
                            )),
                        ),
                    );
                }
                if (isset($comp->fields['serial'])) {
                    $line1 = sprintf(
                        __s('%1$s - %2$s'),
                        $line1,
                        sprintf(
                            __s('%1$s: %2$s'),
                            '<b>' . __s('Serial number') . '</b>',
                            $comp->fields['serial'],
                        ),
                    );
                }


                if (isset($comp->fields['otherserial'])) {
                    $line1 = sprintf(
                        __s('%1$s - %2$s'),
                        $line1,
                        sprintf(
                            __s('%1$s: %2$s'),
                            '<b>' . __s('Inventory number') . '</b>',
                            $item->getField('otherserial'),
                        ),
                    );
                }
                $line2 = '';
                if ($info->fields['immo_number']) {
                    $line2 = sprintf(
                        __s('%1$s - %2$s'),
                        $line2,
                        sprintf(
                            __s('%1$s: %2$s'),
                            '<b>' . __s('Immobilization number') . '</b>',
                            $info->fields['immo_number'],
                        ),
                    );
                }
                if ($line2 !== '' && $line2 !== '0') {
                    $pdf->displayText(
                        '<b>' . sprintf(__s('%1$s: %2$s'), __s('Computer') . '</b>', ''),
                        $line1 . "\n" . $line2,
                        2,
                    );
                } else {
                    $pdf->displayText(
                        '<b>' . sprintf(__s('%1$s: %2$s'), __s('Computer') . '</b>', ''),
                        $line1,
                        1,
                    );
                }
            }// each device   of current type
        } // No row
        $pdf->displaySpace();
    }
}

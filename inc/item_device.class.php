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

class PluginPdfItem_Device extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Item_Devices());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $devtypes = Item_Devices::getDeviceTypes();

        $ID = $item->getField('id');
        if (!$item->can($ID, READ)) {
            return false;
        }

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . Toolbox::ucfirst(_sn('Component', 'Components', 2)) . '</b>');

        $pdf->setColumnsSize(3, 14, 42, 41);

        $vide = true;
        foreach ($devtypes as $itemtype) {
            $dbu_local = new DbUtils();
            $devicetypes = $dbu_local->getItemForItemtype($itemtype);
            $specificities = $devicetypes->getSpecificities();
            $specif_fields = array_keys($specificities);

            $associated_type = str_replace('Item_', '', $itemtype);
            $linktable       = $dbu->getTableForItemType($itemtype);
            $fk              = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($associated_type));

            $select_fields = ['COUNT(*) AS NB', 'id', $fk];
            if ($specif_fields !== []) {
                $select_fields = array_merge($select_fields, $specif_fields);
            }

            // Construction of the GROUP BY clause
            $group_by = [$fk];
            if ($specif_fields !== []) {
                $group_by = array_merge($group_by, $specif_fields);
            }

            $query_params = [
                'SELECT' => $select_fields,
                'FROM' => $linktable,
                'WHERE' => [
                    'items_id' => $ID,
                    'itemtype' => $item->getType(),
                ],
                'GROUPBY' => $group_by,
            ];

            $dbu = new DbUtils();
            foreach ($DB->request($query_params) as $data) {
                $device = $dbu->getItemForItemtype($associated_type);
                $itemdevice = $dbu->getItemForItemtype($itemtype);
                $itemdevice->getFromDB($data['id']);
                if ($device->getFromDB($data[$fk])) {
                    $spec = [];
                    if (method_exists($device, 'getAdditionalFields')) {
                        $spec = $device->getAdditionalFields();
                    }

                    $col4 = '';
                    if (count($spec) > 0) {
                        $colspan = (60 / count($spec));
                        foreach ($spec as $i => $label) {
                            $toto  = substr($label['name'], 0, strpos($label['name'], '_'));
                            $value = '';
                            if (isset($itemdevice->fields[$toto]) && !empty($itemdevice->fields[$toto])) {
                                $value = $itemdevice->fields[$toto];
                            }
                            if (isset($device->fields[$label['name']])
                                && !empty($device->fields[$label['name']])) {
                                if (($label['type'] == 'dropdownValue')
                                    && ($device->fields[$label['name']] != 0)) {
                                    if (empty($value)) {
                                        $table = getTableNameForForeignKeyField($label['name']);
                                        $value = Dropdown::getDropdownName(
                                            $table,
                                            $device->fields[$label['name']],
                                        );
                                    }
                                    $col4 .= '<b><i>' . sprintf(
                                        __s('%1$s: %2$s'),
                                        $label['label'] . '</i></b>',
                                        Toolbox::stripTags($value) . ' ',
                                    );
                                } else {
                                    if (empty($value)) {
                                        $value = $device->fields[$label['name']];
                                    }
                                    if ($label['type'] == 'bool') {
                                        $value = $value == 1 ? __s('Yes') : __s('No');
                                    }
                                    if (isset($label['unit'])) {
                                        $labelname = '<b><i>' . sprintf(
                                            __s('%1$s (%2$s)'),
                                            $label['label'],
                                            $label['unit'],
                                        ) . '</i></b>';
                                    } else {
                                        $labelname = $label['label'];
                                    }
                                    $col4 .= '<b><i>' . sprintf(__s('%1$s: %2$s'), $labelname . '</i></b>', $value . ' ');
                                }
                            } elseif (isset($device->fields[$label['name'] . '_default'])
                                       && !empty($device->fields[$label['name'] . '_default'])) {
                                $col4 .= '<b><i>' . sprintf(
                                    __s('%1$s: %2$s'),
                                    $label['label'] . '</i></b>',
                                    $device->fields[$label['name'] . '_default'] . ' ',
                                );
                            }
                        }
                    }
                    $pdf->displayLine($data['NB'], $device->getTypeName(), $device->getName(), $col4);
                    $vide = false;
                }
            }
        }
        if ($vide) {
            $pdf->setColumnsSize(100);
            $pdf->setColumnsAlign('center');
            $pdf->displayLine(__s('No item to display'));
        }

        $pdf->displaySpace();
    }
}

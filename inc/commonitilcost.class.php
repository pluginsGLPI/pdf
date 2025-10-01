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

class PluginPdfCommonItilCost extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new TicketCost());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $job)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID        = $job->getField('id');
        $type      = $job->gettype();
        $table     = 'glpi_' . (strtolower($type)) . 'costs';
        $classname = $type . 'Cost';

        $result = $DB->request([
            'FROM'  => $table,
            'WHERE' => [$job->getForeignKeyField() => $ID],
            'ORDER' => 'begin_date',
        ]);

        $number = count($result);

        if ($number === 0) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle(sprintf(
                __s('%1$s: %2$s'),
                '<b>' . $classname::getTypeName(2) . '</b>',
                __s('No item to display'),
            ));
        } else {
            $pdf->setColumnsSize(60, 20, 20);
            $title = $classname::getTypeName($number);
            if (!empty(PluginPdfConfig::currencyName())) {
                $title = sprintf(
                    __s('%1$s (%2$s)'),
                    $classname::getTypeName($number),
                    PluginPdfConfig::currencyName(),
                );
            }
            $pdf->displayTitle(
                '<b>' . $title . '</b>',
                '<b>' . __s('Duration') . '</b>',
                '<b>' . CommonITILObject::getActionTime($job->fields['actiontime']) . '</b>',
            );

            $pdf->setColumnsSize(20, 10, 10, 10, 9, 10, 10, 10, 10);
            $pdf->setColumnsAlign(
                'center',
                'center',
                'center',
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
            );
            $pdf->displayTitle(
                '<b><i>' . __s('Name') . '</i></b>',
                '<b><i>' . __s('Begin date') . '</i></b>',
                '<b><i>' . __s('End date') . '</i></b>',
                '<b><i>' . __s('Budget') . '</i></b>',
                '<b><i>' . __s('Duration') . '</i></b>',
                '<b><i>' . __s('Time cost') . '</i></b>',
                '<b><i>' . __s('Fixed cost') . '</i></b>',
                '<b><i>' . __s('Material cost') . '</i></b>',
                '<b><i>' . __s('Total cost') . '</i></b>',
            );

            $total          = 0;
            $total_time     = 0;
            $total_costtime = 0;
            $total_fixed    = 0;
            $total_material = 0;

            foreach ($result as $data) {
                $cost = $classname::computeTotalCost(
                    $data['actiontime'],
                    $data['cost_time'],
                    $data['cost_fixed'],
                    $data['cost_material'],
                );
                $pdf->displayLine(
                    $data['name'],
                    Html::convDate($data['begin_date']),
                    Html::convDate($data['end_date']),
                    Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_budgets',
                        $data['budgets_id'],
                    )),
                    CommonITILObject::getActionTime($data['actiontime']),
                    PluginPdfConfig::formatNumber($data['cost_time']),
                    PluginPdfConfig::formatNumber($data['cost_fixed']),
                    PluginPdfConfig::formatNumber($data['cost_material']),
                    PluginPdfConfig::formatNumber($cost),
                );

                $total_time     += $data['actiontime'];
                $total_costtime += ($data['actiontime'] * $data['cost_time'] / HOUR_TIMESTAMP);
                $total_fixed    += $data['cost_fixed'];
                $total_material += $data['cost_material'];
                $total          += $cost;
            }
            $pdf->setColumnsSize(52, 8, 10, 10, 10, 10);
            $pdf->setColumnsAlign('right', 'right', 'right', 'right', 'right', 'right');
            $pdf->displayLine(
                '<b>' . __s('Total') . '</b>',
                '<b>' . CommonITILObject::getActionTime($total_time) . '</b>',
                '<b>' . PluginPdfConfig::formatNumber($total_costtime) . '</b>',
                '<b>' . PluginPdfConfig::formatNumber($total_fixed) . '</b>',
                '<b>' . PluginPdfConfig::formatNumber($total_material) . '</b>',
                '<b>' . PluginPdfConfig::formatNumber($total) . '</b>',
            );
        }
        $pdf->displaySpace();
    }
}

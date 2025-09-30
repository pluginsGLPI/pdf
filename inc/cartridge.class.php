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

class PluginPdfCartridge extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    /**
     * @param $obj (defult NULL)
    **/
    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Cartridge());
    }

    /**
     * @param $pdf                PluginPdfSimplePDF object
     * @param $p                  Printer object
     * @param $old
    **/
    public static function pdfForPrinter(PluginPdfSimplePDF $pdf, Printer $p, $old = false)
    {
        /** @var DBmysql $DB */
        global $DB;

        $instID = $p->getField('id');

        if (!Session::haveRight('cartridge', READ)) {
            return false;
        }

        $dateout = 'IS NULL ';
        if ($old) {
            $dateout = ' IS NOT NULL ';
        }
        $query = [
            'SELECT' => [
                'glpi_cartridgeitems.id AS tID',
                'glpi_cartridgeitems.is_deleted',
                'glpi_cartridgeitems.ref',
                'glpi_cartridgeitems.name AS type',
                'glpi_cartridges.id',
                'glpi_cartridges.pages',
                'glpi_cartridges.date_use',
                'glpi_cartridges.date_out',
                'glpi_cartridges.date_in',
                'glpi_cartridgeitemtypes.name AS typename',
            ],
            'FROM' => 'glpi_cartridges',
            'INNER JOIN' => [
                'glpi_cartridgeitems' => [
                    'FKEY' => [
                        'glpi_cartridges' => 'cartridgeitems_id',
                        'glpi_cartridgeitems' => 'id',
                    ],
                ],
            ],
            'LEFT JOIN' => [
                'glpi_cartridgeitemtypes' => [
                    'FKEY' => [
                        'glpi_cartridgeitems' => 'cartridgeitemtypes_id',
                        'glpi_cartridgeitemtypes' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_cartridges.printers_id' => $instID,
                'glpi_cartridges.date_out' => ($old ? ['NOT' => null] : null),
            ],
            'ORDER' => [
                'glpi_cartridges.date_out ASC',
                'glpi_cartridges.date_use DESC',
                'glpi_cartridges.date_in',
            ],
        ];

        $result = $DB->request($query);
        $number = count($result);
        $pages  = $p->fields['init_pages_counter'];

        $pdf->setColumnsSize(100);
        $title = '<b>' . ($old ? __s('Worn cartridges') : __s('Used cartridges')) . '</b>';

        $stock_time       = 0;
        $use_time         = 0;
        $pages_printed    = 0;
        $nb_pages_printed = 0;

        if ($number === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            $title = sprintf(__s('%1$s: %2$s'), $title, $number);

            $pdf->displayTitle($title);

            if (!$old) {
                $pdf->setColumnsSize(5, 35, 30, 15, 15);
                $pdf->displayTitle(
                    '<b><i>' . __s('ID'),
                    __s('Cartridge model'),
                    __s('Cartridge type'),
                    __s('Add date'),
                    __s('Use date') .
                               '</b></i>',
                );
            } else {
                $pdf->setColumnsSize(4, 20, 20, 10, 10, 10, 13, 13);
                $pdf->displayTitle(
                    '<b><i>' . __s('ID'),
                    __s('Cartridge model'),
                    __s('Cartridge type'),
                    __s('Add date'),
                    __s('Use date'),
                    __s('End date'),
                    __s('Printer counter'),
                    __s('Printed pages') .
                               '</b></i>',
                );
            }

            foreach ($result as $data) {
                $date_in  = Html::convDate($data['date_in']);
                $date_use = Html::convDate($data['date_use']);
                $date_out = Html::convDate($data['date_out']);

                $col1 = $data['id'];
                $col2 = sprintf(__s('%1$s - %2$s'), $data['type'], $data['ref']);
                $col6 = $data['pages'];
                $col7 = '';

                $tmp_dbeg = explode('-', $data['date_in']);
                $tmp_dend = explode('-', $data['date_use']);

                $stock_time_tmp = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                                  - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
                $stock_time += $stock_time_tmp;

                if ($old) {
                    $tmp_dbeg = explode('-', $data['date_use']);
                    $tmp_dend = explode('-', $data['date_out']);

                    $use_time_tmp = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                                    - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
                    $use_time += $use_time_tmp;

                    if ($pages < $data['pages']) {
                        $pages_printed += $data['pages'] - $pages;
                        $nb_pages_printed++;
                        $col7 = sprintf(
                            __s('%1$s (%2$s)'),
                            $col6,
                            __s('%d printed pages'),
                            ($data['pages'] - $pages),
                        );
                        $pages = $data['pages'];
                    }
                }
                if (!$old) {
                    $pdf->displayLine($col1, $col2, $data['typename'], $date_in, $date_use);
                } else {
                    $pdf->displayLine(
                        $col1,
                        $col2,
                        $data['typename'],
                        $date_in,
                        $date_use,
                        $date_out,
                        $col6,
                        $col7,
                    );
                }
            } // Each cartridge
        }

        if ($old) {
            if ($number > 0) {
                if ($nb_pages_printed == 0) {
                    $nb_pages_printed = 1;
                }

                $time_stock = round($stock_time / $number / 60 / 60 / 24 / 30.5, 1);
                $time_use   = round($use_time / $number / 60 / 60 / 24 / 30.5, 1);
                $pdf->setColumnsSize(33, 33, 34);
                $pdf->displayTitle(
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Average time in stock') . '</i></b>',
                        sprintf(_sn('%d month', '%d months', (int) $time_stock), $time_stock),
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Average time in use') . '</i></b>',
                        sprintf(_sn('%d month', '%d months', (int) $time_use), $time_use),
                    ),
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Average number of printed pages') . '</i></b>',
                        round($pages_printed / $nb_pages_printed),
                    ),
                );
            }
            $pdf->displaySpace();
        }
    }

    public static function pdfForCartridgeItem(PluginPdfSimplePDF $pdf, CartridgeItem $cartitem, $state)
    {
        /** @var DBmysql $DB */
        global $DB;

        $tID = $cartitem->getField('id');
        if (!$cartitem->can($tID, READ)) {
            return false;
        }

        $where = ['glpi_cartridges.cartridgeitems_id' => $tID];
        $order = ['glpi_cartridges.date_use ASC',
            'glpi_cartridges.date_out DESC',
            'glpi_cartridges.date_in'];

        if ($state == 'new') {
            $where['glpi_cartridges.date_out'] = null;
            $where['glpi_cartridges.date_use'] = null;
            $order                             = ['glpi_cartridges.date_out ASC',
                'glpi_cartridges.date_use ASC',
                'glpi_cartridges.date_in'];
        } elseif ($state == 'used') {
            $where['glpi_cartridges.date_out'] = null;
            $where['NOT']                      = ['glpi_cartridges.date_use' => null];
        } else { //OLD
            $where['NOT'] = ['glpi_cartridges.date_out' => null];
        }

        $stock_time       = 0;
        $use_time         = 0;
        $pages_printed    = 0;
        $nb_pages_printed = 0;

        $iterator = $DB->request([
            'FROM' => Cartridge::getTable(),
            'SELECT' => [
                'glpi_cartridges.*',
                'glpi_printers.id AS printID',
                'glpi_printers.name AS printname',
                'glpi_printers.init_pages_counter',
            ],
            'LEFT JOIN' => [
                'glpi_printers' => [
                    'FKEY' => [
                        Cartridge::getTable() => 'printers_id',
                        'glpi_printers' => 'id',
                    ],
                ],
            ],
            'WHERE' => $where,
            'ORDER' => $order,
        ]);

        $number = count($iterator);

        $pages = [];

        if ($number !== 0) {
            if ($state == 'new') {
                $pdf->setColumnsSize(25, 25, 25, 25);
                $pdf->displayTitle(
                    '<b><i>' . __s('Total') . '</i></b>',
                    '<b><i>' . Cartridge::getTotalNumber($tID) . '</i></b>',
                    '<b><i>' . sprintf(
                        __s('%1$s %2$s'),
                        _sn('Cartridge', 'Cartridges', $number),
                        _nx('cartridge', 'New', 'New', $number),
                    ) . '</i></b>',
                    '<b><i>' . Cartridge::getUnusedNumber($tID) . '</i></b>',
                );
                $pdf->displayTitle(
                    '<b><i>' . __s('Used cartridges') . '</i></b>',
                    '<b><i>' . Cartridge::getUsedNumber($tID),
                    '<b><i>' . __s('Worn cartridges') . '</i></b>',
                    '<b><i>' . Cartridge::getOldNumber($tID),
                );

                $pdf->setColumnsSize(100);
                $pdf->displayTitle('<b>' . sprintf(
                    __s('%1$s %2$s'),
                    _sn('Cartridge', 'Cartridges', $number),
                    _nx('cartridge', 'New', 'New', $number),
                ) . '</b>');
            } elseif ($state == 'used') {
                $pdf->setColumnsSize(100);
                $pdf->displayTitle('<b>' . __s('Used cartridges') . '</b>');
            } else { // Old
                $pdf->setColumnsSize(100);
                $pdf->displayTitle('<b>' . __s('Worn cartridges') . '</b>');
            }

            if ($state != 'old') {
                $pdf->setColumnsSize(5, 20, 20, 20, 35);
                $pdf->displayLine(
                    '<b>' . __s('ID') . '</b>',
                    '<b>' . _x('item', 'State') . '</b>',
                    '<b>' . __s('Add date') . '</b>',
                    '<b>' . __s('Use date') . '</b>',
                    '<b>' . __s('Used on') . '</b>',
                );
            } else {
                $pdf->setColumnsSize(5, 20, 15, 15, 15, 15, 15);
                $pdf->displayLine(
                    '<b>' . __s('ID') . '</b>',
                    '<b>' . _x('item', 'State') . '</b>',
                    '<b>' . __s('Add date') . '</b>',
                    '<b>' . __s('Use date') . '</b>',
                    '<b>' . __s('Used on') . '</b>',
                    '<b>' . __s('End date') . '</b>',
                    '<b>' . __s('Printer counter') . '</b>',
                );
            }

            foreach ($iterator as $data) {
                $date_in  = Html::convDate($data['date_in']);
                $date_use = Html::convDate($data['date_use']);
                $date_out = Html::convDate($data['date_out']);
                $printer  = $data['printers_id'];

                if (!is_null($date_use)) {
                    $tmp_dbeg       = explode('-', $data['date_in']);
                    $tmp_dend       = explode('-', $data['date_use']);
                    $stock_time_tmp = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                                      - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
                    $stock_time += $stock_time_tmp;
                }
                $pdfpages = '';
                if ($state == 'old') {
                    $tmp_dbeg     = explode('-', $data['date_use']);
                    $tmp_dend     = explode('-', $data['date_out']);
                    $use_time_tmp = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                    - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
                    $use_time += $use_time_tmp;

                    // Get initial counter page
                    if (!isset($pages[$printer])) {
                        $pages[$printer] = $data['init_pages_counter'];
                    }
                    if ($pages[$printer] < $data['pages']) {
                        $pages_printed += $data['pages'] - $pages[$printer];
                        $nb_pages_printed++;
                        $pp              = $data['pages'] - $pages[$printer];
                        $pdfpages        = sprintf(_sn('%d printed page', '%d printed pages', $pp), $pp);
                        $pages[$printer] = $data['pages'];
                    } elseif ($data['pages'] != 0) {
                        $pdfpages = __s('Counter error');
                    }
                }
                $pdf->displayLine(
                    $data['id'],
                    Cartridge::getStatus($data['date_use'], $data['date_out']),
                    $date_in,
                    $date_use,
                    $data['printname'],
                    $date_out,
                    $pdfpages,
                );
            }

            if ($state == 'old') {
                if ($nb_pages_printed == 0) {
                    $nb_pages_printed = 1;
                }
                $pdf->setColumnsSize(33, 33, 34);
                $pdf->displayLine(
                    '<b>' . __s('Average time in stock') . '</b>',
                    '<b>' . __s('Average time in use') . '</b>',
                    '<b>' . __s('Average number of printed pages') . '</b>',
                );

                $pdf->displayLine(
                    round($stock_time / $number / 60 / 60 / 24 / 30.5, 1) . ' ' . __s('month'),
                    round($use_time / $number / 60 / 60 / 24 / 30.5, 1) . ' ' . __s('month'),
                    round($pages_printed / $nb_pages_printed),
                );
            }
            $pdf->displaySpace();
        }
    }
}

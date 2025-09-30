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

class PluginPdfComputerAntivirus extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new ItemAntivirus());
    }

    public static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID = $item->getField('id');

        $result = $DB->request(['FROM' => 'glpi_itemantiviruses'] + ['computers_id' => $ID,
            'is_deleted'                                                   => 0]);
        $number = count($result);

        $pdf->setColumnsSize(100);
        $title = '<b>' . __s('Antivirus') . '</b>';

        if ($number === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            if ($number > $_SESSION['glpilist_limit']) {
                $title = sprintf(__s('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'] . ' / ' . $number);
            } else {
                $title = sprintf(__s('%1$s: %2$s'), $title, $number);
            }
            $pdf->displayTitle($title);

            $pdf->setColumnsSize(25, 20, 15, 15, 5, 5, 15);
            $pdf->displayTitle(
                __s('Name'),
                __s('Manufacturer'),
                __s('Antivirus version'),
                __s('Signature database version'),
                __s('Active'),
                __s('Up to date'),
                __s('Expiration date'),
            );

            $antivirus = new ItemAntivirus();
            foreach ($result as $data) {
                $pdf->displayLine(
                    $data['name'],
                    Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_manufacturers',
                        $data['manufacturers_id'],
                    )),
                    $data['antivirus_version'],
                    $data['signature_version'],
                    Dropdown::getYesNo($data['is_active']),
                    Dropdown::getYesNo($data['is_uptodate']),
                    Html::convDate($data['date_expiration']),
                );
            }
        }
    }
}

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

class PluginPdfChangeTask extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new ChangeTask());
    }

    public static function pdfForChange(PluginPdfSimplePDF $pdf, Change $job)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $ID = $job->getField('id');

        $result = $DB->request([
            'FROM'      => 'glpi_changetasks',
            'WHERE'     => ['changes_id' => $ID],
            'ORDER'     => 'date DESC',
        ]);

        $number = count($result);

        $pdf->setColumnsSize(100);
        $title = '<b>' . ChangeTask::getTypeName(2) . '</b>';
        if ($number === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            $title = sprintf(__s('%1$s: %2$s'), $title, $number);
            $pdf->displayTitle($title);

            foreach ($result as $data) {
                $pdf->setColumnsSize(20, 20, 20, 20, 20);
                $pdf->displayTitle(
                    '<i>' . __s('Type'),
                    __s('Date'),
                    __s('Duration'),
                    __s('Writer'),
                    __s('Planning') . '</i>',
                );

                $actiontime    = Html::timestampToString($data['actiontime'], false);
                $planification = '';
                if (empty($data['begin'])) {
                    if (isset($data['state'])) {
                        $planification = Planning::getState($data['state']) . '<br>';
                    }
                } else {
                    if (isset($data['state']) && $data['state']) {
                        $planification = sprintf(
                            __s('%1$s: %2$s'),
                            _x('item', 'State'),
                            Planning::getState($data['state']),
                        );
                    }
                    $planification .= '<br>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('Begin'),
                        Html::convDateTime($data['begin']),
                    );
                    $planification .= '<br>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('End'),
                        Html::convDateTime($data['end']),
                    );
                    $planification .= '<br>' . sprintf(
                        __s('%1$s: %2$s'),
                        __s('By'),
                        $dbu->getUserName($data['users_id_tech']),
                    );
                }

                $lib = $data['taskcategories_id'] ? Dropdown::getDropdownName('glpi_taskcategories', $data['taskcategories_id']) : '';

                $pdf->displayLine(
                    '</b>' . Toolbox::stripTags($lib),
                    Html::convDateTime($data['date']),
                    Html::timestampToString($data['actiontime'], false),
                    Toolbox::stripTags($dbu->getUserName($data['users_id'])),
                    Toolbox::stripTags($planification),
                    1,
                );
                $pdf->displayText(
                    '<b><i>' . sprintf(__s('%1$s: %2$s') . '</i></b>', __s('Description'), ''),
                    $data['content'],
                    1,
                );
            }
        }

        $pdf->displaySpace();
    }
}

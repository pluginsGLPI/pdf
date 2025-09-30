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

class PluginPdfTicketSatisfaction extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new TicketSatisfaction());
    }

    public static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $ticket)
    {
        $survey = new TicketSatisfaction();

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . __s('Satisfaction survey') . '</b>');
        if (!$survey->getFromDB($ticket->getID())) {
            $pdf->displayLine(__s('No generated survey'));
        } elseif ($survey->getField('type') == 2) {
            $url = Entity::generateLinkSatisfaction($ticket);
            $pdf->displayLine(sprintf(__s('%1$s (%2$s)'), __s('External survey'), $url));
        } elseif ($survey->getField('date_answered')) {
            $sat    = $survey->getField('satisfaction');
            $tabsat = [0 => __s('None'),
                1        => __s('1 star', 'pdf'),
                2        => __s('2 stars', 'pdf'),
                3        => __s('3 stars', 'pdf'),
                4        => __s('4 stars', 'pdf'),
                5        => __s('5 stars', 'pdf')];
            if (isset($tabsat[$sat])) {
                $sat = $tabsat[$sat] . "  ($sat/5)";
            }
            $pdf->displayLine('<b>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Response date to the satisfaction survey') . '</b>',
                Html::convDateTime($survey->getField('date_answered')),
            ));
            $pdf->displayLine('<b>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Satisfaction with the resolution of the ticket') . '</b>',
                $sat,
            ));
            $pdf->displayText(
                '<b>' . sprintf(__s('%1$s: %2$s'), __s('Comments') . '</b>', ''),
                $survey->getField('comment'),
            );
        } else {   // No answer
            $pdf->displayLine(sprintf(
                __s('%1$s: %2$s'),
                __s('Creation date of the satisfaction survey'),
                Html::convDateTime($survey->getField('date_begin')),
            ));
            $pdf->displayLine(__s('No answer', 'pdf'));
        }
        $pdf->displaySpace();
    }
}

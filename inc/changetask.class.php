<?php

/**
 *  * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
 *  -------------------------------------------------------------------------
 *  pdf - Export to PDF plugin for GLPI
 *  Copyright (C) 2003-2011 by the pdf Development Team.
 *
 *  https://forge.indepnet.net/projects/pdf
 *  -------------------------------------------------------------------------
 *
 *  LICENSE
 *
 *  This file is part of pdf.
 *
 *  pdf is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  pdf is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with pdf. If not, see <http://www.gnu.org/licenses/>.
 *  --------------------------------------------------------------------------
 */

class PluginPdfChangeTask extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ChangeTask());
   }


   static function pdfForChange(PluginPdfSimplePDF $pdf, Change $job) {
      global $DB;

      $dbu = new DbUtils();

      $ID = $job->getField('id');

      $result = $DB->request('glpi_changetasks',
                             ['WHERE'  => ['changes_id' => $ID],
                              'ORDER'  => 'date DESC']);

      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = "<b>".ChangeTask::getTypeName(2)."</b>";
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, $number);
         $pdf->displayTitle($title);

         foreach ($result as $data) {
            $pdf->setColumnsSize(20,20,20,20,20);
            $pdf->displayTitle("<i>".__('Type'), __('Date'), __('Duration'), __('Writer'),
                                     __('Planning')."</i>");

            $actiontime = Html::timestampToString($data['actiontime'], false);
            $planification = '';
            if (empty($data['begin'])) {
               if (isset($data["state"])) {
                  $planification = Planning::getState($data["state"])."<br>";
               }
            } else {
               if (isset($data["state"]) && $data["state"]) {
                  $planification = sprintf(__('%1$s: %2$s'), _x('item', 'State'),
                                           Planning::getState($data["state"]));
               }
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('Begin'),
                                                Html::convDateTime($data["begin"]));
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('End'),
                                                Html::convDateTime($data["end"]));
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('By'),
                                                $dbu->getUserName($data["users_id_tech"]));
                           }

            if ($data['taskcategories_id']) {
               $lib = Dropdown::getDropdownName('glpi_taskcategories', $data['taskcategories_id']);
            } else {
               $lib = '';
            }

            $pdf->displayLine("</b>".Toolbox::stripTags($lib),
                              Html::convDateTime($data["date"]),
                              Html::timestampToString($data["actiontime"], 0),
                              Toolbox::stripTags($dbu->getUserName($data["users_id"])),
                              Toolbox::stripTags($planification),1);
            $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s')."</i></b>", __('Description'), ''),
                                               $data["content"], 1);
         }
      }

      $pdf->displaySpace();
   }
}
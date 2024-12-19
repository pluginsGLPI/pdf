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

class PluginPdfTicketTask extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new TicketTask());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $job, $private) {
      global $DB;

      $dbu = new DbUtils();

      $ID  = $job->getField('id');

      //////////////Tasks///////////

      $query = ['FROM'  => 'glpi_tickettasks',
                'WHERE' => ['tickets_id' => $ID],
                'ORDER' => 'date DESC'];

      if (!$private) {
         // Don't show private'
         $query['WHERE']['is_private'] = 0;
      } else if (!Session::haveRight('task', TicketTask::SEEPRIVATE)) {
         // No right, only show connected user private one
         $query['WHERE']['OR'] = ['is_private'    => 0,
                                  'users_id'      => Session::getLoginUserID(),
                                  'users_id_tech' => Session::getLoginUserID()];
      }

      $result = $DB->request($query);

      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'.TicketTask::getTypeName($number).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s (%2$s)'), $title, $_SESSION['glpilist_limit']."/".$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(20,20,20,20,20);
         $pdf->displayTitle("<i>".__('Type'), __('Date'), __('Duration'), __('Writer'),
               __('Planning')."</i>");


         foreach ($result as $data) {

            $actiontime = Html::timestampToString($data['actiontime'], false);
            $planification = '';
            if (isset($data["state"])) {
               $planification = sprintf(__('%1$s: %2$s'), _x('item', 'State'),
                                           Planning::getState($data["state"]));
            }
            if (!empty($data['begin'])) {
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('Begin'),
                                                Html::convDateTime($data["begin"]));
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('End'),
                                                Html::convDateTime($data["end"]));
            }
            if ($data['users_id_tech'] > 0) {
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('By user', 'pdf'),
                                                $dbu->getUserName($data["users_id_tech"]));
            }
            if ($data['groups_id_tech'] > 0) {
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('By group', 'pdf'),
                                                Dropdown::getDropdownName('glpi_groups',
                                                                            $data["groups_id_tech"]));
            }
            if ($data['taskcategories_id']) {
               $lib = Dropdown::getDropdownName('glpi_taskcategories',  $data['taskcategories_id']);
            } else {
               $lib = '';
            }
            if ($data['is_private']) {
               $lib = sprintf(__('%1$s (%2$s)'), $lib, __('Private'));
            }

            $pdf->displayLine("</b>".Toolbox::stripTags($lib),
                              Html::convDateTime($data["date"]),
                              Html::timestampToString($data["actiontime"], 0),
                              Toolbox::stripTags($dbu->getUserName($data["users_id"])),
                              $planification);
            $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s')."</i></b>", __('Description'), ''),
                                               '<br />'.$data["content"], 1);
         }
      }

      $pdf->displaySpace();
   }
}
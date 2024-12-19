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

class PluginPdfLog extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Log());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {

      // Get the Full history for the item (really a good idea ?, should we limit this)
      $changes = Log::getHistoryData($item);
      $number  = count($changes);

      $pdf->setColumnsSize(100);
      $title = "<b>".__('Historical')."</b>";

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(10,15,24,11,40);
         $pdf->displayTitle('<b><i>'.__('ID'), __('Date'), __('User'), __('Field'),
                            _x('name', 'Update').'</i></b>');

         $tot = 0;
         foreach ($changes as $data) {
            if ($data['display_history'] && ($tot < $_SESSION['glpilist_limit'])) {
               $pdf->displayLine($data['id'], $data['date_mod'], $data['user_name'], $data['field'],
                                 Toolbox::stripTags($data['change']));
               $tot++;
            }
         } // Each log
      }
      $pdf->displaySpace();
   }
}
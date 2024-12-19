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

class PluginPdfItem_Knowbaseitem extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_Disk());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB;

      $ID = $item->getField('id');

      $result = $DB->request('glpi_knowbaseitems',
                             ['SELECT'    => ['glpi_knowbaseitems.*',
                                              'glpi_knowbaseitems_items.itemtype',
                                              'glpi_knowbaseitems_items.items_id'],
                              'LEFT JOIN' => ['glpi_knowbaseitems_items'
                                              => ['FKEY' => ['glpi_knowbaseitems_items' => 'knowbaseitems_id',
                                                             'glpi_knowbaseitems'       => 'id']]],
                              'WHERE'     => ['items_id'   => $ID,
                                              'itemtype'   => $item->getType()]]);
      $number = count($result);

      $pdf->setColumnsSize(100);

      if (!$number) {
         $pdf->displayTitle("<b>".__('No knowledge base entries linked')."</b>");
      } else {
         $title = "<b>".__('Link a knowledge base entry')."</b>";
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(40,40,10,10);
         $pdf->displayTitle(__('Type'), __('Item'), __('Creation date'), __('Update date'));

         foreach ($result as $data) {
            $pdf->displayLine(__('Knowledge base'),
                              $data['name'],
                              Html::convDateTime($data['date_creation']),
                              Html::convDateTime($data['date_mod']));
         }
      }
      $pdf->displaySpace();
   }
}
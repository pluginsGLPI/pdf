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

class PluginPdfDomain_Item extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Domain_Item());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $ID   = $item->getField('id');

      $query = ['SELECT'     => ['glpi_domains.*',
                                 'glpi_domains_items.domainrelations_id'],
                'FROM'       => 'glpi_domains',
                'INNER JOIN' => ['glpi_domains_items'
                                 => ['FKEY' => ['glpi_domains'       => 'id',
                                                'glpi_domains_items' => 'domains_id']]],
                'WHERE'      => ['glpi_domains_items.itemtype' => $item->getType(),
                                 'glpi_domains_items.items_id' => $ID],
                'ORDER'      => 'glpi_domains.name'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'.Domain::getTypeName($number).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(17,15,10,10,8,8,16,16);
         $pdf->displayTitle(__('Name'), __('Entity'), __('Group in charge'), __('Technician in charge'),
                            __('Type'), __('Domain relation'), __('Creation date'),
                            __('Expiration date'));

         foreach ($result as $data) {
            $pdf->displayLine($data["name"],
                              Dropdown::getDropdownName("glpi_entities", $data["entities_id"]),
                              Dropdown::getDropdownName("glpi_groups", $data["groups_id_tech"]),
                              getUserName($data["users_id_tech"]),
                              Dropdown::getDropdownName("glpi_domaintypes", $data["domaintypes_id"]),
                              Dropdown::getDropdownName("glpi_domainrelations", $data["domainrelations_id"]),
                              Html::convDate($data["date_creation"]),
                              Html::convDate($data["date_expiration"]));
         }
      }
      $pdf->displaySpace();
   }

}
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

class PluginPdfTicket_Contract extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Ticket_Contract());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $type       = $item->getType();
      $ID         = $item->getField('id');
      $itemtable  = getTableForItemType($type);
      $con        = new Contract();
      $dbu        = new DbUtils();

     $query = ['SELECT'    => ['glpi_tickets_contracts.*', 'glpi_contracts.*'],
               'FROM'      => 'glpi_tickets_contracts',
               'LEFT JOIN' => ['glpi_contracts'
                               => ['FKEY' => ['glpi_contracts' => 'id',
                                             'glpi_tickets_contracts' => 'contracts_id']]],
               'WHERE'    => ['glpi_tickets_contracts.tickets_id' => $ID ]
                              + $dbu->getEntitiesRestrictCriteria('glpi_contracts','','',true),
               'ORDER'    => 'glpi_contracts.name'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Associated contract', 'Associated contracts', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, $number));

         $pdf->setColumnsSize(19,19,15,10,16,11,10);
         $pdf->displayTitle(__('Name'), __('Entity'), _x('phone', 'Number'), __('Contract type'),
                            __('Supplier'), __('Start date'), __('Initial contract period'));

         foreach ($result as $row) {
            $cID     = $row['contracts_id'];
            $assocID = $row['id'];

            if ($con->getFromDB($cID)) {
               $textduration = '';
               if ($con->fields['duration'] > 0) {
                  $textduration = sprintf(__('Valid to %s'),
                                          Infocom::getWarrantyExpir($con->fields["begin_date"],
                                                                    $con->fields["duration"]));
               }
               $pdf->displayLine(
                  (empty($con->fields["name"]) ? "(".$con->fields["id"].")" : $con->fields["name"]),
                  Dropdown::getDropdownName("glpi_entities", $con->fields["entities_id"]),
                  $con->fields["num"],
                  Toolbox::stripTags(Dropdown::getDropdownName("glpi_contracttypes",
                                                               $con->fields["contracttypes_id"])),
                  str_replace("<br>", " ", $con->getSuppliersNames()),
                  Html::convDate($con->fields["begin_date"]),
                  sprintf(__('%1$s - %2$s'),
                          sprintf(_n('%d month', '%d months', $con->fields["duration"]),
                                  $con->fields["duration"]),
                          $textduration));
            }
         }
      }
      $pdf->displaySpace();
   }

}
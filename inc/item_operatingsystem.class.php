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

class PluginPdfItem_OperatingSystem extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_OperatingSystem());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, $item) {
      global $DB;


      $instID = $item->fields['id'];
      $type   = $item->getType();

      if (!$item->can($instID, READ)) {
         return false;
      }

      $query = ['SELECT'    => ['glpi_items_operatingsystems.*',
                                'glpi_operatingsystemversions.name',
                                'glpi_operatingsystemarchitectures.name',
                                'glpi_operatingsystemservicepacks.name',
                                'glpi_operatingsystemkernelversions.name',
                                'glpi_operatingsystemeditions.name'],
                'FROM'      => 'glpi_items_operatingsystems',
                'LEFT JOIN' => ['glpi_operatingsystems'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystems_id',
                                                'glpi_operatingsystems'       => 'id']],
                                'glpi_operatingsystemservicepacks'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystemservicepacks_id',
                                                'glpi_operatingsystemservicepacks' => 'id']],
                                 'glpi_operatingsystemarchitectures'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystemarchitectures_id',
                                                'glpi_operatingsystemarchitectures' => 'id']],
                                 'glpi_operatingsystemversions'
                                 => ['FKEY' => ['glpi_items_operatingsystems'  => 'operatingsystemversions_id',
                                                'glpi_operatingsystemversions' => 'id']],
                                 'glpi_operatingsystemkernelversions'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystemkernelversions_id',
                                                'glpi_operatingsystemkernelversions' => 'id']],
                                 'glpi_operatingsystemeditions'
                                 => ['FKEY' => ['glpi_items_operatingsystems'  => 'operatingsystemeditions_id',
                                                'glpi_operatingsystemeditions' => 'id']]],
                'WHERE'     => ['items_id' => $instID,
                                'itemtype' => $type],
                'ORDER'     => 'glpi_items_operatingsystems.id'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'.__('Operating system').'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(17,10,14,15,10,10,12,12);
         $pdf->displayTitle(__('Name'), __('Version'), __('Architecture'), __('Service pack'),
                            __('Kernel'), __('Edition'), __('Product ID'), __('Serial number'));

      }

      foreach ($result as $data) {
         $pdf->displayLine(Dropdown::getDropdownName('glpi_operatingsystems', $data['operatingsystems_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemversions',
                                                     $data['operatingsystemversions_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemarchitectures',
                                                     $data['operatingsystemarchitectures_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemservicepacks',
                                                     $data['operatingsystemservicepacks_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemkernelversions',
                                                     $data['operatingsystemkernelversions_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemeditions',
                                                     $data['operatingsystemeditions_id']),
                           $data['licenseid'], $data['license_number']);
      }
      $pdf->displaySpace();
   }
}
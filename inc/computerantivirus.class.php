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

class PluginPdfComputerAntivirus extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ComputerAntivirus());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $item) {
      global $DB;

      $ID = $item->getField('id');

      $result = $DB->request('glpi_computerantiviruses', ['computers_id' => $ID,
                                                          'is_deleted'   => 0]);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = "<b>".__('Antivirus')."</b>";

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
                  $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(25,20,15,15,5,5,15);
         $pdf->displayTitle(__('Name'), __('Manufacturer'), __('Antivirus version'),
                            __('Signature database version'), __('Active'),__('Up to date'),
                            __('Expiration date'));

         $antivirus = new ComputerAntivirus();
         foreach($result as $data) {
            $pdf->displayLine($data['name'],
                              Toolbox::stripTags(Dropdown::getDropdownName('glpi_manufacturers',
                                                                           $data['manufacturers_id'])),
                              $data['antivirus_version'],
                              $data['signature_version'],
                              Dropdown::getYesNo($data['is_active']),
                              Dropdown::getYesNo($data['is_uptodate']),
                              Html::convDate($data['date_expiration']));
         }
      }

   }
}
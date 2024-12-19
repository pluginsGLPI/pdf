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

class PluginPdfNetworkEquipment extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new NetworkEquipment());
   }


   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['NetworkName$1']);
      unset($onglets['Certificate_Item$1']);
      unset($onglets['Impact$1']);
      unset($onglets['Appliance_Item$1']);
      unset($onglets['Glpi\Socket$1']);
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, NetworkEquipment $item) {

      $dbu = new DbUtils();

      PluginPdfCommon::mainTitle($pdf, $item);

      PluginPdfCommon::mainLine($pdf, $item, 'name-status');
      PluginPdfCommon::mainLine($pdf, $item, 'location-type');
      PluginPdfCommon::mainLine($pdf, $item, 'tech-manufacturer');
      PluginPdfCommon::mainLine($pdf, $item, 'group-model');
      PluginPdfCommon::mainLine($pdf, $item, 'contactnum-serial');
      PluginPdfCommon::mainLine($pdf, $item, 'contact-otherserial');



      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('User').'</i></b>',
                          $dbu->getUserName($item->fields['users_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_networks',
                                                                       $item->fields['networks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
         '<b><i>'.__('The MAC address and the IP of the equipment are included in an aggregated network port'),
         '<b><i>'.sprintf(__('%1$s: %2$s'),
                          sprintf(__('%1$s (%2$s)'), __('Memory'),__('Mio')).'</i></b>',
                                  $item->fields['ram']));

      $pdf->setColumnsSize(100);
      PluginPdfCommon::mainLine($pdf, $item, 'comment');

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         default :
            return false;
      }
      return true;
   }
}
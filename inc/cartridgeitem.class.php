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

class PluginPdfCartridgeItem extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new CartridgeItem());
   }


   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, CartridgeItem $cartitem){

      $dbu = new DbUtils();

      PluginPdfCommon::mainTitle($pdf, $cartitem);

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Name').'</i></b>', $cartitem->fields['name']),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Location').'</i></b>',
                             Toolbox::stripTags(Dropdown::getDropdownName('glpi_locations',
                                                              $cartitem->fields['locations_id']))));
      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Type').'</i></b>',
                             Toolbox::stripTags(Dropdown::getDropdownName('glpi_cartridgeitemtypes',
                                                      $cartitem->fields['cartridgeitemtypes_id']))),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Reference').'</i></b>', $cartitem->fields['ref']));


      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Technician in charge of the hardware').'</i></b>',
                                              $dbu->getUserName($cartitem->fields['users_id_tech'])),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Manufacturer').'</i></b>',
                             Toolbox::stripTags(Dropdown::getDropdownName('glpi_manufacturers',
                                                           $cartitem->fields['manufacturers_id']))));
      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'),  __('Group in charge of the hardware').'</i></b>',
                             Dropdown::getDropdownName('glpi_groups',
                                                       $cartitem->fields['groups_id_tech'])));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Stock location').'</i></b>',
                             Dropdown::getDropdownName('glpi_locations',
                                                       $cartitem->fields['locations_id'])),
            '<b><i>'.sprintf(__('%1$s: %2$s'),  __('Alert threshold').'</i></b>',
                             $cartitem->getField('alarm_threshold')));

      PluginPdfCommon::mainLine($pdf, $cartitem, 'comment');

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'Cartridge$1' :
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'new');
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'used');
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'old');
            break;

         case 'CartridgeItem_PrinterModel$1' :
            self::pdfForPrinterModel($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }


   static function pdfForPrinterModel(PluginPdfSimplePDF $pdf, CartridgeItem $item) {

      $instID = $item->getField('id');
      if (!$item->can($instID, READ)) {
         return false;
      }

      $iterator = CartridgeItem_PrinterModel::getListForItem($item);
      $number = count($iterator);

      foreach ($iterator as $data) {
         $datas[$data["linkid"]]  = $data;
      }

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Printer model', 'Printer models', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(_('No printel model associated', 'pdf'));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         foreach ($datas as $data) {
            $pdf->displayLine($data['name']);
         }
      }
   }
}
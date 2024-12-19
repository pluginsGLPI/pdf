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

class PluginPdfDocument extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Document());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $ID   = $item->getField('id');
      $type = get_class($item);

      $result = $DB->request('glpi_documents_items',
                             ['SELECT'    => ['glpi_documents_items.id',
                                              'glpi_documents_items.date_mod',
                                              'glpi_documents.*', 'glpi_entities.id',
                                              'completename'],
                              'LEFT JOIN' => ['glpi_documents'
                                                => ['FKEY' => ['glpi_documents_items' => 'documents_id',
                                                               'glpi_documents'       => 'id']],
                                              'glpi_entities'
                                                => ['FKEY' => ['glpi_documents' => 'entities_id',
                                                               'glpi_entities'  => 'id']]],
                              'WHERE'     => ['items_id' => $ID,
                                              'itemtype' => $type]], true);

       $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Document', 'Documents', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(20,15,10,10,10,8,20,7);
         $pdf->displayTitle(__('Name'), __('Entity'), __('File'), __('Web link'), __('Heading'),
                            __('MIME type'), __('Tag'), __('Date'));
         foreach ($result as $data) {
            if (empty($data["link"])) {
                $data["link"] = '';   
            }            
            $pdf->displayLine($data["name"], $data['completename'], basename($data["filename"]),
                              $data["link"], Dropdown::getDropdownName("glpi_documentcategories",
                                                                       $data["documentcategories_id"]),
                              $data["mime"],
                              !empty($data["tag"]) ? Document::getImageTag($data["tag"]) : '',
                              Html::convDateTime($data["date_mod"]));
         }
      }
      $pdf->displaySpace();
   }
}
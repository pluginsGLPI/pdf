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

define('GLPI_KEEP_CSRF_TOKEN', true); // 0.90
$token = (isset($_POST['_glpi_csrf_token']) ? $_POST['_glpi_csrf_token'] : false);

include ("../../../inc/includes.php");

/* 0.85 Hack to allow multiple exports, yes this is an hack, yes an awful one */
if (!isset($_SESSION['glpicsrftokens'][$token])) {
   $_SESSION['glpicsrftokens'][$token] = time() + GLPI_CSRF_EXPIRES;
}

Plugin::load('pdf', true);

$dbu = new DbUtils();

if (isset($_POST["plugin_pdf_inventory_type"])
    && ($item = $dbu->getItemForItemtype($_POST["plugin_pdf_inventory_type"]))
    && isset($_POST["itemID"])) {

   $type = $_POST["plugin_pdf_inventory_type"];
   $item->check($_POST["itemID"], READ);

   if (isset($_SESSION["plugin_pdf"][$type])) {
      unset($_SESSION["plugin_pdf"][$type]);
   }

   $tab = [];

   if (isset($_POST['item'])) {
      foreach ($_POST['item'] as $key => $val) {
         if (!in_array($key, $tab)) {
            $tab[] = $_SESSION["plugin_pdf"][$type][] = $key;
         }
      }
   }
   if (empty($tab)) {
      $tab[] = $type.'$main';
   }

   if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])
       && class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {

      $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
      $itempdf->generatePDF([$_POST["itemID"]], $tab, (isset($_POST["page"]) ? $_POST["page"] : 0));
   } else {
      die("Missing hook");
   }
} else {
   die("Missing context");
}
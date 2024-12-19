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

include ("../../../inc/includes.php");

Plugin::load('pdf', true);

$type = $_SESSION["plugin_pdf"]["type"];
$item = new $type();

$tab_id = unserialize($_SESSION["plugin_pdf"]["tab_id"]);
unset($_SESSION["plugin_pdf"]["tab_id"]);

$result = $DB->request('glpi_plugin_pdf_preferences',
                       ['SELECT' => 'tabref',
                        'WHERE'  => ['users_ID' => $_SESSION['glpiID'],
                                     'itemtype' => $type]]);

$tab = [];

foreach ($result as $data) {
   if ($data["tabref"] == 'landscape') {
      $pag = 1;
   } else {
      $tab[]= $data["tabref"];
   }
}
   if (empty($tab)) {
      $tab[] = $type.'$main';
   }

if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {

   $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
   $itempdf->generatePDF($tab_id, $tab, (isset($pag) ? $pag : 0));
} else {
   die("Missing hook");
}
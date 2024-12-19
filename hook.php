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

function plugin_pdf_postinit() {
   global $CFG_GLPI, $PLUGIN_HOOKS;

   foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $typepdf) {
      CommonGLPI::registerStandardTab($type, $typepdf);
   }
}


function plugin_pdf_MassiveActions($type) {
   global $PLUGIN_HOOKS;

   switch ($type) {
      default :
         if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
            return ['PluginPdfCommon'.MassiveAction::CLASS_ACTION_SEPARATOR.'DoIt'
                     => __('Print to pdf', 'pdf')];
         }
   }
   return [];
}


function plugin_pdf_install() {
   global $DB;

   $migration = new Migration('3.0.0');

   include_once(Plugin::getPhpDir('pdf')."/inc/profile.class.php");
   PluginPdfProfile::install($migration);

   include_once(Plugin::getPhpDir('pdf')."/inc/preference.class.php");
   PluginPdfPreference::install($migration);

   include_once(Plugin::getPhpDir('pdf')."/inc/config.class.php");
   PluginPdfConfig::install($migration);

   $migration->executeMigration();

   return true;
}


function plugin_pdf_uninstall() {
   global $DB;

   $migration = new Migration('3.0.0');
   
   $tables = ['glpi_plugin_pdf_configs',
              'glpi_plugin_pdf_preferences'];
   
   foreach($tables as $table) {
       $migration->dropTable($table);
   }

   //Delete rights associated with the plugin
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'plugin_pdf'";
   $DB->queryOrDie($query, $DB->error());

   $migration->executeMigration();

   return true;
}


/**
 * @since version 1.0.2
**/
function plugin_pdf_registerMethods() {
   global $WEBSERVICES_METHOD;

   $WEBSERVICES_METHOD['pdf.getTabs']  = ['PluginPdfRemote', 'methodGetTabs'];
   $WEBSERVICES_METHOD['pdf.getPdf']   = ['PluginPdfRemote', 'methodGetPdf'];
}


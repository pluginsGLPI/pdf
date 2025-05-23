<?php

/**
 *  -------------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of PDF plugin for GLPI.
 *
 *  PDF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PDF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nelly Mahu-Lasson, Remi Collet, Teclib
 * @copyright Copyright (c) 2009-2022 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

include_once('../../../inc/includes.php');

Session::checkLoginUser();

/** @var \DBmysql $DB */
global $DB;

//Save user preferences
if (isset($_POST['plugin_pdf_user_preferences_save'])
    && isset($_POST['plugin_pdf_inventory_type'])) {
    $DB->doQuery("DELETE
               FROM `glpi_plugin_pdf_preferences`
               WHERE `users_id` ='" . $_SESSION['glpiID'] . "'
                     AND `itemtype`='" . $_POST['plugin_pdf_inventory_type'] . "'");

    if (isset($_POST['item'])) {
        foreach ($_POST['item'] as $key => $val) {
            $DB->doQuery("INSERT INTO `glpi_plugin_pdf_preferences`
                            (`id` ,`users_id` ,`itemtype` ,`tabref`)
                     VALUES (NULL , '" . $_SESSION['glpiID'] . "',
                             '" . $_POST['plugin_pdf_inventory_type'] . "', '$key')");
        }
    }
    if (isset($_POST['page']) && $_POST['page']) {
        $DB->doQuery("INSERT INTO `glpi_plugin_pdf_preferences`
                         (`id` ,`users_id` ,`itemtype` ,`tabref`)
                  VALUES (NULL , '" . $_SESSION['glpiID'] . "',
                          '" . $_POST['plugin_pdf_inventory_type'] . "', 'landscape')");
    }
    Html::back();
} else {
    Html::redirect('../../../front/preference.php');
}

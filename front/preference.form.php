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

include_once(__DIR__ . '/../../../inc/includes.php');

Session::checkRight('plugin_pdf', READ);

//Save user preferences
if (isset($_POST['plugin_pdf_user_preferences_save'])
    && isset($_POST['plugin_pdf_inventory_type'])) {
    $itemtype = (string) $_POST['plugin_pdf_inventory_type'];
    $users_id = (int) $_SESSION['glpiID'];

    $pref = new PluginPdfPreference();
    $pref->deleteByCriteria([
        'users_id' => $users_id,
        'itemtype' => $itemtype,
    ], force: true);

    if (isset($_POST['item'])) {
        foreach ($_POST['item'] as $key => $val) {
            $pref->add([
                'users_id' => $users_id,
                'itemtype' => $itemtype,
                'tabref'   => $key,
            ]);
        }
    }
    if (isset($_POST['page']) && $_POST['page']) {
        $pref->add([
            'users_id' => $users_id,
            'itemtype' => $itemtype,
            'tabref'   => 'landscape',
        ]);
    }
    Html::back();
} else {
    Html::redirect('../../../front/preference.php');
}

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

/** @var array $PLUGIN_HOOKS */
global $PLUGIN_HOOKS;

Session::checkRight('plugin_pdf', READ);

Plugin::load('pdf', true);

$type = $_SESSION['plugin_pdf']['type'];
$dbu = new DbUtils();
$item = $dbu->getItemForItemtype($type);
if (!$item) {
    throw new InvalidArgumentException('Invalid item type: ' . $type);
}

$tab_id = unserialize($_SESSION['plugin_pdf']['tab_id']);
unset($_SESSION['plugin_pdf']['tab_id']);

/** @var DBmysql $DB */
global $DB;

$result = $DB->request([
    'FROM' => 'glpi_plugin_pdf_preferences',
    'SELECT' => 'tabref',
    'WHERE' => [
        'users_ID' => $_SESSION['glpiID'],
        'itemtype' => $type,
    ],
]);

$tab = [];

foreach ($result as $data) {
    if ($data['tabref'] == 'landscape') {
        $pag = 1;
    } else {
        $tab[] = $data['tabref'];
    }
}
if ($tab === []) {
    $tab[] = $type . '$main';
}

if (isset($PLUGIN_HOOKS['plugin_pdf'][$type]) && class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {
    $pdf_class = $PLUGIN_HOOKS['plugin_pdf'][$type];
    if (!is_a($pdf_class, PluginPdfCommon::class, true)) {
        throw new RuntimeException('Invalid PDF plugin class for type: ' . $type);
    }

    $itempdf = new $pdf_class($item);
    $itempdf->generatePDF($tab_id, $tab, ($pag ?? 0));
} else {
    throw new RuntimeException('Missing PDF plugin hook for type: ' . $type);
}

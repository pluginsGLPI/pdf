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

$token = ($_POST['_glpi_csrf_token'] ?? false);

Session::checkRight('plugin_pdf', READ);

Plugin::load('pdf', true);

$dbu = new DbUtils();

if (isset($_POST['plugin_pdf_inventory_type'])
    && ($item = $dbu->getItemForItemtype($_POST['plugin_pdf_inventory_type']))
    && isset($_POST['itemID'])) {
    $type = $_POST['plugin_pdf_inventory_type'];
    $item->check($_POST['itemID'], READ);

    if (isset($_SESSION['plugin_pdf'][$type])) {
        unset($_SESSION['plugin_pdf'][$type]);
    }

    $tab = [];

    if (isset($_POST['item'])) {
        foreach ($_POST['item'] as $key => $val) {
            if (!in_array($key, $tab)) {
                $tab[] = $_SESSION['plugin_pdf'][$type][] = $key;
            }
        }
    }
    if ($tab === []) {
        $tab[] = $type . '$main';
    }

    if (
        isset($PLUGIN_HOOKS['plugin_pdf'][$type])
        && class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])
    ) {
        $pdf_class = $PLUGIN_HOOKS['plugin_pdf'][$type];
        if (!is_a($pdf_class, PluginPdfCommon::class, true)) {
            throw new RuntimeException('Invalid PDF plugin class for type: ' . $type);
        }

        $itempdf = new $pdf_class($item);
        $itempdf->generatePDF([$_POST['itemID']], $tab, ($_POST['page'] ?? 0));
    } else {
        throw new RuntimeException('Missing PDF plugin hook for type: ' . $type);
    }
} else {
    throw new InvalidArgumentException('Missing required context or parameters for PDF generation');
}

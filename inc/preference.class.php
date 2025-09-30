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

use Glpi\Application\View\TemplateRenderer;

class PluginPdfPreference extends CommonDBTM
{
    public static $rightname = 'plugin_pdf';

    public static function getTypeName($nb = 0)
    {
        return __s('PDF export', 'pdf');
    }

    public static function showPreferences()
    {
        /** @var array $PLUGIN_HOOKS */
        global $PLUGIN_HOOKS;

        $target = Toolbox::getItemTypeFormURL(self::class);
        $pref   = new self();
        $dbu    = new DbUtils();

        echo "<div class='center' id='pdf_type'>";
        foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $plug) {
            if (!($item = $dbu->getItemForItemtype($type))) {
                continue;
            }
            if ($item->canView()) {
                $pref->menu($item, $target);
            }
        }
        echo '</div>';
    }

    /**
     * @param $item
     * @param $action
    **/
    public function menu($item, $action)
    {
        /** @var DBmysql $DB */
        /** @var array $PLUGIN_HOOKS */
        global $DB, $PLUGIN_HOOKS;

        $type = $item->getType();

        if (isset($item->fields['id'])) {
            $ID = $item->fields['id'];
        } else {
            $ID = 0;
            $item->getEmpty();
        }

        if (!isset($PLUGIN_HOOKS['plugin_pdf'][$type])
            || !class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {
            return;
        }

        $pdf_class = $PLUGIN_HOOKS['plugin_pdf'][$type];
        if (!is_a($pdf_class, PluginPdfCommon::class, true)) {
            return;
        }

        $itempdf = new $pdf_class($item);
        $options = $itempdf->defineAllTabsPDF();

        $landscape = false;
        $values    = [];

        $criterias = [
            'SELECT' => 'tabref',
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'users_id' => $_SESSION['glpiID'],
                'itemtype' => $type,
            ],
        ];

        foreach ($DB->request($criterias) as $data) {
            if ($data['tabref'] == 'landscape') {
                $landscape = true;
            } else {
                $values[$data['tabref']] = $data['tabref'];
            }
        }

        if (!count($values) && isset($options[$type . '$main'])) {
            $values[$type . '$main'] = 1;
        }

        $formid = "plugin_pdf_{$type}_" . mt_rand();

        $template_data = [
            'form_id' => $formid,
            'action' => $action,
            'item_id' => $ID,
            'item_type_name' => $item->getTypeName(),
            'inventory_type' => $type,
            'options' => $options,
            'values' => $values,
            'landscape' => $landscape,
            'debug_mode' => $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE,
        ];

        TemplateRenderer::getInstance()->display('@pdf/preference_form.html.twig', $template_data);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (($item->getType() == 'Preference')) {
            return self::createTabEntry(self::getTypeName(), 0, $item::getType(), PluginPdfConfig::getIcon());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Preference') {
            self::showPreferences();
        }

        return true;
    }

    public static function install(Migration $mig)
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = 'glpi_plugin_pdf_preferences';
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        if (!$DB->tableExists('glpi_plugin_pdf_preference')
            && !$DB->tableExists($table)) {
            $default_charset   = DBConnection::getDefaultCharset();
            $default_collation = DBConnection::getDefaultCollation();

            $query = 'CREATE TABLE `' . $table . "`(
                  `id` int $default_key_sign NOT NULL AUTO_INCREMENT,
                  `users_id` int $default_key_sign NOT NULL COMMENT 'RELATION to glpi_users (id)',
                  `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant',
                  `tabref` VARCHAR(255) NOT NULL COMMENT 'ref of tab to display, or plugname_#, or option name',
                  PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET= {$default_charset}
                 COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC";
            $DB->doQuery($query);
        } else {
            if ($DB->tableExists('glpi_plugin_pdf_preference')) {
                $mig->renameTable('glpi_plugin_pdf_preference', 'glpi_plugin_pdf_preferences');
            }
            // 0.6.0
            if ($DB->fieldExists($table, 'user_id')) {
                $mig->changeField(
                    $table,
                    'user_id',
                    'users_id',
                    "int {$default_key_sign} NOT NULL DEFAULT '0'",
                    ['comment' => 'RELATION to glpi_users (id)'],
                );
            }
            // 0.6.1
            if ($DB->fieldExists($table, 'FK_users')) {
                $mig->changeField(
                    $table,
                    'FK_users',
                    'users_id',
                    "int {$default_key_sign} NOT NULL DEFAULT '0'",
                    ['comment' => 'RELATION to glpi_users (id)'],
                );
            }
            // 0.6.0
            if ($DB->fieldExists($table, 'cat')) {
                $mig->changeField(
                    $table,
                    'cat',
                    'itemtype',
                    'VARCHAR(100) NOT NULL',
                    ['comment' => 'see define.php *_TYPE constant'],
                );
            }
            // 0.6.1
            if ($DB->fieldExists($table, 'device_type')) {
                $mig->changeField(
                    $table,
                    'device_type',
                    'itemtype',
                    'VARCHAR(100) NOT NULL',
                    ['comment' => 'see define.php *_TYPE constant'],
                );
            }
            // 0.6.0
            if ($DB->fieldExists($table, 'table_num')) {
                $mig->changeField(
                    $table,
                    'table_num',
                    'tabref',
                    'string',
                    ['comment' => 'ref of tab to display, or plugname_#, or option name'],
                );
            }
        }
    }
}

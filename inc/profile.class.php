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

class PluginPdfProfile extends Profile
{
    public static $rightname = 'profile';

    public static function getTypeName($nb = 0)
    {
        return _sn('PDF export', 'PDF export', $nb, 'pdf');
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = ['id' => 'common',
            'name'     => __s('PDF export', 'pdf')];


        $tab[] = ['id'  => '2',
            'table'     => $this->getTable(),
            'field'     => 'use',
            'linkfield' => 'id',
            'datatype'  => 'bool'];

        return $tab;
    }

    public function showForm($ID, $options = [])
    {
        $profile = new Profile();

        if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
            echo "<form action='" . $profile->getFormURL() . "' method='post'>";
        }
        $profile->getFromDB($ID);

        $real_right = ProfileRight::getProfileRights($ID, ['plugin_pdf']);
        $checked    = 0;
        if (isset($real_right)
            && ($real_right['plugin_pdf'] == 1)) {
            $checked = 1;
        }
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2' class='center b'>" . __s('PDF export', 'pdf');
        echo '</th></tr>';

        echo "<tr class='tab_bg_1'>";
        echo '<td>' . __s('PDF export', 'pdf') . '</td><td>';
        Html::showCheckbox(['name' => '_plugin_pdf',
            'checked'              => $checked]);
        echo "</td></tr></table>\n";

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Update'), ['name' => 'update',
                'class'                                        => 'btn btn-primary',
                'icon'                                         => 'ti ti-device-floppy']);
            echo "</div>\n";
            Html::closeForm();
        }
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            return self::createTabEntry(self::getTypeName(), 0, $item::getType(), PluginPdfConfig::getIcon());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Profile) {
            $prof = new self();
            $ID   = $item->getField('id');
            $prof->showForm($ID);
        }

        return true;
    }

    public static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false)
    {
        $profileRight = new ProfileRight();
        $dbu          = new DbUtils();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable(
                'glpi_profilerights',
                ['profiles_id' => $profiles_id,
                    'name'     => $right],
            )
                && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id,
                    'name'                                     => $right]);
            }

            if (!$dbu->countElementsInTable(
                'glpi_profilerights',
                ['profiles_id' => $profiles_id,
                    'name'     => $right],
            )) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    public static function createFirstAccess($ID)
    {
        self::addDefaultProfileInfos($ID, ['plugin_pdf' => 1], true);
    }

    public static function getAllRights($all = false)
    {
        $rights = [['itemtype' => 'PluginPdf',
            'label'            => __s('PDF export', 'pdf'),
            'field'            => 'plugin_pdf']];

        return $rights;
    }

    public static function initProfile()
    {
        /** @var DBmysql $DB */
        global $DB;

        $profile = new self();
        $dbu     = new DbUtils();

        //Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights() as $data) {
            if ($dbu->countElementsInTable(
                'glpi_profilerights',
                ['name' => $data['field']],
            ) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }

        foreach ($DB->request(
            ['FROM' => 'glpi_profilerights'] + ['profiles_id' => $_SESSION['glpiactiveprofile']['id'],
                'name'     => ['LIKE', '%plugin_pdf%']],
        ) as $prof) {
            $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
        }
    }

    public static function removeRightsFromSession()
    {
        foreach (self::getAllRights(true) as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }

    public static function install(Migration $mig)
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = 'glpi_plugin_pdf_profiles';
        if (!$DB->tableExists($table)
            && !$DB->tableExists('glpi_plugin_pdf_preferences')) {
            self::initProfile();
            self::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
        } else {
            if ($DB->tableExists($table)
                && $DB->fieldExists($table, 'ID')) { //< 0.7.0
                $mig->changeField($table, 'ID', 'id', 'autoincrement');
            }
            // -- SINCE 0.85 --
            //Add new rights in glpi_profilerights table
            $profileRight = new ProfileRight();

            if ($DB->tableExists($table)) {
                $criterias = [
                    'FROM' => $table,
                    'WHERE' => [
                        'use' => 1,
                    ],
                ];
                foreach ($DB->request($criterias) as $data) {
                    $right['profiles_id'] = $data['id'];
                    $right['name']        = 'plugin_pdf';
                    $right['rights']      = $data['use'];

                    $profileRight->add($right);
                }
                $mig->dropTable($table);
            }
        }
    }
}

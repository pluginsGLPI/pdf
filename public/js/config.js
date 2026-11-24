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
 * @copyright Copyright (C) 2015-2023 by PDF plugin team.
 * @license   AGPL License 3.0 or (at your option) any later version
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @link      https://github.com/pluginsGLPI/pdf
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

function pdfInitBrandingLogo(root) {
    document.getElementById("use_branding_logo_checkbox").addEventListener("change", function () {
        document.getElementById("use_branding_logo_hidden").value = this.checked ? 1 : 0;
    });
}

document.querySelectorAll('[data-pdf-branding-logo]:not([data-pdf-branding-ready])').forEach(function (root) {
    root.dataset.pdfBrandingReady = '';
    pdfInitBrandingLogo(root);
});

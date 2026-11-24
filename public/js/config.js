/**
 * -------------------------------------------------------------------------
 * PDF plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of PDF.
 *
 * PDF is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * PDF is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PDF. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2015-2023 by PDF plugin team.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/pdf
 * -------------------------------------------------------------------------
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

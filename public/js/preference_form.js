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

function selectAllCheckboxes(formId) {
    $('#' + formId + ' input[type="checkbox"][name^="item["]').each(function () {
        $(this).prop('checked', true);
        $(this).closest('.card').addClass('border-success');
    });
    updateSelectionCount(formId);
}

function selectNoCheckboxes(formId) {
    $('#' + formId + ' input[type="checkbox"][name^="item["]').each(function () {
        $(this).prop('checked', false);
        $(this).closest('.card').removeClass('border-success');
    });
    updateSelectionCount(formId);
}

function updateSelectionCount(formId) {
    var checkedCount = $('#' + formId + ' input[type="checkbox"][name^="item["]:checked').length;
    $('#selected-count-' + formId).text(checkedCount);
}

function pdfInitPreferenceForm(root) {
    var formId = root.dataset.pdfFormId;

    $('#select_all_' + formId).on('click', function () {
        selectAllCheckboxes(formId);
    });
    $('#select_none_' + formId).on('click', function () {
        selectNoCheckboxes(formId);
    });

    $('#' + formId + ' input[type="checkbox"][name^="item["]').each(function () {
        if ($(this).is(':checked')) {
            $(this).closest('.card').addClass('border-success');
        }

        $(this).on('change', function () {
            var card = $(this).closest('.card');
            if ($(this).is(':checked')) {
                card.addClass('border-success');
            } else {
                card.removeClass('border-success');
            } updateSelectionCount(formId);
        });
    });

    updateSelectionCount(formId);

    function refreshCsrfToken(formId) {
        fetch(window.location.href, { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => {
                try {
                    const newTokenInput = new DOMParser()
                        .parseFromString(html, 'text/html')
                        .querySelector('input[name="_glpi_csrf_token"]');
                    if (!newTokenInput) return;

                    const currentTokenInput = document.querySelector(`#${formId} input[name="_glpi_csrf_token"]`);
                    if (currentTokenInput) {
                        currentTokenInput.value = newTokenInput.value;
                    }
                } catch (e) {
                    // Ignore errors silently
                }
            })
            .catch(() => {
                // Ignore errors silently
            });
    }

    // Schedule a CSRF token refresh after form submission
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', () => {
            // Delay to allow server-side AJAX handlers to complete; full navigation will reload everything
            setTimeout(() => refreshCsrfToken(formId), 800);
        });
    }
}

$(function () {
    document.querySelectorAll('[data-pdf-form-id]:not([data-pdf-form-ready])').forEach(function (root) {
        root.dataset.pdfFormReady = '';
        pdfInitPreferenceForm(root);
    });
});

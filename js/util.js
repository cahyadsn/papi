/*
================================================================================
 *  BISMILLAAHIRRAHMAANIRRAHIIM - In the Name of Allah, Most Gracious, Most Merciful
================================================================================
FILENAME     : js/util.js
AUTHOR       : CAHYA DSN
CREATED DATE : 2017-04-09
UPDATED DATE : 2026-07-14
DEMO SITE    : http://psycho.cahyadsn.com/papi
SOURCE CODE  : https://github.com/cahyadsn/papi
================================================================================
*/

// Current page index (0-based)
var p = 0;

/**
 * Validate that every question pair on all pages has been answered.
 * Shows the alert and returns false if any question is unanswered.
 */
function check() {
    var unanswered = [];
    for (var i = 1; i <= 90; i++) {
        var a = document.getElementById('s_' + i + '_0');
        var b = document.getElementById('s_' + i + '_1');
        if (a && b && !a.checked && !b.checked) {
            unanswered.push(i);
        }
    }
    if (unanswered.length > 0) {
        showAlert('Pertanyaan belum diisi: ' + unanswered.join(', '));
        return false;
    }
    return true;
}

/**
 * Navigate to a page by index (called from intro/instruction pages).
 */
function next(pageNum) {
    for (var i = 1; i <= 3; i++) {
        var el = document.getElementById('page' + i);
        if (el) el.style.display = (i === pageNum) ? 'block' : 'none';
    }
}

/**
 * Transition between question table segments.
 * @param {number} dir  +1 (forward) or -1 (backward)
 */
function trans(dir) {
    p += dir;

    var tables = document.getElementsByClassName('q-table');
    for (var i = 0; i < tables.length; i++) {
        tables[i].style.display = (i === p) ? 'block' : 'none';
    }

    // Scroll table into view smoothly
    var currentTable = document.getElementById('t' + p);
    if (currentTable) currentTable.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Update prev/next/submit button states
    var prevBtn   = document.getElementById('prev');
    var nextBtn   = document.getElementById('next');
    var submitBtn = document.getElementById('submit');

    if (prevBtn)   prevBtn.disabled   = (p <= 0);
    if (nextBtn)   nextBtn.style.display   = (p < total - 1) ? 'inline-flex' : 'none';
    if (submitBtn) submitBtn.style.display = (p < total - 1) ? 'none' : 'inline-flex';

    // Update progress bar
    updateProgress();
}

/**
 * Update the progress bar and label.
 */
function updateProgress() {
    var label = document.getElementById('progress-label');
    var fill  = document.getElementById('progress-fill');
    if (label) label.textContent = 'Bagian ' + (p + 1) + ' / ' + total;
    if (fill)  fill.style.width  = Math.round(((p + 1) / total) * 100) + '%';
}

/**
 * Show the alert banner with a message.
 */
function showAlert(msg) {
    // Try the form-scoped alert first, fall back to the page-level one
    var msgEl   = document.getElementById('msg-form') || document.getElementById('msg');
    var wrapEl  = document.getElementById('alert-form-wrap') || document.getElementById('alert-wrap');
    if (msgEl)  msgEl.textContent = msg;
    if (wrapEl) wrapEl.style.display = 'block';
}

// Initialise on load
trans(0);

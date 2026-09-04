/**
 * Shipping Rules Tester admin UI.
 */
(function () {
  'use strict';

  function escapeHtml(value) {
    var element = document.createElement('div');
    element.textContent = value == null ? '' : value;
    return element.innerHTML;
  }

  function renderResults(data) {
    var html = '<div class="srt-result-card"><h2>' + escapeHtml(srtData.i18n.matchedZone) + '</h2><p><strong>' + escapeHtml(data.zone) + '</strong></p></div>';
    html += '<div class="srt-result-card"><h2>' + escapeHtml(srtData.i18n.methods) + '</h2>';
    if (!data.methods.length) {
      html += '<p>' + escapeHtml(srtData.i18n.noMethods) + '</p>';
    } else {
      html += '<table class="widefat striped"><thead><tr><th>' + escapeHtml(srtData.i18n.method) + '</th><th>' + escapeHtml(srtData.i18n.result) + '</th><th>' + escapeHtml(srtData.i18n.details) + '</th></tr></thead><tbody>';
      data.methods.forEach(function (method) {
        var result = method.status === 'matched' ? method.cost : method.status === 'no-rate' ? srtData.i18n.noRate : srtData.i18n.notTested;
        html += '<tr><td>' + escapeHtml(method.id) + '</td><td>' + escapeHtml(result) + '</td><td>' + escapeHtml(method.note) + '</td></tr>';
      });
      html += '</tbody></table>';
    }
    html += '</div>';
    document.getElementById('srt-results').innerHTML = html;
    document.getElementById('srt-results').hidden = false;
  }

  function getFormData(form) {
    var data = {};
    new FormData(form).forEach(function (value, key) {
      data[key] = value;
    });
    return data;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('srt-form');
    var button = document.getElementById('srt-submit');
    var status = document.getElementById('srt-status');
    var results = document.getElementById('srt-results');

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      results.hidden = true;
      results.innerHTML = '';
      status.textContent = srtData.i18n.testing;
      button.disabled = true;

      wp.apiFetch({
        path: srtData.restUrl,
        method: 'POST',
        data: getFormData(form)
      }).then(function (data) {
        status.textContent = '';
        renderResults(data);
      }).catch(function (error) {
        status.textContent = error && error.message ? error.message : srtData.i18n.error;
      }).finally(function () {
        button.disabled = false;
      });
    });
  });
}());

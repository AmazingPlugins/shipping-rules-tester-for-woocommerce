/* Shipping Rules Tester admin UI. */
(function ($) {
  'use strict';

  function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : value).html();
  }

  function renderResults(data) {
    var html = '<div class="srt-result-card"><h2>' + escapeHtml(srtData.i18n.matchedZone) + '</h2><p><strong>' + escapeHtml(data.zone) + '</strong></p></div>';
    html += '<div class="srt-result-card"><h2>' + escapeHtml(srtData.i18n.methods) + '</h2>';
    if (!data.methods.length) {
      html += '<p>' + escapeHtml(srtData.i18n.noMethods) + '</p>';
    } else {
      html += '<table class="widefat striped"><thead><tr><th>' + escapeHtml(srtData.i18n.method) + '</th><th>' + escapeHtml(srtData.i18n.result) + '</th><th>' + escapeHtml(srtData.i18n.details) + '</th></tr></thead><tbody>';
      $.each(data.methods, function (_, method) {
        var result = method.status === 'matched' ? method.cost : method.status === 'no-rate' ? srtData.i18n.noRate : srtData.i18n.notTested;
        html += '<tr><td>' + escapeHtml(method.id) + '</td><td>' + escapeHtml(result) + '</td><td>' + escapeHtml(method.note) + '</td></tr>';
      });
      html += '</tbody></table>';
    }
    html += '</div>';
    $('#srt-results').html(html).prop('hidden', false);
  }

  $(function () {
    $('#srt-form').on('submit', function (event) {
      event.preventDefault();
      var $form = $(this);
      var $button = $('#srt-submit');
      $('#srt-results').prop('hidden', true).empty();
      $('#srt-status').text(srtData.i18n.testing);
      $button.prop('disabled', true);
      $.post(srtData.ajaxUrl, $form.serialize() + '&action=srt_test_shipping&nonce=' + encodeURIComponent(srtData.nonce))
        .done(function (response) {
          if (!response.success) {
            $('#srt-status').text(response.data && response.data.message ? response.data.message : srtData.i18n.error);
            return;
          }
          $('#srt-status').text('');
          renderResults(response.data);
        })
        .fail(function () {
          $('#srt-status').text(srtData.i18n.error);
        })
        .always(function () {
          $button.prop('disabled', false);
        });
    });
  });
}(jQuery));

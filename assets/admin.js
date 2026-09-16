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

  function renderScenario(data, index) {
    var scenario = srtData.i18n.scenario.replace('%d', String(index));
    var destination = [data.package.country, data.package.state, data.package.postcode, data.package.city].filter(function (value) { return value; }).join(', ');
    var packageSummary = srtData.i18n.value + ': ' + srtData.currency + ' ' + data.package.value + '; ' + srtData.i18n.weight + ': ' + data.package.weight + ' ' + srtData.weightUnit + '; ' + srtData.i18n.quantity + ': ' + data.package.quantity;
    var zone = data.fallback ? data.zone + ' (' + srtData.i18n.fallback + ')' : data.zone;
    var productSummary = '';
    if (data.product) {
      var product = data.product;
      var dimensions = ['length', 'width', 'height'].map(function (dimension) {
        return product.dimensions[dimension] || '0';
      }).join(' × ');
      productSummary = '<div class="srt-product-details"><h4>' + escapeHtml(srtData.i18n.productDetails) + '</h4><p><strong>' + escapeHtml(srtData.i18n.product) + ':</strong> ' + escapeHtml(product.name + ' (#' + product.id + ')') + '</p><p><strong>' + escapeHtml(srtData.i18n.productPrice) + ':</strong> ' + escapeHtml(srtData.currency + ' ' + product.price) + '; <strong>' + escapeHtml(srtData.i18n.productWeight) + ':</strong> ' + escapeHtml(product.weight + ' ' + srtData.weightUnit) + '; <strong>' + escapeHtml(srtData.i18n.shippingClass) + ':</strong> ' + escapeHtml(product.shipping_class || srtData.i18n.none) + '; <strong>' + escapeHtml(srtData.i18n.dimensions) + ':</strong> ' + escapeHtml(dimensions + ' ' + srtData.dimensionUnit) + '; <strong>' + escapeHtml(srtData.i18n.taxClass) + ':</strong> ' + escapeHtml(product.tax_class || srtData.i18n.standard) + '</p></div>';
    }
    var zoneRules = '';
    if (data.zone_locations && data.zone_locations.length) {
      zoneRules = '<p><strong>' + escapeHtml(srtData.i18n.zoneRules) + ':</strong> ' + data.zone_locations.map(function (location) {
        return escapeHtml(location.type + ': ' + location.code);
      }).join(', ') + '</p>';
    } else if (data.fallback) {
      zoneRules = '<p>' + escapeHtml(srtData.i18n.fallbackRule) + '</p>';
    }
    var html = '<div class="srt-scenario"><h3>' + escapeHtml(scenario) + '</h3><p><strong>' + escapeHtml(srtData.i18n.destination) + ':</strong> ' + escapeHtml(destination) + '</p><p><strong>' + escapeHtml(srtData.i18n.package) + ':</strong> ' + escapeHtml(packageSummary) + '</p>' + productSummary + '<div class="srt-result-card"><h4>' + escapeHtml(srtData.i18n.matchedZone) + '</h4><p><strong>' + escapeHtml(zone) + '</strong></p>' + zoneRules + '</div><div class="srt-result-card"><h4>' + escapeHtml(srtData.i18n.methods) + '</h4>';
    if (!data.methods.length) {
      html += '<p>' + escapeHtml(srtData.i18n.noMethods) + '</p>';
    } else {
      html += '<table class="widefat striped"><thead><tr><th>' + escapeHtml(srtData.i18n.method) + '</th><th>' + escapeHtml(srtData.i18n.result) + '</th><th>' + escapeHtml(srtData.i18n.details) + '</th></tr></thead><tbody>';
      data.methods.forEach(function (method) {
        var result = method.status === 'matched' ? method.cost : method.status === 'no-rate' ? srtData.i18n.noRate : method.status === 'unavailable' ? srtData.i18n.unavailable : method.status === 'disabled' ? srtData.i18n.disabled : srtData.i18n.notTested;
        var details = method.note;
        if (method.rates && method.rates.length) {
          details = method.rates.map(function (rate) {
            return (rate.id ? rate.id + ': ' : '') + (rate.cost ? rate.cost + ' + ' + rate.tax + ' ' + srtData.i18n.tax + ' = ' + rate.total + ' ' + srtData.i18n.total : srtData.i18n.unavailable);
          }).join('; ');
          if (method.rates.every(function (rate) { return rate.available && rate.zero_cost; })) {
            details += ' ' + srtData.i18n.zeroCost;
          }
        }
        html += '<tr><td>' + escapeHtml(method.id) + '</td><td>' + escapeHtml(result) + '</td><td>' + escapeHtml(details) + '</td></tr>';
      });
      html += '</tbody></table>';
    }
    return html + '</div></div>';
  }

  function renderResults(data) {
    document.getElementById('srt-results').innerHTML = renderScenario(data, 1);
    document.getElementById('srt-results').hidden = false;
  }

  function renderComparison(data) {
    var html = '<div class="srt-result-card"><h2>' + escapeHtml(srtData.i18n.comparison) + '</h2>';
    data.forEach(function (scenario, index) {
      html += renderScenario(scenario, index + 1);
    });
    document.getElementById('srt-results').innerHTML = html + '</div>';
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
    var keepButton = document.getElementById('srt-keep');
    var clearButton = document.getElementById('srt-clear');
    var productSelect = document.getElementById('srt-product');
    var valueInput = form.querySelector('input[name="value"]');
    var weightInput = form.querySelector('input[name="weight"]');
    var status = document.getElementById('srt-status');
    var results = document.getElementById('srt-results');
    var currentResult = null;
    var comparisonResults = [];

    function updateComparisonControls() {
      keepButton.hidden = !currentResult;
      clearButton.hidden = !comparisonResults.length;
    }

    function updateProductInputs() {
      var usesProduct = productSelect.value !== '0';
      valueInput.disabled = usesProduct;
      weightInput.disabled = usesProduct;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      results.hidden = true;
      results.innerHTML = '';
      status.textContent = srtData.i18n.testing;
      button.disabled = true;

      wp.apiFetch({
        path: srtData.restUrl,
        method: 'POST',
        headers: {
          'X-WP-Nonce': srtData.nonce
        },
        data: getFormData(form)
      }).then(function (data) {
        status.textContent = '';
        currentResult = data;
        if (comparisonResults.length) {
          renderComparison(comparisonResults.concat([data]));
        } else {
          renderResults(data);
        }
        updateComparisonControls();
      }).catch(function (error) {
        status.textContent = error && error.message ? error.message : srtData.i18n.error;
      }).finally(function () {
        button.disabled = false;
      });
    });

    keepButton.addEventListener('click', function () {
      if (!currentResult) {
        return;
      }

      comparisonResults.push(currentResult);
      currentResult = null;
      results.hidden = true;
      results.innerHTML = '';
      status.textContent = srtData.i18n.kept;
      updateComparisonControls();
    });

    clearButton.addEventListener('click', function () {
      comparisonResults = [];
      currentResult = null;
      results.hidden = true;
      results.innerHTML = '';
      status.textContent = '';
      updateComparisonControls();
    });

    productSelect.addEventListener('change', updateProductInputs);
    updateProductInputs();
    updateComparisonControls();
  });
}());

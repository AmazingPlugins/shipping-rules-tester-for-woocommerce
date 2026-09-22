/**
 * Shipping Rules Tester admin UI.
 */
(function () {
  'use strict';

  function escapeHtml(value) {
    var element = document.createElement('div');
    element.textContent = value == null ? '' : String(value);
    return element.innerHTML;
  }

  function formatNumber(value) {
    var number = parseFloat(value);
    return Number.isFinite(number) ? number.toFixed(2) : '0.00';
  }

  function statusClass(status) {
    var allowed = ['matched', 'not-tested', 'disabled', 'no-rate', 'unavailable', 'error'];
    return allowed.indexOf(status) !== -1 ? 'is-' + status : 'is-not-tested';
  }

  function statusLabel(status) {
    var labels = {
      matched: srtData.i18n.matched,
      'not-tested': srtData.i18n.notTested,
      disabled: srtData.i18n.disabled,
      'no-rate': srtData.i18n.noRate,
      unavailable: srtData.i18n.unavailable,
      error: srtData.i18n.errorStatus
    };
    return labels[status] || labels['not-tested'];
  }

  function getRows() {
    return Array.prototype.slice.call(document.querySelectorAll('#srt-items-list [data-item-row]'));
  }

  function getRowField(row, field) {
    return row.querySelector('[data-item-field="' + field + '"]');
  }

  function setRowField(row, field, value) {
    var element = getRowField(row, field);
    if (element) {
      element.value = value == null ? '' : String(value);
    }
  }

  function copyOptions(source, target) {
    if (!source || !target) {
      return;
    }

    target.innerHTML = '';
    Array.prototype.forEach.call(source.options, function (option) {
      target.appendChild(option.cloneNode(true));
    });
  }

  function setItemMode(row) {
    var source = getRowField(row, 'source');
    var isProduct = source && source.value === 'product';
    var productField = row.querySelector('.srt-item-product-field');
    var productSelect = getRowField(row, 'product_id');
    var value = getRowField(row, 'value');
    var weight = getRowField(row, 'weight');
    var shippingClass = getRowField(row, 'shipping_class_id');
    var dimensions = row.querySelectorAll('[data-item-field="length"], [data-item-field="width"], [data-item-field="height"]');

    if (productField) {
      productField.hidden = !isProduct;
    }
    if (productSelect) {
      productSelect.disabled = !isProduct;
    }
    [value, weight, shippingClass].forEach(function (field) {
      if (field) {
        field.disabled = isProduct;
      }
    });
    Array.prototype.forEach.call(dimensions, function (field) {
      field.disabled = isProduct;
    });
  }

  function updateRowNumbers() {
    var rows = getRows();
    rows.forEach(function (row, index) {
      var number = row.querySelector('.srt-item-number');
      var remove = row.querySelector('.srt-remove-item');
      if (number) {
        number.textContent = String(index + 1);
      }
      if (remove) {
        remove.hidden = rows.length < 2;
      }
      setItemMode(row);
    });
  }

  function readRow(row) {
    return {
      source: getRowField(row, 'source').value,
      product_id: getRowField(row, 'product_id').value || '0',
      value: getRowField(row, 'value').value || '0',
      weight: getRowField(row, 'weight').value || '0',
      quantity: getRowField(row, 'quantity').value || '1',
      shipping_class_id: getRowField(row, 'shipping_class_id').value || '0',
      length: getRowField(row, 'length').value || '0',
      width: getRowField(row, 'width').value || '0',
      height: getRowField(row, 'height').value || '0'
    };
  }

  function syncFirstRowFromQuick(form, row) {
    var quickProduct = form.querySelector('#srt-product');
    var quickValue = form.querySelector('input[name="value"]');
    var quickWeight = form.querySelector('input[name="weight"]');
    var quickQuantity = form.querySelector('input[name="quantity"]');
    var quantity = parseInt(quickQuantity.value, 10) || 1;
    var value = parseFloat(quickValue.value) || 0;
    var weight = parseFloat(quickWeight.value) || 0;
    var productSelected = quickProduct.value !== '0';

    setRowField(row, 'source', productSelected ? 'product' : 'custom');
    setRowField(row, 'product_id', quickProduct.value);
    setRowField(row, 'value', (value / quantity).toFixed(2));
    setRowField(row, 'weight', (weight / quantity).toFixed(3));
    setRowField(row, 'quantity', quantity);
    setRowField(row, 'shipping_class_id', '0');
    setRowField(row, 'length', '0');
    setRowField(row, 'width', '0');
    setRowField(row, 'height', '0');
    setItemMode(row);
  }

  function makePayload(form, advancedPanel, advancedDirty) {
    var payload = form.querySelector('#srt-items-payload');
    if (!advancedPanel.hidden) {
      if (!advancedDirty) {
        syncFirstRowFromQuick(form, getRows()[0]);
      }
      payload.value = JSON.stringify(getRows().map(readRow));
    } else {
      payload.value = '';
    }
  }

  function renderItemDetails(data) {
    var items = Array.isArray(data.items) ? data.items : [];
    if (!items.length && data.product) {
      items = [data.product];
    }
    if (!items.length) {
      return '';
    }

	var rows = items.map(function (item, index) {
	  var dimensions = item.dimensions ? [item.dimensions.length, item.dimensions.width, item.dimensions.height].map(formatNumber).join(' × ') : '';
	  var itemName = item.name || srtData.i18n.syntheticItem;
	  if (item.id) {
	    itemName += ' (#' + item.id + ')';
	  }
	  var details = [
	    escapeHtml(item.quantity + ' × ' + itemName),
        escapeHtml(srtData.i18n.value + ': ' + srtData.currency + ' ' + formatNumber(item.line_value)),
        escapeHtml(srtData.i18n.weight + ': ' + formatNumber(item.line_weight) + ' ' + srtData.weightUnit)
      ];
      if (item.shipping_class) {
        details.push(escapeHtml(srtData.i18n.shippingClass + ': ' + item.shipping_class));
      }
      if (item.shipping_class_id) {
        details.push(escapeHtml(srtData.i18n.shippingClass + ': #' + item.shipping_class_id));
      }
      if (dimensions) {
        details.push(escapeHtml(srtData.i18n.dimensions + ': ' + dimensions + ' ' + srtData.dimensionUnit));
      }
      return '<p><strong>' + escapeHtml(srtData.i18n.item + ' ' + (index + 1)) + ':</strong> ' + details.join(' · ') + '</p>';
    }).join('');

    return '<div class="srt-product-details"><h4>' + escapeHtml(srtData.i18n.packageItems) + '</h4>' + rows + '</div>';
  }

  function renderRates(method) {
    if (!Array.isArray(method.rates) || !method.rates.length) {
      return '';
    }

    return '<div class="srt-rate-list">' + method.rates.map(function (rate) {
      var label = rate.id ? rate.id + ': ' : '';
      var amount = rate.available ? rate.cost + ' + ' + rate.tax + ' ' + srtData.i18n.tax + ' = ' + rate.total : srtData.i18n.unavailable;
      return '<span class="srt-rate-chip"><strong>' + escapeHtml(label) + '</strong><span>' + escapeHtml(amount) + '</span></span>';
    }).join('') + '</div>';
  }

  function renderMethods(methods) {
    if (!methods.length) {
      return '<p class="srt-method-note">' + escapeHtml(srtData.i18n.noMethods) + '</p>';
    }

    return '<div class="srt-method-list">' + methods.map(function (method) {
      var status = method.status || 'not-tested';
      var details = method.note || '';
      if (method.rates && method.rates.length && method.rates.every(function (rate) { return rate.available && rate.zero_cost; })) {
        details += (details ? ' ' : '') + srtData.i18n.zeroCost;
      }
      return '<article class="srt-method-card ' + statusClass(status) + '">' +
        '<div class="srt-method-head"><h4>' + escapeHtml(method.id) + '</h4><span class="srt-method-status ' + statusClass(status) + '">' + escapeHtml(statusLabel(status)) + '</span></div>' +
        renderRates(method) +
        (details ? '<p class="srt-method-note">' + escapeHtml(details) + '</p>' : '') +
        '</article>';
    }).join('') + '</div>';
  }

  function renderScenario(data, index) {
    var methods = Array.isArray(data.methods) ? data.methods : [];
    var items = Array.isArray(data.items) ? data.items : (data.product ? [data.product] : []);
    var matched = methods.filter(function (method) { return method.status === 'matched'; }).length;
    var rates = methods.reduce(function (total, method) {
      return total + (Array.isArray(method.rates) ? method.rates.filter(function (rate) { return rate.available; }).length : 0);
    }, 0);
    var packageData = data.package || {};
    var destination = [packageData.country, packageData.state, packageData.postcode, packageData.city].filter(function (value) { return value; }).join(', ');
    var zone = data.fallback ? data.zone + ' · ' + srtData.i18n.fallback : data.zone;
    var scenarioTitle = srtData.i18n.scenario.replace('%d', String(index));
    var zoneRules = '';

    if (data.zone_locations && data.zone_locations.length) {
      zoneRules = data.zone_locations.map(function (location) {
        return escapeHtml(location.type + ': ' + location.code);
      }).join(', ');
    } else if (data.fallback) {
      zoneRules = escapeHtml(srtData.i18n.fallbackRule);
    }

    return '<section class="srt-scenario" aria-labelledby="srt-scenario-' + index + '">' +
      '<div class="srt-result-panel">' +
      '<div class="srt-result-header"><div><span class="srt-result-kicker">' + escapeHtml(srtData.i18n.matchedZone) + '</span><h2 id="srt-scenario-' + index + '">' + escapeHtml(zone) + '</h2></div><span class="srt-zone-badge">' + escapeHtml(scenarioTitle) + '</span></div>' +
      '<div class="srt-result-stats"><div class="srt-result-stat"><span>' + escapeHtml(srtData.i18n.itemsChecked) + '</span><strong>' + escapeHtml(String(items.length)) + '</strong></div><div class="srt-result-stat"><span>' + escapeHtml(srtData.i18n.methodsChecked) + '</span><strong>' + escapeHtml(String(methods.length)) + '</strong></div><div class="srt-result-stat"><span>' + escapeHtml(srtData.i18n.ratesFound) + '</span><strong>' + escapeHtml(String(rates)) + '</strong></div></div>' +
      '<div class="srt-result-body"><div class="srt-result-context"><p><strong>' + escapeHtml(srtData.i18n.destination) + ':</strong> ' + escapeHtml(destination || srtData.i18n.countryOnly) + '</p><p><strong>' + escapeHtml(srtData.i18n.packageTotals) + ':</strong> ' + escapeHtml(srtData.currency + ' ' + formatNumber(packageData.value) + ' · ' + formatNumber(packageData.weight) + ' ' + srtData.weightUnit + ' · ' + packageData.quantity + ' ' + srtData.i18n.quantity) + '</p>' + (zoneRules ? '<p><strong>' + escapeHtml(srtData.i18n.zoneRules) + ':</strong> ' + zoneRules + '</p>' : '') + renderItemDetails(data) + '</div>' +
      '<div class="srt-result-block"><h3>' + escapeHtml(srtData.i18n.methods) + '</h3>' + renderMethods(methods) + '</div></div>' +
      '</div></section>';
  }

  function renderResults(container, data) {
    container.innerHTML = renderScenario(data, 1);
    container.hidden = false;
  }

  function renderComparison(container, data) {
    var content = data.map(function (scenario, index) {
      return renderScenario(scenario, index + 1);
    }).join('');
    container.innerHTML = '<div class="srt-comparison"><h2>' + escapeHtml(srtData.i18n.comparison) + '</h2><p class="srt-side-description">' + escapeHtml(srtData.i18n.comparisonHint) + '</p>' + content + '</div>';
    container.hidden = false;
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
    if (!form) {
      return;
    }

    var button = document.getElementById('srt-submit');
    var submitLabel = button.querySelector('.srt-submit-label');
    var keepButton = document.getElementById('srt-keep');
    var clearButton = document.getElementById('srt-clear');
    var resetButton = document.getElementById('srt-reset');
    var productSelect = document.getElementById('srt-product');
    var valueInput = form.querySelector('input[name="value"]');
    var weightInput = form.querySelector('input[name="weight"]');
    var quantityInput = form.querySelector('input[name="quantity"]');
    var advancedToggle = document.getElementById('srt-advanced-toggle');
    var advancedPanel = document.getElementById('srt-advanced-panel');
    var itemsList = document.getElementById('srt-items-list');
    var itemTemplate = itemsList.querySelector('[data-item-row]');
    var productOptions = productSelect;
    var shippingClassSource = document.getElementById('srt-shipping-class-source');
    var status = document.getElementById('srt-status');
    var results = document.getElementById('srt-results');
    var currentResult = null;
    var comparisonResults = [];
    var advancedDirty = false;

    function setStatus(message, type) {
      status.className = 'srt-status' + (type ? ' is-' + type : '');
      status.textContent = message || '';
    }

    function updateComparisonControls() {
      keepButton.hidden = !currentResult;
      clearButton.hidden = !comparisonResults.length;
    }

    function updateProductInputs() {
      var usesProduct = productSelect.value !== '0';
      valueInput.disabled = usesProduct;
      weightInput.disabled = usesProduct;
      updateLiveSummary();
    }

    function updateLiveSummary() {
      var country = form.querySelector('select[name="country"]');
      var countryLabel = country.options[country.selectedIndex] ? country.options[country.selectedIndex].text : srtData.i18n.countryOnly;
      var selectedProduct = productSelect.options[productSelect.selectedIndex] ? productSelect.options[productSelect.selectedIndex].text : srtData.i18n.syntheticItem;
      var summaryPackage = document.getElementById('srt-summary-package');
      var summaryDestination = document.getElementById('srt-summary-destination');
      var summaryTotals = document.getElementById('srt-summary-totals');
      var value = parseFloat(valueInput.value) || 0;
      var weight = parseFloat(weightInput.value) || 0;
      var quantity = parseInt(quantityInput.value, 10) || 1;

      if (!advancedPanel.hidden && advancedDirty) {
        var rows = getRows().map(readRow);
        value = rows.reduce(function (total, item) { return total + ((parseFloat(item.value) || 0) * (parseInt(item.quantity, 10) || 1)); }, 0);
        weight = rows.reduce(function (total, item) { return total + ((parseFloat(item.weight) || 0) * (parseInt(item.quantity, 10) || 1)); }, 0);
        quantity = rows.reduce(function (total, item) { return total + (parseInt(item.quantity, 10) || 1); }, 0);
        summaryPackage.textContent = srtData.i18n.packageItems;
      } else {
        summaryPackage.textContent = productSelect.value !== '0' ? selectedProduct : srtData.i18n.syntheticItem;
      }

      summaryDestination.textContent = country.value ? countryLabel : srtData.i18n.chooseCountry;
      summaryTotals.textContent = formatNumber(value) + ' ' + srtData.currency + ' · ' + formatNumber(weight) + ' ' + srtData.weightUnit + ' · ' + quantity + ' ' + srtData.i18n.quantity;
    }

    function prepareRow(row) {
      copyOptions(productOptions, getRowField(row, 'product_id'));
      copyOptions(shippingClassSource, getRowField(row, 'shipping_class_id'));
      setItemMode(row);
    }

    function syncAdvancedFromQuick() {
      var rows = getRows();
      if (rows.length) {
        syncFirstRowFromQuick(form, rows[0]);
      }
      advancedDirty = false;
      updateLiveSummary();
    }

    function addItem() {
      var row = itemTemplate.cloneNode(true);
      setRowField(row, 'source', 'custom');
      setRowField(row, 'product_id', '0');
      setRowField(row, 'value', '0');
      setRowField(row, 'weight', '0');
      setRowField(row, 'quantity', '1');
      setRowField(row, 'shipping_class_id', '0');
      setRowField(row, 'length', '0');
      setRowField(row, 'width', '0');
      setRowField(row, 'height', '0');
      prepareRow(row);
      itemsList.appendChild(row);
      advancedDirty = true;
      updateRowNumbers();
      updateLiveSummary();
      getRowField(row, 'value').focus();
    }

    advancedToggle.addEventListener('click', function () {
      var willOpen = advancedPanel.hidden;
      if (willOpen) {
        syncAdvancedFromQuick();
      }
      advancedPanel.hidden = !willOpen;
      advancedToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      advancedToggle.querySelector('.srt-toggle-label').textContent = willOpen ? srtData.i18n.hideAdvanced : srtData.i18n.advanced;
      updateLiveSummary();
    });

    document.getElementById('srt-add-item').addEventListener('click', addItem);

    itemsList.addEventListener('click', function (event) {
      if (!event.target.classList.contains('srt-remove-item')) {
        return;
      }
      var row = event.target.closest('[data-item-row]');
      if (row && getRows().length > 1) {
        row.remove();
        advancedDirty = true;
        updateRowNumbers();
        updateLiveSummary();
      }
    });

    itemsList.addEventListener('input', function () {
      advancedDirty = true;
      updateLiveSummary();
    });

    itemsList.addEventListener('change', function (event) {
      advancedDirty = true;
      if (event.target.classList.contains('srt-item-source')) {
        setItemMode(event.target.closest('[data-item-row]'));
      }
      updateLiveSummary();
    });

    [form.querySelector('select[name="country"]'), form.querySelector('input[name="state"]'), form.querySelector('input[name="postcode"]'), form.querySelector('input[name="city"]'), valueInput, weightInput, quantityInput].forEach(function (field) {
      field.addEventListener('input', updateLiveSummary);
      field.addEventListener('change', updateLiveSummary);
    });

    productSelect.addEventListener('change', function () {
      updateProductInputs();
      if (!advancedPanel.hidden && !advancedDirty) {
        syncAdvancedFromQuick();
      }
    });

    document.querySelectorAll('.srt-preset').forEach(function (preset) {
      preset.addEventListener('click', function () {
        var values = {
          standard: { value: '50', weight: '2', quantity: '1' },
          free: { value: '100', weight: '2', quantity: '1' },
          heavy: { value: '120', weight: '25', quantity: '1' },
          pickup: { value: '20', weight: '1', quantity: '1' }
        }[preset.getAttribute('data-preset')];
        if (!values) {
          return;
        }
        productSelect.value = '0';
        valueInput.value = values.value;
        weightInput.value = values.weight;
        quantityInput.value = values.quantity;
        updateProductInputs();
        if (!advancedPanel.hidden) {
          syncAdvancedFromQuick();
        }
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      makePayload(form, advancedPanel, advancedDirty);
      results.hidden = true;
      results.innerHTML = '';
      setStatus(srtData.i18n.testing, 'loading');
      form.setAttribute('aria-busy', 'true');
      button.disabled = true;
      submitLabel.textContent = srtData.i18n.testingShort;

      wp.apiFetch({
        path: srtData.restUrl,
        method: 'POST',
        headers: {
          'X-WP-Nonce': srtData.nonce
        },
        data: getFormData(form)
      }).then(function (data) {
        setStatus('', 'success');
        currentResult = data;
        if (comparisonResults.length) {
          renderComparison(results, comparisonResults.concat([data]));
        } else {
          renderResults(results, data);
        }
        updateComparisonControls();
        results.setAttribute('tabindex', '-1');
        results.focus({ preventScroll: true });
      }).catch(function (error) {
        setStatus(error && error.message ? error.message : srtData.i18n.error, 'error');
      }).finally(function () {
        form.removeAttribute('aria-busy');
        button.disabled = false;
        submitLabel.textContent = srtData.i18n.testButton;
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
      setStatus(srtData.i18n.kept, 'success');
      updateComparisonControls();
    });

    clearButton.addEventListener('click', function () {
      comparisonResults = [];
      currentResult = null;
      results.hidden = true;
      results.innerHTML = '';
      setStatus('', '');
      updateComparisonControls();
    });

    resetButton.addEventListener('click', function () {
      form.reset();
      while (getRows().length > 1) {
        getRows()[getRows().length - 1].remove();
      }
      var row = getRows()[0];
      setRowField(row, 'source', 'custom');
      setRowField(row, 'product_id', '0');
      setRowField(row, 'value', '0');
      setRowField(row, 'weight', '0');
      setRowField(row, 'quantity', '1');
      setRowField(row, 'shipping_class_id', '0');
      setRowField(row, 'length', '0');
      setRowField(row, 'width', '0');
      setRowField(row, 'height', '0');
      advancedDirty = false;
      advancedPanel.hidden = true;
      advancedToggle.setAttribute('aria-expanded', 'false');
      advancedToggle.querySelector('.srt-toggle-label').textContent = srtData.i18n.advanced;
      updateRowNumbers();
      updateProductInputs();
      results.hidden = true;
      results.innerHTML = '';
      setStatus('', '');
      currentResult = null;
      comparisonResults = [];
      updateComparisonControls();
    });

    prepareRow(itemTemplate);
    updateRowNumbers();
    updateProductInputs();
    updateComparisonControls();
  });
}());

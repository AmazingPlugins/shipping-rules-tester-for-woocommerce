/**
 * Shipping Rules Tester admin UI.
 */
(function () {
  'use strict';

  function moveExternalAdminNotices() {
    var wrapper = document.querySelector('.srt-wrap');
    if (!wrapper || !wrapper.parentNode) {
      return;
    }

    wrapper.querySelectorAll('.notice').forEach(function (notice) {
      wrapper.parentNode.insertBefore(notice, wrapper);
    });
  }

  function setupCountryCombobox() {
    var countrySelect = document.querySelector('select[name="country"]');
    var countrySearch = document.getElementById('srt-country-search');
    var countryOptions = document.getElementById('srt-country-options');
    var countryPicker = document.querySelector('.srt-country-picker');
    if (!countrySelect || !countrySearch || !countryOptions || !countryPicker) {
      return;
    }

    Array.prototype.forEach.call(countrySelect.options, function (option) {
      var code = option.value;
      option.dataset.countryName = option.textContent;
      if (!/^[A-Z]{2}$/.test(code) || 'XK' === code) {
        return;
      }

      var flag = Array.from(code).map(function (letter) {
        return String.fromCodePoint(127397 + letter.charCodeAt(0));
      }).join('');
      option.dataset.countryFlag = flag;
    });

    var allCountryOptions = Array.prototype.map.call(countrySelect.options, function (option) {
      return option.cloneNode(true);
    });
    var activeOptionIndex = -1;

    function countryDisplay(option) {
      var flag = option.dataset.countryFlag ? option.dataset.countryFlag + ' ' : '';
      return flag + option.dataset.countryName + ' (' + option.value + ')';
    }

    function normalizeCountryQuery(value) {
      return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    function closeCountryOptions() {
      countryOptions.hidden = true;
      countrySearch.setAttribute('aria-expanded', 'false');
      countrySearch.removeAttribute('aria-activedescendant');
      activeOptionIndex = -1;
    }

    function setActiveOption(index) {
      var options = countryOptions.querySelectorAll('[role="option"]');
      if (!options.length) {
        return;
      }

      activeOptionIndex = (index + options.length) % options.length;
      Array.prototype.forEach.call(options, function (option, optionIndex) {
        option.classList.toggle('is-active', optionIndex === activeOptionIndex);
      });
      countrySearch.setAttribute('aria-activedescendant', options[activeOptionIndex].id);
      options[activeOptionIndex].scrollIntoView({ block: 'nearest' });
    }

    function chooseCountry(code) {
      var option = allCountryOptions.find(function (countryOption) {
        return countryOption.value === code;
      });
      if (!option) {
        return;
      }

      countrySelect.value = code;
      countrySearch.value = countryDisplay(option);
      countrySearch.setCustomValidity('');
      countrySearch.removeAttribute('aria-invalid');
      countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
      closeCountryOptions();
    }

    function renderCountryOptions(query) {
      var normalizedQuery = normalizeCountryQuery(query);
      var matchingOptions = allCountryOptions.filter(function (option) {
        var name = normalizeCountryQuery(option.dataset.countryName || option.textContent);
        var code = option.value.toLowerCase();
        return option.value && (!normalizedQuery || name.indexOf(normalizedQuery) !== -1 || code.indexOf(normalizedQuery) !== -1);
      });
      matchingOptions.sort(function (left, right) {
        function matchRank(option) {
          var code = option.value.toLowerCase();
          var name = normalizeCountryQuery(option.dataset.countryName || option.textContent);
          if (code === normalizedQuery) {
            return 0;
          }
          if (code.indexOf(normalizedQuery) === 0) {
            return 1;
          }
          if (name.indexOf(normalizedQuery) === 0) {
            return 2;
          }
          return 3;
        }

        return matchRank(left) - matchRank(right);
      });

      countryOptions.replaceChildren();
      activeOptionIndex = -1;
      countrySearch.removeAttribute('aria-activedescendant');

      matchingOptions.forEach(function (option) {
        var result = document.createElement('div');
        result.className = 'srt-country-option';
        result.id = 'srt-country-option-' + option.value.toLowerCase();
        result.setAttribute('role', 'option');
        result.setAttribute('aria-selected', option.value === countrySelect.value ? 'true' : 'false');
        result.dataset.countryCode = option.value;
        if (option.dataset.countryFlag) {
          var flag = document.createElement('span');
          flag.className = 'srt-country-flag';
          flag.setAttribute('aria-hidden', 'true');
          flag.dataset.flag = option.dataset.countryFlag;
          result.appendChild(flag);
        }
        result.appendChild(document.createTextNode(option.dataset.countryName + ' (' + option.value + ')'));
        countryOptions.appendChild(result);
      });

      if (!matchingOptions.length) {
        var noResults = document.createElement('div');
        noResults.className = 'srt-country-empty';
        noResults.setAttribute('role', 'status');
        noResults.textContent = srtData.i18n.noResults;
        countryOptions.appendChild(noResults);
      }

      countryOptions.hidden = false;
      countrySearch.setAttribute('aria-expanded', 'true');
    }

    countrySearch.addEventListener('input', function () {
      if (countrySelect.value) {
        countrySelect.value = '';
        countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
      countrySearch.setCustomValidity(countrySearch.value ? srtData.i18n.chooseFromList : '');
      if (countrySearch.value) {
        countrySearch.setAttribute('aria-invalid', 'true');
      } else {
        countrySearch.removeAttribute('aria-invalid');
      }
      renderCountryOptions(countrySearch.value);
    });

    countrySearch.addEventListener('focus', function () {
      var selectedOption = allCountryOptions.find(function (option) {
        return option.value === countrySelect.value;
      });
      if (selectedOption && countrySearch.value === countryDisplay(selectedOption)) {
        countrySearch.select();
        renderCountryOptions('');
      } else {
        renderCountryOptions(countrySearch.value);
      }
    });

    countrySearch.addEventListener('click', function () {
      if (countryOptions.hidden) {
        renderCountryOptions(countrySearch.value);
      }
    });

    countrySearch.addEventListener('blur', function () {
      window.setTimeout(function () {
        if (!countryPicker.contains(document.activeElement)) {
          closeCountryOptions();
        }
      }, 0);
    });

    countrySearch.addEventListener('keydown', function (event) {
      var options = countryOptions.querySelectorAll('[role="option"]');
      if ('ArrowDown' === event.key) {
        event.preventDefault();
        if (countryOptions.hidden) {
          renderCountryOptions(countrySearch.value);
          options = countryOptions.querySelectorAll('[role="option"]');
        }
        setActiveOption(activeOptionIndex + 1);
      } else if ('ArrowUp' === event.key) {
        event.preventDefault();
        if (countryOptions.hidden) {
          renderCountryOptions(countrySearch.value);
          options = countryOptions.querySelectorAll('[role="option"]');
        }
        setActiveOption(activeOptionIndex < 0 ? options.length - 1 : activeOptionIndex - 1);
      } else if ('Enter' === event.key && !countryOptions.hidden) {
        var activeOption = countryOptions.querySelectorAll('[role="option"]')[activeOptionIndex];
        if (activeOption) {
          event.preventDefault();
          chooseCountry(activeOption.dataset.countryCode);
        }
      } else if ('Escape' === event.key) {
        closeCountryOptions();
      }
    });

    countryOptions.addEventListener('mousedown', function (event) {
      if (event.target.closest('[role="option"]')) {
        event.preventDefault();
      }
    });

    countryOptions.addEventListener('click', function (event) {
      var option = event.target.closest('[role="option"][data-country-code]');
      if (option) {
        chooseCountry(option.dataset.countryCode);
      }
    });

    document.addEventListener('click', function (event) {
      if (!countryPicker.contains(event.target)) {
        closeCountryOptions();
      }
    });
  }

  setupCountryCombobox();
  moveExternalAdminNotices();

  var adminNoticesObserver = new MutationObserver(moveExternalAdminNotices);
  var testerWrapper = document.querySelector('.srt-wrap');
  if (testerWrapper) {
    adminNoticesObserver.observe(testerWrapper, { childList: true, subtree: true });
  }

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
    var resultActions = document.getElementById('srt-result-actions');
    var editParametersButton = document.getElementById('srt-edit-parameters');
    var resultResetButton = document.getElementById('srt-result-reset');
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
    var countryField = form.querySelector('select[name="country"]');
    var countrySearchField = document.getElementById('srt-country-search');
    var countryOptionsList = document.getElementById('srt-country-options');
    var stateStep = document.getElementById('srt-state-step');
    var stateField = document.getElementById('srt-state');
    var skipStateButton = document.getElementById('srt-skip-state');
    var postcodeStep = document.getElementById('srt-postcode-step');
    var postcodeField = document.getElementById('srt-postcode');
    var skipPostcodeButton = document.getElementById('srt-skip-postcode');
    var cityStep = document.getElementById('srt-city-step');
    var cityField = document.getElementById('srt-city');
    var packageSection = document.getElementById('srt-package-section');
    var formActions = document.getElementById('srt-form-actions');
    var summaryCard = document.getElementById('srt-summary-card');
    var shippingClassSource = document.getElementById('srt-shipping-class-source');
    var status = document.getElementById('srt-status');
    var results = document.getElementById('srt-results');
    var currentResult = null;
    var comparisonResults = [];
    var advancedDirty = false;
    var destinationCountry = null;
    var stateSkipped = false;
    var postcodeSkipped = false;

    function setStatus(message, type) {
      status.className = 'srt-status' + (type ? ' is-' + type : '');
      status.textContent = message || '';
    }

    function updateComparisonControls() {
      keepButton.hidden = !currentResult;
      clearButton.hidden = !comparisonResults.length;
    }

    function showEditor() {
      form.hidden = false;
      resultActions.hidden = true;
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      (countryField.value ? (valueInput.disabled ? quantityInput : valueInput) : countrySearchField).focus({ preventScroll: true });
    }

    function showFocusedResult() {
      form.hidden = true;
      resultActions.hidden = false;
      results.hidden = false;
      results.setAttribute('tabindex', '-1');
      results.scrollIntoView({ behavior: 'smooth', block: 'start' });
      results.focus({ preventScroll: true });
    }

    function updateProductInputs() {
      var usesProduct = productSelect.value !== '0';
      valueInput.disabled = usesProduct;
      weightInput.disabled = usesProduct;
      updateLiveSummary();
    }

    function updateDestinationFields() {
      var selectedCountry = countryField.value;
      var hasCountry = Boolean(selectedCountry);

      packageSection.hidden = !hasCountry;
      formActions.hidden = !hasCountry;
      summaryCard.hidden = !hasCountry;

      if (selectedCountry !== destinationCountry) {
        destinationCountry = selectedCountry;
        stateSkipped = false;
        postcodeSkipped = false;
        postcodeField.value = '';
        cityField.value = '';
        stateField.options.length = 1;

        var countryStates = srtData.states && srtData.states[selectedCountry] ? srtData.states[selectedCountry] : {};
        Object.keys(countryStates).forEach(function (stateCode) {
          var option = document.createElement('option');
          option.value = stateCode;
          option.textContent = countryStates[stateCode];
          stateField.appendChild(option);
        });
      }

      var hasStates = stateField.options.length > 1;
      stateStep.hidden = !selectedCountry || !hasStates;
      stateField.disabled = stateStep.hidden;

      var canShowPostcode = Boolean(selectedCountry) && (!hasStates || stateField.value || stateSkipped);
      postcodeStep.hidden = !canShowPostcode;
      postcodeField.disabled = !canShowPostcode;

      var canShowCity = canShowPostcode && (postcodeField.value.trim() !== '' || postcodeSkipped);
      cityStep.hidden = !canShowCity;
      cityField.disabled = !canShowCity;
    }

    function setAdvancedFieldsRequired(required) {
      itemsList.querySelectorAll('[data-item-field="value"], [data-item-field="weight"], [data-item-field="quantity"]').forEach(function (field) {
        field.required = required;
      });
    }

    function updateLiveSummary() {
      var selectedCountryOption = countryField.options[countryField.selectedIndex];
      var countryLabel = selectedCountryOption ? (selectedCountryOption.dataset.countryName || selectedCountryOption.text) : srtData.i18n.countryOnly;
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

      summaryDestination.textContent = countryField.value ? countryLabel : srtData.i18n.chooseCountry;
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
      setAdvancedFieldsRequired(!advancedPanel.hidden);
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
      setAdvancedFieldsRequired(willOpen);
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

    countryField.addEventListener('change', function () {
      updateDestinationFields();
      updateLiveSummary();
    });

    stateField.addEventListener('change', function () {
      stateSkipped = false;
      updateDestinationFields();
      updateLiveSummary();
      if (!postcodeStep.hidden) {
        postcodeField.focus();
      }
    });

    skipStateButton.addEventListener('click', function () {
      stateSkipped = true;
      stateField.value = '';
      updateDestinationFields();
      postcodeField.focus();
    });

    postcodeField.addEventListener('input', function () {
      postcodeSkipped = false;
      updateDestinationFields();
      updateLiveSummary();
    });

    postcodeField.addEventListener('change', updateLiveSummary);

    skipPostcodeButton.addEventListener('click', function () {
      postcodeSkipped = true;
      updateDestinationFields();
      cityField.focus();
    });

    [stateField, cityField, valueInput, weightInput, quantityInput].forEach(function (field) {
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
        showFocusedResult();
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
      showEditor();
    });

    clearButton.addEventListener('click', function () {
      comparisonResults = [];
      currentResult = null;
      results.hidden = true;
      results.innerHTML = '';
      setStatus('', '');
      updateComparisonControls();
      showEditor();
    });

    function resetForm() {
      form.reset();
      countrySearchField.setCustomValidity('');
      countrySearchField.removeAttribute('aria-invalid');
      countrySearchField.removeAttribute('aria-activedescendant');
      countrySearchField.setAttribute('aria-expanded', 'false');
      countryOptionsList.hidden = true;
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
      setAdvancedFieldsRequired(false);
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
      updateDestinationFields();
      showEditor();
    }

    editParametersButton.addEventListener('click', showEditor);
    resultResetButton.addEventListener('click', resetForm);
    resetButton.addEventListener('click', resetForm);

    prepareRow(itemTemplate);
    updateRowNumbers();
    updateDestinationFields();
    updateProductInputs();
    updateComparisonControls();
  });
}());

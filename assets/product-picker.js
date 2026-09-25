/**
 * Accessible, bounded product suggestions shared by quick and advanced inputs.
 */
(function () {
  'use strict';

  var nextID = 0;
  var activePicker = null;
  var suggestions = null;

  function requestProducts(query) {
    return wp.apiFetch({
      path: '/srt/v1/products?search=' + encodeURIComponent(query),
      headers: { 'X-WP-Nonce': srtData.nonce }
    });
  }

  function initialProducts() {
    if (!suggestions) {
      suggestions = requestProducts('').catch(function (error) {
        suggestions = null;
        throw error;
      });
    }
    return suggestions;
  }

  window.srtCreateProductPicker = function (select) {
    if (select.srtPicker) { return select.srtPicker; }
    var id = select.id || 'srt-item-product-' + (++nextID);
    select.id = id;
    select.hidden = true;
    var root = document.createElement('div');
    root.className = 'srt-product-picker';
    var input = document.createElement('input');
    input.type = 'search';
    input.id = id + '-search';
    input.className = 'srt-product-search';
    input.maxLength = 100;
    input.autocomplete = 'off';
    input.placeholder = srtData.i18n.searchHint;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-label', srtData.i18n.productSearch);
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', id + '-options');

    var popup = document.createElement('div');
    popup.className = 'srt-product-popover';
    popup.hidden = true;
    var status = document.createElement('p');
    status.className = 'srt-product-search-status';
    status.setAttribute('role', 'status');
    status.id = id + '-hint';
    input.setAttribute('aria-describedby', status.id);
    var list = document.createElement('div');
    list.className = 'srt-product-options';
    list.id = id + '-options';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', srtData.i18n.searchTop);
    popup.append(status, list);
    root.append(input, popup);
    select.after(root);

    var generation = 0;
    var timer;
    var activeIndex = -1;

    function label() {
      var option = select.options[select.selectedIndex];
      return option ? option.textContent.trim() : srtData.i18n.synthetic;
    }

    function close() {
      generation += 1;
      window.clearTimeout(timer);
      popup.hidden = true;
      root.classList.remove('is-open');
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      input.value = label();
      activeIndex = -1;
      if (activePicker === api) { activePicker = null; }
    }

    function render(products, message) {
      list.replaceChildren();
      status.textContent = message;
      activeIndex = -1;
      input.removeAttribute('aria-activedescendant');
      products.slice(0, 10).concat([{ id: 0, name: srtData.i18n.synthetic }]).forEach(function (product) {
        var option = document.createElement('div');
        option.className = 'srt-product-option' + (product.id === 0 ? ' is-synthetic' : '');
        option.id = id + '-option-' + product.id;
        option.dataset.productId = String(product.id);
        option.textContent = product.name;
        option.setAttribute('role', 'option');
        option.setAttribute('aria-selected', String(product.id) === select.value ? 'true' : 'false');
        list.appendChild(option);
      });
    }

    function load(query, delay) {
      var token = ++generation;
      window.clearTimeout(timer);
      var searching = query.length >= 3;
      render([], searching ? srtData.i18n.searchLoading : srtData.i18n.searchHint);
      function fetchResults() {
        (searching ? requestProducts(query) : initialProducts()).then(function (data) {
          if (token !== generation || popup.hidden || !root.isConnected) { return; }
          var message = searching ? (data.products.length ? srtData.i18n.searchTop : srtData.i18n.searchEmpty) : srtData.i18n.searchHint;
          render(data.products, data.more && searching ? srtData.i18n.searchMore : message);
        }).catch(function (error) {
          if (token === generation && !popup.hidden && root.isConnected) {
            render([], error.message || srtData.i18n.error);
          }
        });
      }
      if (delay && searching) { timer = window.setTimeout(fetchResults, 300); } else { fetchResults(); }
    }

    function open() {
      if (select.disabled || !popup.hidden) { return; }
      if (activePicker) { activePicker.close(); }
      activePicker = api;
      popup.hidden = false;
      root.classList.add('is-open');
      input.setAttribute('aria-expanded', 'true');
      load('', false);
    }

    function choose(option) {
      if (!option || select.disabled) { return; }
      var value = option.dataset.productId;
      var stored = Array.from(select.options).find(function (candidate) { return candidate.value === value; });
      if (!stored) {
        stored = document.createElement('option');
        stored.value = value;
        stored.textContent = option.textContent;
        select.appendChild(stored);
      }
      select.value = value;
      input.focus({ preventScroll: true });
      close();
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function sync() {
      input.disabled = select.disabled;
      if (select.disabled) { close(); }
      if (popup.hidden) { input.value = label(); }
    }

    input.addEventListener('focus', function () { open(); input.select(); });
    input.addEventListener('click', open);
    root.addEventListener('mouseenter', open);
    root.addEventListener('mouseleave', function () {
      if (!root.contains(document.activeElement)) { close(); }
    });
    input.addEventListener('blur', function () {
      window.setTimeout(function () { if (!root.contains(document.activeElement)) { close(); } }, 0);
    });
    input.addEventListener('input', function () {
      open();
      load(input.value.trim(), true);
    });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { event.preventDefault(); close(); return; }
      if (event.key === 'Enter') {
        if (!popup.hidden) {
          event.preventDefault();
          choose(list.children[activeIndex]);
        }
        return;
      }
      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') { return; }
      event.preventDefault();
      open();
      var options = Array.from(list.children);
      if (!options.length) { return; }
      activeIndex = event.key === 'ArrowDown' ? (activeIndex + 1) % options.length : (activeIndex < 0 ? options.length - 1 : (activeIndex + options.length - 1) % options.length);
      options.forEach(function (option, index) { option.classList.toggle('is-active', index === activeIndex); });
      input.setAttribute('aria-activedescendant', options[activeIndex].id);
      options[activeIndex].scrollIntoView({ block: 'nearest' });
    });
    list.addEventListener('mousedown', function (event) { event.preventDefault(); });
    list.addEventListener('click', function (event) { choose(event.target.closest('[data-product-id]')); });
    select.addEventListener('change', sync);

    var api = { close: close, sync: sync };
    select.srtPicker = api;
    sync();
    return api;
  };
}());

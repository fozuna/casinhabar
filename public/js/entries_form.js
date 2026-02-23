(function () {
  function normalizeText(s) {
    return (s || '').toString().toLowerCase();
  }
  function buildSearchField(opt) {
    var parts = [];
    if (opt.name) parts.push(opt.name);
    if (opt.cost_center) parts.push(opt.cost_center);
    if (opt.kindLabel) parts.push(opt.kindLabel);
    if (opt.doc) parts.push(opt.doc);
    if (opt.email) parts.push(opt.email);
    if (opt.phone) parts.push(opt.phone);
    return normalizeText(parts.join(' '));
  }
  function setupCombobox(root, options, hiddenInput, kind) {
    if (!root || !hiddenInput) return;
    var input = root.querySelector('input[role="combobox"]');
    var listbox = root.querySelector('[role="listbox"]');
    if (!input || !listbox) return;
    var state = { open: false, highlighted: -1, items: [] };
    options.forEach(function (o) {
      o._search = buildSearchField(o);
    });
    function renderItems() {
      while (listbox.firstChild) listbox.removeChild(listbox.firstChild);
      var q = normalizeText(input.value);
      var filtered = options.filter(function (o) {
        return !q || o._search.indexOf(q) !== -1;
      });
      state.items = filtered;
      state.highlighted = filtered.length ? 0 : -1;
      if (!filtered.length) {
        var empty = document.createElement('div');
        empty.setAttribute('role', 'option');
        empty.setAttribute('aria-disabled', 'true');
        empty.className = 'px-3 py-2 text-sm text-carbon_black-600';
        empty.textContent = 'Nenhum resultado encontrado';
        listbox.appendChild(empty);
        return;
      }
      filtered.forEach(function (o, idx) {
        var opt = document.createElement('div');
        opt.setAttribute('role', 'option');
        opt.setAttribute('data-id', o.id);
        opt.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-blue_bell-600 hover:text-white';
        if (idx === state.highlighted) {
          opt.className += ' bg-blue_bell-600 text-white';
          opt.setAttribute('aria-selected', 'true');
        }
        var line1 = document.createElement('div');
        line1.textContent = o.name;
        var line2 = document.createElement('div');
        line2.className = 'text-xs text-carbon_black-600';
        if (kind === 'type') {
          line2.textContent = o.kindLabel + ' • ' + (o.cost_center || '');
        } else {
          var info = [];
          info.push(o.kindLabel);
          if (o.doc) info.push(o.doc);
          if (o.email) info.push(o.email);
          line2.textContent = info.join(' • ');
        }
        opt.appendChild(line1);
        opt.appendChild(line2);
        opt.addEventListener('mousedown', function (ev) {
          ev.preventDefault();
          choose(o);
        });
        listbox.appendChild(opt);
      });
    }
    function open() {
      if (state.open) return;
      state.open = true;
      listbox.classList.remove('hidden');
      input.setAttribute('aria-expanded', 'true');
      renderItems();
    }
    function close() {
      if (!state.open) return;
      state.open = false;
      listbox.classList.add('hidden');
      input.setAttribute('aria-expanded', 'false');
    }
    function choose(o) {
      input.value = o.name;
      hiddenInput.value = o.id;
      close();
      updateSelectionSummary(root, o, kind);
      triggerValidation();
    }
    function move(delta) {
      if (!state.items.length) return;
      var idx = state.highlighted + delta;
      if (idx < 0) idx = state.items.length - 1;
      if (idx >= state.items.length) idx = 0;
      state.highlighted = idx;
      renderItems();
    }
    input.addEventListener('focus', function () {
      open();
    });
    input.addEventListener('input', function () {
      hiddenInput.value = '';
      open();
      renderItems();
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        open();
        move(1);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        open();
        move(-1);
      } else if (e.key === 'Enter') {
        if (state.open && state.items[state.highlighted]) {
          e.preventDefault();
          choose(state.items[state.highlighted]);
        }
      } else if (e.key === 'Escape') {
        close();
      }
    });
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) close();
    });
  }
  function updateSelectionSummary(root, option, kind) {
    var summary = root.querySelector('[data-selection-summary]');
    if (!summary) {
      summary = document.createElement('p');
      summary.setAttribute('data-selection-summary', '1');
      summary.className = 'mt-1 text-xs text-carbon_black-600';
      root.appendChild(summary);
    }
    if (kind === 'type') {
      summary.textContent = 'Selecionado: ' + option.name + ' • ' + option.kindLabel + ' • ' + (option.cost_center || '');
    } else {
      var parts = ['Selecionado: ' + option.name, option.kindLabel];
      if (option.doc) parts.push(option.doc);
      if (option.email) parts.push(option.email);
      summary.textContent = parts.join(' • ');
    }
  }
  function triggerValidation() {
    if (typeof window.entryValidateForm === 'function') {
      window.entryValidateForm();
    }
  }
  function setupValidation() {
    var formHidden = document.querySelector('input[name="form"][value="account"]');
    if (!formHidden) return;
    var form = formHidden.closest('form');
    if (!form) return;
    var btn = form.querySelector('button[type="submit"], button:not([type])');
    var errType = form.querySelector('[data-error="type"]');
    var errParty = form.querySelector('[data-error="party"]');
    var total = form.querySelector('input[name="total_amount"]');
    var inst = form.querySelector('input[name="installments"]');
    var due = form.querySelector('input[name="first_due_date"]');
    function setError(el, msg) {
      if (!el) return;
      if (msg) {
        el.textContent = msg;
        el.classList.remove('hidden');
      } else {
        el.textContent = '';
        el.classList.add('hidden');
      }
    }
    window.entryValidateForm = function () {
      var errors = [];
      var typeId = form.querySelector('input[name="account_type_id"]').value;
      var partyTypeSel = form.querySelector('select[name="party_type"]');
      var partyType = partyTypeSel ? partyTypeSel.value : 'customer';
      var partyId = form.querySelector('input[name="party_id"]').value;
      var totalVal = parseFloat(total && total.value ? total.value : '0');
      var instVal = parseInt(inst && inst.value ? inst.value : '0', 10);
      var dueVal = due && due.value ? due.value : '';
      setError(errType, '');
      setError(errParty, '');
      if (!typeId) {
        setError(errType, 'Selecione o tipo de conta');
        errors.push('tipo');
      }
      if (partyType !== 'none' && !partyId) {
        setError(errParty, 'Selecione o vínculo (cliente ou fornecedor)');
        errors.push('vínculo');
      }
      if (!totalVal || totalVal <= 0) {
        total.classList.add('ring-1', 'ring-brand', 'border-brand');
        errors.push('valor');
      } else {
        total.classList.remove('ring-1', 'ring-brand', 'border-brand');
      }
      if (!instVal || instVal <= 0) {
        inst.classList.add('ring-1', 'ring-brand', 'border-brand');
        errors.push('parcelas');
      } else {
        inst.classList.remove('ring-1', 'ring-brand', 'border-brand');
      }
      if (!dueVal) {
        due.classList.add('ring-1', 'ring-brand', 'border-brand');
        errors.push('vencimento');
      } else {
        due.classList.remove('ring-1', 'ring-brand', 'border-brand');
      }
      if (btn) btn.disabled = errors.length > 0;
      return errors.length === 0;
    };
    ['input', 'change'].forEach(function (evt) {
      form.addEventListener(evt, function () {
        window.entryValidateForm();
      });
    });
    form.addEventListener('submit', function (e) {
      if (!window.entryValidateForm()) {
        e.preventDefault();
      }
    });
    window.entryValidateForm();
  }
  document.addEventListener('DOMContentLoaded', function () {
    var typeData = (window.entryTypeOptions || []).map(function (t) {
      return {
        id: String(t.id),
        name: t.name,
        kindLabel: t.kind === 'despesa' ? 'Despesa' : 'Receita',
        cost_center: t.cost_center || ''
      };
    });
    var partyData = (window.entryPartyOptions || []).map(function (p) {
      return {
        id: p.id,
        name: p.name,
        kindLabel: p.kind === 'supplier' ? 'Fornecedor' : 'Cliente',
        doc: p.doc || '',
        email: p.email || '',
        phone: p.phone || ''
      };
    });
    var typeRoot = document.querySelector('[data-entry-combobox="type"]');
    var typeHidden = document.querySelector('input[name="account_type_id"]');
    if (typeRoot && typeHidden) setupCombobox(typeRoot, typeData, typeHidden, 'type');
    var partyRoot = document.querySelector('[data-entry-combobox="party"]');
    var partyHidden = document.querySelector('input[name="party_id"]');
    if (partyRoot && partyHidden) setupCombobox(partyRoot, partyData, partyHidden, 'party');
    setupValidation();
  });
})();


document.addEventListener('DOMContentLoaded', function(){
  var fileInput = document.getElementById('xlsxFile');
  var btnParse = document.getElementById('btnParse');
  var mapping = document.getElementById('mapping');
  var jsonPayload = document.getElementById('jsonPayload');
  var btnImport = document.getElementById('btnImportExcel');
  var form = document.getElementById('excelForm');
  var headers = [];
  var rows = [];

  function fillSelect(sel, label){
    sel.innerHTML = '';
    var opt0 = document.createElement('option'); opt0.value=''; opt0.textContent = label + ' (coluna)'; sel.appendChild(opt0);
    headers.forEach(function(h){ var o=document.createElement('option'); o.value=h; o.textContent=h; sel.appendChild(o); });
  }

  function parse(){
    var f = fileInput.files && fileInput.files[0];
    if(!f){ alert('Selecione um arquivo .xlsx'); return; }
    if(!window.XLSX){ alert('Biblioteca XLSX não carregada. Converta seu Excel para CSV e use a importação CSV.'); return; }
    var reader = new FileReader();
    reader.onload = function(e){
      var data = new Uint8Array(e.target.result);
      var wb = XLSX.read(data, {type:'array'});
      var sheet = wb.Sheets[wb.SheetNames[0]];
      var aoa = XLSX.utils.sheet_to_json(sheet, {header:1, defval:''});
      if(!aoa || aoa.length===0){ alert('Planilha vazia'); return; }
      headers = aoa[0].map(function(x){ return String(x).trim(); });
      rows = aoa.slice(1).map(function(r){ var obj={}; headers.forEach(function(h,idx){ obj[h]=String(r[idx]||''); }); return obj; });
      mapping.classList.remove('hidden');
      fillSelect(document.getElementById('map_date'),'Data');
      fillSelect(document.getElementById('map_amount'),'Valor');
      fillSelect(document.getElementById('map_party'),'Parte');
      fillSelect(document.getElementById('map_desc'),'Descrição');
      fillSelect(document.getElementById('map_doc'),'Documento');
      fillSelect(document.getElementById('map_status'),'Status');
      // preview
      var thead = document.querySelector('#preview thead'); var tbody = document.querySelector('#preview tbody');
      thead.innerHTML = '<tr>' + headers.map(function(h){ return '<th class="text-left py-1">'+h+'</th>'; }).join('') + '</tr>';
      tbody.innerHTML = rows.slice(0,10).map(function(r){ return '<tr class="border-t"><td class="py-1">'+headers.map(function(h){ return (r[h]||'').toString(); }).join('</td><td class="py-1">')+'</td></tr>'; }).join('');
    };
    reader.readAsArrayBuffer(f);
  }

  function buildJSON(){
    var map = {
      date: document.getElementById('map_date').value,
      amount: document.getElementById('map_amount').value,
      party: document.getElementById('map_party').value,
      description: document.getElementById('map_desc').value,
      document: document.getElementById('map_doc').value,
      status: document.getElementById('map_status').value
    };
    if(!map.date || !map.amount){ alert('Selecione ao menos Data e Valor.'); return null; }
    var out = rows.map(function(r){
      return {
        date: r[map.date]||'',
        amount: r[map.amount]||'',
        party: map.party? (r[map.party]||'') : '',
        description: map.description? (r[map.description]||'') : '',
        document: map.document? (r[map.document]||'') : '',
        status: map.status? (r[map.status]||'') : ''
      };
    });
    return out;
  }

  if(btnParse) btnParse.addEventListener('click', parse);
  if(btnImport) btnImport.addEventListener('click', function(){
    var out = buildJSON();
    if(!out) return;
    jsonPayload.value = JSON.stringify(out);
    form.submit();
  });
});

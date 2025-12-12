function drawLineChart(canvasId, labels, s1, s2, c1, c2) {
  var c = document.getElementById(canvasId);
  if (!c || !labels || labels.length === 0) return;
  var ctx = c.getContext('2d');
  var W = c.clientWidth || 600;
  var H = c.clientHeight || 280;
  c.width = W;
  c.height = H;
  var m = {l: 40, r: 10, t: 20, b: 30};
  var maxY = Math.max.apply(null, s1.concat(s2));
  if (!isFinite(maxY) || maxY <= 0) maxY = 1;
  var stepX = (W - m.l - m.r) / Math.max(1, labels.length - 1);
  function y(v){return H - m.b - (v / maxY) * (H - m.t - m.b)}
  ctx.clearRect(0,0,W,H);
  ctx.strokeStyle = '#ddd';
  ctx.beginPath();
  ctx.moveTo(m.l, H - m.b);
  ctx.lineTo(W - m.r, H - m.b);
  ctx.moveTo(m.l, m.t);
  ctx.lineTo(m.l, H - m.b);
  ctx.stroke();
  ctx.strokeStyle = c1;
  ctx.lineWidth = 2;
  ctx.beginPath();
  for (var i=0;i<s1.length;i++){var x = m.l + i*stepX; var yy = y(s1[i]); if(i===0) ctx.moveTo(x,yy); else ctx.lineTo(x,yy);} 
  ctx.stroke();
  ctx.strokeStyle = c2;
  ctx.beginPath();
  for (var j=0;j<s2.length;j++){var x2 = m.l + j*stepX; var yy2 = y(s2[j]); if(j===0) ctx.moveTo(x2,yy2); else ctx.lineTo(x2,yy2);} 
  ctx.stroke();
  ctx.fillStyle = '#555';
  ctx.font = '12px sans-serif';
  var tickCount = Math.min(labels.length, 6);
  for (var k=0;k<tickCount;k++){var idx = Math.round(k*(labels.length-1)/(tickCount-1)); var tx = m.l + idx*stepX; ctx.fillText(labels[idx], tx-20, H-10);} 
  ctx.fillStyle = c1; ctx.fillRect(W-140, m.t, 12, 12); ctx.fillStyle = '#333'; ctx.fillText('Receitas', W-122, m.t+11);
  ctx.fillStyle = c2; ctx.fillRect(W-70, m.t, 12, 12); ctx.fillStyle = '#333'; ctx.fillText('Despesas', W-52, m.t+11);
}

function drawDoughnut(canvasId, values, colors, labels) {
  var c = document.getElementById(canvasId);
  if (!c) return;
  var ctx = c.getContext('2d');
  var W = c.clientWidth || 300;
  var H = c.clientHeight || 240;
  c.width = W; c.height = H;
  var total = 0; for (var i=0;i<values.length;i++) total += values[i];
  var cx = W/2, cy = H/2, r = Math.min(W,H)/2 - 10;
  var start = -Math.PI/2;
  ctx.clearRect(0,0,W,H);
  if (total <= 0) { ctx.fillStyle = '#777'; ctx.font = '12px sans-serif'; ctx.textAlign='center'; ctx.fillText('Sem dados', cx, cy); return; }
  for (var j=0;j<values.length;j++){
    var frac = values[j]/total;
    var end = start + frac*2*Math.PI;
    ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,r,start,end); ctx.closePath(); ctx.fillStyle = colors[j]; ctx.fill(); start = end;
  }
  ctx.beginPath(); ctx.arc(cx,cy,r*0.6,0,2*Math.PI); ctx.fillStyle='#fff'; ctx.fill();
  ctx.fillStyle = '#333'; ctx.textAlign='center'; ctx.font='12px sans-serif';
  ctx.fillText(labels[0]+': '+values[0].toLocaleString('pt-BR',{minimumFractionDigits:2}), cx, cy-8);
  ctx.fillText(labels[1]+': '+values[1].toLocaleString('pt-BR',{minimumFractionDigits:2}), cx, cy+12);
}

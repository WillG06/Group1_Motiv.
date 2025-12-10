/* ========= Motiv Admin Dashboard ========= */

/* ========= DARK / LIGHT THEME HANDLING ========= */

(function () {
  const STORAGE_KEY = 'motiv-theme';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  }

  function getPreferredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'light' || stored === 'dark') return stored;

    return window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';
  }

  function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    applyTheme(next);
    localStorage.setItem(STORAGE_KEY, next);
    updateToggleIcon(next);
  }

  function updateToggleIcon(theme) {
    const btn = document.getElementById('admin-theme-toggle');
    if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  }

  document.addEventListener('DOMContentLoaded', () => {
    const initial = getPreferredTheme();
    applyTheme(initial);
    updateToggleIcon(initial);

    const toggleBtn = document.getElementById('admin-theme-toggle');
    if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);
  });
})();

/* ========= Tiny helpers ========= */
const $ = (s, r=document) => r.querySelector(s);
const $$ = (s, r=document) => [...r.querySelectorAll(s)];
const fmtGBP = n => `£ ${Number(n).toLocaleString('en-UK', {minimumFractionDigits:2, maximumFractionDigits:2})}`;

/* Mock data (replace with backend later) */
const mockSummary = {
  today:   { income: 7800, expenses: 6780, hires: 32, cancels: 5, incomeSpark: [600,950,820,1100,920,1400,2000], expenseSpark:[320,400,550,700,820,900,1790] },
  week:    { income: 42150, expenses: 28990, hires: 166, cancels: 21, incomeSpark: [5200,6300,5900,7200,6800,7600,8150], expenseSpark:[3200,4200,3800,5100,4700,5200,5490] },
  month:   { income: 176400, expenses: 119800, hires: 690, cancels: 74, incomeSpark: [3800, 6400, 5200, 5800, 7100, 8200, 6400, 7800, 9100, 8800, 7600, 8400],
             expenseSpark:[2400, 3500, 3300, 3600, 3900, 4100, 3800, 4000, 4500, 4700, 4300, 4400] }
};

const mockAvailability = [
  { car:"Tesla Model S Plaid",   cls:"EV",           city:"London",     status:"available", rate:120 },
  { car:"BMW M3 Competition",    cls:"Performance",  city:"Manchester", status:"available", rate:105 },
  { car:"Mercedes-AMG C63S",     cls:"Performance",  city:"Leeds",      status:"booked",    rate:110 },
  { car:"Toyota Corolla",        cls:"Economy",      city:"Birmingham", status:"available", rate:42  },
  { car:"Nissan Qashqai",        cls:"Compact SUV",  city:"Liverpool",  status:"service",   rate:49  },
  { car:"Audi A3",               cls:"Compact",      city:"London",     status:"booked",    rate:55  },
  { car:"VW Golf",               cls:"Compact",      city:"Bristol",    status:"available", rate:48  },
  { car:"Kia Sportage",          cls:"SUV",          city:"Glasgow",    status:"available", rate:58  },
];

/* ====== Range controls → update metrics + tiny charts ====== */
function updateSummary(kind){
  const data = mockSummary[kind];

  $('#income-amount').textContent  = fmtGBP(data.income);
  $('#expense-amount').textContent = fmtGBP(data.expenses);
  $('#hires-count').textContent    = data.hires;
  $('#cancels-count').textContent  = data.cancels;

  drawSpark($('#income-chart'),  data.incomeSpark);
  drawSpark($('#expense-chart'), data.expenseSpark);
  drawBars($('#hire-chart'), [data.hires, data.cancels]);
}

$('#income-range').addEventListener('change', e => updateSummary(e.target.value));
$('#expense-range').addEventListener('change', e => updateSummary(e.target.value));
$('#hire-range').addEventListener('change', e => updateSummary(e.target.value));

/* ====== Availability table + filters ====== */
function renderAvailability(rows){
  const tb = $('#availability-table tbody');
  tb.innerHTML = '';
  rows.forEach(r=>{
    const tr = document.createElement('tr');
    const badgeClass = r.status === 'available' ? 'badge--ok' :
                       r.status==='booked'      ? 'badge--bad' :
                                                  'badge--warn';
    tr.innerHTML = `
      <td>${r.car}</td>
      <td>${r.cls}</td>
      <td>${r.city}</td>
      <td><span class="badge ${badgeClass}">${r.status}</span></td>
      <td>${r.rate}</td>
    `;
    tb.appendChild(tr);
  });
}

function applyAvailabilityFilters(){
  const q = $('#car-search').value.trim().toLowerCase();
  const f = $('#availability-filter').value;
  let rows = mockAvailability.filter(r =>
    r.car.toLowerCase().includes(q) ||
    r.city.toLowerCase().includes(q) ||
    r.cls.toLowerCase().includes(q)
  );
  if (f !== 'all') rows = rows.filter(r => r.status === f);
  renderAvailability(rows);
}

$('#car-search').addEventListener('input', applyAvailabilityFilters);
$('#availability-filter').addEventListener('change', applyAvailabilityFilters);

/* ====== Tiny chart drawers ====== */
function drawSpark(canvas, values){
  const ctx = canvas.getContext('2d');
  const W = canvas.width  = canvas.clientWidth  * devicePixelRatio;
  const H = canvas.height = canvas.clientHeight * devicePixelRatio;
  ctx.scale(devicePixelRatio, devicePixelRatio);

  ctx.clearRect(0,0,W,H);

  const max = Math.max(...values), min = Math.min(...values);
  const pad = 8, w = canvas.clientWidth - pad*2, h = canvas.clientHeight - pad*2;

  ctx.lineWidth = 2;
  ctx.strokeStyle = '#9a6bff';
  ctx.beginPath();

  values.forEach((v,i)=>{
    const x = pad + (i/(values.length-1))*w;
    const y = pad + (1 - (v-min)/(max-min||1))*h;
    i ? ctx.lineTo(x,y) : ctx.moveTo(x,y);
  });
  ctx.stroke();

  const g = ctx.createLinearGradient(0,pad,0,h+pad);
  g.addColorStop(0,'rgba(154,107,255,.25)');
  g.addColorStop(1,'rgba(154,107,255,0)');
  ctx.fillStyle = g;

  ctx.lineTo(pad+w, h+pad);
  ctx.lineTo(pad, h+pad);
  ctx.closePath();
  ctx.fill();
}

function drawBars(canvas, values){
  const ctx = canvas.getContext('2d');
  const W = canvas.width  = canvas.clientWidth  * devicePixelRatio;
  const H = canvas.height = canvas.clientHeight * devicePixelRatio;
  ctx.scale(devicePixelRatio, devicePixelRatio);
  ctx.clearRect(0,0,W,H);

  const pad = 12;
  const w = (canvas.clientWidth - pad*3) / 2;
  const max = Math.max(...values,1);
  const colors = ['#6b2bd8','#9a6bff'];

  values.forEach((v,i)=>{
    const x = pad + i*(w+pad);
    const h = (v/max) * (canvas.clientHeight - pad*2);
    const y = canvas.clientHeight - pad - h;
    ctx.fillStyle = colors[i];
    ctx.fillRect(x,y,w,h);
  });
}

/* ====== Router buttons ====== */
$$('.side-link').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    $$('.side-link').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
  });
});

/* ====== Logout ====== */
$('#btn-logout').addEventListener('click', ()=>{
  if (confirm('Log out of admin?')) window.location.href = '/logout';
});

/* ====== Boot ====== */
(function boot(){
  $('#today-chip').textContent = new Date().toLocaleDateString('en-GB', {
    day:'2-digit', month:'short', year:'numeric'
  });
  updateSummary('today');
  applyAvailabilityFilters();
})();

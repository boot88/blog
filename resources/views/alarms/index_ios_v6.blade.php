@extends('layouts.app')
@section('title','')
@section('content')
<style>
body{background:#f5f5f7;color:#111}
h1+div{display:none!important}
.page{display:grid;grid-template-columns:1fr minmax(320px,420px);gap:18px;align-items:start}
.clock-panel{background:#fff;border:1px solid #e5e5ea;border-radius:18px;padding:20px;min-height:420px}
.clock-wrap{display:flex;align-items:center;justify-content:flex-start;gap:20px}
.clock-box{width:190px;height:190px;position:relative}
#digital{position:absolute;top:0;left:0;width:190px;height:190px;display:flex;align-items:center;justify-content:center;font-size:30px}
.next{color:#3c3c43;margin-top:14px}
.alarm-list{margin-top:14px;border-top:1px solid #ececec}
.alarm{display:flex;justify-content:space-between;align-items:center;padding:14px 4px;border-bottom:1px solid #eee;cursor:pointer}
.alarm.disabled{opacity:.5}
.alarm-time{font-size:36px;font-weight:300;line-height:1}
.alarm-note{font-size:13px;color:#6e6e73}
.toggle{width:50px;height:28px;background:#666;border-radius:20px;position:relative;cursor:pointer;flex:0 0 auto}
.toggle.active{background:#34c759}
.toggle::after{content:'';width:24px;height:24px;background:white;border-radius:50%;position:absolute;top:2px;left:2px;transition:.2s}
.toggle.active::after{left:24px}
.alarm-add{margin-top:12px}

.feed{background:#fff;border:1px solid #e5e5ea;border-radius:18px;padding:14px}
.feed-tools{display:flex;gap:8px;align-items:center;margin-bottom:10px}
.feed-tools input{flex:1;border:1px solid #d1d1d6;border-radius:10px;padding:8px 10px}
.btn-mini{border:1px solid #d1d1d6;background:#fff;border-radius:10px;padding:8px 10px;cursor:pointer}
.cats{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.cat-chip{border:1px solid #d1d1d6;background:#fff;color:#1c1c1e;padding:6px 9px;border-radius:999px;font-size:12px;cursor:pointer}
.cat-chip.active{background:#007aff;border-color:#007aff;color:#fff}
.task-list{max-height:360px;overflow:auto;border-top:1px solid #eee;padding-top:6px}
.task{display:flex;justify-content:space-between;gap:8px;padding:10px 4px;border-bottom:1px solid #f0f0f0}
.task-title{font-weight:600}
.task-meta{font-size:12px;color:#6e6e73}
.task-del{border:1px solid #ffd6d3;background:#fff1f0;color:#c62828;border-radius:8px;padding:2px 8px;cursor:pointer;height:28px}
.feed-foot{margin-top:10px}

.quick-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10020}
.quick-overlay{position:absolute;inset:0;background:rgba(0,0,0,.35)}
.quick-body{position:relative;background:#fff;border-radius:16px;padding:14px;width:min(420px,94vw);border:1px solid #e5e5ea}
.quick-row{display:flex;gap:8px;margin-bottom:8px}
.quick-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:10px}
</style>

<div class="page">
  <div class="clock-panel">
    <div class="clock-wrap" onclick="toggleClock()">
      <div class="clock-box">
        <canvas id="clockCanvas" width="190" height="190"></canvas>
        <div id="digital" style="display:none"></div>
      </div>
    </div>
    <div class="next" id="nextText"></div>
    <div class="alarm-list">
      @foreach($alarms as $alarm)
      <div class="alarm {{ $alarm->enabled?'':'disabled' }}" data-id="{{ $alarm->id }}" onclick="editAlarm({{ $alarm->id }})">
        <div>
          <div class="alarm-time">{{ substr($alarm->time,0,5) }}</div>
          <div class="alarm-note">{{ $alarm->title }}</div>
        </div>
        <div class="toggle {{ $alarm->enabled?'active':'' }}" onclick="event.stopPropagation();toggleAlarm(this,{{ $alarm->id }})"></div>
      </div>
      @endforeach
    </div>
    <div class="alarm-add">
      <a href="/alarms/create" class="btn-mini" style="background:#34c759;border-color:#34c759;color:#fff">+ Добавить будильник</a>
    </div>
  </div>

  <aside class="feed">
    <div class="feed-tools">
      <input id="searchInput" placeholder="Поиск по слову...">
      <button class="btn-mini" onclick="openQuickAdd()">+ Добавить</button>
    </div>
    <div id="catChips" class="cats"></div>
    <div id="taskList" class="task-list"></div>
    <div class="feed-foot" id="taskCount"></div>
  </aside>
</div>

<div id="quickModal" class="quick-modal">
  <div class="quick-overlay" onclick="closeQuickAdd()"></div>
  <div class="quick-body">
    <div class="quick-row">
      <input id="quickTitle" placeholder="Название задачи">
    </div>
    <div class="quick-row">
      <input id="quickTime" type="time">
      <select id="quickCategory">
        <option>Общие</option>
        <option>Финанс</option>
        <option>Програмные</option>
        <option>Партнёрки</option>
        <option>Системные</option>
      </select>
    </div>
    <div class="quick-actions">
      <button class="btn-mini" onclick="closeQuickAdd()">Отмена</button>
      <button class="btn-mini" style="background:#007aff;color:#fff;border-color:#007aff" onclick="quickAdd()">Сохранить</button>
    </div>
  </div>
</div>

<script>
let digital=false;
let alarms=@json($alarms);
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const toggleUrlTemplate = @json(route('alarms.toggle-enabled', ['alarm' => '__ALARM_ID__']));
const categories = ['Все','Общие','Финанс','Програмные','Партнёрки','Системные'];
let selectedCategory = 'Все';
const categoryKey = 'alarm_categories_v1';
const pendingKey = 'alarm_pending_category_v1';
let categoryMap = JSON.parse(localStorage.getItem(categoryKey) || '{}');

reconcilePendingCategory();

function persistCategories(){
  localStorage.setItem(categoryKey, JSON.stringify(categoryMap));
}

function reconcilePendingCategory(){
  const pendingRaw = localStorage.getItem(pendingKey);
  if (!pendingRaw) return;
  try{
    const pending = JSON.parse(pendingRaw);
    const sorted = [...alarms].sort((a,b) => sortKey(b) - sortKey(a));
    const match = sorted.find(a => String(a.title) === String(pending.title) && String(a.time).slice(0,5) === String(pending.time));
    if (match && !categoryMap[String(match.id)]) {
      categoryMap[String(match.id)] = pending.category || 'Общие';
      persistCategories();
    }
  }catch(e){}
  localStorage.removeItem(pendingKey);
}

function sortKey(a){
  if (a.created_at) return new Date(a.created_at).getTime();
  return Number(a.id || 0);
}

function alarmCategory(a){
  return categoryMap[String(a.id)] || 'Общие';
}

function filteredAlarms(){
  const q = (document.getElementById('searchInput').value || '').trim().toLowerCase();
  return [...alarms]
    .filter(a => selectedCategory === 'Все' || alarmCategory(a) === selectedCategory)
    .filter(a => !q || `${a.title || ''} ${a.note || ''}`.toLowerCase().includes(q))
    .sort((a,b) => sortKey(b) - sortKey(a));
}

function renderCategoryChips(){
  const wrap = document.getElementById('catChips');
  wrap.innerHTML = categories.map(cat => `
    <button class="cat-chip ${selectedCategory===cat?'active':''}" onclick="setCategory('${cat}')">${cat}</button>
  `).join('');
}

function renderTaskList(){
  const list = document.getElementById('taskList');
  const data = filteredAlarms();

  if (!data.length){
    list.innerHTML = '<div style="padding:12px;color:#8e8e93">Задач не найдено</div>';
  } else {
    list.innerHTML = data.map(a => `
      <div class="task">
        <div>
          <div class="task-title">${escapeHtml(a.title || 'Без названия')}</div>
          <div class="task-meta">${escapeHtml(String(a.time || '').slice(0,5))} · ${escapeHtml(alarmCategory(a))}</div>
        </div>
        <button class="task-del" onclick="removeTask(${a.id})">Удалить</button>
      </div>
    `).join('');
  }

  document.getElementById('taskCount').innerText = `Показано: ${Math.min(data.length, 5)} из ${data.length}`;
}

async function toggleAlarm(el,id){
  el.classList.toggle('active');
  const row=el.closest('.alarm');
  row.classList.toggle('disabled');
  const isActive = el.classList.contains('active');
  const previousState = !isActive;
  alarms = alarms.map(a=> Number(a.id)===Number(id) ? {...a, enabled: isActive} : a);
  computeNextText();

  try{
    const toggleUrl = toggleUrlTemplate.replace('__ALARM_ID__', String(id));
    const res = await fetch(toggleUrl, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ enabled: isActive ? 1 : 0 })
    });
    if(!res.ok) throw new Error('toggle request failed');
  }catch(err){
    el.classList.toggle('active', previousState);
    row.classList.toggle('disabled', !previousState);
    alarms = alarms.map(a=> Number(a.id)===Number(id) ? {...a, enabled: previousState} : a);
    computeNextText();
  }
}

function editAlarm(id){
  window.location = `/alarms/${id}/edit`;
}

function setCategory(cat){
  selectedCategory = cat;
  renderCategoryChips();
  renderTaskList();
}

document.getElementById('searchInput').addEventListener('input', renderTaskList);
renderCategoryChips();
renderTaskList();

function escapeHtml(value){
  return String(value)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#39;");
}

function openQuickAdd(){
  document.getElementById('quickTime').value = new Date().toTimeString().slice(0,5);
  document.getElementById('quickTitle').value = '';
  document.getElementById('quickCategory').value = 'Общие';
  document.getElementById('quickModal').style.display='flex';
}
function closeQuickAdd(){
  document.getElementById('quickModal').style.display='none';
}

async function quickAdd(){
  const title = (document.getElementById('quickTitle').value || '').trim();
  const time = document.getElementById('quickTime').value;
  const category = document.getElementById('quickCategory').value || 'Общие';
  if (!title || !time) return;

  localStorage.setItem(pendingKey, JSON.stringify({title, time, category}));

  const payload = new URLSearchParams();
  payload.set('_token', csrfToken);
  payload.set('title', title);
  payload.set('time', time);
  payload.set('enabled', '1');
  payload.set('weekdays', JSON.stringify([1,1,1,1,1,1,1]));
  payload.set('sound', 'alarm.mp3');
  payload.set('duration', '10');
  payload.set('snooze_duration', '10');
  payload.set('snooze_repeats', '3');

  await fetch('/alarms', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: payload.toString()
  });

  location.reload();
}

async function removeTask(id){
  if (!confirm('Удалить задачу?')) return;
  try{
    const res = await fetch(`/alarms/${id}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({_method:'DELETE'}).toString()
    });
    if(!res.ok) throw new Error('delete failed');
    alarms = alarms.filter(a => Number(a.id) !== Number(id));
    document.querySelector(`.alarm[data-id="${id}"]`)?.remove();
    delete categoryMap[String(id)];
    persistCategories();
    renderTaskList();
    computeNextText();
  }catch(e){}
}

function toggleClock(){
  digital=!digital;
  document.getElementById('clockCanvas').style.display=digital?'none':'block';
  document.getElementById('digital').style.display=digital?'flex':'none';
}

function getNowInAlarmTimezone() {
  return new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Novosibirsk' }));
}

function drawClock(){
  const canvas=document.getElementById('clockCanvas');
  const ctx=canvas.getContext('2d');
  const now = getNowInAlarmTimezone();
  ctx.clearRect(0,0,190,190);

  let grad=ctx.createRadialGradient(95,95,70,95,95,95);
  grad.addColorStop(0,'#ffffff');
  grad.addColorStop(1,'#ddd');
  ctx.fillStyle=grad;
  ctx.beginPath();ctx.arc(95,95,88,0,Math.PI*2);ctx.fill();

  for(let i=0;i<60;i++){
    let a=i*Math.PI/30;
    ctx.beginPath();
    ctx.moveTo(95+74*Math.cos(a),95+74*Math.sin(a));
    ctx.lineTo(95+88*Math.cos(a),95+88*Math.sin(a));
    ctx.strokeStyle='#aaa';ctx.stroke();
  }

  ctx.font='13px Arial';ctx.textAlign='center';ctx.textBaseline='middle';
  for(let i=1;i<=12;i++){
    let a=(i-3)*Math.PI/6;
    ctx.fillStyle='#333';
    ctx.fillText(i,95+62*Math.cos(a),95+62*Math.sin(a));
  }

  let sec=now.getSeconds(), min=now.getMinutes(), hr=now.getHours()%12;
  let hA = (hr + min / 60 - 3) * Math.PI / 6;
  ctx.beginPath();ctx.moveTo(95,95);ctx.lineTo(95+40*Math.cos(hA),95+40*Math.sin(hA));
  ctx.lineWidth=4;ctx.strokeStyle='#444';ctx.stroke();

  let mA = (min - 15) * Math.PI / 30;
  ctx.beginPath();ctx.moveTo(95,95);ctx.lineTo(95+58*Math.cos(mA),95+58*Math.sin(mA));
  ctx.lineWidth=3;ctx.strokeStyle='#666';ctx.stroke();

  let sA = (sec - 15) * Math.PI / 30;
  ctx.beginPath();ctx.moveTo(95,95);ctx.lineTo(95+72*Math.cos(sA),95+72*Math.sin(sA));
  ctx.strokeStyle='#ff3b30';ctx.lineWidth=2;ctx.stroke();
  ctx.beginPath();ctx.arc(95,95,5,0,Math.PI*2);ctx.fillStyle='#000';ctx.fill();

  document.getElementById('digital').innerText = now.toLocaleTimeString('ru-RU');
}
setInterval(drawClock,1000);drawClock();

function getWeekdayIndexMondayFirst(date) {
  return (date.getDay() + 6) % 7;
}

function getNextAlarmDiffMs(alarm, now) {
  if (!alarm.enabled) return null;
  const [h, m] = alarm.time.split(':').map(Number);
  const days = Array.isArray(alarm.weekdays) ? alarm.weekdays : [1,1,1,1,1,1,1];
  if (!days.some(Boolean)) return null;

  let bestDiff = null;
  for (let shift = 0; shift < 7; shift++) {
    const candidate = new Date(now);
    candidate.setDate(candidate.getDate() + shift);
    candidate.setHours(h, m, 0, 0);
    const weekday = getWeekdayIndexMondayFirst(candidate);
    if (!days[weekday]) continue;
    const diff = candidate - now;
    if (diff >= 0 && (bestDiff === null || diff < bestDiff)) bestDiff = diff;
  }
  return bestDiff;
}

function computeNextText(){
  const now = getNowInAlarmTimezone();
  let minDiff=null;
  alarms.forEach(a=>{
    const diff = getNextAlarmDiffMs(a, now);
    if(diff===null) return;
    if(minDiff===null || diff<minDiff) minDiff=diff;
  });

  const el=document.getElementById('nextText');
  if(minDiff===null){
    el.innerText='Нет включенных будильников';
    return;
  }

  let sec=Math.floor(minDiff/1000);
  let d=Math.floor(sec/86400); sec%=86400;
  let h=Math.floor(sec/3600); sec%=3600;
  let m=Math.floor(sec/60);

  let txt='Ближайший сигнал через ';
  if(d) txt+=d+' д ';
  if(h) txt+=h+' ч ';
  txt+=m+' мин';
  el.innerText=txt;
}
computeNextText();
setInterval(computeNextText,60000);
</script>
@endsection

@extends('layouts.app')
@php $noHeader = true; @endphp
@section('title','Будильник')
@section('content')
<style>
body{background:#f5f5f7;color:#000;margin:0}
header,nav,.topbar{display:none!important}

.header{display:flex;justify-content:space-between;align-items:center;padding:15px;background:#fff;font-size:20px}
.btn{font-size:22px;cursor:pointer}

.picker{display:flex;justify-content:center;gap:20px;margin:30px 0}
.col{height:200px;overflow-y:auto;scroll-snap-type:y mandatory}
.col div{height:40px;display:flex;align-items:center;justify-content:center;scroll-snap-align:center;color:#000}

.block{background:#fff;margin:10px;border-radius:12px;padding:15px}
.row{display:flex;justify-content:space-between;cursor:pointer}
.row span{color:#000}

.modal{position:fixed;inset:0;background:rgba(0,0,0,.3);display:none;align-items:center;justify-content:center}
.modal-content{background:#fff;padding:20px;border-radius:12px;width:300px}

.toast{
  position:fixed;
  bottom:20px;
  left:50%;
  transform:translateX(-50%);
  background:rgba(0,0,0,.75);
  color:#fff;
  padding:10px 20px;
  border-radius:20px;
  display:none;
  z-index:9999;
}
</style>

<div class="header">
  <div class="btn" onclick="closePage()">✕</div>
  <div>Будильник</div>
  <div class="btn" onclick="save()">✔</div>
</div>

<div class="picker">
  <div class="col" id="h"></div>
  <div class="col" id="m"></div>
</div>

<div class="block" onclick="openDays()">
  <div class="row"><span>Дни недели</span><span id="daysText">ежедневно</span></div>
</div>

<div class="block" onclick="openSound()">
  <div class="row"><span>Звук</span><span>по умолчанию</span></div>
</div>

<div class="block" onclick="editNote()">
  <div class="row"><span>Описание</span><span id="noteText">{{ $alarm->note ?: '—' }}</span></div>
</div>

<div class="block">
  <div class="row"><span>Название</span><span id="titleText">{{ $alarm->title }}</span></div>
</div>

<div class="block">
  <div class="row"><span>Длительность</span><span>10 мин</span></div>
</div>

<div class="block">
  <div class="row"><span>Пауза</span><span>10 мин ×3</span></div>
</div>

<div class="block" onclick="del()" style="color:red;text-align:center">Удалить</div>

<div class="modal" id="daysModal">
  <div class="modal-content">
    <div>Дни недели</div>
    <div id="daysList"></div>
    <button onclick="closeDays()">OK</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const alarm = {
  id: {{ $alarm->id }},
  time: '{{ substr($alarm->time, 0, 5) }}',
  title: @json($alarm->title),
  note: @json($alarm->note ?? ''),
  enabled: {{ $alarm->enabled ? 'true' : 'false' }},
  date: @json(optional($alarm->date)->format('Y-m-d'))
};

let days = [1,1,1,1,1,1,1];

function showToast(text){
  const toast = document.getElementById('toast');
  toast.innerText = text;
  toast.style.display = 'block';
  setTimeout(() => toast.style.display = 'none', 1800);
}

function fill(){
  const hEl = document.getElementById('h');
  const mEl = document.getElementById('m');

  hEl.innerHTML = '';
  mEl.innerHTML = '';

  for(let i=0;i<24;i++){
    hEl.innerHTML += `<div>${String(i).padStart(2,'0')}</div>`;
  }
  for(let i=0;i<60;i++){
    mEl.innerHTML += `<div>${String(i).padStart(2,'0')}</div>`;
  }

  const [hh, mm] = alarm.time.split(':').map(Number);
  hEl.scrollTop = hh * 40;
  mEl.scrollTop = mm * 40;
}
fill();

function getTime(){
  const hEl = document.getElementById('h');
  const mEl = document.getElementById('m');
  let hi = Math.round(hEl.scrollTop / 40);
  let mi = Math.round(mEl.scrollTop / 40);

  hi = Math.max(0, Math.min(23, hi));
  mi = Math.max(0, Math.min(59, mi));

  return `${String(hi).padStart(2,'0')}:${String(mi).padStart(2,'0')}`;
}

function save(){
  const body = new URLSearchParams();
  body.append('_method', 'PUT');
  body.append('title', alarm.title);
  body.append('note', alarm.note ?? '');
  body.append('time', getTime());
  body.append('enabled', alarm.enabled ? '1' : '0');

  if (alarm.date) {
    body.append('date', alarm.date);
  }

  fetch(`/alarms/${alarm.id}`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    },
    body: body.toString()
  })
  .then(async (r) => {
    const text = await r.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch(e) {}

    if (!r.ok) {
      throw new Error(data.message || 'Не удалось сохранить будильник');
    }

    return data;
  })
  .then(() => {
    showToast('Сохранено');
    setTimeout(() => location.href = '/alarms', 500);
  })
  .catch((e) => {
    console.error(e);
    alert(e.message || 'Ошибка при сохранении');
  });
}

function closePage(){
  history.back();
}

function openDays(){
  document.getElementById('daysModal').style.display = 'flex';
  renderDays();
}

function closeDays(){
  document.getElementById('daysModal').style.display = 'none';
}

function renderDays(){
  const names = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
  const list = document.getElementById('daysList');
  list.innerHTML = '';

  names.forEach((n,i) => {
    list.innerHTML += `<div onclick="toggleDay(${i})">${n} ${days[i] ? '✓' : ''}</div>`;
  });
}

function toggleDay(i){
  days[i] = !days[i];
  renderDays();
}

function openSound(){
  alert('звуки позже подключим');
}

function editNote(){
  const t = prompt('Описание', alarm.note ?? '');
  if (t === null) return;

  alarm.note = t;
  document.getElementById('noteText').innerText = t || '—';
}

function del(){
  if (!confirm('Удалить?')) return;

  const body = new URLSearchParams();
  body.append('_method', 'DELETE');

  fetch(`/alarms/${alarm.id}`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    },
    body: body.toString()
  })
  .then(async (r) => {
    const text = await r.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch(e) {}

    if (!r.ok) {
      throw new Error(data.message || 'Не удалось удалить будильник');
    }

    return data;
  })
  .then(() => {
    showToast('Удалено');
    setTimeout(() => location.href = '/alarms', 400);
  })
  .catch((e) => {
    console.error(e);
    alert(e.message || 'Ошибка при удалении');
  });
}
</script>
@endsection

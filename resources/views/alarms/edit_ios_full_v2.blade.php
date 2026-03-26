@extends('layouts.app')
@php $noHeader = true; @endphp
@section('title','Будильник')
@section('content')
<style>
body{background:#f5f5f7;color:#000;margin:0}
header,nav,.topbar{display:none!important}

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:15px;
  background:#fff;
  font-size:18px;
}

.title{
  font-weight:600;
}

.btn{
  /*ont-size:22px;
  cursor:pointer;
  color:#111; /* ← чёрные */
}

.picker{
  position:relative;
  display:flex;
  justify-content:center;
  gap:20px;
  margin:30px 0;
}

.center-line{
  position:absolute;
  top:50%;
  left:0;
  right:0;
  height:40px;
  margin-top:-20px;
  border-top:1px solid #ccc;
  border-bottom:1px solid #ccc;
  pointer-events:none;
}
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
  
  <div class="center-line"></div>
</div>

<div class="block" onclick="openDays()">
  <div class="row"><span>Дни недели</span><span id="daysText">ежедневно</span></div>
</div>

<div class="block" onclick="openSound()">
  <div class="row"><span>Звук</span><span>по умолчанию</span></div>
</div>

<div class="block" onclick="editDescription()">
  <div class="row"><span>Описание</span><span id="descriptionText">{{ $alarm->title }}</span></div>
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
    <button type="button" onclick="closeDays()">OK</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<form id="saveForm" method="POST" action="{{ route('alarms.update', $alarm) }}" style="display:none;">
  @csrf
  @method('PUT')
  <input type="hidden" name="title" id="formTitle" value="{{ $alarm->title }}">
  <input type="hidden" name="note" id="formNote" value="{{ $alarm->note ?? '' }}">
  <input type="hidden" name="time" id="formTime" value="{{ substr($alarm->time, 0, 5) }}">
  <input type="hidden" name="enabled" id="formEnabled" value="{{ $alarm->enabled ? 1 : 0 }}">
  @if($alarm->date)
    <input type="hidden" name="date" id="formDate" value="{{ $alarm->date->format('Y-m-d') }}">
  @endif
</form>

<form id="deleteForm" method="POST" action="{{ route('alarms.destroy', $alarm) }}" style="display:none;">
  @csrf
  @method('DELETE')
</form>

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
  setTimeout(() => toast.style.display = 'none', 1500);
}

function fill(){
  const hEl = document.getElementById('h');
  const mEl = document.getElementById('m');

  hEl.innerHTML = '';
  mEl.innerHTML = '';

  // Делаем повторение (для бесконечности)
  for(let k=0;k<3;k++){
    for(let i=0;i<24;i++){
      hEl.innerHTML += `<div>${String(i).padStart(2,'0')}</div>`;
    }
  }

  for(let k=0;k<3;k++){
    for(let i=0;i<60;i++){
      mEl.innerHTML += `<div>${String(i).padStart(2,'0')}</div>`;
    }
  }

  const [hh, mm] = alarm.time.split(':').map(Number);

  // ставим в СЕРЕДИНУ
  hEl.scrollTop = (24 + hh) * 40;
  mEl.scrollTop = (60 + mm) * 40;

  setupInfiniteScroll(hEl, 24);
  setupInfiniteScroll(mEl, 60);
}
fill();

function setupInfiniteScroll(el, count){
  el.addEventListener('scroll', () => {
    const itemHeight = 40;
    const total = count * itemHeight;

    if (el.scrollTop < total * 0.5){
      el.scrollTop += total;
    }

    if (el.scrollTop > total * 2){
      el.scrollTop -= total;
    }
  });
}


function getTime(){
  const hEl = document.getElementById('h');
  const mEl = document.getElementById('m');

  let hi = Math.round(hEl.scrollTop / 40) % 24;
  let mi = Math.round(mEl.scrollTop / 40) % 60;

  return `${String(hi).padStart(2,'0')}:${String(mi).padStart(2,'0')}`;
}

function save(){
  document.getElementById('formTitle').value = alarm.title;
  document.getElementById('formNote').value = alarm.note ?? '';
  document.getElementById('formTime').value = getTime();
  document.getElementById('formEnabled').value = alarm.enabled ? '1' : '0';

  showToast('Сохранение...');
  setTimeout(() => {
    document.getElementById('saveForm').submit();
  }, 150);
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

function editDescription(){
  const t = prompt('Описание', alarm.title ?? '');
  if (t === null) return;

  alarm.title = t;
  document.getElementById('descriptionText').innerText = t || '—';
}

function del(){
  if (!confirm('Удалить?')) return;

  showToast('Удаление...');
  setTimeout(() => {
    document.getElementById('deleteForm').submit();
  }, 150);
}
</script>
@endsection

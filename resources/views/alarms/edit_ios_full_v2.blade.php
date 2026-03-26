@extends('layouts.app')
@php $noHeader = true; @endphp
@section('title', 'Изменить будильник')
@section('content')
<style>
body{
  background:#f5f5f7;
  color:#000;
  margin:0;
}
header,nav,.topbar{display:none!important;}

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:15px 18px;
  background:#fff;
  font-size:18px;
  border-bottom:1px solid #ececec;
}
.header-title{
  font-weight:600;
  text-align:center;
  flex:1;
}
.header-btn{
  width:32px;
  text-align:center;
  font-size:24px;
  line-height:1;
  cursor:pointer;
  color:#111;
  user-select:none;
}

.picker{
  position:relative;
  display:flex;
  justify-content:center;
  gap:18px;
  margin:28px 0 18px;
}

.col{
  position:relative;
  width:110px;
  height:200px;
  overflow:hidden;
  user-select:none;
  touch-action:none;
  background:#fff;
  border-radius:16px;
}

.col-inner{
  position:absolute;
  left:0;
  right:0;
  top:0;
  will-change:transform;
}

.item{
  height:40px;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#111;
  font-size:28px;
  font-weight:400;
}

.center-frame{
  position:absolute;
  left:50%;
  transform:translateX(-50%);
  top:50%;
  margin-top:-20px;
  width:238px;
  height:40px;
  border-top:1px solid #cfcfcf;
  border-bottom:1px solid #cfcfcf;
  pointer-events:none;
  border-radius:2px;
}

.block{
  background:#fff;
  margin:10px;
  border-radius:12px;
  padding:15px;
}
.row{
  display:flex;
  justify-content:space-between;
  cursor:pointer;
}
.row span{color:#000;}

.modal{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.3);
  display:none;
  align-items:center;
  justify-content:center;
}
.modal-content{
  background:#fff;
  padding:20px;
  border-radius:12px;
  width:300px;
}

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
  <div class="header-btn" onclick="closePage()">✕</div>
  <div class="header-title">Изменить будильник</div>
  <div class="header-btn" onclick="save()">✔</div>
</div>

<div class="picker">
  <div class="col" id="h"></div>
  <div class="col" id="m"></div>
  <div class="center-frame"></div>
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
const ITEM_HEIGHT = 40;
const VISIBLE_ROWS = 5;
const CENTER_OFFSET = Math.floor(VISIBLE_ROWS / 2);
const REPEAT_COUNT = 7;

const alarm = {
  id: {{ $alarm->id }},
  time: '{{ substr($alarm->time, 0, 5) }}',
  title: @json($alarm->title),
  note: @json($alarm->note ?? ''),
  enabled: {{ $alarm->enabled ? 'true' : 'false' }},
  date: @json(optional($alarm->date)->format('Y-m-d'))
};

let days = [1,1,1,1,1,1,1];

const pickers = {};

function showToast(text){
  const toast = document.getElementById('toast');
  toast.innerText = text;
  toast.style.display = 'block';
  setTimeout(() => toast.style.display = 'none', 1500);
}

function pad(n){
  return String(n).padStart(2, '0');
}

function buildPicker(el, max, initialValue){
  const inner = document.createElement('div');
  inner.className = 'col-inner';

  for(let r = 0; r < REPEAT_COUNT; r++){
    for(let i = 0; i < max; i++){
      const item = document.createElement('div');
      item.className = 'item';
      item.dataset.value = i;
      item.textContent = pad(i);
      inner.appendChild(item);
    }
  }

  el.innerHTML = '';
  el.appendChild(inner);
 
  
 
  const middleCycle = Math.floor(REPEAT_COUNT / 2);
  let index = middleCycle * max + initialValue;

  pickers[el.id] = {
    el,
    inner,
    max,
    index,
    startY: 0,
    startIndex: 0,
    dragging: false
  };

  renderPickerState(pickers[el.id], false);

  el.addEventListener('mousedown', (e) => startDrag(el.id, e.clientY));
  window.addEventListener('mousemove', (e) => moveDrag(el.id, e.clientY));
  window.addEventListener('mouseup', () => endDrag(el.id));

  el.addEventListener('click', (e) => handleClick(el.id, e));
  
  state.offsetY = -(state.index - CENTER_OFFSET) * ITEM_HEIGHT;
}

function normalizeIndex(state){
  const middleCycle = Math.floor(REPEAT_COUNT / 2);
  const min = state.max;
  const max = (REPEAT_COUNT - 2) * state.max;

  if(state.index < min || state.index >= max){
    const v = ((state.index % state.max) + state.max) % state.max;
    state.index = middleCycle * state.max + v;
  }
}

//renderPicker

function renderPickerState(state, smooth = false){
  normalizeIndex(state);

  const targetOffset = -(state.index - CENTER_OFFSET) * ITEM_HEIGHT;

  state.offsetY = targetOffset;

  state.inner.style.transition = smooth ? 'transform 180ms ease' : 'none';
  state.inner.style.transform = `translateY(${targetOffset}px)`;

  [...state.inner.children].forEach((node, idx) => {
    const isSelected = idx === state.index;
    node.style.opacity = isSelected ? '1' : '0.4';
    node.style.fontWeight = isSelected ? '600' : '400';
  });
}

function startDrag(id, clientY){
  const state = pickers[id];
  state.dragging = true;
  state.startY = clientY;
  state.startOffset = state.offsetY || 0;
  state.inner.style.transition = 'none';
}

function moveDrag(id, clientY){
  const state = pickers[id];
  if(!state.dragging) return;

  const delta = clientY - state.startY;

  state.offsetY = state.startOffset + delta;

  applyOffset(state);
}

function endDrag(id){
  const state = pickers[id];
  if(!state.dragging) return;

  state.dragging = false;

  snapToNearest(state);
}


function applyOffset(state){
  state.inner.style.transform = `translateY(${state.offsetY}px)`;
}

function snapToNearest(state){
  const rawIndex = -(state.offsetY / ITEM_HEIGHT) + CENTER_OFFSET;
  state.index = Math.round(rawIndex);

  renderPickerState(state, true);
}



function handleClick(id, e){
  const state = pickers[id];
  if(state.dragging) return;

  const rect = state.el.getBoundingClientRect();
  const y = e.clientY - rect.top;
  const clickedRow = Math.floor(y / ITEM_HEIGHT);
  const deltaRows = clickedRow - CENTER_OFFSET;

  if(deltaRows === 0) return;

  state.index += deltaRows;
  renderPicker(id, true);
}

function getPickerValue(id){
  const state = pickers[id];
  const value = ((state.index % state.max) + state.max) % state.max;
  return pad(value);
}

function getTime(){
  return `${getPickerValue('h')}:${getPickerValue('m')}`;
}

function fill(){
  const [hh, mm] = alarm.time.split(':').map(Number);
  buildPicker(document.getElementById('h'), 24, hh);
  buildPicker(document.getElementById('m'), 60, mm);
}
fill();

function save(){
  document.getElementById('formTitle').value = alarm.title;
  document.getElementById('formNote').value = alarm.note ?? '';
  document.getElementById('formTime').value = getTime();
  document.getElementById('formEnabled').value = alarm.enabled ? '1' : '0';

  showToast('Сохранение...');
  setTimeout(() => document.getElementById('saveForm').submit(), 150);
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
  setTimeout(() => document.getElementById('deleteForm').submit(), 150);
}
</script>
@endsection

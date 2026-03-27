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
  top:50%;
  left:0;
  right:0;
  margin-top:-20px;
  height:40px;
  border-top:1px solid #cfcfcf;
  border-bottom:1px solid #cfcfcf;
  pointer-events:none;
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
.row span:first-child{
  color:#000; /* заголовок */
}

.row span:last-child{
  color:#3c3c43; /* значение */
  font-weight:500;
}



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


.modal{
  position:fixed;
  inset:0;
  display:none;
  z-index:999;
}

.modal-overlay{
  position:absolute;
  inset:0;
  background:rgba(0,0,0,.4);
}

.modal-content.modern{
  position:absolute;
  left:50%;
  bottom:0;
  transform:translateX(-50%);
  width:100%;
  max-width:400px;
  background:#fff;
  border-radius:20px 20px 0 0;
  padding:20px;
}

.modal-title{
  font-weight:600;
  margin-bottom:15px;
}

.days-list div{
  display:flex;
  justify-content:space-between;
  padding:12px 0;
  border-bottom:1px solid #eee;
  cursor:pointer;
}

.checkbox{
  width:20px;
  height:20px;
  border:2px solid #ccc;
  border-radius:4px;
}

.checkbox.active{
  background:#007aff;
  border-color:#007aff;
}

.modal-actions{
  display:flex;
  justify-content:space-between;
  margin-top:20px;
}

.btn-cancel{
  background:none;
  border:none;
  color:#666;
  font-size:16px;
}

.btn-ok{
  background:#007aff;
  color:#fff;
  border:none;
  padding:10px 20px;
  border-radius:10px;
}

.delete-btn{
  width:calc(100% - 20px);
  margin:20px 10px;
  padding:14px;
  border:none;
  border-radius:12px;
  background:#ff3b30;
  color:#fff;
  font-size:16px;
  cursor:pointer;
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



<div class="block" style="margin-top:20px;">
  <div class="row" onclick="openDays()">
    <span>Дни недели</span>
    <span class="row" id="daysText">ежедневно</span>
  </div>
</div>


<div class="block" onclick="openSound()">
  <div class="row"><span>Звук</span><span class="row">по умолчанию</span></div>
</div>

<div class="block" onclick="editDescription()">
  <div class="row"><span>Описание</span><span id="descriptionText">{{ $alarm->title }}</span></div>
</div>

<div class="block-group">
  <div class="block">
    <div class="row"><span>Длительность сигнала</span><span>10 мин</span></div>
  </div>

  <div class="block">
    <div class="row"><span>Длительность паузы</span><span>10 мин ×3</span></div>
  </div>
</div>

<button class="delete-btn" onclick="del()">Удалить</button>

<div class="modal" id="daysModal">
  <div class="modal-overlay" onclick="cancelDays()"></div>

  <div class="modal-content modern">
    <div class="modal-title">Дни недели</div>

    <div id="daysList" class="days-list"></div>

    <div class="modal-actions">
      <button onclick="cancelDays()" class="btn-cancel">Отмена</button>
      <button onclick="applyDays()" class="btn-ok">ОК</button>
    </div>
  </div>
</div>

<div class="modal" id="soundModal">
  <div class="modal-overlay" onclick="closeSound()"></div>

  <div class="modal-content modern">
    <div class="modal-title">Звук</div>

    <div id="soundList" class="days-list"></div>

    <div class="modal-actions">
      <button onclick="closeSound()" class="btn-cancel">Отмена</button>
    </div>
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
  <input type="hidden" name="weekdays" id="formWeekdays">
  <input type="hidden" name="sound" id="formSound">
  @if($alarm->date)
    <input type="hidden" name="date" id="formDate" value="{{ $alarm->date->format('Y-m-d') }}">
  @endif
</form>

<form id="deleteForm" method="POST" action="{{ route('alarms.destroy', $alarm) }}" style="display:none;">
  @csrf
  @method('DELETE')
</form>

<div id="confirmModal" style="
  position:fixed;
  inset:0;
  display:none;
  z-index:9999;
">
  <div style="
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.4);
  " onclick="hideConfirm()"></div>

  <div style="
    position:absolute;
    bottom:0;
    left:0;
    right:0;
    background:#fff;
    border-radius:20px 20px 0 0;
    padding:20px;
    text-align:center;
  ">
    <div style="font-weight:600;margin-bottom:15px;">
      Сохранить изменения?
    </div>

    <button onclick="save()" style="
      width:100%;
      padding:12px;
      background:#007aff;
      color:#fff;
      border:none;
      border-radius:10px;
      margin-bottom:10px;
    ">
      Сохранить
    </button>

    <button onclick="discardChanges()" style="
      width:100%;
      padding:12px;
      background:#eee;
      border:none;
      border-radius:10px;
    ">
      Не сохранять
    </button>
  </div>
</div>


<script>
const ITEM_HEIGHT = 40;
const VISIBLE_ROWS = 5;
const CENTER_OFFSET = Math.floor(VISIBLE_ROWS / 2);
const REPEAT_COUNT = 7;

let days = @json($alarm->weekdays) || [1,1,1,1,1,1,1];

const alarm = {
  id: {{ $alarm->id }},
  time: '{{ substr($alarm->time, 0, 5) }}',
  title: @json($alarm->title),
  note: @json($alarm->note ?? ''),
  enabled: {{ $alarm->enabled ? 'true' : 'false' }},
  date: @json(optional($alarm->date)->format('Y-m-d'))
};

const originalState = JSON.stringify({
  time: alarm.time,
  title: alarm.title,
  note: alarm.note,
  days: days
});



const body = document.getElementById('saveForm');

// 👇 добавляем



let weekdaysInput = document.getElementById('formWeekdays');
if(!weekdaysInput){
  weekdaysInput = document.createElement('input');
  weekdaysInput.type = 'hidden';
  weekdaysInput.name = 'weekdays';
  weekdaysInput.id = 'formWeekdays';
  body.appendChild(weekdaysInput);
}

weekdaysInput.value = JSON.stringify(days);


const dayNames = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];




//let days = [1,1,1,1,1,1,1]; // по умолчанию ежедневно
let tempDays = [...days];

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
  const index = middleCycle * max + initialValue;

  const state = {
    el,
    inner,
    max,
    index,
    startY: 0,
    startIndex: 0,
    startOffset: 0,
    offsetY: -(index - CENTER_OFFSET) * ITEM_HEIGHT,
    dragging: false
  };

  pickers[el.id] = state;

  renderPickerState(state, false);

  el.addEventListener('mousedown', (e) => startDrag(el.id, e.clientY));
  window.addEventListener('mousemove', (e) => moveDrag(el.id, e.clientY));
  window.addEventListener('mouseup', () => endDrag(el.id));

  el.addEventListener('click', (e) => handleClick(el.id, e));
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
  if(!state) return;
  if(state.dragging) return;

  const item = e.target.closest('.item');
  if(!item) return;

  const items = [...state.inner.children];
  const clickedIndex = items.indexOf(item);

  if(clickedIndex === -1) return;

  state.index = clickedIndex;
  renderPickerState(state, true);
}

function getPickerValue(id){
  const state = pickers[id];
  const value = ((state.index % state.max) + state.max) % state.max;
  return pad(value);
}

function getTime(){
  return `${getPickerValue('h')}:${getPickerValue('m')}`;
}


function updateDaysText(){
  const el = document.getElementById('daysText');

  const active = dayNames.filter((_, i) => days[i]);

  if(active.length === 7){
    el.innerText = 'ежедневно';
  } else if(active.length === 0){
    el.innerText = '—';
  } else {
    el.innerText = active.join(', ');
  }
}

function fill(){
  const [hh, mm] = alarm.time.split(':').map(Number);
  buildPicker(document.getElementById('h'), 24, hh);
  buildPicker(document.getElementById('m'), 60, mm);
}
fill();
updateDaysText();
document.getElementById('formTime').value = getTime();


function save(){
  document.getElementById('formTitle').value = alarm.title;
  document.getElementById('formNote').value = alarm.note ?? '';
  document.getElementById('formTime').value = getTime();
  document.getElementById('formEnabled').value = alarm.enabled ? '1' : '0';

  // ✅ сохраняем дни
  document.getElementById('formWeekdays').value = JSON.stringify(days);

  showToast('Сохранение...');
  setTimeout(() => document.getElementById('saveForm').submit(), 150);
  
  document.getElementById('formSound').value = selectedSound;
  
}

function closePage(){
  const currentState = JSON.stringify({
    time: getTime(),
    title: alarm.title,
    note: alarm.note,
    days: days
  });

  // если ничего не меняли
  if(currentState === originalState){
    window.location.href = '/alarms';
    return;
  }

  // если есть изменения
  showConfirmModal();
}

function openDays(){
  tempDays = [...days];
  document.getElementById('daysModal').style.display = 'block';
  renderDays();
}

function applyDays(){
  days = [...tempDays];
  updateDaysText();
  document.getElementById('daysModal').style.display = 'none';
}

function closeDays(){
  document.getElementById('daysModal').style.display = 'none';
}

function cancelDays(){
  document.getElementById('daysModal').style.display = 'none';
}

function renderDays(){
  const list = document.getElementById('daysList');
  list.innerHTML = '';

  dayNames.forEach((name, i) => {
    list.innerHTML += `
      <div onclick="toggleDay(${i})">
        <span>${name}</span>
        <div class="checkbox ${tempDays[i] ? 'active' : ''}"></div>
      </div>
    `;
  });
}

function toggleDay(i){
  tempDays[i] = tempDays[i] ? 0 : 1;
  renderDays();
}



const sounds = [
  {name:'Классический', file:'classic.mp3'},
  {name:'Колокол', file:'bell.mp3'},
  {name:'Цифровой', file:'digital.mp3'},
  {name:'iPhone', file:'iphone.mp3'}
];

function openSound(){
  document.getElementById('soundModal').style.display='block';
  renderSounds();
}

function renderSounds(){
  const list = document.getElementById('soundList');
  list.innerHTML = '';

  sounds.forEach(s=>{
    list.innerHTML += `
      <div onclick="selectSound('${s.file}')">
        <span>${s.name}</span>
        <div class="checkbox ${selectedSound===s.file?'active':''}"></div>
      </div>
    `;
  });
}

function selectSound(file){
  selectedSound = file;

  // проигрываем
  const audio = new Audio('/sounds/' + file);
  audio.play();

  renderSounds();

  // меняем текст
  const name = sounds.find(s=>s.file===file).name;
  document.querySelector('[onclick="openSound()"] span:last-child').innerText = name;
}

function closeSound(){
  document.getElementById('soundModal').style.display='none';
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


function showConfirmModal(){
  document.getElementById('confirmModal').style.display='block';
}

function hideConfirm(){
  document.getElementById('confirmModal').style.display='none';
}

function discardChanges(){
  window.location.href = '/alarms';
}


console.log(pickers);
//document.getElementById('m').style.background = 'red';

</script>
@endsection

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

.toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.7);color:#fff;padding:10px 20px;border-radius:20px;display:none}
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

<div class="block" onclick="openDays()"><div class="row"><span>Дни недели</span><span id="daysText">ежедневно</span></div></div>
<div class="block" onclick="openSound()"><div class="row"><span>Звук</span><span>по умолчанию</span></div></div>
<div class="block" onclick="editTitle()"><div class="row"><span>Описание</span><span id="titleText">{{ $alarm->title }}</span></div></div>
<div class="block"><div class="row"><span>Длительность</span><span>10 мин</span></div></div>
<div class="block"><div class="row"><span>Пауза</span><span>10 мин ×3</span></div></div>
<div class="block" onclick="del()" style="color:red;text-align:center">Удалить</div>

<div class="modal" id="daysModal"><div class="modal-content">
<div>Дни недели</div>
<div id="daysList"></div>
<button onclick="closeDays()">OK</button>
</div></div>

<div class="toast" id="toast"></div>

<script>
let alarm={id:{{ $alarm->id }},time:'{{ $alarm->time }}',title:'{{ $alarm->title }}'};
let days=[1,1,1,1,1,1,1];

function fill(){
for(let i=0;i<24;i++){h.innerHTML+=`<div>${String(i).padStart(2,'0')}</div>`}
for(let i=0;i<60;i++){m.innerHTML+=`<div>${String(i).padStart(2,'0')}</div>`}
}
fill();

function getTime(){
let hi=Math.round(h.scrollTop/40);
let mi=Math.round(m.scrollTop/40);
return `${String(hi).padStart(2,'0')}:${String(mi).padStart(2,'0')}`;
}

function save(){
fetch(`/alarms/${alarm.id}`,{
method:'POST',
headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
body:JSON.stringify({_method:'PUT',time:getTime(),title:alarm.title})
}).then(()=>location.href='/alarms');
}

function closePage(){history.back()}

function openDays(){daysModal.style.display='flex';renderDays()}
function closeDays(){daysModal.style.display='none'}
function renderDays(){
let names=['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
daysList.innerHTML='';
names.forEach((n,i)=>{
daysList.innerHTML+=`<div onclick="toggleDay(${i})">${n} ${days[i]?'✓':''}</div>`
})}
function toggleDay(i){days[i]=!days[i];renderDays()}

function openSound(){alert('звуки позже подключим')}
function editTitle(){let t=prompt('Описание',alarm.title);if(t)alarm.title=t;titleText.innerText=t}
function del(){if(confirm('Удалить?')) fetch(`/alarms/${alarm.id}`,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},body:new URLSearchParams({_method:'DELETE'})}).then(()=>location.href='/alarms')}
</script>
@endsection
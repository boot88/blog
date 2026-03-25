@extends('layouts.app')

@section('title','Будильники')

@section('content')

<style>
body{background:#f5f5f7;color:#000}
h1+div{display:none!important}

.header{text-align:center;font-size:28px;margin-bottom:10px}

.clock{text-align:center;margin:20px 0;cursor:pointer}
canvas{background:white;border-radius:50%}

.next{text-align:center;color:#666;margin-bottom:20px}

.alarm{
display:flex;
justify-content:space-between;
align-items:center;
padding:18px;
border-bottom:1px solid #ddd;
background:white;
}

.alarm.disabled{opacity:.4}

.time{font-size:44px;font-weight:300}
.label{color:#555}

.toggle{
width:50px;
height:28px;
background:#666; /* темнее */
border-radius:20px;
position:relative;
cursor:pointer;
}

.toggle.active{background:#34c759}

.toggle::after{
content:'';
width:24px;
height:24px;
background:white;
border-radius:50%;
position:absolute;
top:2px;
left:2px;
transition:.2s;
}

.toggle.active::after{left:24px}

.add-btn{
position:fixed;
bottom:40px;
left:50%;
transform:translateX(-50%);
width:70px;
height:70px;
border-radius:50%;
background:#34c759;
display:flex;
align-items:center;
justify-content:center;
font-size:36px;
color:white;
}
</style>

<div class="header">Будильники</div>

<div class="clock" onclick="toggleClock()">
    <canvas id="clockCanvas" width="150" height="150"></canvas>
    <div id="digital" style="display:none;font-size:32px"></div>
</div>

<div class="next" id="nextText"></div>

<div>
@foreach($alarms as $alarm)
<div class="alarm {{ $alarm->enabled?'':'disabled' }}" onclick="edit({{ $alarm->id }})">

<div>
<div class="time">{{ substr($alarm->time,0,5) }}</div>
<div class="label">{{ $alarm->title }}</div>
</div>

<div class="toggle {{ $alarm->enabled?'active':'' }}"
onclick="event.stopPropagation();toggle(this,{{ $alarm->id }})"></div>

</div>
@endforeach
</div>

<a href="/alarms/create" class="add-btn">+</a>

<script>
let digital=false;
const alarms=@json($alarms);

function toggleClock(){
digital=!digital;
document.getElementById('clockCanvas').style.display=digital?'none':'block';
document.getElementById('digital').style.display=digital?'block':'none';
}

function drawClock(){
const c=document.getElementById('clockCanvas');
const ctx=c.getContext('2d');
const now=new Date();

ctx.clearRect(0,0,150,150);

ctx.beginPath();
ctx.arc(75,75,70,0,Math.PI*2);
ctx.stroke();

const sec=now.getSeconds();
const min=now.getMinutes();
const hr=now.getHours();

ctx.beginPath();
ctx.moveTo(75,75);
ctx.lineTo(75+40*Math.cos((hr%12)*Math.PI/6),
           75+40*Math.sin((hr%12)*Math.PI/6));
ctx.stroke();

ctx.beginPath();
ctx.moveTo(75,75);
ctx.lineTo(75+50*Math.cos(min*Math.PI/30),
           75+50*Math.sin(min*Math.PI/30));
ctx.stroke();

ctx.beginPath();
ctx.moveTo(75,75);
ctx.lineTo(75+60*Math.cos(sec*Math.PI/30),
           75+60*Math.sin(sec*Math.PI/30));
ctx.strokeStyle='red';
ctx.stroke();

document.getElementById('digital').innerText =
now.toLocaleTimeString('ru-RU',{timeZone:'Asia/Novosibirsk'});
}

setInterval(drawClock,1000);
drawClock();

function nextAlarm(){
const now=new Date();
let minDiff=null;

alarms.forEach(a=>{
if(!a.enabled)return;

const [h,m]=a.time.split(':');
let t=new Date();
t.setHours(h,m,0,0);

if(t<now)t.setDate(t.getDate()+1);

const diff=t-now;
if(minDiff===null||diff<minDiff)minDiff=diff;
});

if(minDiff===null){
document.getElementById('nextText').innerText='нет включенных будильников';
return;
}

let sec=Math.floor(minDiff/1000);
let d=Math.floor(sec/86400); sec%=86400;
let h=Math.floor(sec/3600); sec%=3600;
let m=Math.floor(sec/60);

let txt='сработает через ';
if(d) txt+=d+' д ';
if(h) txt+=h+' ч ';
txt+=m+' мин';

document.getElementById('nextText').innerText=txt;
}

nextAlarm();
setInterval(nextAlarm,60000);

function toggle(el,id){
el.classList.toggle('active');
const row=el.closest('.alarm');
row.classList.toggle('disabled');
}

function edit(id){
window.location='/alarms/'+id+'/edit';
}
</script>

@endsection

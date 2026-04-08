@extends('layouts.app')

@section('title','Будильники')

@section('content')

<style>
body{background:#f5f5f7;color:#000}
h1+div{display:none!important}

.header{text-align:center;font-size:28px;margin-bottom:10px}

.clock-wrap{display:flex;align-items:center;justify-content:center;gap:20px;margin:20px 0;cursor:pointer}

.clock-box{width:160px;height:160px;display:flex;align-items:center;justify-content:center}

#digital{width:160px;height:160px;display:flex;align-items:center;justify-content:center;font-size:28px}

.next{text-align:center;color:#666;margin-bottom:20px}

.alarm{display:flex;justify-content:space-between;align-items:center;padding:18px;border-bottom:1px solid #ddd;background:white}
.alarm.disabled{opacity:.4}

.time{font-size:44px;font-weight:300}
.label{color:#555}

.toggle{width:50px;height:28px;background:#666;border-radius:20px;position:relative;cursor:pointer}
.toggle.active{background:#34c759}
.toggle::after{content:'';width:24px;height:24px;background:white;border-radius:50%;position:absolute;top:2px;left:2px;transition:.2s}
.toggle.active::after{left:24px}

.add-btn{position:fixed;bottom:40px;left:50%;transform:translateX(-50%);width:70px;height:70px;border-radius:50%;background:#34c759;display:flex;align-items:center;justify-content:center;font-size:36px;color:white}
</style>

<div class="header">Будильники</div>

<div class="clock-wrap" onclick="toggleClock()">

    <div class="clock-box">
        <canvas id="clockCanvas" width="160" height="160"></canvas>
    </div>

    <div id="digital" style="display:none"></div>

</div>

<div class="next" id="nextText"></div>

<div>
@foreach($alarms as $alarm)
<div class="alarm {{ $alarm->enabled?'':'disabled' }}" onclick="edit({{ $alarm->id }})">
<div>
<div class="time">{{ substr($alarm->time,0,5) }}</div>
<div class="label">{{ $alarm->title }}</div>
</div>
<div class="toggle {{ $alarm->enabled?'active':'' }}" onclick="event.stopPropagation();toggle(this,{{ $alarm->id }})"></div>
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
    document.getElementById('digital').style.display=digital?'flex':'none';
}

function drawClock(){
    const canvas=document.getElementById('clockCanvas');
    const ctx=canvas.getContext('2d');
    const now=new Date();

    ctx.clearRect(0,0,160,160);

    // круг
    ctx.beginPath();
    ctx.arc(80,80,75,0,Math.PI*2);
    ctx.stroke();

    // деления
    for(let i=0;i<60;i++){
        let angle=i*Math.PI/30;
        let x1=80+65*Math.cos(angle);
        let y1=80+65*Math.sin(angle);
        let x2=80+75*Math.cos(angle);
        let y2=80+75*Math.sin(angle);
        ctx.beginPath();
        ctx.moveTo(x1,y1);
        ctx.lineTo(x2,y2);
        ctx.stroke();
    }

    // цифры
    ctx.font="12px Arial";
    ctx.textAlign="center";
    ctx.textBaseline="middle";

    for(let i=1;i<=12;i++){
        let angle=(i-3)*Math.PI/6;
        let x=80+55*Math.cos(angle);
        let y=80+55*Math.sin(angle);
        ctx.fillText(i,x,y);
    }

    let sec=now.getSeconds();
    let min=now.getMinutes();
    let hr=now.getHours()%12;

    // часы
    let hAngle=(hr+min/60)*Math.PI/6;
    ctx.beginPath();
    ctx.moveTo(80,80);
    ctx.lineTo(80+40*Math.cos(hAngle),80+40*Math.sin(hAngle));
    ctx.lineWidth=3;
    ctx.stroke();

    // минуты
    let mAngle=min*Math.PI/30;
    ctx.beginPath();
    ctx.moveTo(80,80);
    ctx.lineTo(80+55*Math.cos(mAngle),80+55*Math.sin(mAngle));
    ctx.lineWidth=2;
    ctx.stroke();

    // секунды
    let sAngle=sec*Math.PI/30;
    ctx.beginPath();
    ctx.moveTo(80,80);
    ctx.lineTo(80+65*Math.cos(sAngle),80+65*Math.sin(sAngle));
    ctx.strokeStyle='red';
    ctx.lineWidth=1;
    ctx.stroke();

    document.getElementById('digital').innerText=now.toLocaleTimeString('ru-RU',{timeZone:'Asia/Novosibirsk'});
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

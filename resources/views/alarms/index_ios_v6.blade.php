@extends('layouts.app')
@section('title','Задачи')
@section('content')
<style>
body{background:#f5f5f7;color:#000}
h1+div{display:none!important}
.header{text-align:center;font-size:28px;margin-bottom:10px}

.clock-wrap{display:flex;align-items:center;justify-content:flex-start;gap:20px;margin:20px}
.clock-box{width:160px;height:160px;position:relative}
#digital{position:absolute;top:0;left:0;width:160px;height:160px;display:flex;align-items:center;justify-content:center;font-size:28px}

.next{color:#000;margin:0 20px 20px 20px; /* слева под часами */}




.alarm{display:flex;justify-content:space-between;align-items:center;padding:18px;border-bottom:1px solid #ddd;background:white}
.alarm.disabled{opacity:.5}
.time{font-size:44px;font-weight:300}
.label{color:#555}




.toggle{width:50px;height:28px;background:#666;border-radius:20px;position:relative;cursor:pointer}
.toggle.active{background:#34c759}
.toggle::after{content:'';width:24px;height:24px;background:white;border-radius:50%;position:absolute;top:2px;left:2px;transition:.2s}
.toggle.active::after{left:24px}

.add-btn{position:fixed;bottom:40px;left:50%;transform:translateX(-50%);width:70px;height:70px;border-radius:50%;background:#34c759;display:flex;align-items:center;justify-content:center;font-size:36px;color:white}
</style>

<div class="header">Задачи</div>

<div class="clock-wrap" onclick="toggleClock()">
  <div class="clock-box">
    <canvas id="clockCanvas" width="160" height="160"></canvas>
    <div id="digital" style="display:none"></div>
  </div>
</div>

<div class="next" id="nextText"></div>

<div>
@foreach($alarms as $alarm)
<div class="alarm {{ $alarm->enabled?'':'disabled' }}" data-id="{{ $alarm->id }}" onclick="edit({{ $alarm->id }})">
  <div>
    <div class="time">{{ substr($alarm->time,0,5) }}</div>
    <div style="color:#8e8e93;">
    {{ $alarm->title }}
    @php
        $days = $alarm->weekdays ?? [1,1,1,1,1,1,1];
        $names = ['пн','вт','ср','чт','пт','сб','вс'];

        $active = [];
        foreach ($days as $i => $d) {
            if ($d) $active[] = $names[$i];
        }
    @endphp

    @if(count($active) === 7)
        , Каждый день
    @elseif(count($active) > 0)
        , {{ implode(' ', $active) }}
    @endif
</div>
  </div>
  <div class="toggle {{ $alarm->enabled?'active':'' }}" onclick="event.stopPropagation();toggle(this,{{ $alarm->id }})"></div>
</div>
@endforeach
</div>

<a href="/alarms/create" class="add-btn">+</a>

<script>
let digital=false;
let alarms=@json($alarms);
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const toggleUrlTemplate = @json(route('alarms.toggle-enabled', ['alarm' => '__ALARM_ID__']));

function toggleClock(){
  digital=!digital;
  document.getElementById('clockCanvas').style.display=digital?'none':'block';
  document.getElementById('digital').style.display=digital?'flex':'none';
}

function drawClock(){
  const canvas=document.getElementById('clockCanvas');
  const ctx=canvas.getContext('2d');
  const now = getNowInAlarmTimezone();
  ctx.clearRect(0,0,160,160);

  let grad=ctx.createRadialGradient(80,80,60,80,80,80);
  grad.addColorStop(0,'#ffffff');
  grad.addColorStop(1,'#ddd');
  ctx.fillStyle=grad;
  ctx.beginPath();ctx.arc(80,80,75,0,Math.PI*2);ctx.fill();

  for(let i=0;i<60;i++){
    let a=i*Math.PI/30;
    ctx.beginPath();
    ctx.moveTo(80+65*Math.cos(a),80+65*Math.sin(a));
    ctx.lineTo(80+75*Math.cos(a),80+75*Math.sin(a));
    ctx.strokeStyle='#aaa';ctx.stroke();
  }

  ctx.font='12px Arial';ctx.textAlign='center';ctx.textBaseline='middle';
  for(let i=1;i<=12;i++){
    let a=(i-3)*Math.PI/6;
    ctx.fillStyle='#333';
    ctx.fillText(i,80+55*Math.cos(a),80+55*Math.sin(a));
  }

  let sec=now.getSeconds(), min=now.getMinutes(), hr=now.getHours()%12;
  let hA = (hr + min / 60 - 3) * Math.PI / 6;
  ctx.beginPath();ctx.moveTo(80,80);
  ctx.lineTo(80+35*Math.cos(hA),80+35*Math.sin(hA));
  ctx.lineWidth=4;ctx.strokeStyle='#444';ctx.stroke();

  let mA = (min - 15) * Math.PI / 30;
  ctx.beginPath();ctx.moveTo(80,80);
  ctx.lineTo(80+50*Math.cos(mA),80+50*Math.sin(mA));
  ctx.lineWidth=3;ctx.strokeStyle='#666';ctx.stroke();

  let sA = (sec - 15) * Math.PI / 30;
  ctx.beginPath();ctx.moveTo(80,80);
  ctx.lineTo(80+65*Math.cos(sA),80+65*Math.sin(sA));
  ctx.strokeStyle='#ff3b30';ctx.lineWidth=2;ctx.stroke();

  ctx.beginPath();ctx.arc(80,80,5,0,Math.PI*2);ctx.fillStyle='#000';ctx.fill();

  document.getElementById('digital').innerText =
  now.toLocaleTimeString('ru-RU');
}
setInterval(drawClock,1000);drawClock();

function getNowInAlarmTimezone() {
  return new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Novosibirsk' }));
}

function getWeekdayIndexMondayFirst(date) {
  return (date.getDay() + 6) % 7; // JS: 0=Sun ... 6=Sat => 0=Mon ... 6=Sun
}

function getNextAlarmDiffMs(alarm, now) {
  if (!alarm.enabled) return null;

  const [h, m] = alarm.time.split(':').map(Number);
  const days = Array.isArray(alarm.weekdays) ? alarm.weekdays : [1,1,1,1,1,1,1];
  const hasActiveDays = days.some(Boolean);

  if (!hasActiveDays) return null;

  let bestDiff = null;

  for (let shift = 0; shift < 7; shift++) {
    const candidate = new Date(now);
    candidate.setDate(candidate.getDate() + shift);
    candidate.setHours(h, m, 0, 0);

    const weekday = getWeekdayIndexMondayFirst(candidate);
    if (!days[weekday]) continue;

    const diff = candidate - now;
    if (diff >= 0 && (bestDiff === null || diff < bestDiff)) {
      bestDiff = diff;
    }
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
    el.innerText='Нет включённых задач';
    return;
  }

  let sec=Math.floor(minDiff/1000);
  let d=Math.floor(sec/86400); sec%=86400;
  let h=Math.floor(sec/3600); sec%=3600;
  let m=Math.floor(sec/60);

  let txt='Сработает через ';
  if(d) txt+=d+' д ';
  if(h) txt+=h+' ч ';
  txt+=m+' мин';
  el.innerText=txt;
}

computeNextText();
setInterval(computeNextText,60000);




async function toggle(el,id){
  el.classList.toggle('active');
  const row=el.closest('.alarm');
  row.classList.toggle('disabled');

  const isActive = el.classList.contains('active');
  const previousState = !isActive;

  alarms = alarms.map(a=> a.id===id ? {...a, enabled: isActive} : a);

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

    if(!res.ok){
      throw new Error('toggle request failed');
    }
  }catch(err){
    el.classList.toggle('active', previousState);
    row.classList.toggle('disabled', !previousState);
    alarms = alarms.map(a=> a.id===id ? {...a, enabled: previousState} : a);
    computeNextText();
    return;
  }

  if(isActive){
    const alarm = alarms.find(a => a.id === id);
    const now = getNowInAlarmTimezone();
    const diff = getNextAlarmDiffMs(alarm, now);
    if(diff===null) return;

    let sec = Math.floor(diff / 1000);
    let d = Math.floor(sec / 86400); sec %= 86400;
    let h2 = Math.floor(sec / 3600); sec %= 3600;
    let m2 = Math.floor(sec / 60);

    let text = 'Сработает через ';
    if(d) text += d + ' д ';
    if(h2) text += h2 + ' ч ';
    text += m2 + ' мин';

    const toast = document.createElement('div');
    toast.innerText = text;

    toast.style = `
      position:fixed;
      bottom:20px;
      left:50%;
      transform:translateX(-50%);
      background:#f2f2f7;
      color:#3c3c43;
      padding:10px 20px;
      border-radius:20px;
      z-index:999;
      font-size:14px;
    `;

    document.body.appendChild(toast);

    setTimeout(()=>toast.remove(),3000);
  }
}





function edit(id){ window.location='/alarms/'+id+'/edit'; }
</script>
@endsection

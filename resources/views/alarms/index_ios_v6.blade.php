@extends('layouts.app')
@section('title', 'Будильники и задачи')
@section('header', 'Будильники')

@section('page-actions')
    <a class="btn" href="{{ route('items.index', 'tasks') }}">Все задачи</a>
    <a class="btn btn-primary" href="{{ route('alarms.create') }}">+ Будильник</a>
@endsection

@section('content')
<style>
    .dashboard{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,420px);gap:18px;align-items:start}
    .clock-panel,.task-panel{background:#fff;border:1px solid #e5e5ea;border-radius:18px;padding:18px}
    .clock-wrap{display:flex;align-items:center;justify-content:center;min-height:210px;cursor:pointer}
    .clock-box{width:190px;height:190px;position:relative}
    #digital{position:absolute;inset:0;display:none;align-items:center;justify-content:center;font-size:30px;font-weight:300}
    .next{color:#3c3c43;margin:8px 0 14px;text-align:center}
    .alarm-list{border-top:1px solid #ececec}
    .alarm{display:flex;align-items:center;gap:12px;padding:14px 4px;border-bottom:1px solid #eee;cursor:pointer}
    .alarm.disabled{opacity:.5}.alarm-time{font-size:34px;font-weight:300;line-height:1}.alarm-note{font-size:13px;color:#6e6e73;margin-top:3px}
    .toggle{width:50px;height:28px;background:#8e8e93;border-radius:20px;position:relative;cursor:pointer;flex:0 0 auto}
    .toggle.active{background:#34c759}.toggle::after{content:'';width:24px;height:24px;background:#fff;border-radius:50%;position:absolute;top:2px;left:2px;transition:.2s}.toggle.active::after{left:24px}
    .task-tools{display:flex;gap:8px;align-items:center;margin-bottom:10px}.task-tools input{flex:1}
    .chips{display:flex;gap:6px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none}.chips::-webkit-scrollbar{display:none}
    .chip{border:1px solid #d1d1d6;background:#fff;color:#1c1c1e;padding:7px 10px;border-radius:999px;font-size:12px;cursor:pointer;white-space:nowrap}.chip.active{background:#007aff;border-color:#007aff;color:#fff}
    .task-list{max-height:500px;overflow:auto;border-top:1px solid #eee}
    .task{display:flex;justify-content:space-between;gap:10px;padding:12px 2px;border-bottom:1px solid #f0f0f0}.task-main{min-width:0}.task-title{font-weight:650;overflow-wrap:anywhere}.task-meta{font-size:12px;color:#6e6e73;margin-top:4px}.task-actions{display:flex;gap:5px;align-items:flex-start}.icon-btn{border:1px solid #d1d1d6;background:#fff;border-radius:9px;padding:6px 8px;cursor:pointer;color:#3c3c43}.icon-btn.danger{color:#c62828;border-color:#ffd6d3;background:#fff1f0}
    .panel-footer{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:12px;font-size:13px;color:#6e6e73}
    .quick-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10020;padding:12px}.quick-overlay{position:absolute;inset:0;background:rgba(0,0,0,.35)}.quick-body{position:relative;background:#fff;border-radius:18px;padding:18px;width:min(430px,100%);border:1px solid #e5e5ea}.quick-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}
    @media(max-width:860px){.dashboard{grid-template-columns:1fr}.task-panel{order:-1}.task-list{max-height:none}}
    @media(max-width:520px){.clock-panel,.task-panel{padding:14px;border-radius:15px}.task-tools{display:grid;grid-template-columns:1fr}.task-tools .btn{width:100%}.task{align-items:flex-start}.quick-actions{display:grid;grid-template-columns:1fr}.quick-actions .btn{width:100%}}
</style>

<div class="dashboard">
    <section class="clock-panel">
        <div class="clock-wrap" onclick="toggleClock()" title="Переключить вид часов">
            <div class="clock-box">
                <canvas id="clockCanvas" width="190" height="190"></canvas>
                <div id="digital"></div>
            </div>
        </div>
        <div class="next" id="nextText"></div>
        <div class="alarm-list">
            @forelse($alarms as $alarm)
                <div class="alarm {{ $alarm->enabled ? '' : 'disabled' }}" onclick="editAlarm({{ $alarm->id }})">
                    <div class="toggle {{ $alarm->enabled ? 'active' : '' }}" onclick="event.stopPropagation();toggleAlarm(this,{{ $alarm->id }})" role="switch" aria-checked="{{ $alarm->enabled ? 'true' : 'false' }}"></div>
                    <div><div class="alarm-time">{{ substr($alarm->time, 0, 5) }}</div><div class="alarm-note">{{ $alarm->title }}</div></div>
                </div>
            @empty
                <div class="empty">Будильников пока нет.</div>
            @endforelse
        </div>
    </section>

    <aside class="task-panel">
        <div class="task-tools">
            <input id="searchInput" type="search" placeholder="Поиск по задачам">
            <button class="btn btn-primary" type="button" onclick="openQuickAdd()">+ Задача</button>
        </div>
        <div id="categoryChips" class="chips"></div>
        <div id="taskList" class="task-list"></div>
        <div class="panel-footer"><span id="taskCount"></span><a href="{{ route('items.index', 'tasks') }}">Открыть раздел</a></div>
    </aside>
</div>

<div id="quickModal" class="quick-modal" role="dialog" aria-modal="true" aria-labelledby="quickTitleLabel">
    <div class="quick-overlay" onclick="closeQuickAdd()"></div>
    <div class="quick-body">
        <h2 id="quickTitleLabel" style="margin:0 0 15px">Новая задача</h2>
        <div class="field"><label for="quickTitle">Название</label><input id="quickTitle" maxlength="255" placeholder="Что нужно сделать"></div>
        <div class="field"><label for="quickCategory">Категория</label><input id="quickCategory" maxlength="100" value="Общие" placeholder="Например: Работа"></div>
        <div class="quick-actions"><button class="btn" type="button" onclick="closeQuickAdd()">Отмена</button><button class="btn btn-primary" type="button" id="quickSave" onclick="quickAdd()">Сохранить</button></div>
    </div>
</div>

<script>
let digital = false;
let alarms = @json($alarms);
let tasks = @json($tasks);
let selectedCategory = 'Все';
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const taskStoreUrl = @json(route('items.store', 'tasks'));
const taskImportUrl = @json(route('items.import-local'));
const taskEditUrl = @json(route('items.edit', ['section' => 'tasks', 'item' => '__ITEM_ID__']));
const taskDeleteUrl = @json(route('items.destroy', ['section' => 'tasks', 'item' => '__ITEM_ID__']));
const alarmToggleUrl = @json(route('alarms.toggle-enabled', ['alarm' => '__ALARM_ID__']));

const escapeHtml = value => String(value ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');
const categories = () => ['Все', ...new Set(tasks.map(task => task.category || 'Общие'))];
const filteredTasks = () => {
    const query = document.getElementById('searchInput').value.trim().toLowerCase();
    return tasks.filter(task => selectedCategory === 'Все' || (task.category || 'Общие') === selectedCategory)
        .filter(task => !query || `${task.title || ''} ${task.content || ''} ${task.category || ''}`.toLowerCase().includes(query));
};

function renderTasks(){
    const data = filteredTasks();
    document.getElementById('categoryChips').innerHTML = categories().map(category => `<button class="chip ${category === selectedCategory ? 'active' : ''}" type="button" data-category="${escapeHtml(category)}">${escapeHtml(category)}</button>`).join('');
    document.querySelectorAll('[data-category]').forEach(button => button.onclick = () => { selectedCategory = button.dataset.category; renderTasks(); });
    document.getElementById('taskList').innerHTML = data.length ? data.map(task => `
        <div class="task">
            <div class="task-main"><div class="task-title">${escapeHtml(task.title)}</div><div class="task-meta">${escapeHtml(task.category || 'Общие')} · ${new Date(task.updated_at || task.created_at).toLocaleString('ru-RU')}</div></div>
            <div class="task-actions"><a class="icon-btn" href="${taskEditUrl.replace('__ITEM_ID__', task.id)}" aria-label="Изменить">✎</a><button class="icon-btn danger" type="button" onclick="removeTask(${Number(task.id)})" aria-label="Удалить">×</button></div>
        </div>`).join('') : '<div class="empty">Задач не найдено.</div>';
    document.getElementById('taskCount').textContent = `Показано: ${data.length}`;
}

document.getElementById('searchInput').addEventListener('input', renderTasks);
renderTasks();

function openQuickAdd(){ document.getElementById('quickTitle').value=''; document.getElementById('quickCategory').value='Общие'; document.getElementById('quickModal').style.display='flex'; setTimeout(() => document.getElementById('quickTitle').focus(), 0); }
function closeQuickAdd(){ document.getElementById('quickModal').style.display='none'; }
async function quickAdd(){
    const title = document.getElementById('quickTitle').value.trim();
    if (!title) return document.getElementById('quickTitle').focus();
    const button = document.getElementById('quickSave'); button.disabled = true;
    try {
        const response = await fetch(taskStoreUrl, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify({title,category:document.getElementById('quickCategory').value.trim() || 'Общие'})});
        if (!response.ok) throw new Error('Не удалось сохранить задачу');
        const data = await response.json(); tasks.unshift(data.item); closeQuickAdd(); renderTasks();
    } catch (error) { alert(error.message); } finally { button.disabled = false; }
}
async function removeTask(id){
    if (!confirm('Удалить задачу?')) return;
    try {
        const response = await fetch(taskDeleteUrl.replace('__ITEM_ID__', id), {method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrfToken}});
        if (!response.ok) throw new Error('Не удалось удалить задачу');
        tasks = tasks.filter(task => Number(task.id) !== Number(id)); renderTasks();
    } catch (error) { alert(error.message); }
}

async function importLocalTasks(){
    const key = 'side_tasks_v1';
    let localTasks = [];
    try { localTasks = JSON.parse(localStorage.getItem(key) || '[]'); } catch (_) {}
    if (!Array.isArray(localTasks) || !localTasks.length) return;
    try {
        const response = await fetch(taskImportUrl, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify({tasks:localTasks})});
        if (!response.ok) return;
        localStorage.removeItem(key); window.location.reload();
    } catch (_) {}
}
importLocalTasks();

async function toggleAlarm(element, id){
    const oldState = element.classList.contains('active');
    const newState = !oldState;
    element.classList.toggle('active', newState); element.closest('.alarm').classList.toggle('disabled', !newState); element.setAttribute('aria-checked', String(newState));
    alarms = alarms.map(alarm => Number(alarm.id) === Number(id) ? {...alarm, enabled:newState} : alarm); computeNextText();
    try {
        const response = await fetch(alarmToggleUrl.replace('__ALARM_ID__', id), {method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify({enabled:newState})});
        if (!response.ok) throw new Error();
    } catch (_) { element.classList.toggle('active', oldState); element.closest('.alarm').classList.toggle('disabled', !oldState); element.setAttribute('aria-checked', String(oldState)); alarms = alarms.map(alarm => Number(alarm.id) === Number(id) ? {...alarm, enabled:oldState} : alarm); computeNextText(); }
}
function editAlarm(id){ window.location = `/alarms/${id}/edit`; }
function toggleClock(){ digital=!digital; document.getElementById('clockCanvas').style.display=digital?'none':'block'; document.getElementById('digital').style.display=digital?'flex':'none'; }
function nowInTimezone(){ return new Date(new Date().toLocaleString('en-US',{timeZone:'Asia/Novosibirsk'})); }
function drawClock(){
    const canvas=document.getElementById('clockCanvas'),ctx=canvas.getContext('2d'),now=nowInTimezone(); ctx.clearRect(0,0,190,190);
    const grad=ctx.createRadialGradient(95,95,70,95,95,95); grad.addColorStop(0,'#fff');grad.addColorStop(1,'#ddd');ctx.fillStyle=grad;ctx.beginPath();ctx.arc(95,95,88,0,Math.PI*2);ctx.fill();
    for(let i=0;i<60;i++){const a=i*Math.PI/30;ctx.beginPath();ctx.moveTo(95+76*Math.cos(a),95+76*Math.sin(a));ctx.lineTo(95+86*Math.cos(a),95+86*Math.sin(a));ctx.lineWidth=i%5===0?2:1;ctx.strokeStyle='#aaa';ctx.stroke();}
    ctx.font='13px Arial';ctx.textAlign='center';ctx.textBaseline='middle';for(let i=1;i<=12;i++){const a=(i-3)*Math.PI/6;ctx.fillStyle='#333';ctx.fillText(i,95+62*Math.cos(a),95+62*Math.sin(a));}
    const hand=(angle,length,width,color)=>{ctx.beginPath();ctx.moveTo(95,95);ctx.lineTo(95+length*Math.cos(angle),95+length*Math.sin(angle));ctx.lineWidth=width;ctx.strokeStyle=color;ctx.stroke();};
    hand((now.getHours()%12+now.getMinutes()/60-3)*Math.PI/6,40,4,'#444');hand((now.getMinutes()-15)*Math.PI/30,58,3,'#666');hand((now.getSeconds()-15)*Math.PI/30,72,2,'#ff3b30');ctx.beginPath();ctx.arc(95,95,5,0,Math.PI*2);ctx.fillStyle='#000';ctx.fill();
    document.getElementById('digital').textContent=now.toLocaleTimeString('ru-RU');
}
function nextAlarmDiff(alarm, now){
    if (!alarm.enabled) return null; const [hour,minute]=alarm.time.split(':').map(Number); const days=Array.isArray(alarm.weekdays)?alarm.weekdays:[1,1,1,1,1,1,1]; let best=null;
    for(let shift=0;shift<7;shift++){const candidate=new Date(now);candidate.setDate(candidate.getDate()+shift);candidate.setHours(hour,minute,0,0);const weekday=(candidate.getDay()+6)%7;if(!days[weekday])continue;const diff=candidate-now;if(diff>=0&&(best===null||diff<best))best=diff;} return best;
}
function computeNextText(){
    const now=nowInTimezone();let min=null;alarms.forEach(alarm=>{const diff=nextAlarmDiff(alarm,now);if(diff!==null&&(min===null||diff<min))min=diff;});const element=document.getElementById('nextText');
    if(min===null){element.textContent='Нет включённых будильников';return;}let seconds=Math.floor(min/1000),days=Math.floor(seconds/86400);seconds%=86400;let hours=Math.floor(seconds/3600);seconds%=3600;let minutes=Math.floor(seconds/60);element.textContent=`Ближайший сигнал через ${days?days+' д ':''}${hours?hours+' ч ':''}${minutes} мин`;
}
drawClock();computeNextText();setInterval(drawClock,1000);setInterval(computeNextText,60000);
</script>
@endsection

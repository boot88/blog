@extends('layouts.app')

@section('title', 'Будильники')

@section('content')

<style>
body {
    background:#f5f5f7;
    color:#000;
}

.header {
    text-align:center;
    font-size:28px;
    margin-bottom:10px;
}

.alarm {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:18px;
    border-bottom:1px solid #ddd;
    background:white;
}

.alarm.disabled {
    opacity:0.4;
}

.time {
    font-size:44px;
    font-weight:300;
}

.label {
    color:#555;
}

.toggle {
    width:50px;
    height:28px;
    background:#ccc;
    border-radius:20px;
    position:relative;
    cursor:pointer;
}

.toggle.active {
    background:#34c759;
}

.toggle::after {
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

.toggle.active::after {
    left:24px;
}

.add-btn {
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

<div>
@foreach($alarms as $alarm)
<div class="alarm {{ $alarm->enabled ? '' : 'disabled' }}" onclick="edit({{ $alarm->id }})">

    <div>
        <div class="time">{{ substr($alarm->time,0,5) }}</div>
        <div class="label">{{ $alarm->title }}</div>
    </div>

    <div class="toggle {{ $alarm->enabled ? 'active' : '' }}"
         onclick="event.stopPropagation(); toggle(this, {{ $alarm->id }})">
    </div>

</div>
@endforeach
</div>

<a href="/alarms/create" class="add-btn">+</a>

<script>
function toggle(el, id){
    el.classList.toggle('active');

    const row = el.closest('.alarm');
    row.classList.toggle('disabled');

    fetch(`/alarms/${id}`, {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body: JSON.stringify({
            enabled: el.classList.contains('active')
        })
    });
}

function edit(id){
    window.location = '/alarms/'+id+'/edit';
}
</script>

@endsection

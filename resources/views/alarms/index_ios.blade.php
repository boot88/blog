@extends('layouts.app')

@section('title', 'Будильники (iOS UI)')

@section('content')

<style>
body { background:#000; }
.alarm { display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid #222; }
.time { font-size:48px; font-weight:300; }
.label { color:#aaa; }
.toggle { width:50px; height:28px; background:#444; border-radius:20px; position:relative; cursor:pointer; }
.toggle.active { background:#30d158; }
.toggle::after { content:''; width:24px; height:24px; background:white; border-radius:50%; position:absolute; top:2px; left:2px; transition:.2s; }
.toggle.active::after { left:24px; }
.add-btn { position:fixed; bottom:30px; right:30px; width:60px; height:60px; border-radius:50%; background:#30d158; display:flex; align-items:center; justify-content:center; font-size:32px; }
</style>

<div>
@foreach($alarms as $alarm)
<div class="alarm" onclick="edit({{ $alarm->id }})">
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

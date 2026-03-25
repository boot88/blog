@extends('layouts.app')
@section('title','Изменить будильник')
@section('content')
<style>
body{background:#f5f5f7;color:#000}
h1+div{display:none!important}
.header{display:flex;justify-content:space-between;align-items:center;padding:15px 20px;font-size:20px;background:#fff}
.btn{cursor:pointer;font-size:22px}
.container{padding:20px}
.field{margin-bottom:20px}
.label{color:#888;font-size:14px;margin-bottom:5px}
input{font-size:26px;padding:10px;width:100%;border:1px solid #ddd;border-radius:10px}
.toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#333;color:#fff;padding:10px 20px;border-radius:20px;display:none}
</style>
<div class="header">
<div class="btn" onclick="closePage()">✕</div>
<div>Изменить будильник</div>
<div class="btn" onclick="save()">✔</div>
</div>
<div class="container">
<div class="field"><div class="label">Время</div><input id="time" value="{{ $alarm->time }}"></div>
<div class="field"><div class="label">Описание</div><input id="title" value="{{ $alarm->title }}"></div>
</div>
<div id="toast" class="toast"></div>
<script>
let original={time:'{{ $alarm->time }}',title:'{{ $alarm->title }}'};
function changed(){return time.value!==original.time||title.value!==original.title}
function closePage(){if(!changed())history.back();else if(confirm('Сохранить изменения?'))save();else history.back()}
function save(){fetch('/alarms/{{ $alarm->id }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},body:JSON.stringify({_method:'PUT',time:time.value,title:title.value})}).then(()=>history.back())}
</script>
@endsection
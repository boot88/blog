@extends('layouts.app')
@section('title','Изменить будильник')
@section('content')

<style>
body{background:#f5f5f7;color:#000}
h1+div{display:none!important}

.header{display:flex;justify-content:space-between;align-items:center;padding:15px;font-size:20px}
.btn{cursor:pointer;font-size:22px}

.container{padding:20px}
input{font-size:28px;padding:10px;width:100%;margin-bottom:20px}

.save-toast{
position:fixed;
bottom:20px;
left:50%;
transform:translateX(-50%);
background:#000;color:#fff;
padding:10px 20px;
border-radius:20px;
display:none;
}
</style>

<div class="header">
  <div class="btn" onclick="closePage()">✕</div>
  <div>Изменить будильник</div>
  <div class="btn" onclick="save()">✔</div>
</div>

<div class="container">
  <input id="time" value="{{ $alarm->time }}">
  <input id="title" value="{{ $alarm->title }}">
</div>

<div id="toast" class="save-toast"></div>

<script>
let original={time:'{{ $alarm->time }}',title:'{{ $alarm->title }}'};

function changed(){
return document.getElementById('time').value!==original.time ||
       document.getElementById('title').value!==original.title;
}

function closePage(){
if(!changed()){
window.history.back();
}else{
if(confirm('Сохранить изменения?')) save(); else window.history.back();
}
}

function save(){
fetch('/alarms/{{ $alarm->id }}',{
method:'POST',
headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
body:JSON.stringify({
 time:document.getElementById('time').value,
 title:document.getElementById('title').value
})
}).then(()=>{
window.history.back();
});
}

// уведомление при включении
function showToast(text){
const t=document.getElementById('toast');
t.innerText=text;
t.style.display='block';
setTimeout(()=>t.style.display='none',2000);
}

// пример вызова (подключи при toggle на главной)
// showToast('Сработает через 6 дней 2 часа');
</script>

@endsection

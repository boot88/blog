<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cms')</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px;background:#0b1220;color:#e8eefc}
        a{color:#9cc2ff;text-decoration:none}
        .card{background:#121b2f;border:1px solid #223055;border-radius:14px;padding:16px;margin-bottom:14px}
        .row{display:flex;gap:12px;flex-wrap:wrap}
        .btn{display:inline-block;border:1px solid #2b3b66;background:#1a2650;color:#e8eefc;padding:8px 12px;border-radius:10px;cursor:pointer}
        .btn-danger{border-color:#6a2b2b;background:#3a1414}
        .btn-primary{border-color:#2b6a3a;background:#143a22}
        input,textarea{width:100%;padding:10px;border-radius:10px;border:1px solid #2b3b66;background:#0f1830;color:#e8eefc}
        label{font-size:13px;opacity:.9}
        .tag{display:inline-block;padding:3px 8px;border-radius:999px;border:1px solid #2b3b66;font-size:12px;opacity:.9}
        .ok{padding:10px 12px;border-radius:12px;background:#143a22;border:1px solid #2b6a3a;margin-bottom:12px}
        .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:9999}
        .modal{width:min(560px,92vw);background:#121b2f;border:1px solid #223055;border-radius:16px;padding:16px}
        .modal h2{margin:0 0 8px 0}
        .muted{opacity:.8}
    </style>
</head>
<body>
    <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:14px">
        
        @if(!isset($noHeader))
        <h1 style="margin:0">@yield('header', 'Будильники')</h1>
        <div class="row">
            <a class="btn" href="{{ route('alarms.index') }}">Список</a>
            <a class="btn btn-primary" href="{{ route('alarms.create') }}">+ Добавить</a>
        </div>
        @endif
        
    </div>

    @if(session('ok'))
        <div class="ok">{{ session('ok') }}</div>
    @endif

    @yield('content')

    <!-- Модалка срабатывания -->
    <div id="alarmBackdrop" class="modal-backdrop">
        <div class="modal">
            <div class="row" style="justify-content:space-between;align-items:flex-start">
                <div>
                    <h2 id="alarmTitle">Будильник2</h2>
                    <div id="alarmNote" class="muted"></div>
                    <div id="alarmMeta" class="muted" style="margin-top:8px;font-size:12px"></div>
                </div>
                <button id="alarmStop" class="btn btn-danger">Остановить</button>
            </div>
        </div>
    </div>

    <!-- Звук -->
    <audio id="alarmAudio" preload="auto" loop>
        <source src="{{ asset('sounds/alarm.mp3') }}" type="audio/mpeg">
    </audio>

    <script>
        // --- ВАЖНО: браузеры могут блокировать звук, если не было взаимодействия.
        // Поэтому показываем кнопку "Разрешить звук" при первом входе.
        (function initSoundUnlock(){
            const audio = document.getElementById('alarmAudio');
            let unlocked = false;

            function unlock(){
                if (unlocked) return;
                audio.play().then(() => {
                    audio.pause();
                    audio.currentTime = 0;
                    unlocked = true;
                    btn?.remove();
                }).catch(()=>{});
            }

            const btn = document.createElement('button');
            btn.className = 'btn';
            btn.textContent = '🔊 Разрешить звук';
            btn.style.position = 'fixed';
            btn.style.right = '16px';
            btn.style.bottom = '16px';
            btn.style.zIndex = 9998;
            btn.onclick = unlock;
            document.body.appendChild(btn);

            window.addEventListener('click', unlock, { once: true });
        })();

        // --- Поллинг "due" раз в секунду
        const dueUrl = @json(route('alarms.due'));
        const backdrop = document.getElementById('alarmBackdrop');
        const alarmTitle = document.getElementById('alarmTitle');
        const alarmNote = document.getElementById('alarmNote');
        const alarmMeta = document.getElementById('alarmMeta');
        const stopBtn = document.getElementById('alarmStop');
        const audio = document.getElementById('alarmAudio');

        // чтобы не показывать один и тот же будильник много раз в ту же минуту
        const firedKey = 'alarms_fired_v1';
        const fired = JSON.parse(localStorage.getItem(firedKey) || '{}'); // { "alarmId|YYYY-MM-DD|HH:MM": true }

        function markFired(id, date, time){
            fired[`${id}|${date||'daily'}|${time}`] = true;
            localStorage.setItem(firedKey, JSON.stringify(fired));
        }
        function isFired(id, date, time){
            return !!fired[`${id}|${date||'daily'}|${time}`];
        }

        function openModal(alarm, nowIso){
            alarmTitle.textContent = alarm.title || 'Будильник';
            alarmNote.textContent = alarm.note || 'Напоминание без описания.';
            alarmMeta.textContent = `Сработал: ${new Date(nowIso).toLocaleString()}`;

            backdrop.style.display = 'flex';

            // звук
            const file = (alarm.sound || 'alarm.mp3').replace(/[^a-zA-Z0-9._-]/g, '');
            audio.src = `/sounds/${file}`;
            audio.currentTime = 0;
            audio.play().catch(()=>{ /* может быть заблокировано */ });

            stopBtn.onclick = () => {
                audio.pause();
                audio.currentTime = 0;
                backdrop.style.display = 'none';
            };
        }

        async function checkDue(){
            try{
                const res = await fetch(dueUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                if(!res.ok) return;
                const data = await res.json();

                for(const alarm of (data.alarms || [])){
                    if (isFired(alarm.id, alarm.date, alarm.time)) continue;
                    markFired(alarm.id, alarm.date, alarm.time);
                    openModal(alarm, data.now);
                    break; // один за раз
                }
            }catch(e){}
        }

        setInterval(checkDue, 1000);

        // Пуш-уведомления (по желанию)
        if ('Notification' in window && Notification.permission === 'default') {
            // не просим сразу, чтобы не бесить; можно сделать кнопку отдельно
        }
    </script>
</body>
</html>

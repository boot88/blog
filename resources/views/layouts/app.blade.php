<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Органайзер')</title>
    <style>
        :root{color-scheme:light;--bg:#f5f5f7;--card:#fff;--line:#e5e5ea;--text:#111;--muted:#6e6e73;--blue:#007aff;--red:#c62828;--green:#248a3d}
        *{box-sizing:border-box}
        html{background:var(--bg)}
        body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:var(--bg);color:var(--text);min-height:100vh}
        a{color:var(--blue);text-decoration:none}
        button,input,textarea,select{font:inherit}
        .app-nav{position:sticky;top:0;z-index:1000;background:rgba(245,245,247,.94);backdrop-filter:blur(16px);border-bottom:1px solid var(--line)}
        .app-nav-inner{max-width:1200px;margin:0 auto;padding:10px 20px;display:flex;gap:8px;overflow-x:auto;scrollbar-width:none}
        .app-nav-inner::-webkit-scrollbar{display:none}
        .nav-link{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:8px 13px;border-radius:12px;color:#3c3c43;white-space:nowrap;font-weight:600;font-size:14px}
        .nav-link.active{background:var(--blue);color:#fff}
        .app-shell{max-width:1200px;margin:0 auto;padding:22px 20px 40px}
        .page-header{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px}
        .page-header h1{margin:0;font-size:clamp(25px,4vw,34px);line-height:1.1}
        .card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px;margin-bottom:14px;box-shadow:0 2px 12px rgba(0,0,0,.025)}
        .row{display:flex;gap:12px;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;border:1px solid #d1d1d6;background:#fff;color:#1c1c1e;padding:9px 14px;border-radius:11px;cursor:pointer;font-weight:600}
        .btn:hover{filter:brightness(.98)}
        .btn-danger{border-color:#ffd6d3;background:#fff1f0;color:var(--red)}
        .btn-primary{border-color:var(--blue);background:var(--blue);color:#fff}
        input,textarea,select{width:100%;padding:11px 12px;border-radius:11px;border:1px solid #c7c7cc;background:#fff;color:#111;min-height:44px}
        textarea{resize:vertical;line-height:1.45}
        input:focus,textarea:focus,select:focus{outline:3px solid rgba(0,122,255,.15);border-color:var(--blue)}
        label{display:block;font-size:13px;font-weight:650;margin-bottom:6px;color:#3c3c43}
        .field{margin-bottom:14px}
        .tag{display:inline-flex;padding:4px 9px;border-radius:999px;background:#f2f2f7;font-size:12px;color:#636366}
        .ok{padding:11px 13px;border-radius:12px;background:#e9f7ee;border:1px solid #b8e3c7;margin-bottom:14px;color:#1d6b34}
        .error{font-size:13px;color:var(--red);margin-top:5px}
        .muted{color:var(--muted)}
        .inline-form{display:inline}
        .empty{text-align:center;padding:34px 18px;color:var(--muted)}
        .pagination svg{width:20px}.pagination nav>div:first-child{display:none}
        .pagination nav>div:last-child>div:first-child{display:none}
        .pagination nav>div:last-child>div:last-child{display:flex;gap:5px;align-items:center;flex-wrap:wrap}
        .pagination span,.pagination a{display:inline-flex;min-width:38px;min-height:38px;align-items:center;justify-content:center;border:1px solid var(--line);background:#fff;border-radius:9px;padding:6px}
        .alarm-backdrop{position:fixed;inset:0;background:rgba(17,17,17,.35);display:none;align-items:center;justify-content:center;z-index:10000;padding:16px}
        .alarm-modal{width:min(460px,96vw);background:#fff;border:1px solid #ececec;border-radius:20px;padding:18px;box-shadow:0 20px 55px rgba(0,0,0,.18)}
        .alarm-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}
        .btn-stop-today{border-color:#ffe7b8;background:#fff8e8;color:#8a5a00}.btn-stop-now{border-color:#ffd6d3;background:#fff1f0;color:var(--red)}
        .alarm-modal h2{margin:0 0 8px}
        @media(max-width:700px){
            .app-nav-inner{padding:8px 12px}.nav-link{min-height:40px;padding:7px 11px}
            .app-shell{padding:16px 12px 30px}.page-header{align-items:flex-start;flex-direction:column}
            .page-header .row,.page-header .btn{width:100%}.card{border-radius:15px;padding:14px}
            .btn{min-height:46px}.form-actions{display:grid!important;grid-template-columns:1fr;gap:8px}.form-actions .btn{width:100%}
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="app-nav" aria-label="Разделы органайзера">
        <div class="app-nav-inner">
            <a class="nav-link {{ request()->routeIs('alarms.*') ? 'active' : '' }}" href="{{ route('alarms.index') }}">Будильники</a>
            @foreach(\App\Models\OrganizerItem::SECTIONS as $sectionKey => $sectionLabel)
                <a class="nav-link {{ request()->routeIs('items.*') && request()->route('section') === $sectionKey ? 'active' : '' }}" href="{{ route('items.index', $sectionKey) }}">{{ $sectionLabel }}</a>
            @endforeach
        </div>
    </nav>

    <main class="app-shell">
        @if(!isset($noHeader))
            <div class="page-header">
                <h1>@yield('header', 'Органайзер')</h1>
                <div class="row">@yield('page-actions')</div>
            </div>
        @endif

        @if(session('ok'))
            <div class="ok" role="status">{{ session('ok') }}</div>
        @endif

        @yield('content')
    </main>

    <div id="alarmBackdrop" class="alarm-backdrop">
        <div class="alarm-modal">
            <h2 id="alarmTitle">Будильник</h2>
            <div id="alarmNote" class="muted"></div>
            <div id="alarmMeta" class="muted" style="margin-top:8px;font-size:12px"></div>
            <div class="alarm-actions">
                <button id="alarmStopToday" class="btn btn-stop-today">Выключить на сегодня</button>
                <button id="alarmStopNow" class="btn btn-stop-now">Остановить сейчас</button>
            </div>
        </div>
    </div>
    <audio id="alarmAudio" preload="auto" loop><source src="{{ asset('sounds/alarm.mp3') }}" type="audio/mpeg"></audio>

    <script>
    (() => {
        const dueUrl = @json(route('alarms.due'));
        const audio = document.getElementById('alarmAudio');
        const backdrop = document.getElementById('alarmBackdrop');
        const firedKey = 'alarms_fired_v2';
        const dismissedKey = 'alarms_dismissed_today_v1';
        const fired = JSON.parse(localStorage.getItem(firedKey) || '{}');
        const dismissed = JSON.parse(localStorage.getItem(dismissedKey) || '{}');
        let active = null, ringTimer = null, snoozeTimer = null, ringing = false, unlocked = false;

        const localDate = iso => new Intl.DateTimeFormat('en-CA', {timeZone:'Asia/Novosibirsk',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date(iso || Date.now()));
        const fireKey = (alarm, iso) => `${alarm.id}|${alarm.date || localDate(iso)}|${alarm.time}`;
        const dismissedToday = (id, iso) => dismissed[String(id)] === localDate(iso);

        function unlockSound(){
            if (unlocked) return;
            audio.play().then(() => { audio.pause(); audio.currentTime = 0; unlocked = true; }).catch(() => {});
        }
        window.addEventListener('pointerdown', unlockSound, {once:true});

        function stopRing(){
            audio.pause(); audio.currentTime = 0; ringing = false; backdrop.style.display = 'none';
            if (ringTimer) clearTimeout(ringTimer); ringTimer = null;
        }
        function scheduleRepeat(session){
            if (!session || session.cancelled || session.repeatsDone >= session.maxRepeats) return;
            snoozeTimer = setTimeout(() => startRing(session, false), Math.max(1, session.snooze) * 60000);
        }
        function startRing(session, first){
            active = session; ringing = true;
            document.getElementById('alarmTitle').textContent = session.alarm.title || 'Будильник';
            document.getElementById('alarmNote').textContent = session.alarm.note || 'Напоминание без описания.';
            document.getElementById('alarmMeta').textContent = first ? 'Первый сигнал' : `Повтор ${session.repeatsDone + 1} из ${session.maxRepeats}`;
            backdrop.style.display = 'flex';
            audio.src = `{{ asset('sounds') }}/${session.alarm.sound || 'alarm.mp3'}`;
            audio.play().catch(() => {});
            ringTimer = setTimeout(() => { stopRing(); session.repeatsDone++; scheduleRepeat(session); }, Math.max(1, session.duration) * 60000);
        }
        function runAlarm(alarm){
            startRing({alarm,duration:Number(alarm.duration || 10),snooze:Number(alarm.snooze_duration || 10),maxRepeats:Number(alarm.snooze_repeats || 0),repeatsDone:0,cancelled:false}, true);
        }
        async function checkDue(){
            if (ringing) return;
            try {
                const response = await fetch(dueUrl, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
                if (!response.ok) return;
                const data = await response.json();
                for (const alarm of (data.alarms || [])) {
                    const key = fireKey(alarm, data.now);
                    if (dismissedToday(alarm.id, data.now) || fired[key]) continue;
                    fired[key] = true; localStorage.setItem(firedKey, JSON.stringify(fired)); runAlarm(alarm); break;
                }
            } catch (_) {}
        }
        document.getElementById('alarmStopNow').onclick = () => { if (!active) return; stopRing(); active.repeatsDone++; scheduleRepeat(active); };
        document.getElementById('alarmStopToday').onclick = () => {
            if (!active) return; dismissed[String(active.alarm.id)] = localDate(); localStorage.setItem(dismissedKey, JSON.stringify(dismissed));
            active.cancelled = true; if (snoozeTimer) clearTimeout(snoozeTimer); stopRing();
        };
        checkDue(); setInterval(checkDue, 1000);
    })();
    </script>
    @stack('scripts')
</body>
</html>

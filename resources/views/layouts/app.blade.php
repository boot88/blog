<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cms')</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px;background:#f5f5f7;color:#111}
        a{color:#007aff;text-decoration:none}
        .card{background:#fff;border:1px solid #e5e5ea;border-radius:14px;padding:16px;margin-bottom:14px}
        .row{display:flex;gap:12px;flex-wrap:wrap}
        .btn{display:inline-block;border:1px solid #d1d1d6;background:#fff;color:#1c1c1e;padding:8px 12px;border-radius:10px;cursor:pointer}
        .btn-danger{border-color:#ffd6d3;background:#fff1f0;color:#c62828}
        .btn-primary{border-color:#007aff;background:#007aff;color:#fff}
        input,textarea{width:100%;padding:10px;border-radius:10px;border:1px solid #d1d1d6;background:#fff;color:#111}
        label{font-size:13px;opacity:.9}
        .tag{display:inline-block;padding:3px 8px;border-radius:999px;border:1px solid #d1d1d6;font-size:12px;opacity:.9}
        .ok{padding:10px 12px;border-radius:12px;background:#e9f7ee;border:1px solid #b8e3c7;margin-bottom:12px}
        .alarm-backdrop{position:fixed;inset:0;background:rgba(17,17,17,.35);display:none;align-items:center;justify-content:center;z-index:10000;padding:16px}
        .alarm-modal{width:min(460px,96vw);background:#fff;border:1px solid #ececec;border-radius:20px;padding:18px;box-shadow:0 20px 55px rgba(0,0,0,.18)}
        .alarm-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}
        .alarm-actions .btn{padding:10px 14px}
        .btn-stop-today{border-color:#ffe7b8;background:#fff8e8;color:#8a5a00}
        .btn-stop-now{border-color:#ffd6d3;background:#fff1f0;color:#c62828}
        .alarm-modal h2{margin:0 0 8px 0}
        .muted{opacity:.8}
    </style>
</head>
<body>
    <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:14px">
        
        @if(!isset($noHeader))
        <h1 style="margin:0">@yield('header', 'Задачи')</h1>
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
        const stopNowBtn = document.getElementById('alarmStopNow');
        const stopTodayBtn = document.getElementById('alarmStopToday');
        const audio = document.getElementById('alarmAudio');
        let ringTimeout = null;
        let snoozeTimeout = null;
        let activeSession = null;
        let ringing = false;

        const firedKey = 'alarms_fired_v2';
        const fired = JSON.parse(localStorage.getItem(firedKey) || '{}'); // { "alarmId|YYYY-MM-DD|HH:MM": true }
        const dismissedTodayKey = 'alarms_dismissed_today_v1';
        const dismissedToday = JSON.parse(localStorage.getItem(dismissedTodayKey) || '{}');

        function resolveFireDate(alarmDate, nowIso){
            if (alarmDate) return alarmDate; // разовая задача
            const now = new Date(nowIso || Date.now());
            const y = now.getFullYear();
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`; // ежедневная задача: ключ на текущий день
        }

        function makeFireKey(id, date, time, nowIso){
            return `${id}|${resolveFireDate(date, nowIso)}|${time}`;
        }

        function markFired(id, date, time, nowIso){
            fired[makeFireKey(id, date, time, nowIso)] = true;
            localStorage.setItem(firedKey, JSON.stringify(fired));
        }

        function isFired(id, date, time, nowIso){
            return !!fired[makeFireKey(id, date, time, nowIso)];
        }

        function getTodayDate(nowIso){
            const now = new Date(nowIso || Date.now());
            const y = now.getFullYear();
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function isDismissedForToday(alarmId, nowIso){
            return dismissedToday[String(alarmId)] === getTodayDate(nowIso);
        }

        function dismissForToday(alarmId, nowIso){
            dismissedToday[String(alarmId)] = getTodayDate(nowIso);
            localStorage.setItem(dismissedTodayKey, JSON.stringify(dismissedToday));
        }

        function stopCurrentRing(){
            audio.pause();
            audio.currentTime = 0;
            ringing = false;
            backdrop.style.display = 'none';
            if (ringTimeout) {
                clearTimeout(ringTimeout);
                ringTimeout = null;
            }
        }

        function scheduleNextSnooze(session){
            if (!session) return;
            if (session.completedRepeats >= session.maxRepeats) return;

            const pauseMs = Math.max(1, session.snoozeDurationMinutes) * 60 * 1000;
            snoozeTimeout = setTimeout(() => {
                if (!activeSession || activeSession.cancelled || activeSession.id !== session.id) return;
                if (isDismissedForToday(session.alarm.id, new Date().toISOString())) return;
                startRingSession(session, false);
            }, pauseMs);
        }

        function startRingSession(session, isFirstRing){
            activeSession = session;
            ringing = true;

            if (snoozeTimeout) {
                clearTimeout(snoozeTimeout);
                snoozeTimeout = null;
            }

            alarmTitle.textContent = session.alarm.title || 'Будильник';
            alarmNote.textContent = session.alarm.note || 'Напоминание без описания.';
            const ringLabel = isFirstRing ? 'Первый сигнал' : `Повтор ${session.completedRepeats + 1} из ${session.maxRepeats}`;
            alarmMeta.textContent = `${ringLabel}. Длительность: ${session.durationMinutes} мин`;
            backdrop.style.display = 'flex';

            audio.pause();
            audio.currentTime = 0;
            audio.src = `/sounds/${session.alarm.sound || 'alarm.mp3'}`;
            audio.play().catch(()=>{});

            const ringMs = Math.max(1, session.durationMinutes) * 60 * 1000;
            ringTimeout = setTimeout(() => {
                stopCurrentRing();
                session.completedRepeats += 1;
                scheduleNextSnooze(session);
            }, ringMs);
        }

        function runAlarm(alarm, nowIso){
            if (isDismissedForToday(alarm.id, nowIso)) return;
            const session = {
                id: `${alarm.id}-${Date.now()}`,
                alarm,
                durationMinutes: Math.max(1, Number(alarm.duration || 10)),
                snoozeDurationMinutes: Math.max(1, Number(alarm.snooze_duration || 10)),
                maxRepeats: Math.max(0, Number(alarm.snooze_repeats || 0)),
                completedRepeats: 0,
                cancelled: false,
            };

            startRingSession(session, true);
        }

        async function checkDue(){
            if (ringing) return;
            try{
                const res = await fetch(dueUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                if(!res.ok) return;
                const data = await res.json();

                for(const alarm of (data.alarms || [])){
                    if (isDismissedForToday(alarm.id, data.now)) continue;
                    if (isFired(alarm.id, alarm.date, alarm.time, data.now)) continue;
                    markFired(alarm.id, alarm.date, alarm.time, data.now);
                    runAlarm(alarm, data.now);
                    break; // один за раз
                }
            }catch(e){}
        }

        checkDue();
        setInterval(checkDue, 1000);

        // Пуш-уведомления (по желанию)
        if ('Notification' in window && Notification.permission === 'default') {
            // не просим сразу, чтобы не бесить; можно сделать кнопку отдельно
        }

        stopNowBtn.onclick = () => {
            if (!activeSession) return;
            stopCurrentRing();
            activeSession.completedRepeats += 1;
            scheduleNextSnooze(activeSession);
        };

        stopTodayBtn.onclick = () => {
            if (!activeSession) return;
            dismissForToday(activeSession.alarm.id, new Date().toISOString());
            activeSession.cancelled = true;
            if (snoozeTimeout) {
                clearTimeout(snoozeTimeout);
                snoozeTimeout = null;
            }
            stopCurrentRing();
        };
    </script>
</body>
</html>

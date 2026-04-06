<?php

namespace App\Http\Controllers;

use App\Models\Alarm;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlarmController extends Controller
{
    public function index()
    {
        $alarms = Alarm::orderByDesc('enabled')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return view('alarms.index_ios_v6', compact('alarms'));
    }

    public function create()
    {
        return view('alarms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'note' => ['nullable','string','max:2000'],
            'date' => ['nullable','date'],      // null = ежедневно
            'time' => ['required','date_format:H:i'],
            'enabled' => ['nullable','boolean'],
           'weekdays' => ['nullable'],
           'sound' => ['nullable','string'],
           'duration' => ['nullable','integer'],
           'snooze_duration' => ['nullable','integer'],
           'snooze_repeats' => ['nullable','integer'],
        ]);
        
        $data['weekdays'] = $request->filled('weekdays')
    ? json_decode($request->weekdays, true)
    : null;
        
        
        
        $data['enabled'] = (bool)($data['enabled'] ?? false);
        $data['timezone'] = config('app.timezone');

        Alarm::create($data);

        return redirect()->route('alarms.index')->with('ok', 'Будильник создан.');
    }

    public function edit(Alarm $alarm)
    {
        return view('alarms.edit_ios_full_v2', compact('alarm'));
    }

    public function update(Request $request, Alarm $alarm)
{
    $data = $request->validate([
        'title' => ['required','string','max:255'],
        'note' => ['nullable','string','max:2000'],
        'date' => ['nullable','date'],
        'time' => ['required','date_format:H:i'],
        'enabled' => ['nullable','boolean'],
        'weekdays' => ['nullable'],
        'sound' => ['nullable','string'],
        'duration' => ['nullable','integer'],
        'snooze_duration' => ['nullable','integer'],
        'snooze_repeats' => ['nullable','integer'],
        
    ]);
    
    $data['weekdays'] = $request->filled('weekdays')
    ? json_decode($request->weekdays, true)
    : null;
    
    $data['sound'] = $request->input('sound');
    
    $data['duration'] = $request->input('duration', 10);
    
    $data['snooze_duration'] = $request->input('snooze_duration', 10);
    $data['snooze_repeats'] = $request->input('snooze_repeats', 3);
    
    $data['enabled'] = array_key_exists('enabled', $data)
        ? (bool)$data['enabled']
        : $alarm->enabled;

    $alarm->update($data);

    // 👇 ВАЖНО
    if ($request->expectsJson()) {
        return response()->json([
            'ok' => true,
            'alarm' => $alarm->fresh(),
        ]);
    }

    return redirect()->route('alarms.index');
}

    public function destroy(Alarm $alarm)
{
    $alarm->delete();

    if (request()->expectsJson()) {
        return response()->json(['ok' => true]);
    }

    return redirect()->route('alarms.index')
        ->with('ok', 'Будильник удалён.');
}

    /**
     * API-проверка: фронт дергает раз в 1 сек.
     * Возвращаем список будильников, которые должны сработать "прямо сейчас".
     */
    public function due(Request $request)
    {
        $appTz = config('app.timezone');
        $now = Carbon::now($appTz);
        $triggeredAt = Carbon::now('UTC');

        $alarms = Alarm::query()
            ->where('enabled', true)
            ->get();

        $alarms = $alarms->filter(function (Alarm $alarm) {
            $alarmTz = $alarm->timezone ?: config('app.timezone');
            $alarmNow = Carbon::now($alarmTz);
            $today = $alarmNow->format('Y-m-d');
            $time = $alarmNow->format('H:i');
            $weekDayIndex = $alarmNow->isoWeekday() - 1; // 0=пн ... 6=вс

            if ($alarm->time !== $time) {
                return false;
            }

            $days = $alarm->weekdays;
            if (is_array($days) && count($days) === 7 && array_sum(array_map('intval', $days)) > 0) {
                if (empty($days[$weekDayIndex])) {
                    return false;
                }
            }

            if ($alarm->date && $alarm->date->format('Y-m-d') !== $today) {
                return false;
            }

            if ($alarm->last_triggered_at) {
                $lastTriggeredAt = $alarm->last_triggered_at->copy()->timezone($alarmTz);
                if ($lastTriggeredAt->format('Y-m-d H:i') === $alarmNow->format('Y-m-d H:i')) {
                    return false;
                }
            }

            return true;
        })->values();

        foreach ($alarms as $alarm) {
            $alarm->last_triggered_at = $triggeredAt;
            $alarm->save();
        }

        return response()->json([
            'now' => $now->toIso8601String(),
            'alarms' => $alarms->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'note' => $a->note,
                'date' => $a->date?->format('Y-m-d'),
                'time' => $a->time,
                'sound' => $a->sound,
            ])->values(),
        ]);
    }

    public function toggleEnabled(Request $request, Alarm $alarm)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $alarm->enabled = (bool) $data['enabled'];
        $alarm->save();

        return response()->json([
            'ok' => true,
            'alarm' => [
                'id' => $alarm->id,
                'enabled' => (bool) $alarm->enabled,
            ],
        ]);
    }
}

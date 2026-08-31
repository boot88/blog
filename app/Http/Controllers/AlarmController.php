<?php

namespace App\Http\Controllers;

use App\Models\Alarm;
use App\Models\OrganizerItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlarmController extends Controller
{
    private const DEFAULT_ALARM_TIMEZONE = 'Asia/Novosibirsk';

    public function index()
    {
        $alarms = Alarm::orderByDesc('enabled')
            ->orderBy('date')
            ->orderBy('time')
            ->get();
        $tasks = OrganizerItem::forSection('tasks')->latest()->get();

        return view('alarms.index_ios_v6', compact('alarms', 'tasks'));
    }

    public function create()
    {
        $alarm = new Alarm([
            'title' => 'Новая задача',
            'time' => now()->format('H:i'),
            'enabled' => true,
            'weekdays' => [1, 1, 1, 1, 1, 1, 1],
            'sound' => 'alarm.mp3',
            'duration' => 10,
            'snooze_duration' => 10,
            'snooze_repeats' => 3,
            'timezone' => self::DEFAULT_ALARM_TIMEZONE,
        ]);

        return view('alarms.edit_ios_full_v2', compact('alarm'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedAlarm($request);
        $data['enabled'] = (bool) ($data['enabled'] ?? false);
        $data['timezone'] = self::DEFAULT_ALARM_TIMEZONE;

        Alarm::create($data);

        return redirect()->route('alarms.index')->with('ok', 'Будильник создан.');
    }

    public function edit(Alarm $alarm)
    {
        return view('alarms.edit_ios_full_v2', compact('alarm'));
    }

    public function update(Request $request, Alarm $alarm)
    {
        $data = $this->validatedAlarm($request);
        $data['enabled'] = array_key_exists('enabled', $data)
            ? (bool) $data['enabled']
            : $alarm->enabled;

        $alarm->update($data);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'alarm' => $alarm->fresh()]);
        }

        return redirect()->route('alarms.index')->with('ok', 'Будильник обновлён.');
    }

    public function destroy(Request $request, Alarm $alarm)
    {
        $alarm->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('alarms.index')->with('ok', 'Будильник удалён.');
    }

    public function due(Request $request)
    {
        $appNow = Carbon::now(config('app.timezone'));
        $triggeredAt = Carbon::now('UTC');

        $alarms = Alarm::query()
            ->where('enabled', true)
            ->get()
            ->filter(function (Alarm $alarm) {
                $alarmTimezone = $alarm->timezone;
                if (!$alarmTimezone || strtoupper($alarmTimezone) === 'UTC') {
                    $alarmTimezone = self::DEFAULT_ALARM_TIMEZONE;
                }
                $alarmNow = Carbon::now($alarmTimezone);

                // MySQL returns TIME as HH:MM:SS, while the form submits HH:MM.
                if (substr((string) $alarm->time, 0, 5) !== $alarmNow->format('H:i')) {
                    return false;
                }
                if ($alarm->date && $alarm->date->format('Y-m-d') !== $alarmNow->format('Y-m-d')) {
                    return false;
                }

                $weekdays = is_array($alarm->weekdays) ? $alarm->weekdays : [1, 1, 1, 1, 1, 1, 1];
                if (count($weekdays) === 7 && empty($weekdays[$alarmNow->isoWeekday() - 1])) {
                    return false;
                }

                if ($alarm->last_triggered_at) {
                    $last = $alarm->last_triggered_at->copy()->timezone($alarmTimezone);
                    if ($last->format('Y-m-d H:i') === $alarmNow->format('Y-m-d H:i')) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        foreach ($alarms as $alarm) {
            $alarm->update(['last_triggered_at' => $triggeredAt]);
        }

        return response()->json([
            'now' => $appNow->toIso8601String(),
            'alarms' => $alarms->map(fn (Alarm $alarm) => [
                'id' => $alarm->id,
                'title' => $alarm->title,
                'note' => $alarm->note,
                'date' => $alarm->date?->format('Y-m-d'),
                'time' => substr((string) $alarm->time, 0, 5),
                'sound' => $alarm->sound ?: 'alarm.mp3',
                'duration' => (int) ($alarm->duration ?: 10),
                'snooze_duration' => (int) ($alarm->snooze_duration ?: 10),
                'snooze_repeats' => (int) ($alarm->snooze_repeats ?: 0),
            ]),
        ]);
    }

    public function toggleEnabled(Request $request, Alarm $alarm)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $alarm->update(['enabled' => (bool) $data['enabled']]);

        return response()->json([
            'ok' => true,
            'alarm' => ['id' => $alarm->id, 'enabled' => (bool) $alarm->enabled],
        ]);
    }

    private function validatedAlarm(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'date' => ['nullable', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'enabled' => ['nullable', 'boolean'],
            'weekdays' => ['nullable'],
            'sound' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:120'],
            'snooze_duration' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'snooze_repeats' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $data['weekdays'] = $request->filled('weekdays')
            ? json_decode((string) $request->input('weekdays'), true)
            : null;
        $data['sound'] = $data['sound'] ?? 'alarm.mp3';
        $data['duration'] = $data['duration'] ?? 10;
        $data['snooze_duration'] = $data['snooze_duration'] ?? 10;
        $data['snooze_repeats'] = $data['snooze_repeats'] ?? 3;

        return $data;
    }
}

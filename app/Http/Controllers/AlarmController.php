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
        ]);

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
    ]);

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

    public function destroy(Request $request, Alarm $alarm)
{
    $alarm->delete();

    if ($request->expectsJson()) {
        return response()->json(['ok' => true]);
    }

    return redirect()->route('alarms.index');
}

    /**
     * API-проверка: фронт дергает раз в 1 сек.
     * Возвращаем список будильников, которые должны сработать "прямо сейчас".
     */
    public function due(Request $request)
    {
        $tz = config('app.timezone');
        $now = Carbon::now($tz);

        $today = $now->format('Y-m-d');
        $time = $now->format('H:i');

        // Будильники:
        // - включены
        // - либо на сегодня (date = today), либо ежедневные (date is null)
        // - время = текущее H:i
        $alarms = Alarm::query()
            ->where('enabled', true)
            ->where('time', $time)
            ->where(function ($q) use ($today) {
                $q->whereNull('date')->orWhere('date', $today);
            })
            ->get();

        // Чтобы не “дребезжало” при обновлениях — отметим last_triggered_at (раз в минуту)
        foreach ($alarms as $alarm) {
            $alarm->last_triggered_at = $now;
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
            ])->values(),
        ]);
    }
}

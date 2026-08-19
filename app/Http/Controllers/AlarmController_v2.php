<?php

namespace App\Http\Controllers;

use App\Models\Alarm;
use Illuminate\Http\Request;

class AlarmController_v2 extends Controller
{
    public function index()
    {
        $alarms = Alarm::orderByDesc('enabled')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return view('alarms.index_ios_v6', compact('alarms'));
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

        $data['enabled'] = (bool)($data['enabled'] ?? $alarm->enabled);

        $alarm->update($data);

        return response()->json([
            'ok' => true,
            'alarm' => $alarm->fresh(),
        ]);
    }


    public function destroy(Alarm $alarm)
{
    $alarm->delete();

    return response()->json([
        'ok' => true
    ]);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'note' => ['nullable','string','max:2000'],
            'date' => ['nullable','date'],
            'time' => ['required','date_format:H:i'],
            'enabled' => ['nullable','boolean'],
        ]);

        $data['enabled'] = (bool)($data['enabled'] ?? false);
        $data['timezone'] = config('app.timezone');

        $alarm = Alarm::create($data);

        return response()->json([
            'ok' => true,
            'alarm' => $alarm,
        ]);
    }
}

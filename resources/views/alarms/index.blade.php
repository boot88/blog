@extends('layouts.app')

@section('title', 'Задачи')
@section('header', 'Задачи')

@section('content')
    <div class="card">
        <div class="muted">Подсказка: оставь поле “Дата” пустым — будет ежедневный будильник.</div>
    </div>

    @forelse($alarms as $alarm)
        <div class="card">
            <div class="row" style="justify-content:space-between;align-items:flex-start">
                <div style="flex:1;min-width:260px">
                    <div class="row" style="align-items:center">
                        <strong style="font-size:18px">{{ $alarm->title }}</strong>
                        @if($alarm->enabled)
                            <span class="tag">включён</span>
                        @else
                            <span class="tag" style="opacity:.6">выключён</span>
                        @endif

                        @if($alarm->date)
                            <span class="tag">дата: {{ $alarm->date->format('d.m.Y') }}</span>
                        @else
                            <span class="tag">ежедневно</span>
                        @endif

                        <span class="tag">время: {{ substr($alarm->time,0,5) }}</span>
                    </div>

                    @if($alarm->note)
                        <div class="muted" style="margin-top:8px;white-space:pre-wrap">{{ $alarm->note }}</div>
                    @endif
                </div>

                <div class="row">
                    <a class="btn" href="{{ route('alarms.edit', $alarm) }}">Изменить</a>
                    <form method="POST" action="{{ route('alarms.destroy', $alarm) }}"
                          onsubmit="return confirm('Удалить будильник?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card">Пока нет будильников. Нажми “+ Добавить”.</div>
    @endforelse
@endsection
@extends('layouts.app')

@section('title', 'Задачи')
@section('header', 'Задачи')

@section('content')
    <div class="card">
        <div class="muted">Подсказка: оставь поле “Дата” пустым — будет ежедневный будильник.</div>
    </div>

    @forelse($alarms as $alarm)
        <div class="card">
            <div class="row" style="justify-content:space-between;align-items:flex-start">
                <div style="flex:1;min-width:260px">
                    <div class="row" style="align-items:center">
                        <strong style="font-size:18px">{{ $alarm->title }}</strong>
                        @if($alarm->enabled)
                            <span class="tag">включён</span>
                        @else
                            <span class="tag" style="opacity:.6">выключён</span>
                        @endif

                        @if($alarm->date)
                            <span class="tag">дата: {{ $alarm->date->format('d.m.Y') }}</span>
                        @else
                            <span class="tag">ежедневно</span>
                        @endif

                        <span class="tag">время: {{ substr($alarm->time,0,5) }}</span>
                    </div>

                    @if($alarm->note)
                        <div class="muted" style="margin-top:8px;white-space:pre-wrap">{{ $alarm->note }}</div>
                    @endif
                </div>

                <div class="row">
                    <a class="btn" href="{{ route('alarms.edit', $alarm) }}">Изменить</a>
                    <form method="POST" action="{{ route('alarms.destroy', $alarm) }}"
                          onsubmit="return confirm('Удалить будильник?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card">Пока нет будильников. Нажми “+ Добавить”.</div>
    @endforelse
@endsection

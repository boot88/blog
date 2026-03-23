@extends('layouts.app')

@section('title', 'Изменить будильник')
@section('header', 'Изменить будильник')

@section('content')
    <div class="card">
        <form method="POST" action="{{ route('alarms.update', $alarm) }}">
            @csrf
            @method('PUT')

            <div style="margin-bottom:12px">
                <label>Название</label>
                <input name="title" value="{{ old('title', $alarm->title) }}" required>
                @error('title')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div style="margin-bottom:12px">
                <label>Что сделать (описание)</label>
                <textarea name="note" rows="4">{{ old('note', $alarm->note) }}</textarea>
                @error('note')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div style="flex:1;min-width:180px;margin-bottom:12px">
                    <label>Дата (пусто = ежедневно)</label>
                    <input type="date" name="date" value="{{ old('date', optional($alarm->date)->format('Y-m-d')) }}">
                    @error('date')<div class="muted">{{ $message }}</div>@enderror
                </div>
                <div style="flex:1;min-width:180px;margin-bottom:12px">
                    <label>Время</label>
                    <input type="time" name="time" value="{{ old('time', substr($alarm->time,0,5)) }}" required>
                    @error('time')<div class="muted">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin:10px 0 16px 0">
                <label>
                    <input type="checkbox" name="enabled" value="1" {{ old('enabled', $alarm->enabled) ? 'checked' : '' }}>
                    Включён
                </label>
            </div>

            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a class="btn" href="{{ route('alarms.index') }}">Назад</a>
        </form>
    </div>
@endsection

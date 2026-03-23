@extends('layouts.app')

@section('title', 'Добавить будильник')
@section('header', 'Добавить будильник')

@section('content')
    <div class="card">
        <form method="POST" action="{{ route('alarms.store') }}">
            @csrf

            <div style="margin-bottom:12px">
                <label>Название</label>
                <input name="title" value="{{ old('title') }}" required>
                @error('title')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div style="margin-bottom:12px">
                <label>Что сделать (описание)</label>
                <textarea name="note" rows="4">{{ old('note') }}</textarea>
                @error('note')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div style="flex:1;min-width:180px;margin-bottom:12px">
                    <label>Дата (пусто = ежедневно)</label>
                    <input type="date" name="date" value="{{ old('date') }}">
                    @error('date')<div class="muted">{{ $message }}</div>@enderror
                </div>
                <div style="flex:1;min-width:180px;margin-bottom:12px">
                    <label>Время</label>
                    <input type="time" name="time" value="{{ old('time','09:00') }}" required>
                    @error('time')<div class="muted">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin:10px 0 16px 0">
                <label>
                    <input type="checkbox" name="enabled" value="1" {{ old('enabled', true) ? 'checked' : '' }}>
                    Включён
                </label>
            </div>

            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a class="btn" href="{{ route('alarms.index') }}">Отмена</a>
        </form>
    </div>
@endsection

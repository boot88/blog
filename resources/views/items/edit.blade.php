@extends('layouts.app')

@section('title', 'Редактирование — '.$sectionTitle)
@section('header', 'Редактирование записи')

@section('page-actions')
    <a class="btn" href="{{ route('items.index', $section) }}">Назад к списку</a>
@endsection

@section('content')
<div class="card" style="max-width:760px;margin:0 auto">
    <form method="POST" action="{{ route('items.update', [$section, $item]) }}">
        @csrf @method('PUT')
        <div class="field">
            <label for="title">Название</label>
            <input id="title" name="title" value="{{ old('title', $item->title) }}" required maxlength="255">
            @error('title')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="content">Описание или заметка</label>
            <textarea id="content" name="content" rows="12" maxlength="20000">{{ old('content', $item->content) }}</textarea>
            @error('content')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="category">Категория <span class="muted">(необязательно)</span></label>
            <input id="category" name="category" value="{{ old('category', $item->category) }}" maxlength="100">
            @error('category')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="row form-actions">
            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a class="btn" href="{{ route('items.index', $section) }}">Отмена</a>
        </div>
    </form>
</div>
@endsection

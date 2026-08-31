@extends('layouts.app')

@section('title', $sectionTitle)
@section('header', $sectionTitle)

@section('content')
<style>
    .section-grid{display:grid;grid-template-columns:minmax(280px,360px) minmax(0,1fr);gap:18px;align-items:start}
    .create-card{position:sticky;top:80px}
    .search-row{display:flex;gap:8px;margin-bottom:14px}.search-row input{flex:1}
    .item-card{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
    .item-main{min-width:0;flex:1}.item-title{font-size:18px;font-weight:700;overflow-wrap:anywhere}
    .item-content{white-space:pre-wrap;overflow-wrap:anywhere;margin-top:8px;color:#3c3c43;line-height:1.45}
    .item-meta{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px;font-size:12px;color:#8e8e93}
    .item-actions{display:flex;gap:7px;flex:0 0 auto}
    @media(max-width:820px){.section-grid{grid-template-columns:1fr}.create-card{position:static}.item-card{flex-direction:column}.item-actions{width:100%}.item-actions .btn,.item-actions form{flex:1}.item-actions form .btn{width:100%}}
    @media(max-width:480px){.search-row{display:grid;grid-template-columns:1fr}.search-row .btn{width:100%}}
</style>

<div class="section-grid">
    <section class="card create-card">
        <h2 style="margin:0 0 15px;font-size:20px">Добавить запись</h2>
        <form method="POST" action="{{ route('items.store', $section) }}">
            @csrf
            <div class="field">
                <label for="title">Название</label>
                <input id="title" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="Введите название">
                @error('title')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="content">Описание или заметка</label>
                <textarea id="content" name="content" rows="6" maxlength="20000" placeholder="Дополнительная информация">{{ old('content') }}</textarea>
                @error('content')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="category">Категория <span class="muted">(необязательно)</span></label>
                <input id="category" name="category" value="{{ old('category') }}" maxlength="100" placeholder="Например: Работа">
                @error('category')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit" style="width:100%">Сохранить</button>
        </form>
    </section>

    <section>
        <form class="search-row" method="GET" action="{{ route('items.index', $section) }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="Поиск по названию, тексту или категории" aria-label="Поиск">
            <button class="btn btn-primary" type="submit">Найти</button>
            @if($search !== '')<a class="btn" href="{{ route('items.index', $section) }}">Сбросить</a>@endif
        </form>

        @forelse($items as $item)
            <article class="card item-card">
                <div class="item-main">
                    <div class="item-title">{{ $item->title }}</div>
                    @if($item->content)<div class="item-content">{{ $item->content }}</div>@endif
                    <div class="item-meta">
                        @if($item->category)<span class="tag">{{ $item->category }}</span>@endif
                        <time datetime="{{ $item->updated_at->toIso8601String() }}">Обновлено {{ $item->updated_at->format('d.m.Y H:i') }}</time>
                    </div>
                </div>
                <div class="item-actions">
                    <a class="btn" href="{{ route('items.edit', [$section, $item]) }}">Изменить</a>
                    <form method="POST" action="{{ route('items.destroy', [$section, $item]) }}" onsubmit="return confirm('Удалить запись?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger" type="submit">Удалить</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card empty">{{ $search !== '' ? 'По вашему запросу ничего не найдено.' : 'Здесь пока нет записей.' }}</div>
        @endforelse

        @if($items->hasPages())<div class="pagination">{{ $items->links() }}</div>@endif
    </section>
</div>
@endsection

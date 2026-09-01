<?php

namespace App\Http\Controllers;

use App\Models\OrganizerItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrganizerItemController extends Controller
{
    public function index(Request $request, string $section): View
    {
        $this->ensureSectionExists($section);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));

        $items = OrganizerItem::query()
            ->forSection($section)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $pattern = '%'.$search.'%';
                    $searchQuery
                        ->where('title', 'like', $pattern)
                        ->orWhere('content', 'like', $pattern)
                        ->orWhere('category', 'like', $pattern);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'section' => $section,
            'sectionTitle' => OrganizerItem::sectionTitle($section),
            'search' => $search,
        ]);
    }

    public function store(Request $request, string $section): RedirectResponse|JsonResponse
    {
        $this->ensureSectionExists($section);
        $item = OrganizerItem::create([
            ...$this->validatedItem($request),
            'section' => $section,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'item' => $item], 201);
        }

        return redirect()
            ->route('items.index', $section)
            ->with('ok', 'Запись добавлена.');
    }

    public function edit(string $section, OrganizerItem $item): View
    {
        $this->ensureMatchingItem($section, $item);

        return view('items.edit', [
            'item' => $item,
            'section' => $section,
            'sectionTitle' => OrganizerItem::sectionTitle($section),
        ]);
    }

    public function update(Request $request, string $section, OrganizerItem $item): RedirectResponse|JsonResponse
    {
        $this->ensureMatchingItem($section, $item);
        $item->update($this->validatedItem($request));

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'item' => $item->fresh()]);
        }

        return redirect()
            ->route('items.index', $section)
            ->with('ok', 'Изменения сохранены.');
    }

    public function destroy(Request $request, string $section, OrganizerItem $item): RedirectResponse|JsonResponse
    {
        $this->ensureMatchingItem($section, $item);
        $item->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('items.index', $section)
            ->with('ok', 'Запись удалена.');
    }

    public function importLocalTasks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tasks' => ['required', 'array', 'max:500'],
            'tasks.*.id' => ['nullable', 'string', 'max:150'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.category' => ['nullable', 'string', 'max:100'],
            'tasks.*.created_at' => ['nullable', 'date'],
        ]);

        $imported = DB::transaction(function () use ($validated): int {
            $count = 0;

            foreach ($validated['tasks'] as $index => $task) {
                $identity = (string) ($task['id'] ?? ($task['title'].'|'.($task['created_at'] ?? $index)));
                $sourceKey = 'local:'.hash('sha256', $identity);

                OrganizerItem::firstOrCreate(
                    ['source_key' => $sourceKey],
                    [
                        'section' => 'tasks',
                        'title' => $task['title'],
                        'category' => $task['category'] ?? 'Общие',
                        'created_at' => $task['created_at'] ?? now(),
                        'updated_at' => $task['created_at'] ?? now(),
                    ]
                );
                $count++;
            }

            return $count;
        });

        return response()->json(['ok' => true, 'processed' => $imported]);
    }

    private function validatedItem(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:20000'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function ensureSectionExists(string $section): void
    {
        abort_unless(array_key_exists($section, OrganizerItem::SECTIONS), 404);
    }

    private function ensureMatchingItem(string $section, OrganizerItem $item): void
    {
        $this->ensureSectionExists($section);
        abort_unless($item->section === $section, 404);
    }
}

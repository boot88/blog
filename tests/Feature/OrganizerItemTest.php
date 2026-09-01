<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\OrganizerItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_organizer_section_is_available(): void
    {
        foreach (array_keys(OrganizerItem::SECTIONS) as $section) {
            $this->get(route('items.index', $section))->assertOk();
        }
    }

    public function test_item_can_be_created_searched_updated_and_deleted(): void
    {
        $this->post(route('items.store', 'drafts'), [
            'title' => 'Тестовый черновик',
            'content' => 'Текст для поиска',
            'category' => 'Работа',
        ])->assertRedirect(route('items.index', 'drafts'));

        $item = OrganizerItem::firstOrFail();
        $this->get(route('items.index', ['section' => 'drafts', 'q' => 'поиска']))
            ->assertOk()
            ->assertSee('Тестовый черновик');

        $this->put(route('items.update', ['drafts', $item]), [
            'title' => 'Обновлённый черновик',
            'content' => 'Новый текст',
        ])->assertRedirect(route('items.index', 'drafts'));

        $this->assertDatabaseHas('organizer_items', [
            'id' => $item->id,
            'title' => 'Обновлённый черновик',
        ]);

        $this->delete(route('items.destroy', ['drafts', $item]))
            ->assertRedirect(route('items.index', 'drafts'));
        $this->assertDatabaseMissing('organizer_items', ['id' => $item->id]);
    }

    public function test_item_cannot_be_edited_through_another_section(): void
    {
        $item = OrganizerItem::create([
            'section' => 'programs',
            'title' => 'Программа',
        ]);

        $this->get(route('items.edit', ['drafts', $item]))->assertNotFound();
    }

    public function test_local_tasks_import_is_idempotent(): void
    {
        $payload = [
            'tasks' => [[
                'id' => 'task-123',
                'title' => 'Старая локальная задача',
                'category' => 'Общие',
                'created_at' => '2026-08-30T10:00:00Z',
            ]],
        ];

        $this->postJson(route('items.import-local'), $payload)->assertOk();
        $this->postJson(route('items.import-local'), $payload)->assertOk();

        $this->assertDatabaseCount('organizer_items', 1);
        $this->assertDatabaseHas('organizer_items', [
            'section' => 'tasks',
            'title' => 'Старая локальная задача',
        ]);
    }

    public function test_mysql_time_format_does_not_prevent_alarm_from_firing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 09:00:30', 'Asia/Novosibirsk'));

        $alarm = Alarm::create([
            'title' => 'Проверка MySQL TIME',
            'time' => '09:00',
            'enabled' => true,
            'timezone' => 'Asia/Novosibirsk',
            'weekdays' => [1, 1, 1, 1, 1, 1, 1],
        ]);

        $this->getJson(route('alarms.due'))
            ->assertOk()
            ->assertJsonPath('alarms.0.id', $alarm->id)
            ->assertJsonPath('alarms.0.time', '09:00');

        Carbon::setTestNow();
    }
}

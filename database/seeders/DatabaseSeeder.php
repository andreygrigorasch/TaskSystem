<?php

namespace Database\Seeders;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo-only credentials. Repeat seeding preserves existing data.
        foreach ([
            ['admin@example.com', 'Анна Смирнова', 'admin'],
            ['employee@example.com', 'Михаил Волков', 'employee'],
            ['employee2@example.com', 'Елена Орлова', 'employee'],
        ] as [$email, $name, $role]) {
            User::firstOrCreate(['email' => $email], ['name' => $name, 'role' => $role, 'password' => Hash::make('password')]);
        }
        if (ServiceRequest::exists()) {
            return;
        }
        $mikhail = User::where('email', 'employee@example.com')->value('id');
        $elena = User::where('email', 'employee2@example.com')->value('id');
        $examples = [
            ['Подключить рабочее место нового сотрудника', 'Подготовить ноутбук, подключить монитор и настроить доступ к корпоративной почте. Новый сотрудник выходит в понедельник.', 'new', $mikhail],
            ['Настроить доступ к CRM', 'Выдать отделу продаж доступ к новой воронке и проверить права на экспорт контактов.', 'in_progress', $elena],
            ['Заменить картридж в переговорной', 'Принтер в большой переговорной сообщает о низком уровне тонера. Запасной картридж находится на складе.', 'new', $mikhail],
            ['Проверить резервное копирование', 'Проверить ночные резервные копии и выполнить тестовое восстановление одного документа.', 'done', $elena],
            ['Обновить сертификат на внутреннем портале', 'Установить новый сертификат и проверить цепочку доверия в браузере.', 'in_progress', $mikhail],
            ['Открыть доступ к папке проекта', 'Предоставить проектной команде права на чтение общей папки с документами.', 'new', null],
            ['Подготовить оборудование для презентации', 'Проверить проектор, звук и подключение гостевого ноутбука в конференц-зале.', 'new', $elena],
            ['Исправить подключение к Wi-Fi', 'В кабинете 204 периодически пропадает подключение. Проверить точку доступа и уровень сигнала.', 'done', $mikhail],
            ['Установить редактор документов', 'Установить офисный пакет на ноутбук бухгалтерии и активировать лицензию.', 'new', $mikhail],
            ['Проверить права на общий календарь', 'Сотрудники не видят бронирования малой переговорной. Проверить права группы.', 'in_progress', $elena],
            ['Подключить сканер на ресепшене', 'Настроить сканирование в сетевую папку и проверить сохранение PDF.', 'new', $mikhail],
            ['Обновить инструкцию по VPN', 'Проверить шаги подключения и опубликовать актуальные скриншоты в базе знаний.', 'new', $elena],
        ];
        foreach ($examples as $index => [$title, $description, $status, $assignee]) {
            $ticket = new ServiceRequest(['title' => $title, 'description' => $description, 'assignee_id' => $assignee]);
            $ticket->status = $status;
            $ticket->version = match ($status) {
                'done' => 3, 'in_progress' => 2, default => 1
            };
            $ticket->created_at = now()->subDays(5)->addHours($index * 4);
            $ticket->updated_at = $ticket->created_at->copy()->addHours($ticket->version - 1);
            $ticket->save();
        }
    }
}

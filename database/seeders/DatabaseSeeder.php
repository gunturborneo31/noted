<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Note;
use App\Models\NoteCredential;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@noted.app'],
            [
                'name'     => 'Demo User',
                'password' => Hash::make('password'),
            ]
        );

        // Clients
        $acme    = Client::create(['name' => 'Acme Corp',    'slug' => 'acme-corp']);
        $startup = Client::create(['name' => 'Startup Labs', 'slug' => 'startup-labs']);

        // Projects
        $proj1 = Project::create([
            'client_id'    => $acme->id,
            'project_name' => 'Website Redesign',
            'slug'         => 'website-redesign',
            'status'       => 'active',
        ]);
        $proj2 = Project::create([
            'client_id'    => $acme->id,
            'project_name' => 'Mobile App',
            'slug'         => 'mobile-app',
            'status'       => 'active',
        ]);
        $proj3 = Project::create([
            'client_id'    => $startup->id,
            'project_name' => 'API Integration',
            'slug'         => 'api-integration',
            'status'       => 'active',
        ]);

        // Tasks
        Task::create([
            'project_id' => $proj1->id,
            'task_name'  => 'Design homepage mockup',
            'content'    => "Create a bold Neo-Brutalism homepage design.\n\n#design #ui\n\n- [ ] Wireframe\n- [ ] Color palette\n- [x] Typography selection",
            'status'     => 'in_progress',
            'due_date'   => now()->addDays(3),
        ]);

        Task::create([
            'project_id' => $proj1->id,
            'task_name'  => 'Setup Laravel project',
            'content'    => "Initialize project with Livewire and Tailwind.\n\n#laravel #setup\n\n- [x] Install Laravel\n- [x] Configure database\n- [ ] Deploy to staging",
            'status'     => 'done',
            'due_date'   => now()->subDays(2),
        ]);

        Task::create([
            'project_id' => $proj2->id,
            'task_name'  => 'Fix login bug on iOS',
            'content'    => "Users cannot login on iOS 16. Investigate and fix. #bug #mobile #urgent",
            'status'     => 'todo',
            'due_date'   => now()->subDays(1),
        ]);

        Task::create([
            'project_id' => $proj3->id,
            'task_name'  => 'API documentation review',
            'content'    => "Review and update Swagger docs.\n\n#api #docs\n\n- [ ] Review endpoints\n- [ ] Add examples\n- [ ] Publish",
            'status'     => 'todo',
            'due_date'   => now()->addDays(5),
        ]);

        Task::create([
            'project_id' => $proj3->id,
            'task_name'  => 'Integrate payment gateway',
            'content'    => "Stripe integration for subscription billing. #stripe #payment #backend",
            'status'     => 'in_progress',
            'due_date'   => now()->addDays(7),
        ]);

        // Notes
        $note1 = Note::create([
            'user_id' => $user->id,
            'title'   => 'Project Planning Notes',
            'body'    => "## Q3 Planning\n\nKey priorities for this quarter:\n\n- [ ] Launch website redesign #design\n- [ ] Complete mobile app MVP #mobile\n- [x] Setup CI/CD pipeline #devops\n\n## Resources\n\nCheck the [Tailwind docs](https://tailwindcss.com) for UI.\n\n#planning #q3",
        ]);

        $note2 = Note::create([
            'user_id' => $user->id,
            'title'   => 'Dev Environment Setup',
            'body'    => "## Local Setup\n\nSteps to get started:\n\n1. Clone the repo\n2. Run `composer install`\n3. Run `npm install && npm run dev`\n4. Copy `.env.example` to `.env`\n\n#setup #devops #laravel",
        ]);

        NoteCredential::create([
            'note_id'   => $note2->id,
            'url_login' => 'https://staging.example.com/login',
            'username'  => 'admin@example.com',
            'password'  => 'SuperSecret123!',
        ]);
    }
}

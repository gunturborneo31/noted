<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'demo@noted.app'],
            [
                'name'     => 'Demo User',
                'password' => Hash::make('password'),
            ]
        );

        $clients = [];
        $projects = [];

        $resolveProjectId = function (string $clientName, string $projectName) use (&$clients, &$projects): int {
            if (!isset($clients[$clientName])) {
                $clients[$clientName] = Client::create([
                    'name' => $clientName,
                    'slug' => Str::slug($clientName),
                ]);
            }

            $key = $clientName.'|'.$projectName;
            if (!isset($projects[$key])) {
                $projects[$key] = Project::create([
                    'client_id'    => $clients[$clientName]->id,
                    'project_name' => $projectName,
                    'slug'         => Str::slug($clientName.' '.$projectName),
                    'status'       => 'active',
                ]);
            }

            return $projects[$key]->id;
        };

        $tasks = [
            // urgent (belum)
            ['desain poster simpelsibang', 'bappelitbangda', 'simpelsibang', 'todo', 'urgent'],
            ['ganti logo alur simpelsibang', 'bappelitbangda', 'simpelsibang', 'todo', 'urgent'],
            ['desain poster e-csr', 'bappelitbangda', 'e-csr', 'todo', 'urgent'],
            ['ganti logo alur e-csr', 'bappelitbangda', 'e-csr', 'todo', 'urgent'],
            ['pesan desain neon box', 'kedai ak', 'renov', 'todo', 'urgent'],
            ['antar mesin kopi ke pak catur', 'kedai ak', 'renov', 'todo', 'urgent'],
            ['ambil kursi di mas tejo', 'kedai ak', 'renov', 'todo', 'urgent'],
            ['update harga menjadi 15k', 'kedai ak', 'renov', 'todo', 'urgent'],
            ['cetak harga menu', 'kedai ak', 'renov', 'todo', 'urgent'],
            ['pindahkan cctv', 'kedai ak', 'renov', 'todo', 'urgent'],
            ['visi misi perusahaan sangatta fiesta', 'Sangatta Fiesta', 'website', 'todo', 'urgent'],
            ['cek inputan inovasi iga provinsi', 'bappelitbangda', 'simpelsibang', 'todo', 'urgent'],
            ['Beli pewangi gantung', 'kedai ak', 'renov', 'todo', 'urgent'],
            ['redesain slider e-csr', 'bappelitbangda', 'e-csr', 'todo', 'urgent'],

            // tidak urgent (belum)
            ['upload potret pimpinan', 'prokopim', 'potret pimpinan', 'todo', 'tidak urgent'],
            ['update website prokopim', 'prokopim', 'website', 'todo', 'tidak urgent'],
            ['update agpim prokopim', 'prokopim', 'agpim', 'todo', 'tidak urgent'],
            ['koordinasi desain sosmd prokopim', 'prokopim', 'sosmed', 'todo', 'tidak urgent'],
            ['ganti alur kabel neon box', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['bongkar dinding nusaboat gudang', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['tembel pintu nusaboat dan pasang rangka gudang', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['buat rak gundag sampai atas', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['ganti lampu ruangan belakang lebih terang', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['update harga di gojek', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['beli mika ukuran a3 untuk di pintu tengah', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['Cetak tulisan ak kreatif ukuran A3', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['Listing harga untuk kedai tata, gelas - gelas, kursi - kursi pendek', 'kedai ak', 'cabang Belimau', 'todo', 'tidak urgent'],
            ['masukkan nomor perusahaan csr berdasarkan undangan', 'bappelitbangda', 'e-csr', 'todo', 'tidak urgent'],
            ['anak magang dibuatkan baju atau tidak', 'ak kreatif', 'magang', 'todo', 'tidak urgent'],
            ['Potong sterofoam ukuran A4 jadi pajangan', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['Lepas blower', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['mie dug-dug pakai mie kuning', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['beli tumbler', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['beli gelas mug', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['aplikasi siap dan e-skm uptd pprd samarinda', 'uptd pprd', 'aplikasi', 'todo', 'tidak urgent'],
            ['aplikasi kasir', 'kedai ak', 'renov', 'todo', 'tidak urgent'],
            ['aplikasi piston', 'piston coffee', 'evaluasi', 'todo', 'tidak urgent'],

            // sudah
            ['baiki rolling door', 'kedai ak', 'renov', 'done', 'sudah'],
        ];

        foreach ($tasks as [$taskName, $clientName, $projectName, $status, $priority]) {
            Task::create([
                'project_id' => $resolveProjectId($clientName, $projectName),
                'task_name'  => $taskName,
                'content'    => 'Prioritas: '.$priority,
                'status'     => $status,
                'due_date'   => null,
            ]);
        }
    }
}

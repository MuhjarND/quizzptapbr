<?php
namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateHost extends Command
{
    protected $signature = 'host:create {email} {--name=Admin PTA Papua Barat}';
    protected $description = 'Membuat akun host baru dengan kata sandi acak.';

    public function handle()
    {
        $email = mb_strtolower(trim($this->argument('email')));
        $name = trim($this->option('name'));
        $validator = Validator::make(compact('email', 'name'), [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return 1;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('Email sudah terdaftar. Gunakan akun tersebut atau pilih email lain.');
            return 1;
        }

        $password = bin2hex(random_bytes(10)).'!A7';
        User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
        $this->info('Akun host berhasil dibuat. Simpan kata sandi berikut.');
        $this->line('Nama: '.$name);
        $this->line('Email: '.$email);
        $this->line('Kata sandi: '.$password);
        return 0;
    }
}

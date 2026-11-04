<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\{User, Quiz};
class QuizArenaSeeder extends Seeder
{
    public function run()
    {
        $host = User::firstOrCreate(['email' => 'host@quizarena.test'], ['name' => 'Raka Pratama', 'password' => Hash::make('QuizArena123!')]);
        $sets = [
            ['Jelajah Nusantara', 'islands', 'ready', 'Kenali kekayaan alam, budaya, dan keunikan Indonesia.', [
                ['Pulau Komodo berada di provinsi mana?', ['Nusa Tenggara Timur', 'Bali', 'Sulawesi Selatan', 'Maluku'], 0],
                ['Alat musik angklung berasal dari daerah mana?', ['Sumatra Barat', 'Jawa Barat', 'Jawa Timur', 'Papua'], 1],
                ['Danau vulkanik terbesar di Indonesia adalah…', ['Danau Sentani', 'Danau Poso', 'Danau Toba', 'Danau Maninjau'], 2],
                ['Candi Borobudur terletak di…', ['Yogyakarta', 'Magelang', 'Solo', 'Semarang'], 1],
                ['Burung cenderawasih identik dengan wilayah…', ['Kalimantan', 'Jawa', 'Sumatra', 'Papua'], 3]
            ]],
            ['Sains di Sekitar Kita', 'science', 'draft', 'Temukan sains dalam hal-hal kecil di sekitar kita.', [
                ['Planet yang dikenal sebagai Planet Merah adalah…', ['Venus', 'Mars', 'Jupiter', 'Saturnus'], 1],
                ['Tumbuhan membuat makanan melalui proses…', ['Respirasi', 'Evaporasi', 'Fotosintesis', 'Kondensasi'], 2],
                ['Pada tekanan 1 atmosfer, air mendidih pada suhu…', ['50 °C', '75 °C', '100 °C', '150 °C'], 2],
                ['Organ yang memompa darah ke seluruh tubuh adalah…', ['Paru-paru', 'Hati', 'Ginjal', 'Jantung'], 3],
                ['Sumber energi utama bagi bumi adalah…', ['Matahari', 'Bulan', 'Angin', 'Bintang jatuh'], 0]
            ]],
            ['Seberapa Kenal Teknologi?', 'tech', 'ready', 'Uji pengetahuan digital Anda bersama teman dan tim.', [
                ['CPU merupakan singkatan dari…', ['Central Processing Unit', 'Computer Personal Unit', 'Core Program Utility', 'Central Power Unit'], 0],
                ['Bahasa yang digunakan untuk menyusun struktur halaman web adalah…', ['CSS', 'HTML', 'SQL', 'PHP'], 1],
                ['Manakah contoh kata sandi yang paling kuat?', ['12345678', 'namasaya', 'A7!kP2#vL9', 'password'], 2],
                ['Perangkat untuk menghubungkan beberapa jaringan disebut…', ['Monitor', 'Keyboard', 'Printer', 'Router'], 3],
                ['Penyimpanan berbasis internet biasa disebut…', ['Cloud storage', 'Floppy disk', 'RAM', 'Cache lokal'], 0]
            ]]
        ];
        foreach ($sets as $i => $set) {
            if (Quiz::where('user_id', $host->id)->where('title', $set[0])->exists()) continue;
            $quiz = Quiz::create(['user_id' => $host->id, 'title' => $set[0], 'theme' => $set[1], 'status' => $set[2], 'description' => $set[3], 'created_at' => now()->subMinutes($i), 'updated_at' => now()->subMinutes($i)]);
            foreach ($set[4] as $j => $data) {
                $q = $quiz->questions()->create(['text' => $data[0], 'position' => $j + 1, 'duration' => 20]);
                foreach ($data[1] as $k => $text) $q->choices()->create(['text' => $text, 'is_correct' => $k === $data[2], 'position' => $k]);
            }
        }
    }
}

<?php
return [
    'required' => ':attribute wajib diisi.', 'string' => ':attribute harus berupa teks.',
    'integer' => ':attribute harus berupa angka bulat.', 'numeric' => ':attribute harus berupa angka.',
    'array' => 'Format :attribute tidak valid.', 'email' => 'Masukkan alamat email yang valid.',
    'unique' => ':attribute sudah digunakan.', 'confirmed' => 'Konfirmasi :attribute tidak sama.',
    'in' => 'Pilihan :attribute tidak valid.', 'exists' => ':attribute tidak ditemukan.',
    'digits' => ':attribute harus terdiri dari :digits angka.', 'regex' => 'Format :attribute tidak valid.',
    'image' => ':attribute harus berupa gambar.', 'mimes' => 'Format :attribute harus :values.',
    'uploaded' => 'Unggahan :attribute gagal. Periksa ukuran file dan coba kembali.',
    'max' => ['numeric'=>':attribute maksimal :max.','file'=>':attribute maksimal :max KB.','string'=>':attribute maksimal :max karakter.','array'=>':attribute maksimal :max item.'],
    'min' => ['numeric'=>':attribute minimal :min.','file'=>':attribute minimal :min KB.','string'=>':attribute minimal :min karakter.','array'=>':attribute minimal :min item.'],
    'between' => ['numeric'=>':attribute harus antara :min dan :max.','string'=>':attribute harus antara :min dan :max karakter.','array'=>':attribute harus memiliki :min–:max item.'],
    'attributes' => ['name'=>'Nama','email'=>'Email','password'=>'Kata sandi','nickname'=>'Nama panggilan','pin'=>'PIN','action'=>'Aksi','version'=>'Versi permainan','question_id'=>'Pertanyaan','choice_index'=>'Pilihan jawaban']
];

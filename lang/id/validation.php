<?php

/*
| Indonesian validation messages for the rules used by ZI-TERPADU.
| Rules not listed here fall back to the English defaults (APP_FALLBACK_LOCALE).
*/

return [
    'accepted' => ':Attribute harus disetujui.',
    'after_or_equal' => ':Attribute harus berupa tanggal setelah atau sama dengan :date.',
    'array' => ':Attribute harus berupa daftar.',
    'before_or_equal' => ':Attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus memiliki :min sampai :max item.',
        'file' => ':Attribute harus berukuran :min sampai :max kilobita.',
        'numeric' => ':Attribute harus bernilai :min sampai :max.',
        'string' => ':Attribute harus berisi :min sampai :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'distinct' => ':Attribute memiliki nilai yang duplikat.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'file' => ':Attribute harus berupa berkas.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'lowercase' => ':Attribute harus menggunakan huruf kecil.',
    'max' => [
        'array' => ':Attribute maksimal berisi :max item.',
        'file' => ':Attribute maksimal berukuran :max kilobita.',
        'numeric' => ':Attribute maksimal bernilai :max.',
        'string' => ':Attribute maksimal berisi :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa berkas bertipe: :values.',
    'min' => [
        'array' => ':Attribute minimal berisi :min item.',
        'file' => ':Attribute minimal berukuran :min kilobita.',
        'numeric' => ':Attribute minimal bernilai :min.',
        'string' => ':Attribute minimal berisi :min karakter.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus mengandung minimal satu huruf.',
        'mixed' => ':Attribute harus mengandung minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus mengandung minimal satu angka.',
        'symbols' => ':Attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':Attribute ini pernah muncul dalam kebocoran data. Gunakan :attribute lain.',
    ],
    'prohibited' => ':Attribute tidak boleh diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_unless' => ':Attribute wajib diisi kecuali :other bernilai :values.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah digunakan.',
    'uploaded' => ':Attribute gagal diunggah. Periksa ukuran berkas.',

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password' => 'kata sandi saat ini',
        'code' => 'kode',
        'type' => 'tipe',
        'parent_id' => 'organisasi induk',
        'title' => 'judul',
        'description' => 'deskripsi',
        'notes' => 'catatan',
        'status' => 'status',
    ],
];

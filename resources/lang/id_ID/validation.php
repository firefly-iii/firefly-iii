<?php
/**
 * validation.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

return [
    'iban'                           => 'Ini bukan IBAN yang valid.',
    'unique_account_number_for_user' => 'Sepertinya nomor rekening ini sudah digunakan.',
    'deleted_user'                   => 'Kerena kendala keamanan, anda tidak bisa mendaftar menggunkan alamat email ini.',
    'rule_trigger_value'             => 'Nilai ini tidak validi untuk trigger yang dipilih.',
    'rule_action_value'              => 'Nilai ini tidak valid untuk tindakan yang dipilih.',
    'file_already_attached'          => 'Upload file ";name" sudah terpasang pada objek ini.',
    'file_attached'                  => 'File yang diupload dengan sukses ":name.',
    'file_invalid_mime'              => 'File ":name" adalah tipe ":mime" yang tidak diterima sebagai upload baru.',
    'file_too_large'                 => 'File "; name" terlalu besar.',
    'belongs_to_user'                => 'Nilai dari :attribute tidak diketahui',
    'accepted'                       => 'Atribut: harus diterima.',
    'bic'                            => 'Ini bukan BIC yang valid.',
    'more'                           => ':atribut harus lebih besar dari nol.',
    'active_url'                     => ':atribut bukan URL yang valid.',
    'after'                          => ':atribut harus tanggal setelah :tanggal.',
    'alpha'                          => ':atribut hanya boleh berisi huruf.',
    'alpha_dash'                     => ':atribut hanya boleh berisi huruf, angka dan tanda hubung.',
    'alpha_num'                      => ':Atribut hanya boleh berisi huruf dan angka.',
    'array'                          => ':atribut harus berupa array.',
    'unique_for_user'                => 'Sudah ada entri dengan :atribut ini.',
    'before'                         => ':atribut harus tanggal sebelum :tanggal.',
    'unique_object_for_user'         => 'Nama ini sudah digunakan',
    'unique_account_for_user'        => 'Nama akun ini sudah digunakan',
    'between.numeric'                => ':Atribut harus antara :min dan :maks.',
    'between.file'                   => ':Atribut harus antara :min dan :maks kilobyte.',
    'between.string'                 => ':Atribut harus antara :min dan :maks karakter.',
    'between.array'                  => ':Atribut harus antara :min dan :maks item.',
    'boolean'                        => 'Bidang :atribut harus benar atau salah.',
    'confirmed'                      => 'Konfirmasi :atribut tidak cocok.',
    'date'                           => ':atribut bukan tanggal yang valid.',
    'date_format'                    => ':atribut tidak cocok dengan the format :format.',
    'different'                      => ':Atribut dan :other harus berbeda.',
    'digits'                         => ':Atribut harus angka :digit.',
    'digits_between'                 => ':Atribut harus antara :min dan :max angka.',
    'email'                          => ':Atribut harus alamat email yang valid.',
    'filled'                         => 'Bidang :atribut diperlukan.',
    'exists'                         => ':Atribut yang dipilih tidak valid.',
    'image'                          => ':Atribut harus gambar.',
    'in'                             => ':Atribut yang dipilih tidak valid.',
    'integer'                        => ':Atribut harus bilangan bulat.',
    'ip'                             => ':Atribut harus alamat IP yang valid.',
    'json'                           => ':Atribut harus string JSON yang valid.',
    'max.numeric'                    => ':Atribut tidak boleh lebih besar dari :max.',
    'max.file'                       => ':Atribut tidak boleh lebih besar dari kilobyte :max.',
    'max.string'                     => ':Atribut tidak boleh lebih besar dari karakter :max.',
    'max.array'                      => ':Atribut tidak boleh memiliki lebih dari item :max.',
    'mimes'                          => ':Atribut harus jenis file: :values.',
    'min.numeric'                    => ':Atribut harus sedikitnya :min.',
    'min.file'                       => 'Atribut harus minimal kilobyte :min.',
    'min.string'                     => ':Atribut harus minimal karakter :min.',
    'min.array'                      => ':Atribut harus minimal item :min.',
    'not_in'                         => ':Atribut yang dipilih tidak valid.',
    'numeric'                        => ':Atribut harus angka.',
    'regex'                          => 'Format :atribut tidak valid.',
    'required'                       => 'Bidang :atribut diperlukan.',
    'required_if'                    => 'Bidang :atribut diperlukan ketika :other adalah :value.',
    'required_unless'                => 'Bidang :atribut diperlukan minimal :other adalah dalam :values.',
    'required_with'                  => 'Bidang :atribut diperlukan ketika :values terdapat nilai.',
    'required_with_all'              => 'Bidang :atribut diperlukan ketika :values ada.',
    'required_without'               => 'Bidang :atribut diperlukan ketika :values tidak ada.',
    'required_without_all'           => 'Bidang :atribut diperlukan ketika tidak ada satupun :values ada.',
    'same'                           => ':Atribut dan :other harus cocok.',
    'size.numeric'                   => ':Atribut harus :size.',
    'size.file'                      => ':Atribut harus kilobyte :size.',
    'size.string'                    => ':Atribut harus karakter :size.',
    'size.array'                     => ':Atribut harus berisi item :size.',
    'unique'                         => ':Atribut sudah diambil.',
    'string'                         => ':Atribut harus sebuah string.',
    'url'                            => 'Format atribut tidak valid.',
    'timezone'                       => ':Atribut harus zona yang valid.',
    '2fa_code'                       => 'Bidang :atribut tidak valid.',
    'dimensions'                     => ':Atribut memiliki dimensi gambar yang tidak valid.',
    'distinct'                       => 'Bidang :atribut memiliki nilai duplikat.',
    'file'                           => ':Atribut harus berupa file.',
    'in_array'                       => 'Bidang :atribut tidak ada in :other.',
    'present'                        => 'Bidang :atribut harus ada.',
    'amount_zero'                    => 'Jumlah total tidak boleh nol',
    'secure_password'                => 'Ini bukan kata sandi yang aman. Silahkan coba lagi. Untuk informasi lebih lanjut, kunjungi https://goo.gl/NCh2tN',
];

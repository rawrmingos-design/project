import React from 'react';
import ApiBase from '@theme/ApiBase';

/**
 * Registrasi komponen global untuk MDX.
 *
 * `ApiBase` dipakai di banyak halaman untuk menampilkan base URL API dari satu tempat.
 * Didaftarkan di sini supaya setiap halaman bisa memakainya langsung tanpa baris `import`,
 * dan supaya pemakaian di dalam tabel tetap seragam.
 */
export default {
  ApiBase,
};

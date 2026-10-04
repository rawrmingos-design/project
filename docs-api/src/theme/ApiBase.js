import React from 'react';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';

/**
 * Menampilkan base URL API dari satu tempat saja.
 *
 * Nilainya datang dari `customFields.apiBaseUrl` (docusaurus.config.js) yang diisi
 * build arg `DOCS_API_BASE_URL`. Tujuannya: host API tidak pernah ditulis ulang di
 * dalam dokumen, jadi tidak ada halaman yang bisa "basi" saat environment berubah.
 */
export default function ApiBase() {
  const {siteConfig} = useDocusaurusContext();

  return <code>{siteConfig.customFields.apiBaseUrl}</code>;
}

// @ts-check
// Konfigurasi dokumentasi API H2H (reseller).
//
// Prinsip (lihat plan §0): docs-only, datar, seragam.
// - `routeBasePath: '/'` -> domain docs langsung menampilkan isi, bukan landing marketing.
// - `blog: false`        -> blog cuma nambah menu & noise.
// - navbar minimal       -> logo + judul + search. Tanpa toggle versi/blog.
// - nol komponen React kustom; tema bawaan sudah rapi.

import {themes as prismThemes} from 'prism-react-renderer';

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Dokumentasi API',
  tagline: 'Integrasi H2H untuk reseller',

  // Diisi dari build arg; jatuh ke domain prod kalau tidak diset.
  url: process.env.DOCS_SITE_URL || 'https://docs.jasakoding.web.id',
  baseUrl: '/',
  favicon: 'img/favicon.ico',

  // Satu-satunya tempat host API ditulis. Semua halaman memakainya lewat
  // komponen <ApiBase />, jadi tidak ada contoh yang bisa basi.
  customFields: {
    apiBaseUrl: process.env.DOCS_API_BASE_URL || 'https://istanatopup.com/api/v1',
  },

  future: {
    v4: true,
  },

  // Konten berbahasa Indonesia; ini juga menentukan atribut lang pada <html>.
  i18n: {
    defaultLocale: 'id',
    locales: ['id'],
  },

  // Link menuju halaman yang tidak ada harus MENGGAGAL kan build, bukan diam-diam
  // jadi 404 di produksi. CI yang gagal jauh lebih murah daripada integrator
  // yang tersesat.
  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'throw',
    },
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          // Docs-only: isi disajikan di akar domain.
          routeBasePath: '/',
          sidebarPath: './sidebars.js',
          // Tanpa "edit this page": sumbernya repo privat, link akan mati bagi pembaca.
          editUrl: undefined,
          breadcrumbs: true,
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
        // Tanpa sitemap: seluruh situs di balik login, tidak ada yang boleh terindeks.
        sitemap: false,
      }),
    ],
  ],

  themes: [
    [
      require.resolve('@easyops-cn/docusaurus-search-local'),
      /** @type {import('@easyops-cn/docusaurus-search-local').PluginOptions} */
      ({
        hashed: true,
        // 'en' dipakai untuk tokenisasi/stemming teks Latin; kontennya sendiri
        // berbahasa Indonesia (lunr tidak punya stemmer bahasa Indonesia).
        language: ['en'],
        indexDocs: true,
        indexBlog: false,
        indexPages: false,
        docsRouteBasePath: '/',
        highlightSearchTermsOnTargetPage: true,
        searchResultLimits: 8,
        searchBarShortcutHint: true,
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      // Navbar minimal: tanpa item menu. Search ditambahkan otomatis oleh
      // plugin search lokal; sidebar di dokumen adalah navigasi utamanya.
      navbar: {
        title: 'Dokumentasi API',
        logo: {
          alt: 'Logo',
          src: 'img/logo.svg',
        },
        items: [],
        hideOnScroll: false,
      },
      // Tanpa footer: sidebar + search sudah cukup, footer cuma menambah tinggi
      // halaman tanpa informasi baru.
      footer: undefined,
      docs: {
        sidebar: {
          hideable: true,
          autoCollapseCategories: true,
        },
      },
      colorMode: {
        respectPrefersColorScheme: true,
      },
      prism: {
        theme: prismThemes.github,
        darkTheme: prismThemes.dracula,
        additionalLanguages: ['bash', 'json', 'php', 'http'],
      },
      tableOfContents: {
        minHeadingLevel: 2,
        maxHeadingLevel: 3,
      },
    }),
};

export default config;

# 📚 Learning Resources & References

Curated resources to help you during the migration.

---

## 🎓 Official Documentation

### Laravel
- **Laravel 12 Docs**: https://laravel.com/docs/12.x
- **Laravel Upgrade Guide**: https://laravel.com/docs/12.x/upgrade
- **Laravel API Reference**: https://laravel.com/api/12.x/

### Filament
- **Filament 3 Docs**: https://filamentphp.com/docs/3.x
- **Filament Panels**: https://filamentphp.com/docs/3.x/panels
- **Filament Forms**: https://filamentphp.com/docs/3.x/forms
- **Filament Tables**: https://filamentphp.com/docs/3.x/tables
- **Filament Plugins**: https://filamentphp.com/plugins

### Inertia.js
- **Inertia Docs**: https://inertiajs.com
- **Inertia React Adapter**: https://inertiajs.com/client-side-setup#react
- **Server-side Setup**: https://inertiajs.com/server-side-setup#laravel

### React
- **React Official Docs**: https://react.dev
- **React Hooks Reference**: https://react.dev/reference/react
- **React API Reference**: https://react.dev/reference/react-dom

### Vite
- **Vite Guide**: https://vitejs.dev/guide/
- **Laravel Vite Plugin**: https://laravel.com/docs/12.x/vite

### Tailwind CSS
- **Tailwind Docs**: https://tailwindcss.com/docs
- **Tailwind UI Components**: https://tailwindui.com

### shadcn/ui
- **shadcn/ui Docs**: https://ui.shadcn.com
- **Components**: https://ui.shadcn.com/docs/components

---

## 📺 Video Tutorials

### Filament
- **Filament Basics** (Laracasts): https://laracasts.com/series/build-modern-laravel-apps-using-filament
- **Filament From Scratch** (YouTube): Search "Filament PHP tutorial"
- **Filament Tips & Tricks** (YouTube): Filament Daily channel

### Inertia + React
- **Inertia.js Course** (Laracasts): https://laracasts.com/series/build-modern-laravel-apps-using-inertia-js
- **Laravel + Inertia + React** (YouTube): Search "Laravel Inertia React tutorial 2024"
- **React Crash Course** (YouTube): Traversy Media

### Laravel 12
- **What's New in Laravel 12**: https://laravel-news.com/laravel-12
- **Laravel 12 Features** (YouTube): Laravel official channel

---

## 📖 Articles & Blog Posts

### Migration Guides
- **Laravel Upgrade Checklist**: https://laravel-upgrade.com
- **Filament Migration Guide**: https://filamentphp.com/docs/3.x/upgrade-guide
- **Blade to Inertia Migration**: Search on Medium, Dev.to

### Best Practices
- **Laravel Best Practices**: https://github.com/alexeymezenin/laravel-best-practices
- **React Best Practices**: https://react.dev/learn/thinking-in-react
- **TypeScript Best Practices**: https://typescript-eslint.io/

---

## 🛠️ Tools & Utilities

### Development Tools

**IDE Extensions (VS Code):**
- Laravel Extension Pack
- PHP Intelephense
- Laravel Blade Snippets
- ES7+ React/Redux/React-Native snippets
- Tailwind CSS IntelliSense
- ESLint
- Prettier
- GitLens

**Browser Extensions:**
- React Developer Tools
- Vue.js devtools (for Inertia debugging)
- Laravel Debugbar

**CLI Tools:**
```bash
# Laravel
composer global require laravel/installer

# Node version management
nvm (Node Version Manager)

# Code quality
composer require --dev laravel/pint
composer require --dev phpstan/phpstan
```

---

## 📦 Useful Packages

### Laravel Packages

**Development:**
```bash
# Debug bar
composer require barryvdh/laravel-debugbar --dev

# IDE Helper
composer require --dev barryvdh/laravel-ide-helper

# Testing
composer require --dev pestphp/pest
composer require --dev pestphp/pest-plugin-laravel
```

**Production:**
```bash
# Error tracking
composer require sentry/sentry-laravel

# Performance monitoring
composer require spatie/laravel-ray

# Settings management
composer require spatie/laravel-settings
```

### Filament Packages

```bash
# Additional components
composer require filament/spatie-laravel-media-library-plugin
composer require filament/spatie-laravel-settings-plugin
composer require filament/spatie-laravel-tags-plugin

# Charts
composer require filament/widgets

# Import/Export
composer require pxlrbt/filament-excel
```

### NPM Packages

**UI Libraries:**
```bash
# Icons
npm install lucide-react

# Utilities
npm install clsx tailwind-merge
npm install date-fns

# Forms
npm install react-hook-form
npm install zod

# Data fetching (optional)
npm install @tanstack/react-query
```

**Development:**
```bash
# TypeScript
npm install -D typescript @types/react @types/react-dom @types/node

# Linting
npm install -D eslint @typescript-eslint/eslint-plugin @typescript-eslint/parser
npm install -D prettier prettier-plugin-tailwindcss

# Testing
npm install -D @playwright/test
npm install -D vitest @testing-library/react @testing-library/jest-dom
```

---

## 🎨 Design Resources

### UI Inspiration
- **Dribbble**: https://dribbble.com (search "admin dashboard", "game top-up")
- **Behance**: https://behance.net
- **UI8**: https://ui8.net

### Component Libraries
- **shadcn/ui**: https://ui.shadcn.com (Recommended)
- **Headless UI**: https://headlessui.com
- **Radix UI**: https://radix-ui.com
- **daisyUI**: https://daisyui.com

### Icons
- **Lucide Icons**: https://lucide.dev
- **Heroicons**: https://heroicons.com
- **Font Awesome**: https://fontawesome.com

### Colors & Themes
- **Tailwind Color Generator**: https://uicolors.app
- **Coolors**: https://coolors.co
- **Realtime Colors**: https://realtimecolors.com

---

## 👥 Community & Support

### Discord Servers
- **Laravel Discord**: https://discord.gg/laravel
- **Filament Discord**: https://filamentphp.com/discord
- **Inertia Discord**: https://discord.gg/inertiajs
- **React Discord**: https://discord.gg/react

### Forums & Q&A
- **Laravel Forum**: https://laracasts.com/discuss
- **Filament Discussions**: https://github.com/filamentphp/filament/discussions
- **Stack Overflow**: https://stackoverflow.com/questions/tagged/laravel
- **Reddit**: r/laravel, r/reactjs

### Newsletter & Blogs
- **Laravel News**: https://laravel-news.com
- **Filament Blog**: https://filamentphp.com/blog
- **Laravel Daily**: https://laraveldaily.com

---

## 📚 Books

### Laravel
- **Laravel: Up & Running** by Matt Stauffer
- **Laravel Design Patterns and Best Practices**
- **Test-Driven Laravel** (free): https://testdrivenlaravel.com

### React
- **Learning React** by Alex Banks & Eve Porcello
- **React Cookbook** by David Griffiths
- **Fluent React** by Tejas Kumar

---

## 🧪 Testing Resources

### Testing Guides
- **Laravel Testing**: https://laravel.com/docs/12.x/testing
- **Pest PHP**: https://pestphp.com
- **React Testing Library**: https://testing-library.com/react
- **Playwright**: https://playwright.dev

### Testing Tools
```bash
# PHP Testing
composer require --dev pestphp/pest
composer require --dev pestphp/pest-plugin-laravel

# E2E Testing
npm install -D @playwright/test
npm install -D cypress

# Component Testing
npm install -D vitest @testing-library/react
```

---

## 🚀 Deployment Resources

### Hosting Platforms
- **Laravel Forge**: https://forge.laravel.com (Recommended)
- **Laravel Vapor**: https://vapor.laravel.com (Serverless)
- **Ploi**: https://ploi.io
- **DigitalOcean App Platform**: https://digitalocean.com
- **Vercel** (Frontend only): https://vercel.com

### CI/CD
- **GitHub Actions**: https://github.com/features/actions
- **GitLab CI**: https://docs.gitlab.com/ee/ci/
- **CircleCI**: https://circleci.com

### Monitoring
- **Sentry**: https://sentry.io (Error tracking)
- **New Relic**: https://newrelic.com
- **Laravel Telescope**: https://laravel.com/docs/12.x/telescope
- **Laravel Pulse**: https://laravel.com/docs/12.x/pulse

---

## 💡 Cheat Sheets

### Laravel Cheat Sheet
```bash
# Artisan commands
php artisan list
php artisan make:model Product -mcr
php artisan migrate
php artisan db:seed
php artisan cache:clear
php artisan config:clear
php artisan route:list
php artisan tinker

# Useful one-liners
php artisan optimize:clear  # Clear all caches
php artisan serve           # Start dev server
php artisan queue:work      # Process queue jobs
```

### Filament Cheat Sheet
```bash
# Generate resources
php artisan make:filament-resource Product
php artisan make:filament-page Settings
php artisan make:filament-widget StatsOverview

# Useful commands
php artisan filament:user           # Create admin user
php artisan filament:upgrade        # Upgrade Filament
```

### Inertia Cheat Sheet
```php
// Controller
use Inertia\Inertia;

return Inertia::render('Products/Index', [
    'products' => Product::all(),
]);

// React Component
import { usePage, Link, useForm } from '@inertiajs/react';

const { products } = usePage().props;
const { data, setData, post, processing, errors } = useForm({...});
```

### React Hooks Cheat Sheet
```typescript
import { useState, useEffect, useMemo, useCallback } from 'react';

// State
const [count, setCount] = useState(0);

// Effect
useEffect(() => {
    // Side effect
}, [dependencies]);

// Memoization
const expensive = useMemo(() => compute(), [deps]);

// Callback
const handler = useCallback(() => {}, [deps]);
```

---

## 🔍 Search Tips

When searching for solutions:

**Good search queries:**
- "Laravel 12 Filament 3 tutorial"
- "Inertia React form validation Laravel"
- "Filament custom action button"
- "React TypeScript best practices 2024"

**Include version numbers** for accurate results

**Search on:**
- Google (obvious but effective)
- Stack Overflow (specific problems)
- GitHub Issues (package-specific problems)
- Laravel.io (Laravel questions)
- Dev.to (tutorials)

---

## 📊 Performance Resources

### Optimization Guides
- **Laravel Performance**: https://laravel.com/docs/12.x/deployment#optimization
- **React Performance**: https://react.dev/learn/render-and-commit
- **Vite Performance**: https://vitejs.dev/guide/performance

### Analysis Tools
- **Laravel Debugbar**: https://github.com/barryvdh/laravel-debugbar
- **Lighthouse**: https://pagespeed.web.dev
- **Bundle Analyzer**: `npm install -D vite-bundle-visualizer`

---

## 🎯 Project-Specific Resources

### Game Top-Up Specific
- **Payment Gateway Docs:**
  - Duitku: https://docs.duitku.com
  - TokoPay: https://tokopay.id/documentation
  - TriPay: https://tripay.co.id/developer
  - Ipay88: https://www.ipay88.com.my/developers/

- **Game Provider APIs:**
  - DigiFlazz: https://digiflazz.com/api-documentation
  - (Add others as needed)

---

## 🆘 Emergency Contacts

**When really stuck:**
1. Project documentation (this folder)
2. AI assistant with proper prompts
3. Community Discord servers
4. Stack Overflow
5. Hire expert (Upwork, Codementor)

---

## ✅ Recommended Learning Path

### Week 1 (Filament Focus):
1. Watch Filament basics course (Laracasts)
2. Read Filament docs (Forms, Tables, Resources)
3. Build first resource following docs
4. Join Filament Discord for questions

### Week 4 (Inertia Focus):
1. Watch Inertia course (Laracasts)
2. Read Inertia docs fully
3. Read React docs (hooks, components)
4. Setup and test simple Inertia page

### Week 9 (Polish):
1. Performance optimization guides
2. Testing best practices
3. Deployment guides
4. Security checklist

---

**Bookmark this page! You'll reference it throughout the migration. 🎯**

**Last Updated:** 2025-10-03

# 🔧 Troubleshooting Guide

Common issues and solutions during migration.

---

## 🚨 Phase 1: Filament Issues

### Installation Problems

#### Error: "Your requirements could not be resolved"
```
Problem: Filament 3 requires PHP 8.1+
Solution: 
1. Verify PHP version: php -v
2. Update PHP to 8.2+
3. Update composer.json: "php": "^8.2"
4. Run: composer update
```

#### Error: "Class 'Filament\Panel' not found"
```
Solution:
composer dump-autoload
php artisan optimize:clear
```

#### Can't access /admin route
```
Solutions:
1. Check if route registered:
   php artisan route:list | grep admin
   
2. Clear all caches:
   php artisan optimize:clear
   
3. Check config/filament.php exists:
   php artisan vendor:publish --tag=filament-config
   
4. Verify middleware in web.php
```

---

### Authentication Issues

#### Admin user can't login
```
Checklist:
1. Verify user exists in database
2. Check email is correct
3. Try password reset
4. Check User model implements FilamentUser interface:

// app/Models/User.php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin'); // Or your logic
    }
}
```

#### "Unauthenticated" error in admin panel
```
Solution:
1. Check session driver in .env:
   SESSION_DRIVER=database (or file)
   
2. Run migrations for sessions:
   php artisan session:table
   php artisan migrate
   
3. Clear sessions:
   php artisan session:clear
```

---

### Resource Issues

#### Relationship not showing in Select
```
Problem: Select dropdown empty for relationship

Solution:
// Check relationship exists in Model
public function kategori()
{
    return $this->belongsTo(Kategori::class);
}

// In Resource
Select::make('kategori_id')
    ->relationship('kategori', 'nama')
    ->searchable()
    ->preload() // Load all options
```

#### Form validation not working
```
Solution:
// Use proper validation rules
TextInput::make('email')
    ->email()
    ->required()
    ->unique(ignoreRecord: true),

TextInput::make('harga')
    ->numeric()
    ->required()
    ->minValue(0)
```

#### Image upload not working
```
Checklist:
1. Storage link created:
   php artisan storage:link
   
2. Directory writable:
   chmod -R 775 storage/app/public
   
3. Correct disk in config/filesystems.php
   
4. FileUpload component configured:
   FileUpload::make('image')
       ->disk('public')
       ->directory('products')
       ->image()
       ->maxSize(2048)
```

---

### Performance Issues

#### Admin panel slow to load
```
Solutions:
1. Add database indexes:
   Schema::table('produks', function (Blueprint $table) {
       $table->index('kategori_id');
       $table->index('status');
   });
   
2. Use lazy loading for relationships:
   Select::make('kategori_id')
       ->relationship('kategori', 'nama')
       ->lazy()
       
3. Paginate table results:
   protected static int $defaultPaginationPageOption = 25;
   
4. Cache expensive queries:
   Cache::remember('categories', 3600, fn() => Kategori::all());
```

---

## 🚨 Phase 2: Inertia Installation Issues

### Vite Build Errors

#### Error: "vite: command not found"
```
Solution:
npm install
npm install -D vite
```

#### Error: "Cannot find module '@vitejs/plugin-react'"
```
Solution:
npm install -D @vitejs/plugin-react
```

#### Vite config not working
```
Check vite.config.js:

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
```

---

### Inertia Setup Issues

#### Blank page, no errors
```
Checklist:
1. Check browser console for errors
2. Verify app.blade.php has @inertia directive:
   
   <!DOCTYPE html>
   <html>
   <head>
       <meta charset="utf-8">
       <meta name="viewport" content="width=device-width, initial-scale=1">
       <title>{{ config('app.name') }}</title>
       @vite(['resources/css/app.css', 'resources/js/app.tsx'])
       @inertiaHead
   </head>
   <body>
       @inertia
   </body>
   </html>

3. Check resources/js/app.tsx exists:
   
   import { createInertiaApp } from '@inertiajs/react'
   import { createRoot } from 'react-dom/client'
   
   createInertiaApp({
       resolve: (name) => {
           const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true })
           return pages[`./Pages/${name}.tsx`]
       },
       setup({ el, App, props }) {
           createRoot(el).render(<App {...props} />)
       },
   })
```

#### Hot reload not working
```
Solutions:
1. Check vite dev server running:
   npm run dev
   
2. Verify @vite directive in blade template
   
3. Clear browser cache
   
4. Check firewall not blocking port 5173
```

---

### React/TypeScript Errors

#### Error: "Cannot find module './Pages/Home'"
```
Solution:
1. Check file exists: resources/js/Pages/Home.tsx
2. Check capitalization (case-sensitive)
3. Ensure import.meta.glob pattern matches
```

#### TypeScript errors in IDE
```
Solution:
1. Create/verify tsconfig.json:

{
  "compilerOptions": {
    "target": "ESNext",
    "lib": ["DOM", "DOM.Iterable", "ESNext"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["resources/js/*"]
    }
  },
  "include": ["resources/js/**/*.ts", "resources/js/**/*.tsx"],
  "exclude": ["node_modules"]
}

2. Install type definitions:
   npm install -D @types/react @types/react-dom @types/node
   
3. Restart TypeScript server in VS Code:
   Ctrl+Shift+P → "TypeScript: Restart TS Server"
```

---

### Styling Issues

#### Tailwind classes not working
```
Checklist:
1. Tailwind installed:
   npm install -D tailwindcss postcss autoprefixer
   
2. Config exists (tailwind.config.js):
   
   export default {
     content: [
       './resources/**/*.blade.php',
       './resources/**/*.tsx',
       './resources/**/*.ts',
     ],
     theme: {
       extend: {},
     },
     plugins: [],
   }
   
3. CSS imported in app.tsx:
   import '../css/app.css'
   
4. app.css has directives:
   @tailwind base;
   @tailwind components;
   @tailwind utilities;
   
5. Build running:
   npm run dev
```

---

## 🚨 Phase 2-3: Inertia Runtime Issues

### Form Submission Issues

#### Form not submitting
```
Debug steps:
1. Check network tab for request
2. Verify CSRF token included (Inertia handles automatically)
3. Check controller method exists and route correct

Example:
// React
import { useForm } from '@inertiajs/react';

const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
});

const submit = (e) => {
    e.preventDefault();
    post('/login'); // Check route exists
};
```

#### Validation errors not showing
```
Solution:
// Inertia automatically passes errors
const { errors } = usePage().props;

// Display errors
{errors.email && (
    <div className="text-red-500">{errors.email}</div>
)}

// Or with useForm
const { data, setData, post, errors } = useForm({...});
```

#### Form data not updating
```
Problem: Input value not changing

Solution:
// Controlled input
<input
    type="text"
    value={data.name}
    onChange={(e) => setData('name', e.target.value)}
/>

// Not this (uncontrolled):
<input type="text" defaultValue={data.name} />
```

---

### Navigation Issues

#### Links cause full page reload
```
Problem: Using <a> tag instead of Inertia Link

Solution:
import { Link } from '@inertiajs/react';

// Use this
<Link href="/products">Products</Link>

// Not this
<a href="/products">Products</a>
```

#### Back button doesn't work properly
```
Solution:
Inertia handles history automatically. If issues:

1. Check browser console for errors
2. Verify using Inertia navigation (Link, router.visit)
3. Clear browser cache
```

---

### Data Loading Issues

#### Props undefined in component
```
Debug:
1. Check controller returns Inertia response:
   return Inertia::render('Products/Index', [
       'products' => Product::all(),
   ]);

2. Access in component:
   import { usePage } from '@inertiajs/react';
   
   const { products } = usePage().props;
   
   // Or via page props
   const ProductsPage = ({ products }) => {
       // products available here
   };
```

#### Shared data not available
```
Setup shared data in HandleInertiaRequests middleware:

public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
        ],
        'flash' => [
            'message' => session('message'),
        ],
    ]);
}

// Access in any component
const { auth, flash } = usePage().props;
```

---

### Performance Issues

#### Initial page load slow
```
Solutions:
1. Analyze bundle size:
   npm run build
   npx vite-bundle-visualizer
   
2. Code split heavy components:
   const HeavyComponent = lazy(() => import('./HeavyComponent'));
   
3. Optimize images:
   - Use WebP format
   - Lazy load images
   - Compress images
   
4. Enable caching:
   - Laravel route caching
   - Asset versioning
```

#### Subsequent navigations slow
```
Solutions:
1. Use Inertia prefetching:
   <Link href="/products" prefetch>Products</Link>
   
2. Optimize database queries:
   - Add indexes
   - Eager load relationships
   - Use pagination
   
3. Implement caching:
   Cache::remember('products', 3600, fn() => Product::all());
```

---

## 🚨 Production Deployment Issues

### Build Errors

#### "npm run build" fails
```
Solutions:
1. Clear node_modules:
   rm -rf node_modules package-lock.json
   npm install
   
2. Check memory:
   NODE_OPTIONS=--max-old-space-size=4096 npm run build
   
3. Fix TypeScript errors:
   npm run build -- --mode development (see errors)
```

#### Assets not loading in production
```
Checklist:
1. Build completed:
   npm run build
   
2. Manifest exists:
   ls public/build/manifest.json
   
3. Correct asset URL in .env:
   ASSET_URL=https://yourdomain.com
   
4. Vite directive in blade:
   @vite(['resources/css/app.css', 'resources/js/app.tsx'])
```

---

### Server Issues

#### 500 Internal Server Error
```
Debug steps:
1. Check Laravel logs:
   tail -f storage/logs/laravel.log
   
2. Enable debug mode (staging only):
   APP_DEBUG=true
   
3. Check file permissions:
   chmod -R 775 storage bootstrap/cache
   
4. Clear all caches:
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
```

#### CORS errors
```
Solution (if API separate):
1. Install cors package:
   composer require fruitcake/laravel-cors
   
2. Configure config/cors.php:
   'paths' => ['api/*'],
   'allowed_origins' => [env('FRONTEND_URL')],
   
3. Add middleware to api routes

For Inertia (same domain): No CORS needed
```

---

### Database Issues

#### Migration fails in production
```
Solutions:
1. Check database connection:
   php artisan tinker
   DB::connection()->getPdo();
   
2. Run migrations with force:
   php artisan migrate --force
   
3. Check migration table exists:
   php artisan migrate:install
   
4. Rollback and retry:
   php artisan migrate:rollback
   php artisan migrate
```

---

## 🆘 Emergency Rollback

### Quick Rollback Procedure

```bash
# 1. Switch to previous tag
git checkout pre-phase-X

# 2. Restore database backup
mysql -u user -p database < backup.sql

# 3. Clear caches
php artisan optimize:clear

# 4. Rebuild assets (if needed)
npm install
npm run build

# 5. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

---

## 📞 Getting More Help

### When Stuck:

1. **Check logs first:**
   - Laravel: `storage/logs/laravel.log`
   - Browser console
   - Network tab
   - Server error logs

2. **Use AI assistant:**
   - Provide error message
   - Describe what you tried
   - Share relevant code

3. **Community resources:**
   - Filament Discord
   - Laravel Discord
   - Stack Overflow
   - GitHub Issues

4. **Prompt template:**
```
I'm encountering an error in [Phase X, Task Y]:

Error message:
[paste full error]

What I was doing:
[describe steps]

What I've tried:
1. [attempt 1]
2. [attempt 2]

Relevant code:
[paste code if applicable]

Please help debug this issue.
```

---

**Remember: Most issues have simple solutions. Stay calm, check logs, and ask for help! 🚀**

**Last Updated:** 2025-10-03

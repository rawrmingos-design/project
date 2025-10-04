# 🤖 AI Prompt Templates for Migration

Use these prompts with your AI assistant to get precise, context-aware help for each phase.

---

## 📖 How to Use These Prompts

1. **Copy the prompt ID** (e.g., `PHASE1_WEEK1_DAY1-2_INSTALLATION`)
2. **Paste to AI**: "Use prompt template: PHASE1_WEEK1_DAY1-2_INSTALLATION"
3. **AI will execute** the specific task with full context

---

## PHASE 1: FILAMENT ADMIN PANEL

### `PHASE1_WEEK1_DAY1-2_INSTALLATION`
```
I'm migrating my Laravel 8 game top-up application to Laravel 12 + Filament + Inertia.

Current status: Laravel 12 upgrade in progress/completed.
Current step: Phase 1, Week 1, Day 1-2 - Filament Installation

Tasks:
1. Install Filament 4.x package compatible with Laravel 12
2. Run Filament panel installation
3. Create initial admin user
4. Verify Filament panel is accessible
5. Provide commit message

Context:
- Project path: d:\Backend-game-topup\web\project
- 14 admin controllers to migrate
- Existing admin route prefix: /admin (cause we install user filament for name admin)

Please help me install and verify Filament works correctly.
```

### `PHASE1_WEEK1_DAY3-4_CONFIGURATION`
```
Phase 1, Week 1, Day 3-4 - Filament Configuration

Filament is now installed. Help me configure:
1. Admin panel path (check existing routes to avoid conflicts)
2. Authentication setup using existing User model
3. Add role-based access (middleware: 'auth', 'check.role')
4. Configure navigation groups
5. Customize theme to match brand
6. Test panel configuration

Context:
- Existing middleware: 'check.role' for admin
- May need to create admin role or use existing logic
```

### `PHASE1_WEEK1_DAY5-7_FIRST_RESOURCES`
```
Phase 1, Week 1, Day 5-7 - First Filament Resources

Create first 3 simple resources:
1. KategoriResource (model: Kategori)
2. LayananResource (model: Layanan)  
3. MethodResource (model: Method - payment methods)

For each resource:
- Generate using artisan command
- Configure form fields based on model
- Configure table columns
- Add basic filters
- Test CRUD operations
- Organize in navigation groups

Please start with KategoriResource and show me the pattern.
```

### `PHASE1_WEEK2_DAY8-10_PRODUCT_MANAGEMENT`
```
Phase 1, Week 2, Day 8-10 - Product Management Resource

Create ProdukResource with advanced features:
- Form fields for product (nama, kode, harga, status, deskripsi, etc)
- Relationships: kategori_id, layanan_id
- Image upload handling
- Bulk actions (activate/deactivate products)
- Filters (status, kategori, provider)
- Search by nama, kode
- Custom actions for syncing from providers (BangJeff, Topupedia)

This is a complex resource, please help implement it step by step.
```

### `PHASE1_WEEK2_DAY11-12_ORDER_MANAGEMENT`
```
Phase 1, Week 2, Day 11-12 - Order Management Resource

Create PembelianResource for orders:
- Mostly read-only views (admins view/manage, not create)
- Table columns: invoice, user.name, product, status, amount, created_at
- Status badges with colors
- Filters: status, date range, payment method
- Actions: view detail, process order, cancel, refund
- Bulk export to Excel
- Custom detail page showing full order info
- Relationships with User, Produk, Method

Focus on viewing and managing existing orders.
```

### `PHASE1_WEEK2_DAY13-14_USER_DEPOSIT`
```
Phase 1, Week 2, Day 13-14 - User & Deposit Resources

Create 2 resources:

1. UserResource:
   - Form fields: name, email, phone, balance, role
   - Password handling (hashed)
   - Actions: ban user, adjust balance
   - Relation managers: orders, deposits
   
2. DepositResource:
   - View deposit requests
   - Approve/reject actions
   - Manual deposit entry form
   - Filters by status, date

Help implement both with proper relationships.
```

### `PHASE1_WEEK3_DAY15-16_SETTINGS`
```
Phase 1, Week 3, Day 15-16 - Settings & Configuration

Create resources:
1. Settings page (website config, payment API keys, etc)
2. VoucherResource
3. WhitelistedIPResource
4. BeritaResource (news/announcements with rich text)

Consider using spatie/laravel-settings for settings page.
For Berita, need rich text editor and image upload.
```

### `PHASE1_WEEK3_DAY17-18_WIDGETS`
```
Phase 1, Week 3, Day 17-18 - Dashboard Widgets

Create dashboard widgets:
1. Stats Overview (total orders, revenue today, active users, pending deposits)
2. Orders Chart (last 7 days trend)
3. Recent Orders table widget
4. Provider status (optional)

Use Filament widget classes. Show real-time data from Pembelian, User, Deposit models.
```

### `PHASE1_WEEK3_DAY19-20_REPORTS`
```
Phase 1, Week 3, Day 19-20 - Reports & Analytics

Create:
1. Custom reports page with date range selector
2. Revenue report
3. Product sales report  
4. Export to Excel functionality
5. RatingResource for viewing customer ratings

Help implement reporting features using Filament custom pages.
```

### `PHASE1_WEEK3_DAY21_TESTING`
```
Phase 1, Week 3, Day 21 - Testing & Documentation

Tasks:
1. Create admin user guide (markdown)
2. List all testing scenarios for UAT
3. Security checklist (roles, permissions)
4. Performance testing approach
5. Create commit for Phase 1 completion

Generate comprehensive testing checklist for admin panel.
```

---

## PHASE 2: INERTIA PUBLIC PAGES

### `PHASE2_WEEK4_DAY22-23_INSTALLATION`
```
Phase 2, Week 4, Day 22-23 - Inertia Installation

Install Inertia.js with React for Laravel 12:
1. Server-side: inertiajs/inertia-laravel
2. Client-side: @inertiajs/react, react, react-dom
3. Vite plugin: @vitejs/plugin-react
4. Publish Inertia middleware
5. Create app.blade.php root template
6. Test basic Inertia page renders

Project already uses webpack.mix.js - need to migrate to Vite for Laravel 12.
```

### `PHASE2_WEEK4_DAY24-25_BUILD_CONFIG`
```
Phase 2, Week 4, Day 24-25 - Build System Configuration

Configure:
1. vite.config.js for React + Inertia
2. Tailwind CSS installation and setup
3. TypeScript (tsconfig.json)
4. shadcn/ui initialization
5. Test hot reload

Current project has Laravel Mix. Need complete Vite migration guide for Laravel 12.
```

### `PHASE2_WEEK4_DAY26-28_COMPONENTS`
```
Phase 2, Week 4, Day 26-28 - Core Components

Create React component library:
1. Folder structure (Components/, Layouts/, Pages/, Hooks/, Utils/)
2. GuestLayout.tsx (Navbar, Footer, main content)
3. Reusable components: Button, Card, Input, Modal, Loading
4. Test page to verify all components work

Use TypeScript and shadcn/ui. Follow modern React best practices.
```

### `PHASE2_WEEK5_DAY29-31_LANDING`
```
Phase 2, Week 5, Day 29-31 - Landing & Static Pages

Migrate Blade views to Inertia React:
1. Landing page (/) - Hero, featured products, testimonials
2. Privacy Policy
3. Terms & Conditions

For each page:
- Create .tsx page component
- Update route to return Inertia response
- Add SEO meta tags
- Ensure responsive design
- Match existing Blade design

Start with landing page.
```

### `PHASE2_WEEK5_DAY32-35_ADDITIONAL`
```
Phase 2, Week 5, Day 32-35 - Additional Public Pages

Migrate:
1. Price List page (fetch categories/products)
2. Leaderboard page
3. Calculator pages (Hitung WR, Point MW, Point Zodiac)

These pages fetch data from API. Show how to use Inertia data props properly.
```

### `PHASE2_WEEK6_DAY36-38_AUTH`
```
Phase 2, Week 6, Day 36-38 - Authentication Pages

Migrate auth pages to Inertia React:
1. Login page with form validation
2. Register page
3. Forgot password page

Use Inertia form helpers. Handle validation errors from Laravel.
Update LoginController, RegisterController to return Inertia responses.
```

### `PHASE2_WEEK6_DAY39-42_AUTH_TESTING`
```
Phase 2, Week 6, Day 39-42 - Auth Testing

Test thoroughly:
1. Login flow (success, errors, validation)
2. Registration (success, duplicate email, validation)
3. Password reset
4. Loading states
5. Mobile responsive
6. Cross-browser

Provide testing checklist and common issues to watch for.
```

### `PHASE2_WEEK7-8_DAY43-46_PRODUCTS`
```
Phase 2, Week 7-8, Day 43-46 - Product Catalog

Migrate product pages:
1. Product listing (/id/{kategori})
2. Category selector
3. Product grid with search/filter
4. Product detail/order page

Current Blade view: template.id.index
This is critical revenue page - must be thoroughly tested.
```

### `PHASE2_WEEK7-8_DAY47-49_ORDER_FLOW`
```
Phase 2, Week 7-8, Day 47-49 - Order Flow Implementation

Implement complete order flow:
1. User ID inputs (game ID, zone, server)
2. Package selection
3. Price calculation (API call)
4. Payment method selection
5. Voucher redemption
6. Order confirmation modal
7. Submit order (Inertia form)
8. Redirect to invoice

This is THE most critical feature. Help implement with proper error handling.
```

### `PHASE2_WEEK7-8_DAY50-56_TESTING`
```
Phase 2, Week 7-8, Day 50-56 - Critical Testing

Comprehensive testing needed:
1. Test all payment gateways (Duitku, TokoPay, TriPay, etc)
2. Test all game categories
3. Edge cases (invalid IDs, network errors)
4. Mobile testing (iOS Safari, Android Chrome)
5. Performance (Lighthouse audit)
6. Monitor conversion rate vs old Blade pages

Provide complete testing strategy and checklist.
```

---

## PHASE 3: INERTIA AUTH PAGES

### `PHASE3_WEEK9-10_DAY57-59_DASHBOARD`
```
Phase 3, Week 9-10, Day 57-59 - User Dashboard

Create authenticated user area:
1. AuthLayout.tsx (sidebar, user info, balance, logout)
2. Dashboard page (stats, recent orders, quick actions)

Use Inertia shared data for auth user and balance.
```

### `PHASE3_WEEK9-10_DAY60-63_PROFILE`
```
Phase 3, Week 9-10, Day 60-63 - Profile & Settings

Create:
1. Profile page (view/edit user info, change password)
2. Settings page (preferences, notifications)
3. Avatar upload (if applicable)

Handle form submission with Inertia, show validation errors.
```

### `PHASE3_WEEK11_DAY64-66_ORDERS`
```
Phase 3, Week 11, Day 64-66 - Order History

Create:
1. Order history page (table with filters, pagination)
2. Order detail page (full order info, timeline)
3. Invoice page (printable)

Fetch user's orders, display status, allow reorder.
```

### `PHASE3_WEEK11_DAY67-70_DEPOSIT`
```
Phase 3, Week 11, Day 67-70 - Deposit & Wallet

Create:
1. Deposit request page
2. Deposit history
3. Wallet/balance page
4. Deposit invoice with payment instructions

Handle deposit flow with unique codes and payment proof upload.
```

### `PHASE3_WEEK12_DAY71-73_ADDITIONAL`
```
Phase 3, Week 12, Day 71-73 - Additional Features

Migrate remaining features:
1. Gift skin page
2. Rating/review submission
3. Voucher redemption page

Complete all user-facing features in Inertia.
```

### `PHASE3_WEEK12_DAY74-77_E2E_TESTING`
```
Phase 3, Week 12, Day 74-77 - E2E Testing

Setup end-to-end testing:
1. Install Playwright or Cypress
2. Write E2E tests for critical flows:
   - Registration → Login → Order → Payment
   - Deposit flow
   - Profile update
3. Run test suite
4. Fix failing tests

Provide E2E test setup and example tests.
```

### `PHASE3_WEEK12_DAY78-80_OPTIMIZATION`
```
Phase 3, Week 12, Day 78-80 - Performance Optimization

Optimize application:
1. Bundle analysis (identify large dependencies)
2. Code splitting improvements
3. Image optimization (WebP, lazy loading)
4. API optimization (React Query, caching)
5. Lighthouse audit (target 90+ score)

Provide optimization checklist and tools.
```

### `PHASE3_WEEK12_DAY81-84_LAUNCH`
```
Phase 3, Week 12, Day 81-84 - Soft Launch & Production

Final steps:
1. Deploy to staging
2. Beta testing with users
3. Setup monitoring (Sentry, analytics)
4. Gradual rollout with feature flags
5. Monitor metrics (errors, performance, conversion)
6. Full production release
7. Remove old Blade views
8. Documentation

Provide launch checklist and monitoring strategy.
```

---

## GENERAL PROMPTS

### `HELP_DEBUG_ERROR`
```
I'm encountering an error in [Phase X, Week Y]:

Error: [paste error message]

Context:
- What I was doing: [describe]
- Expected behavior: [describe]
- Actual behavior: [describe]
- Relevant code: [paste code if needed]

Please help debug this issue.
```

### `CODE_REVIEW_REQUEST`
```
Please review this code for [feature/component]:

[paste code]

Review for:
1. Best practices (React/Laravel)
2. Performance issues
3. Security concerns
4. Accessibility
5. TypeScript types
6. Code organization

Provide specific improvement suggestions.
```

### `EXPLAIN_CONCEPT`
```
Please explain [concept] in the context of this migration:
- Why is it needed?
- How does it work?
- Best practices?
- Examples in our codebase?
- Common pitfalls?

Keep explanation practical and project-specific.
```

---

## 📌 Quick Reference

**Starting a new phase:**
```
I'm starting [Phase X]. 
Please provide an overview and checklist for this phase.
Reference: MIGRATION_ROADMAP.md and PROGRESS_TRACKER.md
```

**Stuck on a task:**
```
I'm stuck on [Phase X, Week Y, Day Z - Task Name].
Current issue: [describe]
What I've tried: [list attempts]

Please help me proceed.
```

**Need a commit message:**
```
I've completed [task description].
Files changed: [list files]
Generate a conventional commit message.
```

---

**Last Updated:** 2025-10-03
**Version:** 1.0.0

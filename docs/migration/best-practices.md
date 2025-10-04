# ✅ Best Practices & Guidelines

Essential best practices for successful migration.

---

## 🔐 Safety First

### Version Control

**DO:**
- ✅ Commit after every completed task
- ✅ Write descriptive commit messages
- ✅ Create feature branches for major changes
- ✅ Tag major milestones (`git tag v1-filament-complete`)

**DON'T:**
- ❌ Commit broken code
- ❌ Mix multiple features in one commit
- ❌ Push directly to production branch

**Commit Message Format:**
```
feat: add KategoriResource to Filament
fix: resolve validation error in order form
docs: update migration progress tracker
chore: remove unused Blade views
```

### Backups

**Before Starting Each Phase:**
```bash
# Database backup
php artisan db:backup

# Code backup
git tag pre-phase-1
git tag pre-phase-2
git tag pre-phase-3
```

**Daily:**
- Database backup (automated if possible)
- Push code to remote repository

---

## 🧪 Testing Strategy

### Test Before Moving On

**After Every Feature:**
1. Manual testing in browser
2. Test on mobile device
3. Test edge cases
4. Check browser console for errors

**Before Committing:**
```bash
# Run tests if you have them
php artisan test

# Check for PHP errors
php artisan route:list
```

### Testing Levels

**Phase 1 (Filament):**
- [ ] Can create record ✓
- [ ] Can edit record ✓
- [ ] Can delete record ✓
- [ ] Relationships work ✓
- [ ] Filters work ✓
- [ ] Bulk actions work ✓

**Phase 2-3 (Inertia):**
- [ ] Page loads without errors ✓
- [ ] Forms submit successfully ✓
- [ ] Validation shows correctly ✓
- [ ] Mobile responsive ✓
- [ ] Cross-browser compatible ✓

---

## 📝 Code Quality

### Filament Resources

**DO:**
```php
// Clear, descriptive form fields
public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('nama')
            ->required()
            ->maxLength(255)
            ->label('Nama Kategori'),
            
        Textarea::make('deskripsi')
            ->rows(3)
            ->label('Deskripsi'),
            
        Toggle::make('is_active')
            ->default(true)
            ->label('Status Aktif'),
    ]);
}
```

**DON'T:**
```php
// Unclear, no validation
public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('nama'),
        Textarea::make('deskripsi'),
        Toggle::make('is_active'),
    ]);
}
```

### React Components

**DO:**
```typescript
// TypeScript, clear props, reusable
interface ProductCardProps {
    product: Product;
    onOrder: (productId: number) => void;
}

export const ProductCard: React.FC<ProductCardProps> = ({ 
    product, 
    onOrder 
}) => {
    return (
        <Card>
            <CardHeader>
                <h3>{product.nama}</h3>
            </CardHeader>
            <CardContent>
                <p>Rp {product.harga.toLocaleString()}</p>
            </CardContent>
            <CardFooter>
                <Button onClick={() => onOrder(product.id)}>
                    Order Now
                </Button>
            </CardFooter>
        </Card>
    );
};
```

**DON'T:**
```jsx
// No types, inline styles, not reusable
export const ProductCard = (props) => {
    return (
        <div style={{border: '1px solid black'}}>
            <h3>{props.product.nama}</h3>
            <p>Rp {props.product.harga}</p>
            <button onClick={() => alert(props.product.id)}>
                Order
            </button>
        </div>
    );
};
```

---

## 🎯 Performance

### Filament Optimization

**DO:**
- ✅ Use `->lazy()` for heavy relationships
- ✅ Limit table queries with pagination
- ✅ Add indexes to frequently filtered columns
- ✅ Cache expensive operations

```php
// Optimize relationships
Select::make('kategori_id')
    ->relationship('kategori', 'nama')
    ->searchable()
    ->preload() // Only if small dataset
```

**DON'T:**
- ❌ Load all records without pagination
- ❌ N+1 queries in relation managers
- ❌ Heavy computations in table columns

### React Optimization

**DO:**
```typescript
// Memoize expensive computations
const totalPrice = useMemo(() => {
    return items.reduce((sum, item) => sum + item.price, 0);
}, [items]);

// Lazy load heavy components
const HeavyChart = lazy(() => import('./HeavyChart'));
```

**DON'T:**
```typescript
// Re-compute on every render
const totalPrice = items.reduce((sum, item) => sum + item.price, 0);
```

---

## 🔒 Security

### Authentication & Authorization

**Filament:**
```php
// In Resource class
public static function canViewAny(): bool
{
    return auth()->user()?->hasRole('admin');
}

public static function canCreate(): bool
{
    return auth()->user()?->can('create_products');
}
```

**Inertia Controllers:**
```php
public function store(Request $request)
{
    // Always validate
    $validated = $request->validate([
        'product_id' => 'required|exists:produks,id',
        'user_id' => 'required|string',
        'amount' => 'required|numeric|min:0',
    ]);
    
    // Authorize
    $this->authorize('create', Pembelian::class);
    
    // Then process
    $order = Pembelian::create($validated);
    
    return redirect()->route('invoice', $order);
}
```

### Never Trust Client Input

**DO:**
- ✅ Validate on server ALWAYS
- ✅ Sanitize inputs
- ✅ Use prepared statements (Eloquent does this)
- ✅ Check authorization

**DON'T:**
- ❌ Trust client-side validation alone
- ❌ Use raw queries without binding
- ❌ Skip authorization checks

---

## 📱 Responsive Design

### Mobile-First Approach

**DO:**
```tsx
// Mobile first, then scale up
<div className="
    grid 
    grid-cols-1 
    md:grid-cols-2 
    lg:grid-cols-3 
    gap-4
">
    {products.map(product => (
        <ProductCard key={product.id} product={product} />
    ))}
</div>
```

**Test on Real Devices:**
- iPhone (Safari)
- Android (Chrome)
- Tablet
- Desktop (various sizes)

---

## 🌐 API Best Practices

### Inertia Data Passing

**DO:**
```php
// Controller - pass only needed data
public function index()
{
    return Inertia::render('Products/Index', [
        'products' => Product::with('kategori')
            ->select(['id', 'nama', 'harga', 'kategori_id', 'image'])
            ->paginate(20),
        'categories' => Kategori::select(['id', 'nama'])->get(),
    ]);
}
```

**DON'T:**
```php
// Don't send everything
return Inertia::render('Products/Index', [
    'products' => Product::with('everything')->get(), // Too much!
    'all_data' => DB::table('everything')->get(), // Unnecessary
]);
```

### Error Handling

**DO:**
```typescript
// Graceful error handling
const submitOrder = async (data: OrderData) => {
    try {
        await router.post('/orders', data, {
            onError: (errors) => {
                toast.error('Please check the form for errors');
                console.error(errors);
            },
            onSuccess: () => {
                toast.success('Order placed successfully!');
            },
        });
    } catch (error) {
        console.error('Unexpected error:', error);
        toast.error('Something went wrong. Please try again.');
    }
};
```

---

## 🎨 UI/UX Guidelines

### Consistency

**DO:**
- ✅ Use design system (shadcn/ui)
- ✅ Consistent spacing (Tailwind classes)
- ✅ Consistent colors (brand palette)
- ✅ Consistent button styles
- ✅ Consistent form layouts

**Component Library:**
```
/Components/ui/
  ├── button.tsx        (One source of truth)
  ├── card.tsx
  ├── input.tsx
  └── modal.tsx

// Use throughout app
import { Button } from '@/Components/ui/button';
```

### Loading States

**Always show feedback:**
```tsx
const OrderButton = ({ onClick, loading }) => (
    <Button 
        onClick={onClick} 
        disabled={loading}
    >
        {loading ? (
            <>
                <Loader className="mr-2 h-4 w-4 animate-spin" />
                Processing...
            </>
        ) : (
            'Place Order'
        )}
    </Button>
);
```

### Form Validation

**Show inline errors:**
```tsx
<div>
    <Input 
        type="email"
        error={errors.email}
        {...register('email')}
    />
    {errors.email && (
        <p className="text-sm text-red-500 mt-1">
            {errors.email}
        </p>
    )}
</div>
```

---

## 📊 Monitoring & Debugging

### Development

**Browser DevTools:**
- React DevTools extension
- Network tab for API calls
- Console for errors/warnings

**Laravel Debugbar (Development Only):**
```bash
composer require barryvdh/laravel-debugbar --dev
```

### Production

**Error Tracking:**
```bash
# Install Sentry
composer require sentry/sentry-laravel

# Configure in .env
SENTRY_LARAVEL_DSN=your-dsn-here
```

**Logging:**
```php
// In controllers
Log::info('Order created', ['order_id' => $order->id]);
Log::error('Payment failed', ['error' => $e->getMessage()]);
```

**Monitor:**
- Error rates
- Response times
- Conversion rates
- User feedback

---

## 🚀 Deployment

### Pre-Deployment Checklist

**Phase 1 (Filament):**
- [ ] All resources tested
- [ ] Permissions configured
- [ ] No console errors
- [ ] Database migrations run
- [ ] Environment variables set

**Phase 2-3 (Inertia):**
- [ ] Build production assets: `npm run build`
- [ ] Test production build locally
- [ ] All forms tested
- [ ] Payment flows tested
- [ ] SEO meta tags verified
- [ ] Lighthouse score > 90

### Deployment Process

```bash
# On server
git pull origin main
composer install --optimize-autoloader --no-dev
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Gradual Rollout

**Use Feature Flags:**
```php
// config/features.php
'use_inertia_products' => env('FEATURE_INERTIA_PRODUCTS', false),

// Controller
if (config('features.use_inertia_products')) {
    return Inertia::render('Products/Index');
}
return view('template.products'); // Fallback
```

**Rollout Strategy:**
1. 0% - Internal testing
2. 10% - Beta users
3. 50% - Half traffic
4. 100% - Full rollout

Monitor at each stage before proceeding.

---

## 📚 Documentation

### Code Documentation

**DO:**
```php
/**
 * Process order and initiate payment
 * 
 * @param OrderRequest $request Validated order data
 * @return RedirectResponse Redirect to invoice
 * @throws PaymentException If payment initialization fails
 */
public function processOrder(OrderRequest $request): RedirectResponse
{
    // Implementation
}
```

### User Documentation

**Create guides for:**
- Admin: How to use Filament panel
- Developers: How to add new features
- Deployment: Step-by-step deployment guide

---

## 🎯 Summary: Golden Rules

1. **Commit Often** - Small, working increments
2. **Test Thoroughly** - Before moving to next task
3. **Mobile First** - Design for mobile, enhance for desktop
4. **Security Always** - Validate, authorize, sanitize
5. **Performance Matters** - Optimize as you build
6. **User Experience** - Loading states, error messages, feedback
7. **Code Quality** - TypeScript, proper structure, reusable components
8. **Document** - Code comments, user guides, deployment docs
9. **Monitor** - Errors, performance, user behavior
10. **Ask for Help** - When stuck, use AI prompts or community

---

**Follow these practices and your migration will be smooth and successful! 🎉**

**Last Updated:** 2025-10-03

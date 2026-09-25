<?php
/**
 * Demo data seeder (development preview).
 * Usage: php demo-seed.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Gateway;
use App\Models\Package;
use App\Models\PackageImage;
use App\Models\PackageLicense;
use App\Models\PackagePricingPlan;
use App\Models\PackagePurchase;
use App\Models\PackageVersion;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use App\Models\Update;
use Illuminate\Support\Str;

// ---------- projects ----------
$projects = collect([
    ['name' => 'فروشگاه ساز وب‌ترو', 'slug' => 'webtro-shop', 'description' => 'سیستم فروشگاه اینترنتی ماژولار', 'status' => 'active'],
    ['name' => 'سامانه انبار', 'slug' => 'warehouse', 'description' => 'مدیریت انبار و موجودی', 'status' => 'active'],
    ['name' => 'پرتال سازمانی', 'slug' => 'portal', 'description' => 'پرتال داخلی سازمان', 'status' => 'pending'],
])->map(fn ($p) => Project::updateOrCreate(['slug' => $p['slug']], $p + ['repository_url' => 'https://github.com/example/' . $p['slug']]));

// ---------- packages ----------
$pkgData = [
    ['name' => 'درگاه پرداخت ملت', 'slug' => 'behpardakht-gateway', 'category' => 'payment', 'price' => 950000, 'author' => 'تیم وب‌ترو', 'short' => 'اتصال به درگاه به‌پرداخت ملت', 'status' => 'active'],
    ['name' => 'ماژول پیامکی کاوه‌نگار', 'slug' => 'kavenegar-sms', 'category' => 'notification', 'price' => 450000, 'author' => 'تیم وب‌ترو', 'short' => 'ارسال پیامک با کاوه‌نگار', 'status' => 'active'],
    ['name' => 'سئوپروس', 'slug' => 'seo-pro', 'category' => 'seo', 'price' => 1200000, 'author' => 'علیرضا محمدی', 'short' => 'ابزارک‌های سئو پیشرفته', 'status' => 'active'],
    ['name' => 'بلاگ حرفه‌ای', 'slug' => 'pro-blog', 'category' => 'blog', 'price' => 780000, 'author' => 'سارا احمدی', 'short' => 'بلاگ با ویرایشگر گرافیکی', 'status' => 'draft'],
    ['name' => 'قالب فروشگاهی مدرن', 'slug' => 'modern-shop-theme', 'category' => 'theme', 'price' => 0, 'is_free' => true, 'author' => 'تیم طراحی', 'short' => 'قالب ریسپانسیو فروشگاه', 'status' => 'active'],
];

$packages = collect($pkgData)->map(function ($p) use ($projects) {
    return Package::updateOrCreate(['slug' => $p['slug']], [
        'project_id' => $projects->firstWhere('slug', 'webtro-shop')->id,
        'name' => $p['name'],
        'short_description' => $p['short'],
        'description' => $p['short'] . ' با مستندات کامل و پشتیبانی یک‌ساله. سازگار با آخرین نسخه فریم‌ورک.',
        'author' => $p['author'],
        'category' => $p['category'],
        'thumbnail' => null,
        'is_free' => $p['is_free'] ?? false,
        'default_price' => $p['price'],
        'status' => $p['status'],
        'module_name' => ucfirst(Str::camel($p['slug'])),
        'downloads_count' => rand(40, 900),
        'purchases_count' => 0,
        'sort_order' => rand(0, 50),
    ]);
});

// ---------- versions ----------
$packages->each(function ($package, $i) {
    $versions = [
        ['version' => '1.0.0', 'type' => 'major', 'date' => now()->subMonths(4)],
        ['version' => '1.1.0', 'type' => 'minor', 'date' => now()->subMonths(2)],
        ['version' => '1.1.3', 'type' => 'patch', 'date' => now()->subDays(12)],
    ];
    foreach ($versions as $v) {
        PackageVersion::updateOrCreate([
            'package_id' => $package->id,
            'version' => $v['version'],
        ], [
            'type' => $v['type'],
            'changelog' => 'بهبود عملکرد و رفع باگ‌های گزارش‌شده.',
            'what_added' => 'امکانات جدید گزارش‌گیری',
            'what_changed' => 'بازطراحی بخش تنظیمات',
            'what_fixed' => 'رفع مشکل کش درگاه پرداخت',
            'file_path' => "packages/{$package->slug}-{$v['version']}.zip",
            'file_size' => rand(300000, 5000000),
            'file_hash' => hash('sha256', $package->slug . $v['version']),
            'min_project_version' => '2.5.0',
            'min_php_version' => '8.1',
            'min_laravel_version' => '10.0',
            'is_mandatory' => $v['type'] === 'major',
            'status' => 'active',
            'downloads_count' => rand(10, 400),
            'release_date' => $v['date'],
        ]);
    }
});

// ---------- pricing plans ----------
$packages->where('is_free', false)->each(function ($package) {
    PackagePricingPlan::updateOrCreate(['package_id' => $package->id, 'name' => 'یک‌ساله'], [
        'duration_months' => 12, 'price' => $package->default_price, 'discount_price' => (int) ($package->default_price * 0.85),
        'is_one_time' => false, 'description' => 'لایسنس ۱۲ ماهه با آپدیت رایگان', 'is_active' => true, 'sort_order' => 1,
    ]);
    PackagePricingPlan::updateOrCreate(['package_id' => $package->id, 'name' => 'خرید دائمی'], [
        'duration_months' => 0, 'price' => (int) ($package->default_price * 2), 'discount_price' => null,
        'is_one_time' => true, 'description' => 'دسترسی دائمی + ۲ سال آپدیت', 'is_active' => true, 'sort_order' => 2,
    ]);
});

// ---------- customers ----------
$customerNames = [
    ['name' => 'علی رضایی', 'email' => 'ali@example.com', 'phone' => '09121234567', 'site' => 'https://ali-shop.ir'],
    ['name' => 'مریم حسینی', 'email' => 'maryam@example.com', 'phone' => '09351112233', 'site' => 'https://maryamstore.com'],
    ['name' => 'شرکت پارس‌فن', 'email' => 'info@parsfan.ir', 'phone' => '02155667788', 'site' => 'https://parsfan.ir'],
    ['name' => 'حسن کریمی', 'email' => 'hassan@example.com', 'phone' => '09199876543', 'site' => null],
    ['name' => 'نرگس محمدی', 'email' => 'narges@example.com', 'phone' => '09361239876', 'site' => 'https://narges.shop'],
    ['name' => 'فروشگاه دیجی‌لند', 'email' => 'support@digiland.ir', 'phone' => '05138889977', 'site' => 'https://digiland.ir'],
    ['name' => 'مهدی توکلی', 'email' => 'mehdi@example.com', 'phone' => '09121110000', 'site' => null],
    ['name' => 'آتنا شریفی', 'email' => 'atena@example.com', 'phone' => '09301234567', 'site' => 'https://atena.ir'],
];
$customers = collect($customerNames)->map(function ($c, $i) {
    $created = now()->subDays(rand(10, 160));
    return Customer::updateOrCreate(['email' => $c['email']], $c + [
        'website_url' => $c['site'] ?? ('https://' . Str::slug($c['name']) . '.ir'),
        'status' => $i === 3 ? 'inactive' : 'active',
        'created_at' => $created, 'updated_at' => $created,
    ]);
});

// ---------- updates (legacy project updates) ----------
$updates = collect([
    ['title' => 'رفع باگ پرداخت', 'version' => '2.5.1', 'status' => 'active'],
    ['title' => 'افزودن گزارش فروش', 'version' => '2.5.0', 'status' => 'active'],
    ['title' => 'بازطراحی پنل کاربری', 'version' => '2.4.0', 'status' => 'archived'],
    ['title' => 'بهبود سرعت صفحه‌محصول', 'version' => '2.6.0-beta', 'status' => 'draft'],
]);
$updates->each(function ($u, $i) use ($projects) {
    Update::updateOrCreate(['title' => $u['title'], 'version' => $u['version']], [
        'project_id' => $projects[0]->id,
        'description' => $u['title'] . ' — جزئیات کامل در CHANGELOG.',
        'type' => ['patch', 'minor', 'major'][$i % 3],
        'download_link' => 'updates/' . Str::slug($u['title']) . '.zip',
        'file_size' => '2.5MB',
        'release_date' => now()->subDays(rand(1, 90)),
        'status' => $u['status'],
        'is_mandatory' => $i === 0,
        'created_at' => now()->subDays(rand(1, 90)),
    ]);
});

// ---------- subscriptions ----------
$customers->take(5)->each(function ($customer, $i) use ($projects) {
    $price = $i % 2 ? 800000 : 350000;
    $start = now()->subMonths(2);
    Subscription::updateOrCreate(['customer_id' => $customer->id, 'start_date' => $start->format('Y-m-d')], [
        'project_id' => $projects[$i % 2]->id,
        'status' => $i === 4 ? 'expired' : 'active',
        'start_date' => $start,
        'end_date' => $i === 4 ? now()->subDays(5) : now()->addMonths(10),
        'expires_at' => $i === 4 ? now()->subDays(5) : now()->addMonths(10),
        'price' => $price,
        'discount' => 0,
        'final_amount' => $price,
        'payment_status' => 'paid',
        'description' => 'اشتراک ' . ($i % 2 ? 'حرفه‌ای' : 'پایه'),
    ]);
});

// ---------- purchases + licenses ----------
$paidPackages = $packages->where('is_free', false)->values();
$customers->take(6)->each(function ($customer, $i) use ($paidPackages, $packages) {
    $package = $paidPackages[$i % $paidPackages->count()];
    $paidAt = now()->subDays(rand(2, 80));
    $months = 12;

    $purchase = PackagePurchase::updateOrCreate(['customer_id' => $customer->id, 'package_id' => $package->id, 'created_at' => $paidAt->copy()], [
        'version_id' => $package->versions()->latest('id')->first()?->id,
        'pricing_plan_id' => $package->pricingPlans()->first()?->id,
        'transaction_id' => 'TRX' . strtoupper(Str::random(8)),
        'amount' => $package->default_price,
        'gateway' => collect(['zarinpal', 'idpay', 'payping'])->random(),
        'status' => $i === 5 ? 'pending' : 'paid',
        'paid_at' => $i === 5 ? null : $paidAt,
        'created_at' => $paidAt,
    ]);

    if ($purchase->status === 'paid') {
        PackageLicense::updateOrCreate(['customer_id' => $customer->id, 'package_id' => $package->id, 'status' => 'active'], [
            'license_key' => 'LIC-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)),
            'purchase_id' => $purchase->id,
            'status' => $i === 2 ? 'revoked' : 'active',
            'starts_at' => $paidAt,
            'expires_at' => $i === 4 ? now()->addDays(9) : $paidAt->copy()->addMonths($months),
            'duration_months' => $months,
            'notes' => $i === 2 ? 'نقض قوانین استفاده' : null,
        ]);
        $package->increment('purchases_count');
    }
});

// one expired license
$expiredCustomer = $customers[7];
$anyPkg = $paidPackages[0];
PackageLicense::updateOrCreate(['customer_id' => $expiredCustomer->id, 'package_id' => $anyPkg->id, 'status' => 'expired'], [
    'license_key' => 'LIC-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-EXPI',
    'starts_at' => now()->subMonths(14),
    'expires_at' => now()->subDays(20),
    'duration_months' => 12,
]);

// ---------- درگاه آزمایشی (local) — برای تست جریان پرداخت بدون درگاه واقعی ----------
// (برای محیط واقعی غیرفعالش کنید یا درگاه بانکی واقعی را در تنظیمات→درگاه‌ها فعال کنید)
Gateway::updateOrCreate(['key' => 'local'], ['is_active' => true]);

// ---------- subscription plans (طرح‌های اشتراک) ----------
$planData = [
    [
        'name' => 'پلن رایگان شروع', 'description' => 'برای آشنایی با پکیج‌های ما — بدون هزینه، پس از تأیید مدیر فعال می‌شود.',
        'duration_months' => 1, 'price' => 0, 'discount_price' => null,
        'is_free' => true, 'is_one_time' => true, 'is_active' => true, 'sort_order' => 1,
        'features' => ['دسترسی به ۲ پکیج منتخب', 'پشتیبانی پایه', 'آپدیت‌های امنیتی'],
    ],
    [
        'name' => 'پلن یک‌ماهه', 'description' => 'دسترسی یک‌ماهه به همه پکیج‌های پلن.',
        'duration_months' => 1, 'price' => 350000, 'discount_price' => null,
        'is_free' => false, 'is_one_time' => false, 'is_active' => true, 'sort_order' => 2,
        'features' => ['دسترسی به همه پکیج‌های پلن', 'پشتیبانی اولویت‌دار', 'آپدیت‌های امنیتی و ارتقا', 'نصب روی یک دامنه'],
    ],
    [
        'name' => 'پلن سه‌ماهه', 'description' => 'دسترسی سه‌ماهه با تخفیف — بهترین شروع برای کسب‌وکارهای کوچک.',
        'duration_months' => 3, 'price' => 900000, 'discount_price' => 720000,
        'is_free' => false, 'is_one_time' => false, 'is_active' => true, 'sort_order' => 3,
        'features' => ['دسترسی به همه پکیج‌های پلن', 'پشتیبانی اولویت‌دار', 'آپدیت‌های امنیتی و ارتقا', 'نصب روی دو دامنه', 'مشاوره نصب'],
    ],
    [
        'name' => 'پلن یک‌ساله حرفه‌ای', 'description' => 'دسترسی کامل یک‌ساله — محبوب‌ترین انتخاب مشتریان.',
        'duration_months' => 12, 'price' => 3600000, 'discount_price' => 2800000,
        'is_free' => false, 'is_one_time' => false, 'is_active' => true, 'sort_order' => 4,
        'features' => ['دسترسی به همه پکیج‌های پلن', 'پشتیبانی اختصاصی ۲۴/۷', 'همه آپدیت‌ها و نسخه‌های جدید', 'نصب روی پنج دامنه', 'مشاوره نصب و راه‌اندازی', 'اولویت توسعه فیچرهای سفارشی'],
    ],
    [
        'name' => 'پلن دائمی ویژه', 'description' => 'یک‌بار بخرید، همیشه استفاده کنید — محدودیت یک‌بارمصرف.',
        'duration_months' => 0, 'price' => 12000000, 'discount_price' => null,
        'is_free' => false, 'is_one_time' => true, 'is_active' => true, 'sort_order' => 5,
        'features' => ['دسترسی دائمی به پکیج‌های پلن', 'پشتیبانی اختصاصی ۲۴/۷', 'همه آپدیت‌های آینده', 'نامحدود در دامنه‌های خودتان', 'مهاجرت و نصب رایگان'],
    ],
    [
        'name' => 'پلن شش‌ماهه (غیرفعال)', 'description' => 'نمونه طرح غیرفعال برای تست.',
        'duration_months' => 6, 'price' => 1800000, 'discount_price' => null,
        'is_free' => false, 'is_one_time' => false, 'is_active' => false, 'sort_order' => 6,
        'features' => ['دسترسی به همه پکیج‌های پلن', 'پشتیبانی اولویت‌دار'],
    ],
];

$subPlans = collect($planData)->map(function ($p) {
    return SubscriptionPlan::updateOrCreate(['name' => $p['name']], $p);
});

// پکیج‌های هر طرح:
//  - پلن رایگان: ۲ پکیج منتخب با مدت اختصاصی ۱ ماهه
//  - پلن‌های پولی: همه پکیج‌های فعال با مدت پیش‌فرض طرح
//  - پلن دائمی: همه پکیج‌ها با مدت اختصاصی ۰ (نامحدود)
$freePlan = $subPlans->firstWhere('name', 'پلن رایگان شروع');
$freePlan->packages()->syncWithPivotValues($packages->take(2)->pluck('id')->all(), ['duration_months' => 1]);

$permanentPlan = $subPlans->firstWhere('name', 'پلن دائمی ویژه');
$permanentPlan->packages()->syncWithPivotValues($packages->pluck('id')->all(), ['duration_months' => 0]);

foreach ($subPlans->whereNotIn('name', ['پلن رایگان شروع', 'پلن دائمی ویژه']) as $p) {
    $p->packages()->sync($packages->pluck('id')->all()); // مدت پیش‌فرض طرح
}

// ---------- subscription requests (درخواست‌های اشتراک) ----------
// ۱) درخواست پرداخت‌شده در انتظار تأیید مدیر
$monthly = $subPlans->firstWhere('name', 'پلن یک‌ماهه');
SubscriptionRequest::updateOrCreate(['customer_id' => $customers[1]->id, 'subscription_plan_id' => $monthly->id], [
    'gateway' => 'local', 'amount' => $monthly->final_price,
    'transaction_id' => 'SUBTRX' . strtoupper(Str::random(8)),
    'payment_status' => 'paid', 'status' => 'pending',
    'paid_at' => now()->subDays(1), 'created_at' => now()->subDays(1),
]);

// ۲) درخواست رایگان در انتظار تأیید
$freeReqCustomer = $customers[2];
SubscriptionRequest::updateOrCreate(['customer_id' => $freeReqCustomer->id, 'subscription_plan_id' => $freePlan->id], [
    'gateway' => null, 'amount' => 0,
    'payment_status' => 'free', 'status' => 'pending',
    'created_at' => now()->subDays(2),
]);

// ۳) درخواست ردشده
$rejectedCustomer = $customers[3];
$annual = $subPlans->firstWhere('name', 'پلن یک‌ساله حرفه‌ای');
SubscriptionRequest::updateOrCreate(['customer_id' => $rejectedCustomer->id, 'subscription_plan_id' => $annual->id], [
    'gateway' => 'zarinpal', 'amount' => $annual->final_price,
    'transaction_id' => 'SUBTRX' . strtoupper(Str::random(8)),
    'payment_status' => 'paid', 'status' => 'rejected',
    'paid_at' => now()->subDays(9), 'approved_at' => null, 'rejected_at' => now()->subDays(8),
    'admin_note' => 'اطلاعات پرداخت قابل تأیید نبود؛ لطفاً با پشتیبانی تماس بگیرید.',
    'created_at' => now()->subDays(9),
]);

echo "Demo data seeded OK\n";
echo 'Projects: ' . Project::count() . ' | Packages: ' . Package::count() . ' | Versions: ' . PackageVersion::count()
    . ' | Plans: ' . PackagePricingPlan::count() . ' | Customers: ' . Customer::count()
    . ' | Licenses: ' . PackageLicense::count() . ' | Purchases: ' . PackagePurchase::count()
    . ' | Subscriptions: ' . Subscription::count() . ' | Updates: ' . Update::count()
    . ' | SubPlans: ' . SubscriptionPlan::count() . ' | SubRequests: ' . SubscriptionRequest::count() . "\n";

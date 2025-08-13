# افزونه لپ‌تاپ پرایسر (Used Laptop Pricer)

افزونه پیشرفته وردپرس برای محاسبه قیمت لپ‌تاپ‌های دست دوم بر اساس روش Market-Based Pricing

## 📋 ویژگی‌ها

### ✨ ویژگی‌های اصلی
- **محاسبه قیمت هوشمند**: بر اساس نرخ استهلاک سالانه و وضعیت ظاهری
- **پشتیبانی کامل از RTL**: رابط کاربری راست‌چین برای زبان فارسی
- **واکنش‌گرا**: سازگار با تمام دستگاه‌ها (موبایل، تبلت، دسکتاپ)
- **مدیریت کامل**: پنل ادمین پیشرفته با قابلیت‌های متنوع
- **آپلود و خروجی Excel**: پشتیبانی از فایل‌های Excel برای ورود و خروج داده

### 🎯 منطق محاسبه قیمت
- **قیمت پایه**: بر اساس مدل و کانفیگ پایه لپ‌تاپ
- **استهلاک سالانه**: 
  - سال اول: 30% کاهش
  - سال دوم: 15% کاهش
  - سال‌های بعدی: 10% کاهش سالانه
- **ضریب وضعیت ظاهری**: نو (1.0)، عالی (0.9)، خوب (0.8)، متوسط (0.7)، ضعیف (0.5)
- **تعدیل قطعات**: محاسبه تفاوت قیمت قطعات با کانفیگ پایه

### 📊 خروجی
- **قیمت نهایی**: قیمت محاسبه‌شده
- **بازه قیمتی**: از 90% تا 100% قیمت نهایی
- **جزئیات محاسبه**: نمایش مراحل محاسبه برای شفافیت

## 🚀 نصب و راه‌اندازی

### پیش‌نیازها
- وردپرس نسخه 5.0 یا بالاتر
- PHP نسخه 7.4 یا بالاتر
- MySQL نسخه 5.6 یا بالاتر

### مراحل نصب

1. **دانلود افزونه**
   ```bash
   # دانلود فایل ZIP
   wget https://github.com/hoseinmos/used-laptop-pricer/releases/latest/download/used-laptop-pricer.zip
   ```

2. **نصب در وردپرس**
   - وارد پنل ادمین وردپرس شوید
   - به بخش "افزونه‌ها" > "افزودن" بروید
   - فایل ZIP را آپلود کنید
   - افزونه را فعال کنید

3. **نصب وابستگی‌ها**
   ```bash
   cd wp-content/plugins/used-laptop-pricer
   composer install --no-dev
   ```

4. **تنظیمات اولیه**
   - به منوی "لپ‌تاپ پرایسر" در پنل ادمین بروید
   - تنظیمات استهلاک و ضرایب وضعیت را بررسی کنید
   - مدل‌های پایه و قطعات را اضافه کنید

## 📖 راهنمای استفاده

### استفاده در صفحات و پست‌ها

#### شورت‌کد ساده
```
[used_laptop_pricer]
```

#### شورت‌کد با پارامترهای سفارشی
```
[used_laptop_pricer title="محاسبه قیمت لپ‌تاپ" show_details="true"]
```

### پارامترهای شورت‌کد
- `title`: عنوان فرم (پیش‌فرض: محاسبه قیمت لپ‌تاپ دست دوم)
- `show_details`: نمایش جزئیات محاسبه (true/false)

### استفاده در کد PHP
```php
// نمایش فرم
echo do_shortcode('[used_laptop_pricer]');

// محاسبه قیمت در کد
$calculator = new ULP_Price_Calculator();
$result = $calculator->calculate_price($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage);
```

## ⚙️ تنظیمات

### تنظیمات استهلاک
- **سال اول**: نرخ کاهش ارزش در سال اول (پیش‌فرض: 30%)
- **سال دوم**: نرخ کاهش ارزش در سال دوم (پیش‌فرض: 15%)
- **سال‌های بعدی**: نرخ کاهش ارزش سالانه (پیش‌فرض: 10%)

### ضرایب وضعیت ظاهری
- **نو**: ضریب 1.0
- **عالی**: ضریب 0.9
- **خوب**: ضریب 0.8
- **متوسط**: ضریب 0.7
- **ضعیف**: ضریب 0.5

### تنظیمات واحد پول
- نام واحد پول (پیش‌فرض: تومان)
- نماد واحد پول (پیش‌فرض: تومان)

## 📁 ساختار فایل‌ها

```
used-laptop-pricer/
├── used-laptop-pricer.php          # فایل اصلی افزونه
├── composer.json                   # وابستگی‌های Composer
├── README.md                       # راهنمای افزونه
├── admin/                          # فایل‌های پنل ادمین
│   ├── admin-init.php
│   ├── settings-page.php
│   ├── model-manager.php
│   ├── parts-manager.php
│   └── views/
│       └── dashboard.php
├── includes/                       # فایل‌های اصلی
│   ├── database.php
│   ├── helpers.php
│   ├── calculate-price.php
│   ├── frontend-init.php
│   └── admin-init.php
├── templates/                      # قالب‌های فرانت‌اند
│   └── form.php
├── assets/                         # فایل‌های استاتیک
│   ├── css/
│   │   ├── style.css
│   │   └── admin.css
│   └── js/
│       ├── form.js
│       └── admin.js
└── languages/                      # فایل‌های ترجمه
    └── used-laptop-pricer-fa_IR.po
```

## 🗄️ ساختار دیتابیس

### جدول مدل‌های لپ‌تاپ
```sql
CREATE TABLE wp_ulp_laptop_models (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    brand varchar(100) NOT NULL,
    model varchar(200) NOT NULL,
    release_year int(4) NOT NULL,
    base_price decimal(15,2) NOT NULL,
    base_cpu varchar(200) NOT NULL,
    base_ram varchar(100) NOT NULL,
    base_gpu varchar(200) NOT NULL,
    base_storage varchar(200) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_model (brand, model, release_year)
);
```

### جدول قیمت قطعات
```sql
CREATE TABLE wp_ulp_parts_prices (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    part_type varchar(50) NOT NULL,
    part_name varchar(200) NOT NULL,
    part_specs text NOT NULL,
    price decimal(15,2) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_part (part_type, part_name, part_specs)
);
```

## 🔧 توسعه و سفارشی‌سازی

### هوک‌های موجود
```php
// قبل از محاسبه قیمت
do_action('ulp_before_calculate_price', $data);

// بعد از محاسبه قیمت
do_action('ulp_after_calculate_price', $result);

// فیلتر کردن نتیجه محاسبه
$result = apply_filters('ulp_calculation_result', $result, $data);
```

### اضافه کردن فیلترهای سفارشی
```php
// تغییر نرخ استهلاک
add_filter('ulp_depreciation_rate', function($rate, $year) {
    // منطق سفارشی
    return $custom_rate;
}, 10, 2);

// تغییر ضریب وضعیت
add_filter('ulp_condition_factor', function($factor, $condition) {
    // منطق سفارشی
    return $custom_factor;
}, 10, 2);
```

## 📊 آمار و گزارش‌گیری

### API Endpoints
- `GET /wp-json/ulp/v1/models` - دریافت لیست مدل‌ها
- `GET /wp-json/ulp/v1/parts` - دریافت لیست قطعات
- `POST /wp-json/ulp/v1/calculate` - محاسبه قیمت

### مثال استفاده از API
```javascript
// محاسبه قیمت
fetch('/wp-json/ulp/v1/calculate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        brand: 'Dell',
        model: 'XPS 13',
        year: 2020,
        condition: 'good',
        cpu: 'Intel Core i7',
        ram: '16GB',
        gpu: 'Intel UHD Graphics',
        storage: '512GB SSD'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

## 🐛 عیب‌یابی

### مشکلات رایج

#### خطای "فایل Excel آپلود نشد"
- بررسی مجوزهای پوشه uploads
- بررسی اندازه فایل (حداکثر 5MB)
- بررسی فرمت فایل (.xlsx یا .xls)

#### خطای "مدل یافت نشد"
- بررسی وجود مدل در دیتابیس
- بررسی تطابق برند و مدل
- بررسی سال عرضه

#### خطای محاسبه قیمت
- بررسی تنظیمات استهلاک
- بررسی ضرایب وضعیت
- بررسی قیمت‌های قطعات

### لاگ‌گیری
```php
// فعال کردن لاگ‌گیری
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// مشاهده لاگ‌ها
tail -f wp-content/debug.log
```

## 🔒 امنیت

### اقدامات امنیتی
- استفاده از WordPress Nonce برای تمام فرم‌ها
- اعتبارسنجی و پاکسازی تمام ورودی‌ها
- محدودیت دسترسی بر اساس نقش کاربر
- محافظت در برابر SQL Injection
- محافظت در برابر XSS

### بهترین شیوه‌ها
- همیشه از آخرین نسخه افزونه استفاده کنید
- تنظیمات امنیتی وردپرس را فعال کنید
- از پسوردهای قوی استفاده کنید
- به طور منظم از دیتابیس پشتیبان تهیه کنید

## 📈 بهینه‌سازی عملکرد

### نکات بهینه‌سازی
- استفاده از کش برای نتایج محاسبات
- بهینه‌سازی کوئری‌های دیتابیس
- فشرده‌سازی فایل‌های CSS و JS
- استفاده از CDN برای فایل‌های استاتیک

### تنظیمات کش
```php
// فعال کردن کش برای محاسبات
add_filter('ulp_enable_cache', '__return_true');

// تنظیم زمان انقضای کش
add_filter('ulp_cache_expiry', function() {
    return 3600; // 1 ساعت
});
```

## 🤝 مشارکت

### نحوه مشارکت
1. Fork کردن پروژه
2. ایجاد شاخه جدید (`git checkout -b feature/amazing-feature`)
3. Commit تغییرات (`git commit -m 'Add amazing feature'`)
4. Push به شاخه (`git push origin feature/amazing-feature`)
5. ایجاد Pull Request

### استانداردهای کدنویسی
- پیروی از استانداردهای WordPress
- استفاده از PHPCS برای بررسی کد
- نوشتن تست‌های واحد
- مستندسازی کد

## 📄 مجوز

این پروژه تحت مجوز GPL v2 یا بالاتر منتشر شده است.

## 👨‍💻 نویسنده

**hoseinmos**
- GitHub: [@hoseinmos](https://github.com/hoseinmos)
- Email: hoseinmos@example.com

## 🙏 تشکر

از تمام کسانی که در توسعه این افزونه مشارکت کرده‌اند تشکر می‌کنیم.

## 📞 پشتیبانی

برای گزارش مشکلات یا درخواست ویژگی‌های جدید:
- ایجاد Issue در GitHub
- ارسال ایمیل به hoseinmos@example.com
- مراجعه به مستندات کامل در [Wiki](https://github.com/hoseinmos/used-laptop-pricer/wiki)

---

**نسخه**: 1.0.0  
**آخرین بروزرسانی**: 2024  
**سازگاری**: WordPress 5.0+ | PHP 7.4+
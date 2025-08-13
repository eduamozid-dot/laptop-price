<?php
/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('داشبورد لپ‌تاپ پرایسر', 'used-laptop-pricer'); ?></h1>
    
    <div class="ulp-dashboard-stats">
        <div class="ulp-stat-card">
            <div class="ulp-stat-icon">
                <span class="dashicons dashicons-laptop"></span>
            </div>
            <div class="ulp-stat-content">
                <h3><?php echo number_format($models_count); ?></h3>
                <p><?php _e('مدل لپ‌تاپ', 'used-laptop-pricer'); ?></p>
            </div>
        </div>
        
        <div class="ulp-stat-card">
            <div class="ulp-stat-icon">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="ulp-stat-content">
                <h3><?php echo number_format($parts_count); ?></h3>
                <p><?php _e('قطعه', 'used-laptop-pricer'); ?></p>
            </div>
        </div>
        
        <div class="ulp-stat-card">
            <div class="ulp-stat-icon">
                <span class="dashicons dashicons-tag"></span>
            </div>
            <div class="ulp-stat-content">
                <h3><?php echo number_format($brands_count); ?></h3>
                <p><?php _e('برند', 'used-laptop-pricer'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="ulp-dashboard-actions">
        <div class="ulp-action-card">
            <h3><?php _e('مدیریت مدل‌های پایه', 'used-laptop-pricer'); ?></h3>
            <p><?php _e('افزودن، ویرایش و حذف مدل‌های لپ‌تاپ و قیمت‌های پایه', 'used-laptop-pricer'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-models'); ?>" class="button button-primary">
                <?php _e('مدیریت مدل‌ها', 'used-laptop-pricer'); ?>
            </a>
        </div>
        
        <div class="ulp-action-card">
            <h3><?php _e('مدیریت قطعات', 'used-laptop-pricer'); ?></h3>
            <p><?php _e('افزودن و ویرایش قیمت قطعات (CPU، RAM، GPU، Storage)', 'used-laptop-pricer'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-parts'); ?>" class="button button-primary">
                <?php _e('مدیریت قطعات', 'used-laptop-pricer'); ?>
            </a>
        </div>
        
        <div class="ulp-action-card">
            <h3><?php _e('تنظیمات', 'used-laptop-pricer'); ?></h3>
            <p><?php _e('تنظیم نرخ استهلاک، ضرایب وضعیت و سایر پارامترها', 'used-laptop-pricer'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-settings'); ?>" class="button button-primary">
                <?php _e('تنظیمات', 'used-laptop-pricer'); ?>
            </a>
        </div>
    </div>
    
    <div class="ulp-dashboard-info">
        <div class="ulp-info-card">
            <h3><?php _e('نحوه استفاده', 'used-laptop-pricer'); ?></h3>
            <p><?php _e('برای نمایش فرم محاسبه قیمت در صفحات یا پست‌ها، از شورت‌کد زیر استفاده کنید:', 'used-laptop-pricer'); ?></p>
            <code>[used_laptop_pricer]</code>
            
            <h4><?php _e('پارامترهای اختیاری:', 'used-laptop-pricer'); ?></h4>
            <ul>
                <li><code>title</code>: <?php _e('عنوان فرم (پیش‌فرض: محاسبه قیمت لپ‌تاپ دست دوم)', 'used-laptop-pricer'); ?></li>
                <li><code>show_details</code>: <?php _e('نمایش جزئیات محاسبه (true/false)', 'used-laptop-pricer'); ?></li>
            </ul>
            
            <p><strong><?php _e('مثال:', 'used-laptop-pricer'); ?></strong></p>
            <code>[used_laptop_pricer title="محاسبه قیمت" show_details="true"]</code>
        </div>
        
        <div class="ulp-info-card">
            <h3><?php _e('آخرین تغییرات', 'used-laptop-pricer'); ?></h3>
            <ul>
                <li><?php _e('افزودن سیستم محاسبه قیمت بر اساس Market-Based Pricing', 'used-laptop-pricer'); ?></li>
                <li><?php _e('پشتیبانی از آپلود و خروجی فایل Excel', 'used-laptop-pricer'); ?></li>
                <li><?php _e('رابط کاربری راست‌چین و واکنش‌گرا', 'used-laptop-pricer'); ?></li>
                <li><?php _e('مدیریت کامل از پنل ادمین', 'used-laptop-pricer'); ?></li>
            </ul>
        </div>
    </div>
</div>
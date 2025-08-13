#!/bin/bash

# Used Laptop Pricer - Build Script
# This script creates a distributable ZIP file of the plugin

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin information
PLUGIN_NAME="used-laptop-pricer"
PLUGIN_VERSION="1.0.0"
BUILD_DIR="build"
DIST_DIR="dist"

echo -e "${GREEN}🚀 Starting build process for Used Laptop Pricer v${PLUGIN_VERSION}${NC}"

# Create build and dist directories
echo -e "${YELLOW}📁 Creating build directories...${NC}"
mkdir -p $BUILD_DIR
mkdir -p $DIST_DIR

# Clean previous builds
echo -e "${YELLOW}🧹 Cleaning previous builds...${NC}"
rm -rf $BUILD_DIR/*
rm -rf $DIST_DIR/*

# Copy plugin files to build directory
echo -e "${YELLOW}📋 Copying plugin files...${NC}"
cp -r admin $BUILD_DIR/
cp -r assets $BUILD_DIR/
cp -r includes $BUILD_DIR/
cp -r languages $BUILD_DIR/
cp -r templates $BUILD_DIR/
cp composer.json $BUILD_DIR/
cp README.md $BUILD_DIR/
cp used-laptop-pricer.php $BUILD_DIR/

# Create necessary directories if they don't exist
mkdir -p $BUILD_DIR/backups
mkdir -p $BUILD_DIR/vendor

# Create .gitignore for build
echo -e "${YELLOW}📝 Creating .gitignore...${NC}"
cat > $BUILD_DIR/.gitignore << EOF
# Dependencies
/vendor/
/node_modules/

# Build files
/build/
/dist/

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Logs
*.log

# Backup files
/backups/*.json
EOF

# Create installation instructions
echo -e "${YELLOW}📖 Creating installation instructions...${NC}"
cat > $BUILD_DIR/INSTALL.md << EOF
# نصب و راه‌اندازی Used Laptop Pricer

## روش 1: نصب از طریق پنل ادمین وردپرس
1. فایل ZIP را در پنل ادمین وردپرس آپلود کنید
2. افزونه را فعال کنید
3. به بخش "لپ‌تاپ پرایسر > تنظیمات" بروید
4. تنظیمات اولیه را انجام دهید

## روش 2: نصب دستی
1. فایل ZIP را استخراج کنید
2. پوشه افزونه را در wp-content/plugins/ کپی کنید
3. وابستگی‌ها را نصب کنید: \`composer install\`
4. افزونه را از پنل ادمین فعال کنید

## پیش‌نیازها
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- Composer (برای نصب وابستگی‌ها)

## تنظیمات اولیه
1. مدل‌های پایه را وارد کنید
2. قیمت قطعات را تنظیم کنید
3. نرخ‌های استهلاک را تعیین کنید
4. از شورت‌کد [used_laptop_pricer] استفاده کنید
EOF

# Create changelog
echo -e "${YELLOW}📝 Creating changelog...${NC}"
cat > $BUILD_DIR/CHANGELOG.md << EOF
# Changelog

## [1.0.0] - 2024-01-01

### Added
- محاسبه قیمت لپ‌تاپ دست دوم با الگوریتم Market-Based Pricing
- پشتیبانی کامل از RTL و زبان فارسی
- مدیریت مدل‌های پایه از طریق پنل ادمین
- مدیریت قیمت قطعات
- ورود و خروجی Excel
- فرم محاسبه قیمت با شورت‌کد
- تنظیمات قابل تغییر نرخ استهلاک
- ضرایب وضعیت ظاهری قابل تنظیم
- رابط کاربری واکنش‌گرا
- امنیت بالا با WordPress Nonce

### Technical
- ساختار ماژولار و قابل توسعه
- کد تمیز مطابق استانداردهای وردپرس
- پشتیبانی از ترجمه
- استفاده از PhpSpreadsheet برای Excel
- AJAX برای محاسبات بدون reload صفحه
EOF

# Create license file
echo -e "${YELLOW}📄 Creating license file...${NC}"
cat > $BUILD_DIR/LICENSE << EOF
GNU GENERAL PUBLIC LICENSE
Version 2, June 1991

Copyright (C) 1989, 1991 Free Software Foundation, Inc.
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

Everyone is permitted to copy and distribute verbatim copies
of this license document, but changing it is not allowed.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
EOF

# Create ZIP file
echo -e "${YELLOW}📦 Creating ZIP file...${NC}"
cd $BUILD_DIR
zip -r "../$DIST_DIR/${PLUGIN_NAME}-v${PLUGIN_VERSION}.zip" . -x "*.git*" "*.DS_Store*" "*.swp*" "*.swo*"
cd ..

# Create source ZIP (without vendor)
echo -e "${YELLOW}📦 Creating source ZIP...${NC}"
cd $BUILD_DIR
zip -r "../$DIST_DIR/${PLUGIN_NAME}-v${PLUGIN_VERSION}-source.zip" . -x "*.git*" "*.DS_Store*" "*.swp*" "*.swo*" "vendor/*"
cd ..

# Display build information
echo -e "${GREEN}✅ Build completed successfully!${NC}"
echo -e "${GREEN}📁 Build files created in: $DIST_DIR/${NC}"
echo -e "${GREEN}📦 Plugin ZIP: ${PLUGIN_NAME}-v${PLUGIN_VERSION}.zip${NC}"
echo -e "${GREEN}📦 Source ZIP: ${PLUGIN_NAME}-v${PLUGIN_VERSION}-source.zip${NC}"

# Display file sizes
echo -e "${YELLOW}📊 File sizes:${NC}"
ls -lh $DIST_DIR/

# Cleanup build directory
echo -e "${YELLOW}🧹 Cleaning up build directory...${NC}"
rm -rf $BUILD_DIR

echo -e "${GREEN}🎉 Build process completed!${NC}"
echo -e "${GREEN}📤 Ready for distribution!${NC}"
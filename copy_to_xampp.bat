@echo off
echo نسخ نظام أرشفة الأساتذة إلى XAMPP...
echo.

REM إنشاء مجلد المشروع في XAMPP
if not exist "C:\xampp\htdocs\school-system" mkdir "C:\xampp\htdocs\school-system"

REM نسخ جميع الملفات
xcopy "d:\ARCH\*" "C:\xampp\htdocs\school-system\" /E /I /Y

echo.
echo تم نسخ الملفات بنجاح!
echo.
echo الخطوات التالية:
echo 1. افتح XAMPP Control Panel
echo 2. شغل Apache
echo 3. افتح المتصفح واذهب إلى: http://localhost/school-system/setup_demo_data.php
echo.
pause

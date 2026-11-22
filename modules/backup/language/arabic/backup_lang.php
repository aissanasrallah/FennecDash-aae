<?php
defined('BASEPATH') or exit('No direct script access allowed');

# Version 2.3.0

$lang['auto_backup_options_updated'] = 'تم تحديث خيارات النسخ الاحتياطي التلقائي';
$lang['auto_backup_every'] = 'إنشاء نسخة احتياطية كل X أيام';
$lang['auto_backup_enabled'] = 'مفعّل (يتطلب Cron)';
$lang['auto_backup'] = 'النسخ الاحتياطي التلقائي';
$lang['backup_delete'] = 'تم حذف النسخة الاحتياطية';
$lang['backup_success'] = 'تم إنشاء النسخة الاحتياطية بنجاح';
$lang['utility_backup'] = 'نسخ قاعدة البيانات احتياطيًا';
$lang['utility_create_new_backup_db'] = 'إنشاء نسخة احتياطية لقاعدة البيانات';
$lang['utility_backup_table_backupname'] = 'النسخة الاحتياطية';
$lang['utility_backup_table_backupsize'] = 'حجم النسخة الاحتياطية';
$lang['utility_backup_table_backupdate'] = 'التاريخ';
$lang['utility_db_backup_note'] = 'ملاحظة: نظرًا لمحدودية وقت التنفيذ والذاكرة المتاحة لـ PHP، قد لا يكون من الممكن إنشاء نسخة احتياطية لقواعد بيانات كبيرة جدًا. إذا كانت قاعدة البيانات لديك كبيرة جدًا، فقد تحتاج إلى إنشاء النسخة الاحتياطية مباشرة من خادم SQL عبر سطر الأوامر، أو أن يقوم مسؤول الخادم بذلك نيابةً عنك إذا لم تكن لديك صلاحيات root.';
$lang['delete_backups_older_then'] = 'حذف النسخ الاحتياطية تلقائيًا الأقدم من X أيام (اضبط 0 للتعطيل)';
$lang['auto_backup_hour'] = 'ساعة اليوم المحددة لإنشاء النسخة الاحتياطية';

<?php
defined('BASEPATH') or exit('No direct script access allowed');

# Version 2.3.0

$lang['goals'] = 'الأهداف';
$lang['goal'] = 'الهدف';
$lang['goals_tracking'] = 'تتبع الأهداف';
$lang['new_goal'] = 'هدف جديد';
$lang['goal_lowercase'] = 'هدف';
$lang['goal_start_date'] = 'تاريخ البدء';
$lang['goal_end_date'] = 'تاريخ الانتهاء';
$lang['goal_subject'] = 'الموضوع';
$lang['goal_description'] = 'الوصف';
$lang['goal_type'] = 'نوع الهدف';
$lang['goal_achievement'] = 'الإنجاز';
$lang['goal_contract_type'] = 'نوع العقد';
$lang['goal_notify_when_fail'] = 'إخطار الموظفين عند الفشل في تحقيق الهدف';
$lang['goal_notify_when_achieve'] = 'إخطار الموظفين عند تحقيق الهدف';
$lang['goal_progress'] = 'التقدم';
$lang['goal_total'] = 'الإجمالي: %s';
$lang['goal_result_heading'] = 'تقدم الهدف';
$lang['goal_income_shown_in_base_currency'] = 'يُعرض إجمالي الدخل بعملتك الأساسية';
$lang['goal_notify_when_end_date_arrives'] = 'سيتم إخطار الموظفين عند وصول تاريخ الانتهاء (يتطلب CRON).';
$lang['goal_staff_members_notified_about_achievement'] = 'تم إخطار الموظفين بتحقيق هذا الهدف';
$lang['goal_staff_members_notified_about_failure'] = 'تم إخطار الموظفين بالفشل';
$lang['goal_notify_staff_manually'] = 'إخطار الموظفين يدويًا';
$lang['goal_notify_staff_notified_manually_success'] = 'تم إخطار الموظفين بنتيجة هذا الهدف';
$lang['goal_notify_staff_notified_manually_fail'] = 'فشل إخطار الموظفين بنتيجة هذا الهدف';
$lang['goal_achieved'] = 'تم التحقيق';
$lang['goal_failed'] = 'فشل';
$lang['goal_close'] = 'قريب جدًا';
$lang['goal_type_total_income'] = 'تحقيق إجمالي الدخل';
$lang['goal_type_convert_leads'] = 'تحويل X عميل محتمل';
$lang['goal_type_increase_customers_without_leads_conversions'] = 'زيادة عدد العملاء';
$lang['goal_type_increase_customers_without_leads_conversions_subtext'] = 'يُستثنى تحويل العملاء المحتملين';
$lang['goal_type_increase_customers_with_leads_conversions'] = 'زيادة عدد العملاء';
$lang['goal_type_increase_customers_with_leads_conversions_subtext'] = 'يُشمل تحويل العملاء المحتملين';
$lang['goal_type_make_contracts_by_type_calc_database'] = 'إبرام العقود حسب النوع';
$lang['goal_type_make_contracts_by_type_calc_database_subtext'] = 'يُحسب من تاريخ الإضافة إلى قاعدة البيانات';
$lang['goal_type_make_contracts_by_type_calc_date'] = 'إبرام العقود حسب النوع';
$lang['goal_type_make_contracts_by_type_calc_date_subtext'] = 'يُحسب من تاريخ بدء العقد';
$lang['goal_type_total_estimates_converted'] = 'تحويل X عرض سعر ';
$lang['goal_type_total_estimates_converted_subtext'] = 'يُحسب فقط من عروض الأسعار التي سيتم تحويلها إلى فواتير';
$lang['goal_type_income_subtext'] = 'سيتم احتساب الدخل بعملتك الأساسية (دون تحويله)';

$lang['not_goal_message_success'] = 'تهانينا! لقد حققنا هدفًا جديدًا.<br /> نوع الهدف: %s
<br />الإنجاز: %s
<br />إجمالي الإنجاز: %s
<br />تاريخ البدء: %s
<br />تاريخ الانتهاء: %s';

$lang['not_goal_message_failed'] = 'لقد فشلنا في تحقيق الهدف!<br /> نوع الهدف: %s
<br />الإنجاز: %s
<br />إجمالي الإنجاز: %s
<br />تاريخ البدء: %s
<br />تاريخ الانتهاء: %s';

# Version 2.4.5
$lang['goal_type_invoiced_amount'] = 'المبلغ المُفوتر';

# Version 2.3.1
$lang['goal_type_invoiced_amount'] = 'المبلغ المفوتر الصافي دون ضريبة (بعد خصم الإشعارات الدائنة)';
$lang['goal_recurrence_type'] = 'الدورية / التكرار';
$lang['goal_recurrence_one_time'] = 'هدف لمرة واحدة';
$lang['goal_recurrence_daily'] = 'يومي';
$lang['goal_recurrence_weekly'] = 'أسبوعي';
$lang['goal_recurrence_monthly'] = 'شهري';
$lang['goal_recurrence_quarterly'] = 'ربع سنوي';
$lang['goal_recurrence_yearly'] = 'سنوي';
$lang['goal_recurrence_custom'] = 'فترة مخصصة (بالأيام)';
$lang['goal_custom_period_days'] = 'مدة الفترة المخصصة (أيام)';
$lang['goal_attribution_mode'] = 'طريقة احتساب رقم الأعمال';
$lang['goal_attribution_individual'] = 'فردي لكل موظف (الفواتير المنسوبة للموظف)';
$lang['goal_attribution_shared'] = 'مشترك / على مستوى الشركة (جميع الفواتير)';
$lang['goal_assigned_staff'] = 'الموظفون المعينون';
$lang['goal_select_all_staff'] = 'تحديد جميع الموظفين';
$lang['goal_period_covered'] = 'الفترة المغطاة';
$lang['goal_periods_history'] = 'الفترات الحالية والسابقة';
$lang['goal_employee_breakdown'] = 'التقدم الفردي لكل موظف';
$lang['goal_filter_period'] = 'تصفية الأهداف';
$lang['goal_filter_all'] = 'جميع الفترات';
$lang['goal_filter_current'] = 'الفترة الحالية';
$lang['goal_filter_historical'] = 'السجل / المنتهية';
$lang['goal_generate_periods'] = 'تحديث الفترات';
$lang['goal_gross_invoiced_ht'] = 'إجمالي المفوتر دون ضريبة';
$lang['goal_credit_notes_ht'] = 'الإشعارات الدائنة المخصومة';
$lang['goal_net_invoiced_ht'] = 'صافي المفوتر دون ضريبة';
$lang['goal_current_period'] = 'جاري';
$lang['goal_historical_period'] = 'سجل';
$lang['goal_all_employees_shared'] = 'جميع الموظفين (مشترك)';

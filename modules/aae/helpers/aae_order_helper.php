<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Order stage state machine.
 *
 * Keys are ASCII so they can safely be used in URLs, database rows and CSS
 * classes; the visible labels come from the language files.
 */
function aae_order_stages()
{
    return [
        // Phase 1: Prospection & Demande Initiale
        'nouveau_contact'      => _l('aae_order_stage_nouveau_contact'),
        'devis_prete'          => _l('aae_order_stage_devis_prete'),
        'devis_envoye'         => _l('aae_order_stage_devis_envoye'),
        
        // Phase 2: Contractualisation & Dossier Maritime
        'commande_confirmee'   => _l('aae_order_stage_commande_confirmee'),
        
        // Phase 3: Paiement & Traitement Fournisseur
        'paiement_client_recu' => _l('aae_order_stage_paiement_client_recu'),
        'fournisseur_paye'     => _l('aae_order_stage_fournisseur_paye'),
        'dossier_fournisseur'  => _l('aae_order_stage_dossier_fournisseur'),
        
        // Phase 4: Préparation & Expédition Maritime
        'depart_confirme'      => _l('aae_order_stage_depart_confirme'),
        'expedie'              => _l('aae_order_stage_expedie'),
        
        // Phase 5: Dédouanement en Algérie
        'dedouanement'         => _l('aae_order_stage_dedouanement'),
        'docs_transitaire'     => _l('aae_order_stage_docs_transitaire'),
        
        // Phase 6: Livraison & Finalisation Administrative
        'arrive'               => _l('aae_order_stage_arrive'),
        'livre'                => _l('aae_order_stage_livre'),
        'papiers_definitifs'   => _l('aae_order_stage_papiers_definitifs'),
        'cloture'              => _l('aae_order_stage_cloture'),
        'annule'               => _l('aae_order_stage_annule'),
    ];
}

function aae_order_stage_label($stage)
{
    $stages = aae_order_stages();

    return isset($stages[$stage]) ? $stages[$stage] : $stage;
}

function aae_order_stage_badge($stage)
{
    $label = aae_order_stage_label($stage);

    return '<span class="aae-order-badge aae-order-badge-' . e($stage) . '">' . e($label) . '</span>';
}

function aae_order_stage_progress($stage)
{
    $ordered = array_keys(aae_order_stages());
    $idx     = array_search($stage, $ordered, true);

    if ($idx === false) {
        return 0;
    }

    // Cancelled orders are terminal but not part of the linear progression.
    if ($stage === 'annule') {
        return 100;
    }

    return (int) round(($idx / (count($ordered) - 2)) * 100);
}

function aae_order_can_transition($from, $to)
{
    if ($to === 'annule') {
        return $from !== 'cloture' && $from !== 'annule';
    }

    $ordered = array_keys(aae_order_stages());
    $i       = array_search($from, $ordered, true);
    $j       = array_search($to, $ordered, true);

    if ($i === false || $j === false) {
        return false;
    }

    return $j === $i + 1;
}

function aae_order_next_stage($stage)
{
    $ordered = array_keys(aae_order_stages());
    $i       = array_search($stage, $ordered, true);

    if ($i === false || ! isset($ordered[$i + 1])) {
        return null;
    }

    $next = $ordered[$i + 1];

    return $next === 'annule' ? null : $next;
}

function aae_order_required_docs()
{
    return [
        'contrat'             => _l('aae_doc_contrat'),
        'facture_client'      => _l('aae_doc_facture_client'),
        'preuve_virement'     => _l('aae_doc_preuve_virement'),
        'facture_fournisseur' => _l('aae_doc_facture_fournisseur'),
        'preuve_fournisseur'  => _l('aae_doc_preuve_fournisseur'),
        'passeport'           => _l('aae_doc_passeport'),
        'formulaire_maritime' => _l('aae_doc_formulaire_maritime'),
        'certificat_residence' => _l('aae_doc_certificat_residence'),
        'acte_naissance'      => _l('aae_doc_acte_naissance'),
        'attestation_honneur' => _l('aae_doc_attestation_honneur'),
        'procuration'         => _l('aae_doc_procuration'),
        'bl'                  => _l('aae_doc_bl'),
        'docs_chine'          => _l('aae_doc_docs_chine'),
        'docs_client'         => _l('aae_doc_docs_client'),
        'dhl'                 => _l('aae_doc_dhl'),
        'laissez_passer'      => _l('aae_doc_laissez_passer'),
        'papiers_definitifs'  => _l('aae_doc_papiers_definitifs'),
        'facture_finale'      => _l('aae_doc_facture_finale'),
    ];
}

/**
 * Resolve the visible label of an order document.
 *
 * Documents seeded before the language files were loaded stored the raw
 * language key (e.g. "aae_doc_bl") in the label column. Translate those on
 * the fly and fall back to the canonical label for the document type.
 *
 * @param object|string $doc
 *
 * @return string
 */
function aae_order_document_label($doc)
{
    $label = is_object($doc) ? (string) ($doc->label ?? '') : (string) $doc;

    if ($label !== '' && strpos($label, 'aae_') === 0) {
        $translated = _l($label, '', false);
        if ($translated !== $label) {
            return $translated;
        }

        $label = '';
    }

    if ($label === '' && is_object($doc) && ! empty($doc->doc_type)) {
        $types = aae_order_required_docs();
        if (isset($types[$doc->doc_type])) {
            return $types[$doc->doc_type];
        }
    }

    return $label;
}

/**
 * Currency used for the export dossiers (EUR when available).
 *
 * @return int
 */
function aae_order_currency_id()
{
    $CI = &get_instance();

    $currency = $CI->db->select('id')
        ->where('name', 'EUR')
        ->get(db_prefix() . 'currencies')
        ->row();

    if ($currency) {
        return (int) $currency->id;
    }

    $currency = $CI->db->select('id')
        ->where('isdefault', 1)
        ->get(db_prefix() . 'currencies')
        ->row();

    return $currency ? (int) $currency->id : 0;
}

function aae_order_payment_types()
{
    return [
        'client_in'    => _l('aae_payment_client_in'),
        'supplier_out' => _l('aae_payment_supplier_out'),
        'transitaire'  => _l('aae_payment_transitaire'),
        'carrier'      => _l('aae_payment_carrier'),
        'refund'       => _l('aae_payment_refund'),
    ];
}

function aae_order_delivery_methods()
{
    return [
        'domicile'   => _l('aae_order_delivery_domicile'),
        'enlevement' => _l('aae_order_delivery_enlevement'),
    ];
}

function aae_format_dzd($amount)
{
    return number_format((float) $amount, 0, ',', ' ') . ' DZD';
}

function aae_format_eur($amount)
{
    return number_format((float) $amount, 2, ',', ' ') . ' €';
}

function aae_order_client_balance($order)
{
    return (float) $order->amount_client + (float) $order->extras_cost - (float) $order->amount_net_received;
}

function aae_order_client_balance_label($order)
{
    $balance = aae_order_client_balance($order);

    if ($balance > 0.01) {
        return _l('aae_order_balance_to_pay', aae_format_eur($balance));
    }

    if ($balance < -0.01) {
        return _l('aae_order_balance_to_refund', aae_format_eur(abs($balance)));
    }

    return _l('aae_order_balance_settled');
}

function aae_order_whatsapp_url($phone, $message)
{
    $phone = preg_replace('/[^0-9]/', '', (string) $phone);

    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
}

/**
 * Message templates for every stage of the export process.
 *
 * Placeholders are replaced by aae_render_order_message() using the values
 * returned from aae_order_message_context().
 *
 * @return array
 */
function aae_order_message_templates()
{
    $fr = [
        'nouveau_contact' => "Bonjour {contact},\n\nMerci de nous avoir contactés chez {company}. Nous vous accompagnons pour l'achat et l'export de votre véhicule.\n\nPour préparer votre devis, merci de nous confirmer :\n- le modèle et la finition souhaités\n- votre budget\n- la ville de destination\n- votre délai souhaité\n\nNous vous enverrons ensuite la fiche technique et une vidéo du véhicule.\n\nCordialement,\n{company}",
        'devis_prete' => "Bonjour {contact},\n\nVotre devis pour le véhicule {vehicle} est prêt (commande n°{order}).\nMontant : {amount}.\n\nVous trouverez ci-joint la fiche technique et la vidéo du véhicule. Le devis inclut le véhicule et les frais de dédouanement.\n\nNous restons à votre disposition pour toute question.\n\nCordialement,\n{company}",
        'devis_envoye' => "Bonjour {contact},\n\nNous revenons vers vous concernant le devis de la commande n°{order} ({vehicle}).\nAvez-vous pu en prendre connaissance ? Souhaitez-vous que nous ajustions le modèle, les options ou le budget ?\n\nLe prix du fournisseur peut évoluer ; nous vous confirmerons le tarif du jour avant tout engagement.\n\nCordialement,\n{company}",
        'commande_confirmee' => "Bonjour {contact},\n\nNous confirmons la commande n°{order} pour le véhicule {vehicle}.\nMontant convenu : {amount}.\n\nPour lancer le dossier, merci de nous transmettre :\n- une copie de votre passeport\n- le formulaire de la compagnie maritime complété\n- la preuve de paiement\n\nNous vous enverrons la facture et le contrat signés.\n\nCordialement,\n{company}",
        'paiement_client_recu' => "Bonjour {contact},\n\nNous accusons réception de votre paiement pour la commande n°{order}.\nMontant reçu : {paid}. Reste à régler : {balance}.\n\nMerci de nous envoyer la preuve de virement si ce n'est pas déjà fait. Nous transmettons ensuite le dossier au fournisseur.\n\nCordialement,\n{company}",
        'fournisseur_paye' => "Bonjour {contact},\n\nBonne nouvelle : le fournisseur a été payé pour la commande n°{order} ({vehicle}).\nNous préparons maintenant le départ du véhicule et vous tiendrons informé dès que la date est confirmée.\n\nCordialement,\n{company}",
        'depart_confirme' => "Bonjour {contact},\n\nLe départ de votre véhicule (commande n°{order}) est confirmé.\nNous vous communiquerons très prochainement le numéro de conteneur, le numéro de BL et les photos/vidéos du chargement.\n\nCordialement,\n{company}",
        'expedie' => "Bonjour {contact},\n\nVotre véhicule est expédié (commande n°{order}).\n- Conteneur : {container}\n- BL : {bl}\n- Suivi : {tracking}\n\nVous pouvez suivre le transport sur le site de la compagnie maritime avec ces références. Les photos et vidéos du chargement sont jointes.\n\nCordialement,\n{company}",
        'dedouanement' => "Bonjour {contact},\n\nVotre véhicule est arrivé et le dédouanement est en cours (commande n°{order}).\n\nMerci de nous transmettre les documents suivants :\n- passeport\n- certificat de résidence\n- acte de naissance\n- attestation sur l'honneur\n- procuration\n\nVous recevrez également un avis de paiement pour régler le transitaire. Vous pouvez nous envoyer les documents ou les déposer au bureau.\n\nCordialement,\n{company}",
        'arrive' => "Bonjour {contact},\n\nVotre véhicule {vehicle} est arrivé (commande n°{order}).\n\nMerci de nous indiquer :\n- livraison à domicile ou enlèvement sur place\n- assurance souhaitée ou non\n- lavage souhaité ou non\n- toute autre demande\n\nNous organiserons ensuite la livraison.\n\nCordialement,\n{company}",
        'livre' => "Bonjour {contact},\n\nVotre véhicule a été livré (commande n°{order}).\nVous trouverez ci-joint le document vous autorisant à conduire le véhicule. Les papiers définitifs arriveront sous 10 à 15 jours ; nous vous contacterons dès réception.\n\nCordialement,\n{company}",
        'cloture' => "Bonjour {contact},\n\nLes papiers définitifs de votre véhicule sont arrivés (commande n°{order}).\nMerci de passer les récupérer, accompagnés de la facture finale. Le solde à régler est de {balance} ; en cas de trop-perçu, un remboursement vous sera effectué.\n\nMerci pour votre confiance.\n\nCordialement,\n{company}",
        'annule' => "Bonjour {contact},\n\nNous vous informons que la commande n°{order} ({vehicle}) est annulée.\nSi un paiement a été effectué, nous procéderons au remboursement selon les conditions convenues.\n\nCordialement,\n{company}",
    ];

    $en = [
        'nouveau_contact' => "Hello {contact},\n\nThank you for contacting {company}. We help you buy and export your vehicle.\n\nTo prepare your quote, please confirm:\n- the model and trim you want\n- your budget\n- the destination city\n- your preferred timeline\n\nWe will then send you the technical sheet and a video of the vehicle.\n\nBest regards,\n{company}",
        'devis_prete' => "Hello {contact},\n\nYour quote for the {vehicle} is ready (order #{order}).\nAmount: {amount}.\n\nThe technical sheet and the vehicle video are attached. The quote covers the vehicle and the customs clearance fees.\n\nPlease let us know if you have any questions.\n\nBest regards,\n{company}",
        'devis_envoye' => "Hello {contact},\n\nWe are following up on the quote for order #{order} ({vehicle}).\nHave you had a chance to review it? Would you like us to adjust the model, the options or the budget?\n\nSupplier prices can change; we will confirm the price of the day before any commitment.\n\nBest regards,\n{company}",
        'commande_confirmee' => "Hello {contact},\n\nWe confirm order #{order} for the {vehicle}.\nAgreed amount: {amount}.\n\nTo start the file, please send us:\n- a copy of your passport\n- the completed shipping company form\n- the payment proof\n\nWe will send you the signed invoice and contract.\n\nBest regards,\n{company}",
        'paiement_client_recu' => "Hello {contact},\n\nWe acknowledge receipt of your payment for order #{order}.\nAmount received: {paid}. Remaining balance: {balance}.\n\nPlease send us the transfer proof if you have not done so yet. We will then forward the file to the supplier.\n\nBest regards,\n{company}",
        'fournisseur_paye' => "Hello {contact},\n\nGood news: the supplier has been paid for order #{order} ({vehicle}).\nWe are now preparing the vehicle departure and will keep you informed as soon as the date is confirmed.\n\nBest regards,\n{company}",
        'depart_confirme' => "Hello {contact},\n\nThe departure of your vehicle (order #{order}) is confirmed.\nWe will send you the container number, the B/L number and the loading photos/videos shortly.\n\nBest regards,\n{company}",
        'expedie' => "Hello {contact},\n\nYour vehicle has been shipped (order #{order}).\n- Container: {container}\n- B/L: {bl}\n- Tracking: {tracking}\n\nYou can track the shipment on the shipping line website with these references. The loading photos and videos are attached.\n\nBest regards,\n{company}",
        'dedouanement' => "Hello {contact},\n\nYour vehicle has arrived and customs clearance is in progress (order #{order}).\n\nPlease send us the following documents:\n- passport\n- residence certificate\n- birth certificate\n- sworn statement\n- power of attorney\n\nYou will also receive a payment notice for the customs broker. You can send us the documents or drop them at the office.\n\nBest regards,\n{company}",
        'arrive' => "Hello {contact},\n\nYour {vehicle} has arrived (order #{order}).\n\nPlease tell us:\n- home delivery or pickup on site\n- insurance required or not\n- washing required or not\n- any other request\n\nWe will then organise the delivery.\n\nBest regards,\n{company}",
        'livre' => "Hello {contact},\n\nYour vehicle has been delivered (order #{order}).\nPlease find attached the document authorising you to drive the vehicle. The final papers will arrive within 10 to 15 days; we will contact you as soon as they are received.\n\nBest regards,\n{company}",
        'cloture' => "Hello {contact},\n\nThe final papers for your vehicle have arrived (order #{order}).\nPlease come and collect them with the final invoice. The balance due is {balance}; if you have overpaid, a refund will be issued.\n\nThank you for your trust.\n\nBest regards,\n{company}",
        'annule' => "Hello {contact},\n\nPlease note that order #{order} ({vehicle}) has been cancelled.\nIf a payment was made, we will issue a refund according to the agreed terms.\n\nBest regards,\n{company}",
    ];

    $ar = [
        'nouveau_contact' => "مرحباً {contact},\n\nشكراً لتواصلكم مع {company}. نحن نساعدكم في شراء وتصدير مركبتكم.\n\nلتحضير عرض السعر، يرجى تأكيد:\n- الموديل والمواصفات المطلوبة\n- ميزانيتكم\n- مدينة الوجهة\n- الموعد المفضل\n\nسنرسل لكم لاحقاً الورقة التقنية وفيديو للمركبة.\n\nمع خالص التحيات,\n{company}",
        'devis_prete' => "مرحباً {contact},\n\nعرض السعر للمركبة {vehicle} جاهز (الطلب رقم {order}).\nالمبلغ: {amount}.\n\nستجدون مرفقاً الورقة التقنية وفيديو المركبة. يشمل العرض المركبة ورسوم التخليص الجمركي.\n\nنحن متاحون لأي استفسار.\n\nمع خالص التحيات,\n{company}",
        'devis_envoye' => "مرحباً {contact},\n\nنعود إليكم بخصوص عرض السعر للطلب رقم {order} ({vehicle}).\nهل أتيحت لكم فرصة لمراجعته؟ هل ترغبون في تعديل الموديل أو الخيارات أو الميزانية؟\n\nقد تتغير أسعار المورد؛ سنؤكد لكم سعر اليوم قبل أي التزام.\n\nمع خالص التحيات,\n{company}",
        'commande_confirmee' => "مرحباً {contact},\n\nنؤكد الطلب رقم {order} للمركبة {vehicle}.\nالمبلغ المتفق عليه: {amount}.\n\nلإطلاق الملف، يرجى إرسال:\n- نسخة من جواز السفر\n- استمارة الشركة البحرية مكتملة\n- إثبات الدفع\n\nسنساعدكم في إرسال الفاتورة والعقد الموقعين.\n\nمع خالص التحيات,\n{company}",
        'paiement_client_recu' => "مرحباً {contact},\n\nنؤكد استلام دفعتكم للطلب رقم {order}.\nالمبلغ المستلم: {paid}. الرصيد المتبقي: {balance}.\n\nيرجى إرسال إثبات التحويل إن لم يكن قد تم بالفعل. سنقوم بعد ذلك بإرسال الملف للمورد.\n\nمع خالص التحيات,\n{company}",
        'fournisseur_paye' => "مرحباً {contact},\n\nأخبار جيدة: تم دفع المورد للطلب رقم {order} ({vehicle}).\nنحن الآن نجهز خروج المركبة وسنخبركم بمجرد تأكيد الموعد.\n\nمع خالص التحيات,\n{company}",
        'depart_confirme' => "مرحباً {contact},\n\nتم تأكيد خروج مركبتكم (الطلب رقم {order}).\nسنتواصل معكم قريباً جداً برقم الحاوية، رقم B/L، وصور/فيديو التحميل.\n\nمع خالص التحيات,\n{company}",
        'expedie' => "مرحباً {contact},\n\nتم شحن مركبتكم (الطلب رقم {order}).\n- الحاوية: {container}\n- B/L: {bl}\n- التتبع: {tracking}\n\nيمكنكم تتبع الشحن على موقع الشركة البحرية بهذه المراجع. صور ومقاطع فيديو التحميل مرفقة.\n\nمع خالص التحيات,\n{company}",
        'dedouanement' => "مرحباً {contact},\n\nوصلت مركبتكم وبدأ التخليص الجمركي (الطلب رقم {order}).\n\nيرجى إرسال الوثائق التالية:\n- جواز السفر\n- شهادة الإقامة\n- شهادة الميلاد\n- تصريح بالشرف\n- وكالة\n\nستتلقون أيضاً إشعار دفع للمخليص الجمركي. يمكنكم إرسال الوثائق إلينا أو إيداعها في المكتب.\n\nمع خالص التحيات,\n{company}",
        'arrive' => "مرحباً {contact},\n\nوصلت مركبتكم {vehicle} (الطلب رقم {order}).\n\nيرجى إعلامنا:\n- التوصيل للمنزل أم استلام في الموقع\n- هل التأمين مطلوب أم لا\n- هل الغسيل مطلوب أم لا\n- أي طلب آخر\n\nسننظم التسليم بعد ذلك.\n\nمع خالص التحيات,\n{company}",
        'livre' => "مرحباً {contact},\n\nتم تسليم مركبتكم (الطلب رقم {order}).\nستجدون مرفقاً الوثيقة التي تسمح لكم بقيادة المركبة. ستصل الأوراق النهائية في غضون 10 إلى 15 يوماً؛ سنتصل بكم فور استلامها.\n\nمع خالص التحيات,\n{company}",
        'cloture' => "مرحباً {contact},\n\nوصلت الأوراق النهائية لمركبتكم (الطلب رقم {order}).\nيرجى الحضور لاستلامها مصحوبة بالفاتورة النهائية. الرصيد الواجب دفعه هو {balance}؛ في حالة الدفع الزائد، سيتم استرداد المبلغ.\n\nشكراً لثقتكم.\n\nمع خالص التحيات,\n{company}",
        'annule' => "مرحباً {contact},\n\nنبلغكم بإلغاء الطلب رقم {order} ({vehicle}).\nفي حال تم دفع مبلغ، سنقوم برد المبلغ حسب الشروط المتفق عليها.\n\nمع خالص التحيات,\n{company}",
    ];

    $templates = [];
    foreach (aae_order_stages() as $stage => $label) {
        $templates[$stage] = [
            'fr' => $fr[$stage] ?? '',
            'en' => $en[$stage] ?? '',
            'ar' => $ar[$stage] ?? '',
        ];
    }

    return $templates;
}

/**
 * Build the placeholder values used by the message templates.
 *
 * @param object      $order
 * @param object|null $client
 * @param object|null $vehicle
 *
 * @return array
 */
function aae_order_message_context($order, $client = null, $vehicle = null)
{
    $CI = &get_instance();

    if (! $client) {
        $client = $CI->db->select('userid, company, phonenumber')
            ->where('userid', (int) $order->client_id)
            ->get(db_prefix() . 'clients')
            ->row();
    }

    $contact = $CI->db->select('firstname, lastname, email, phonenumber')
        ->where('userid', (int) $order->client_id)
        ->where('is_primary', 1)
        ->get(db_prefix() . 'contacts')
        ->row();

    if (! $vehicle && ! empty($order->vehicle_id)) {
        $table   = ($order->vehicle_source ?? 'cars') === 'catalogue' ? 'aae_catalogue' : 'aae_cars';
        $vehicle = $CI->db->where('id', (int) $order->vehicle_id)
            ->get(db_prefix() . $table)
            ->row();
    }

    $vehicle_label = '';
    if ($vehicle) {
        $vehicle_label = trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? ''));
        if (! empty($vehicle->model_year)) {
            $vehicle_label .= ' (' . $vehicle->model_year . ')';
        }
    }

    $contact_name = $contact ? trim(($contact->firstname ?? '') . ' ' . ($contact->lastname ?? '')) : '';
    if ($contact_name === '') {
        $contact_name = $client->company ?? '';
    }

    $phone = $contact->phonenumber ?? '';
    if ($phone === '' && $client) {
        $phone = $client->phonenumber ?? '';
    }

    $balance = (float) $order->amount_client - (float) $order->amount_net_received;

    // Public link the customer can open. Prefer the linked estimate
    // (view/accept page); fall back to the internal order page so the
    // placeholder is never empty.
    $order_link = admin_url('aae/aae_order/view/' . (int) $order->id);
    if (! empty($order->estimate_id)) {
        $estimate = $CI->db->select('id, hash')
            ->where('id', (int) $order->estimate_id)
            ->get(db_prefix() . 'estimates')
            ->row();

        if ($estimate && ! empty($estimate->hash)) {
            $order_link = site_url('estimate/' . (int) $estimate->id . '/' . $estimate->hash);
        }
    }

    return [
        'client'    => $client->company ?? '',
        'contact'   => $contact_name,
        'vehicle'   => $vehicle_label !== '' ? $vehicle_label : _l('aae_vehicle'),
        'order'     => (int) $order->id,
        'amount'    => aae_format_eur($order->amount_client),
        'paid'      => aae_format_eur($order->amount_net_received),
        'balance'   => aae_format_eur($balance),
        'container' => $order->container_number ?: '-',
        'bl'        => $order->bl_number ?: '-',
        'tracking'  => $order->tracking_link ?: '-',
        'company'   => aae_get_option('aae_company_name', 'Algerie Auto Export'),
        'phone'     => $phone,
        'email'     => $contact->email ?? '',
        'order_link' => $order_link,
    ];
}

/**
 * Replace the {placeholders} of a message template.
 *
 * @param string $template
 * @param array  $context
 *
 * @return string
 */
function aae_render_order_message($template, $context)
{
    $replace = [];
    foreach ($context as $key => $value) {
        $replace['{' . $key . '}'] = (string) $value;
    }

    $message = strtr((string) $template, $replace);

    // Every outgoing message must carry the order link, even when the
    // template does not explicitly use the {order_link} placeholder.
    $link = (string) ($context['order_link'] ?? '');
    if ($link !== '' && strpos($message, $link) === false) {
        $message = rtrim($message) . "\n\n" . $link;
    }

    return $message;
}

/**
 * Orders linked to a customer (used by the customer profile tab).
 *
 * @param int $client_id
 *
 * @return array
 */
function aae_get_client_orders($client_id)
{
    $CI = &get_instance();
    $CI->load->model('aae/aae_order_model');

    return $CI->aae_order_model->get_orders_by_client($client_id);
}

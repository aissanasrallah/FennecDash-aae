<?php defined('BASEPATH') or exit('No direct script access allowed');

// Regroupement par marque
$grouped = [];
foreach ($vehicles as $v) {
    $key = $v['marque'] !== null && trim((string)$v['marque']) !== '' ? trim((string)$v['marque']) : _l('aae_none');
    if (!isset($grouped[$key])) {
        $grouped[$key] = [];
    }
    $grouped[$key][] = $v;
}
ksort($grouped);
?>

<style>
/* AAE Import Manager - portail client (autonome) */
.aae-cl-hero { margin-bottom: 24px; }
.aae-cl-hero h2 { margin: 0 0 6px 0; font-size: 24px; }
.aae-cl-hero p { color: #666; margin: 0; }
.aae-cl-brand-title { margin: 26px 0 14px 0; font-size: 17px; font-weight: 600; color: #333; }
.aae-cl-brand-title .aae-cl-brand-count { color: #999; font-weight: 400; font-size: 13px; margin-left: 6px; }
.aae-cl-card {
    border: 1px solid #e6e8ec; border-radius: 8px; background: #fff;
    padding: 16px 18px; height: 100%;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    display: flex; flex-direction: column;
}
.aae-cl-card .aae-cl-marque { text-transform: uppercase; letter-spacing: .4px; font-size: 11px; color: #8b93a2; font-weight: 600; }
.aae-cl-card h5 { margin: 6px 0 4px 0; font-size: 15px; font-weight: 600; line-height: 1.3; word-wrap: break-word; }
.aae-cl-card .aae-cl-meta { color: #777; font-size: 13px; margin-bottom: 8px; }
.aae-cl-card .aae-cl-color { display: inline-block; font-size: 11px; color: #555; border: 1px solid #e0e3e8; border-radius: 10px; padding: 1px 9px; margin-bottom: 10px; }
.aae-cl-card .aae-cl-price { font-size: 18px; font-weight: 700; color: #1b7fd0; margin-top: auto; padding-top: 8px; }
.aae-cl-card .btn { margin-top: 12px; width: 100%; }
.aae-cl-empty { color: #888; border: 1px dashed #d8dbe0; border-radius: 8px; padding: 30px; text-align: center; }
@media (max-width: 767px) { .aae-cl-card h5 { font-size: 14px; } }
</style>

<div class="aae-cl-hero">
    <h2><?php echo _l('aae_client_catalogue_title'); ?></h2>
    <p><?php echo _l('aae_client_contact_hint'); ?></p>
</div>

<?php if (count($grouped) === 0) { ?>
    <div class="aae-cl-empty"><?php echo _l('aae_client_no_vehicles'); ?></div>
<?php } else { ?>
    <?php foreach ($grouped as $marque => $list) { ?>
        <div class="aae-cl-brand-title">
            <i class="fa fa-car"></i> <?php echo e($marque); ?>
            <span class="aae-cl-brand-count">(<?php echo count($list); ?>)</span>
        </div>
        <div class="row">
            <?php foreach ($list as $v) { ?>
                <div class="col-md-3 col-sm-6" style="margin-bottom:18px;">
                    <div class="aae-cl-card">
                        <div class="aae-cl-marque"><?php echo e($v['marque']); ?></div>
                        <h5><?php echo e($v['modele']); ?></h5>
                        <div class="aae-cl-meta">
                            <?php echo !empty($v['moteur']) ? e($v['moteur']) . ' · ' : ''; ?><?php echo !empty($v['annee']) ? (int)$v['annee'] : ''; ?>
                        </div>
                        <?php if (!empty($v['couleur'])) { ?>
                            <span class="aae-cl-color"><?php echo e($v['couleur']); ?></span>
                        <?php } ?>
                        <?php if (!empty($v['ttc_dzd']) && (float)$v['ttc_dzd'] > 0) { ?>
                            <div class="aae-cl-price">
                                <?php echo number_format((float)$v['ttc_dzd'], 0, ',', ' '); ?> DA
                            </div>
                        <?php } ?>
                        <a href="<?php echo site_url('clients/tickets'); ?>" class="btn btn-primary" style="margin-top:12px;">
                            <i class="fa fa-file-text-o"></i> <?php echo _l('aae_client_ask_quote'); ?>
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
<?php } ?>

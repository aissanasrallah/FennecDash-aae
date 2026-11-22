<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get a module option with a fallback when the option is missing or empty.
 *
 * @param string $name
 * @param mixed  $default
 *
 * @return mixed
 */
function aae_get_option($name, $default = '')
{
    $value = get_option($name);

    if ($value === '' || $value === null || $value === false) {
        return $default;
    }

    return $value;
}

/**
 * Exchange rates and markup used by the pricing formula.
 *
 * @return array
 */
function aae_get_rates()
{
    return [
        'eur_rate'   => (float) aae_get_option('aae_eur_rate', 0.892),
        'eur_markup' => (float) aae_get_option('aae_eur_markup', 0.8),
        'dzd_rate'   => (float) aae_get_option('aae_dzd_rate', 282),
    ];
}

/**
 * Default fees used when creating a new car.
 *
 * @return array
 */
function aae_get_form_defaults()
{
    return [
        'doc_fee'      => (float) aae_get_option('aae_default_doc_fee', 100),
        'vehicle_fee'  => (float) aae_get_option('aae_default_vehicle_fee', 0),
        'delivery_fee' => (float) aae_get_option('aae_default_delivery_fee', 1400),
        'commission'   => (float) aae_get_option('aae_default_commission', 850),
    ];
}

function aae_statuses()
{
    return ['available', 'reserved', 'in_transit', 'delivered', 'unavailable'];
}

function aae_destinations()
{
    return ['algeria', 'tunisia', 'france', 'china', 'other'];
}

function aae_status_label($status)
{
    if (! in_array($status, aae_statuses(), true)) {
        return $status;
    }

    return _l('aae_status_' . $status);
}

function aae_destination_label($destination)
{
    if (! in_array($destination, aae_destinations(), true)) {
        return $destination;
    }

    return _l('aae_destination_' . $destination);
}

function aae_status_badge($status)
{
    $status = in_array($status, aae_statuses(), true) ? $status : 'available';

    return '<span class="aae-badge aae-badge-' . $status . '">' . e(aae_status_label($status)) . '</span>';
}

function aae_destination_badge($destination)
{
    $destination = in_array($destination, aae_destinations(), true) ? $destination : 'other';

    return '<span class="aae-destination-badge">' . e(aae_destination_label($destination)) . '</span>';
}

/**
 * Apply the AAE pricing formula.
 *
 * Total purchase = FOB + documents + vehicle + delivery (EUR)
 * Price EUR      = Total purchase * (1 + markup / 100)
 * Total TTC      = Price EUR + commission
 * Price DZD      = Total TTC * DZD rate
 * Millions       = Price DZD / 10,000
 *
 * The database column is still named total_usd for backward compatibility.
 *
 * @param array      $base
 * @param array|null $rates
 *
 * @return array
 */
function aae_calculate_prices($base, $rates = null)
{
    if ($rates === null) {
        $rates = aae_get_rates();
    }

    $fob      = isset($base['fob_price']) ? (float) $base['fob_price'] : 0;
    $doc      = isset($base['doc_fee']) ? (float) $base['doc_fee'] : 0;
    $vehicle  = isset($base['vehicle_fee']) ? (float) $base['vehicle_fee'] : 0;
    $delivery = isset($base['delivery_fee']) ? (float) $base['delivery_fee'] : 0;

    $total_eur = $fob + $doc + $vehicle + $delivery;

    $price_eur  = $total_eur * (1 + ((float) $rates['eur_markup'] / 100));
    $commission = isset($base['commission']) ? (float) $base['commission'] : 0;
    $total_ttc  = $price_eur + $commission;
    $price_dzd  = $total_ttc * (float) $rates['dzd_rate'];

    return [
        'fob_price'      => $fob,
        'doc_fee'        => $doc,
        'vehicle_fee'    => $vehicle,
        'delivery_fee'   => $delivery,
        'total_usd'      => $total_eur,
        'price_eur'      => $price_eur,
        'commission'     => $commission,
        'total_ttc'      => $total_ttc,
        'price_dzd'      => $price_dzd,
        'price_millions' => $price_dzd / 10000,
    ];
}

/**
 * Parse a number from the supplier sheet.
 * Handles "7 643 €", "2 388 129", "239,85", "7.643,50", "1,000", etc.
 *
 * @param mixed $value
 *
 * @return float|null
 */
function aae_parse_number($value)
{
    if ($value === null) {
        return null;
    }

    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $value = str_replace(
        ["\xC2\xA0", "\xE2\x80\xAF", ' ', "\t", '€', '$', '£', 'DZD', 'USD', 'EUR', 'DA'],
        '',
        $value
    );

    $value = preg_replace('/[^0-9,.\-]/u', '', $value);

    if ($value === '' || $value === '-' || $value === '.' || $value === ',') {
        return null;
    }

    $hasComma = strpos($value, ',') !== false;
    $hasDot   = strpos($value, '.') !== false;

    if ($hasComma && $hasDot) {
        if (strrpos($value, ',') > strrpos($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($hasComma) {
        $parts = explode(',', $value);

        if (count($parts) === 2
            && strlen($parts[1]) === 3
            && $parts[0] !== ''
            && $parts[0] !== '0'
            && strpos($parts[0], '0') !== 0) {
            $value = $parts[0] . $parts[1];
        } else {
            $value = str_replace(',', '.', $value);
        }
    }

    if (substr_count($value, '.') > 1) {
        $last  = strrpos($value, '.');
        $value = str_replace('.', '', substr($value, 0, $last)) . substr($value, $last);
    }

    if (! is_numeric($value)) {
        return null;
    }

    return (float) $value;
}

/**
 * Extract a four digit year from a sheet value.
 *
 * @param mixed $value
 *
 * @return int
 */
function aae_parse_year($value)
{
    if (preg_match('/\b(19|20)\d{2}\b/', trim((string) $value), $matches)) {
        return (int) $matches[0];
    }

    return 0;
}

/**
 * Normalize brand/model text so small spelling differences do not create duplicates.
 *
 * @param string $text
 *
 * @return string
 */
function aae_normalize_key($text)
{
    $text = trim((string) $text);

    if ($text === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($converted !== false) {
            $text = $converted;
        }
    }

    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function aae_make_import_key($brand, $model, $year)
{
    return sha1(aae_normalize_key($brand) . '|' . aae_normalize_key($model) . '|' . (int) $year);
}

/**
 * Format an amount using the CRM currency settings.
 *
 * @param float  $amount
 * @param string $currency
 *
 * @return string
 */
function aae_format_money($amount, $currency = 'EUR')
{
    return app_format_money((float) $amount, $currency);
}

/**
 * Format a value expressed in millions (e.g. 239.85 M DZD).
 *
 * @param float $amount
 *
 * @return string
 */
function aae_format_millions($amount)
{
    $decimal  = get_option('decimal_separator');
    $thousand = get_option('thousand_separator');

    if ($decimal === '') {
        $decimal = '.';
    }

    if ($thousand === '') {
        $thousand = ',';
    }

    return number_format((float) $amount, 2, $decimal, $thousand) . ' M';
}

/**
 * Read a value from a car object/array with a fallback.
 *
 * @param mixed  $car
 * @param string $key
 * @param mixed  $default
 *
 * @return mixed
 */
function aae_value($car, $key, $default = '')
{
    if (is_object($car) && isset($car->{$key})) {
        return $car->{$key};
    }

    if (is_array($car) && array_key_exists($key, $car)) {
        return $car[$key];
    }

    return $default;
}

function aae_invoice_url($invoice_id)
{
    return admin_url('invoices/invoice/' . (int) $invoice_id);
}

function aae_client_url($client_id)
{
    return admin_url('clients/client/' . (int) $client_id);
}

/**
 * Cars linked to a customer (used by the customer profile tab).
 *
 * @param int $client_id
 *
 * @return array
 */
function aae_get_client_cars($client_id)
{
    $CI = &get_instance();
    $CI->load->model('aae/aae_model');

    return $CI->aae_model->get_client_cars($client_id);
}

/**
 * Normalize a CSV header cell for matching.
 *
 * @param string $text
 *
 * @return string
 */
function aae_normalize_header($text)
{
    $text = trim((string) $text);

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($converted !== false) {
            $text = $converted;
        }
    }

    $text = strtoupper($text);
    $text = preg_replace('/[^A-Z0-9]+/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function aae_csv_is_header($row)
{
    $brand = false;
    $model = false;

    foreach ($row as $cell) {
        $header = aae_normalize_header($cell);

        if (strpos($header, 'MARQUE') !== false) {
            $brand = true;
        }

        if (strpos($header, 'MODELE') !== false) {
            $model = true;
        }
    }

    return $brand && $model;
}

/**
 * Map the supplier sheet columns to internal keys.
 *
 * @param array $header
 *
 * @return array
 */
function aae_csv_map_header($header)
{
    $map = [];

    foreach ($header as $index => $cell) {
        $name = aae_normalize_header($cell);

        if ($name === '') {
            continue;
        }

        if (strpos($name, 'MARQUE') !== false || strpos($name, 'BRAND') !== false) {
            $map['brand'] = $index;
        } elseif (strpos($name, 'ANNEE') !== false || strpos($name, 'YEAR') !== false) {
            // Must be checked before MODELE: "ANNEE MODELE" contains "MODELE".
            $map['model_year'] = $index;
        } elseif (strpos($name, 'MODELE') !== false || $name === 'MODEL') {
            $map['model'] = $index;
        } elseif (strpos($name, 'MOTEUR') !== false || strpos($name, 'ENGINE') !== false) {
            $map['engine'] = $index;
        } elseif (strpos($name, 'COULEUR') !== false || strpos($name, 'COLOR') !== false) {
            $map['color'] = $index;
        } elseif (strpos($name, 'FOB') !== false) {
            $map['fob_price'] = $index;
        } elseif (strpos($name, 'TRADUCTION DOCUMENTS') !== false) {
            $map['doc_fee'] = $index;
        } elseif (strpos($name, 'TRADUCTION DU VEHICULE') !== false) {
            $map['vehicle_fee'] = $index;
        } elseif (strpos($name, 'FRAIS DE LIVRAISON') !== false) {
            $map['delivery_fee'] = $index;
        } elseif (strpos($name, 'PRIX EN') === 0) {
            $map['price_eur'] = $index;
        } elseif (strpos($name, 'PRIX TOTAL') !== false) {
            $map['total_usd'] = $index;
        } elseif (strpos($name, 'COMMISSION') !== false) {
            $map['commission'] = $index;
        } elseif (strpos($name, 'TOTAL TTC') !== false) {
            $map['total_ttc'] = $index;
        } elseif (strpos($name, 'DA TAUX') !== false) {
            $map['price_dzd'] = $index;
        } elseif (strpos($name, 'TTC EN MILLIONS') !== false) {
            $map['price_millions'] = $index;
        } elseif (strpos($name, 'DEDOUANEMENT') !== false) {
            $map['customs_estimate'] = $index;
        } elseif (strpos($name, 'LIEN') !== false) {
            $map['link'] = $index;
        }
    }

    return $map;
}

function aae_csv_cell($cells, $map, $key)
{
    if (! isset($map[$key])) {
        return '';
    }

    $index = $map[$key];

    return isset($cells[$index]) ? trim((string) $cells[$index]) : '';
}

/**
 * Parse a supplier CSV file.
 *
 * @param string $path
 *
 * @return array
 */
function aae_parse_csv_file($path)
{
    if (! is_readable($path)) {
        return ['error' => _l('aae_import_unreadable')];
    }

    $content = file_get_contents($path);

    if ($content === false || trim($content) === '') {
        return ['error' => _l('aae_import_empty_file')];
    }

    // UTF-16 exports.
    if (substr($content, 0, 2) === "\xFF\xFE") {
        $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
    } elseif (substr($content, 0, 2) === "\xFE\xFF") {
        $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
    }

    // UTF-8 BOM.
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }

    // Excel on Windows often exports Windows-1252.
    if (! mb_check_encoding($content, 'UTF-8')) {
        $converted = @mb_convert_encoding($content, 'UTF-8', 'Windows-1252');

        if ($converted !== false) {
            $content = $converted;
        }
    }

    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $content);
    rewind($handle);

    // Detect the delimiter from the first non-empty line.
    $firstLine = '';
    while (($line = fgets($handle)) !== false) {
        if (trim($line) !== '') {
            $firstLine = $line;
            break;
        }
    }
    rewind($handle);

    $delimiter = ',';
    if ($firstLine !== '') {
        $counts = [
            ','  => substr_count($firstLine, ','),
            ';'  => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($counts);
        $delimiter = key($counts);
    }

    $header    = null;
    $map       = null;
    $rows      = [];
    $lineNo    = 0;
    $junkLines = 0;

    while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
        $lineNo++;

        if ($cells === [null] || (count($cells) === 1 && trim((string) $cells[0]) === '')) {
            continue;
        }

        if ($header === null) {
            if (aae_csv_is_header($cells)) {
                $header = $cells;
                $map    = aae_csv_map_header($cells);

                continue;
            }

            $junkLines++;

            if ($junkLines > 30) {
                break;
            }

            continue;
        }

        $rows[] = [
            'line'  => $lineNo,
            'cells' => $cells,
        ];
    }

    fclose($handle);

    if ($header === null || empty($map)) {
        return ['error' => _l('aae_import_no_header')];
    }

    foreach (['brand', 'model', 'fob_price'] as $required) {
        if (! isset($map[$required])) {
            return ['error' => _l('aae_import_missing_columns')];
        }
    }

    return [
        'rows'      => $rows,
        'header'    => $header,
        'map'       => $map,
        'delimiter' => $delimiter,
    ];
}

/**
 * Fetch published vehicle links from the Algérie Auto Export storefront.
 */
function aae_fetch_storefront_cars()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $url = getenv('AAE_STOREFRONT_CAR_LINKS_URL') ?: 'http://localhost/algerie-autoexport/aae/car-links';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || ! $raw) {
        $cached = [];
        return $cached;
    }

    $decoded = json_decode($raw, true);
    $cached = is_array($decoded['cars'] ?? null) ? $decoded['cars'] : [];
    return $cached;
}

/**
 * Match a Fennec Dash vehicle (brand + model) to its exact Algérie Auto Export URL.
 */
function aae_match_storefront_url($brand, $model, $storefrontCars = null)
{
    if ($storefrontCars === null) {
        $storefrontCars = aae_fetch_storefront_cars();
    }
    if (empty($storefrontCars) || trim((string) $model) === '') {
        return '';
    }

    $normalize = static function ($s) {
        $s = mb_strtolower((string) $s, 'UTF-8');
        return trim(preg_replace('/[^a-z0-9]+/u', ' ', $s));
    };

    $targetModel = $normalize($model);
    $targetFull = $normalize(trim($brand . ' ' . $model));

    foreach ($storefrontCars as $car) {
        $carNorm = $normalize($car['name'] ?? '');
        if ($carNorm === '') {
            continue;
        }
        if ($carNorm === $targetFull || $carNorm === $targetModel || ($targetModel !== '' && strpos($carNorm, $targetModel) !== false)) {
            return (string) ($car['url'] ?? '');
        }
    }

    $tokens = array_values(array_unique(array_filter(explode(' ', $targetFull), static function ($t) {
        return strlen($t) >= 2;
    })));
    $bestUrl = '';
    $bestScore = 0.0;
    foreach ($storefrontCars as $car) {
        $carNorm = $normalize($car['name'] ?? '');
        if ($carNorm === '' || empty($tokens)) {
            continue;
        }
        $hits = 0;
        foreach ($tokens as $tok) {
            if (strpos($carNorm, $tok) !== false) {
                $hits++;
            }
        }
        $score = $hits / count($tokens);
        if ($hits >= 2 && $score >= 0.65 && $score > $bestScore) {
            $bestScore = $score;
            $bestUrl = (string) ($car['url'] ?? '');
        }
    }

    return $bestUrl;
}

/**
 * Push a vehicle price update to the Algérie Auto Export storefront.
 */
function aae_sync_storefront_price($brand, $model, $price)
{
    $price = (float) $price;
    if ($price <= 0 || trim((string) $model) === '') {
        return false;
    }

    $url = getenv('AAE_STOREFRONT_PRICE_SYNC_URL') ?: 'http://localhost/algerie-autoexport/aae/sync-car-price';
    $secret = getenv('AAE_WEBHOOK_SECRET') ?: 'aae-fennec-dash-2026';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-AAE-Webhook-Secret: ' . $secret,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'brand' => (string) $brand,
            'model' => (string) $model,
            'sale_price' => $price,
        ]),
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 300;
}

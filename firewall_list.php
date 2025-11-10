<?php
require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");

/* --------------------------------------------------------------
   1. Load data
   -------------------------------------------------------------- */
$iflist   = filter_get_interface_list();      // logical → friendly (WAN, LAN, OPT34…)
$gateways = return_gateways_array();          // gateway system name → details
$rules    = config_get_path('filter/rule', []);

/* --------------------------------------------------------------
   2. Build display string: "Alias (logical)" or "Friendly (logical)"
   -------------------------------------------------------------- */
$iface_display = [];
foreach ($iflist as $logical => $friendly) {
    $alias = config_get_path("interfaces/{$logical}/descr") ?? '';
    if ($alias) {
        $iface_display[$logical] = "$alias ($logical)";
    } else {
        $iface_display[$logical] = "$friendly ($logical)";
    }
}
$iface_display['FloatingRules'] = 'Floating Rules';

/* --------------------------------------------------------------
   3. Group gateways per interface
   -------------------------------------------------------------- */
$iface_gateways = [];

foreach ($rules as $r) {
    $iface = $r['interface'] ?? null;
    if (isset($r['floating']) && $r['floating']) {
        $iface = 'FloatingRules';
    }
    if (!$iface) continue;

    $gw_raw = $r['gateway'] ?? null;
    $display = $iface_display[$iface] ?? $iface;

    if (!isset($iface_gateways[$display])) {
        $iface_gateways[$display] = ['gateways' => []];
    }

    if ($gw_raw) {
        // Prefer user description
        $gw_name = $gateways[$gw_raw]['descr'] ?? null;

        // Strip default "Interface X Gateway" text
        if (!$gw_name || stripos($gw_name, 'Interface ') === 0) {
            $gw_name = $gateways[$gw_raw]['name'] ?? $gw_raw;
        }
    } else {
        $gw_name = '(default)';
    }

    $iface_gateways[$display]['gateways'][$gw_name] = true;
}
include("head.inc");
/* --------------------------------------------------------------
   4. Render table
   -------------------------------------------------------------- */
?>
<div class="panel panel-default" style="margin-top:2rem;">
    <div class="panel-heading">
        <h2 class="panel-title">Gateway Usage per Interface</h2>
    </div>
    <div class="panel-body table-responsive">
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>Interface (Alias)</th>
                    <th>Gateways Used in Rules</th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($iface_gateways as $display => $data): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($display) ?></strong></td>
                    <td><?= htmlspecialchars(implode(', ', array_keys($data['gateways']))) ?></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include("foot.inc");?>

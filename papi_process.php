<?php
/*
================================================================================
 *  BISMILLAAHIRRAHMAANIRRAHIIM - In the Name of Allah, Most Gracious, Most Merciful
================================================================================
FILENAME     : papi_process.php
AUTHOR       : CAHYA DSN
CREATED DATE : 2017-04-09
UPDATED DATE : 2026-07-12
DEMO SITE    : http://psycho.cahyadsn.com/papi
SOURCE CODE  : https://github.com/cahyadsn/papi
================================================================================
*/
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta http-equiv="content-language" content="id" />
  <meta name="author"      content="Cahya DSN" />
  <meta name="viewport"    content="width=device-width, initial-scale=1, user-scalable=no" />
  <meta name="keywords"    content="Psikotest, PAPI Kostick, test, psychology" />
  <meta name="description" content="PAPI Kostick Test v1.0 — Personality and Preference Inventory (PHP + MySQL)" />
  <meta name="robots"      content="index, follow" />
  <meta http-equiv="expires"       content="<?php echo date('r'); ?>" />
  <meta http-equiv="pragma"        content="no-cache" />
  <meta http-equiv="cache-control" content="no-cache" />
  <title>PAPI Kostick — Hasil</title>
  <link rel="stylesheet" href="css/glass.css" />
</head>
<body>

<div class="glass-page">
  <div class="glass-card">

    <!-- Header -->
    <header class="glass-header">
      <span class="header-icon">📊</span>
      <h1>PAPI KOSTICK — HASIL</h1>
    </header>

    <!-- Result Section -->
    <div class="glass-body" id="result">
      <h2>Interpretasi Hasil</h2>

      <table class="result-table">
        <thead>
          <tr>
            <th style="width:48px;">No</th>
            <th>Aspek</th>
            <th>Peran (Role)</th>
            <th>Interpretasi</th>
          </tr>
        </thead>
        <tbody>
<?php
if (!empty($_POST['s'])) {
    include 'inc/db.php';
    require_once 'inc/score.php';

    $rules_by_role = [];
    $data = aggregate_scores($_POST['s']);

    $sql = "SELECT b.id, a.low_value, a.high_value,
                   c.aspect, b.role, a.interprestation
            FROM   papi_rules  a
            JOIN   papi_roles   b ON b.id   = a.role_id
            JOIN   papi_aspects c ON c.id   = b.aspect_id";

    if ($rst = $db->query($sql)) {
        while ($obj = $rst->fetch_object()) {
            $rules_by_role[$obj->id][] = $obj;
        }
    }

    $results = build_results($data, $rules_by_role);
    $no      = 0;
    $aspect  = '';

    foreach ($results as $row) {
        if ($aspect !== $row['aspect']) {
            $aspect = $row['aspect'];
            echo "<tr class='aspect-row'>";
            echo "  <td>" . (++$no) . "</td>";
            echo "  <td colspan='3'>" . htmlspecialchars($aspect) . "</td>";
            echo "</tr>";
        }
        echo "<tr>";
        echo "  <td></td>";
        echo "  <td>&nbsp;</td>";
        echo "  <td colspan='2'>" . htmlspecialchars($row['role']) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "  <td></td>";
        echo "  <td>&nbsp;</td>";
        echo "  <td>&nbsp;</td>";
        echo "  <td class='interp'>— " . htmlspecialchars($row['interprestation']) . "</td>";
        echo "</tr>";
    }
}
?>
        </tbody>
      </table>

      <div class="btn-row center" style="margin-top:28px;">
        <a href="index.php" class="btn btn-primary">&larr; Ulangi Tes</a>
      </div>
    </div><!-- /result -->

    <!-- Footer -->
    <footer class="glass-footer">
      copyright &copy; <?php echo date('Y'); ?> by
      <a href="mailto:cahyadsn@gmail.com">cahyadsn</a>
    </footer>

  </div><!-- /glass-card -->
</div><!-- /glass-page -->

</body>
</html>

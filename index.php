<?php
/*
================================================================================
 *  BISMILLAAHIRRAHMAANIRRAHIIM - In the Name of Allah, Most Gracious, Most Merciful
================================================================================
FILENAME     : index.php
AUTHOR       : CAHYA DSN
CREATED DATE : 2017-04-09
UPDATED DATE : 2026-07-12
DEMO SITE    : http://psycho.cahyadsn.com/papi
SOURCE CODE  : https://github.com/cahyadsn/papi
================================================================================
*/
session_start();

$view_num   = 5;
$total_page = ceil(90 / $view_num);

if (!isset($_SESSION['papiq'])) {
    include 'inc/db.php';
    $result = $db->query('SELECT * FROM papi_questions');
    $no     = 0;
    $data   = [];
    foreach ($result as $r) {
        $data[++$no] = [$r['value1'], $r['question1'], $r['value2'], $r['question2']];
    }
    $_SESSION['papiq'] = $data;
    unset($data);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
  <meta name="author"      content="Cahya DSN" />
  <meta name="keywords"    content="Psikotest, PAPI Kostick, personality test, psikologi" />
  <meta name="description" content="PAPI Kostick Test — Personality and Preference Inventory (PHP + MySQL)" />
  <title>PAPI Kostick Test</title>
  <link rel="stylesheet" href="css/glass.css" />
</head>
<body>

<div class="glass-page">
  <div class="glass-card">

    <!-- Header -->
    <header class="glass-header">
      <span class="header-icon">🧠</span>
      <h1>PAPI KOSTICK</h1>
    </header>

    <!-- Global alert (hidden by default) -->
    <div class="glass-body" id="alert-wrap" style="padding-bottom:0;display:none;">
      <div class="glass-alert visible" id="alert">
        <div>
          <strong>⚠ Peringatan!</strong>
          <span id="msg"></span>
        </div>
        <button class="alert-close" onclick="document.getElementById('alert-wrap').style.display='none';">&times;</button>
      </div>
    </div>

    <!-- Dynamic Content -->
    <div id="dynamic_content">

      <!-- PAGE 1 — Introduction -->
      <div class="glass-body" id="page1">
        <h2>Tentang PAPI Kostick</h2>
        <p>
          <b>PAPI (<i>Personality and Preference Inventory</i>)</b> adalah <i>personality assessment</i>
          atau alat tes penilaian kepribadian terkemuka yang digunakan oleh para profesional HR
          (<i>Human Resource</i>) dan manajer terkait untuk mengevaluasi perilaku dan gaya kerja
          individu pada semua tingkatan.
        </p>
        <p>
          PAPI dibuat oleh Guru Besar Psikologi Industri dari Massachusetts, Amerika, yang bernama
          <b>Dr. Max Martin Kostick</b> pada awal tahun 1960-an. Versi ini diperkenalkan pada tahun
          1997 dengan versi <i>ipsatif</i> (PAPI-I) dan <i>normatif</i> (PAPI-N). Dasar pemikiran
          desain PAPI didasarkan pada teori kepribadian "<i>needs-press</i>" oleh Murray (1938).
        </p>
        <div class="btn-row center">
          <a href="#" class="btn btn-primary" onclick="next(2); return false;">Lanjut &rarr;</a>
        </div>
      </div>

      <!-- PAGE 2 — Instructions -->
      <div class="glass-body" id="page2" style="display:none;">
        <h2>Petunjuk Pengisian</h2>
        <ul>
          <li>Tes ini terdiri dari <b>90 pasang pernyataan</b> yang berhubungan dengan situasi kerja Saudara.</li>
          <li>Dari setiap pasang pernyataan, pilih <b>satu</b> yang paling menggambarkan diri Saudara.</li>
          <li>Jika keduanya sangat sesuai, tetap pilih yang <em>paling</em> sesuai.</li>
          <li>Jika keduanya tidak sesuai, tetap pilih yang <em>paling mendekati</em> kondisi diri Saudara.</li>
          <li>Jawab dengan <b>jujur</b> — tidak ada jawaban benar atau salah.</li>
          <li>Kerjakan <b>secepat-cepatnya</b> namun tetap teliti. Jangan ada yang kosong atau dobel.</li>
        </ul>
        <div class="btn-row center">
          <a href="#" class="btn btn-primary" onclick="next(3); return false;">Mulai Tes &rarr;</a>
        </div>
      </div>

      <!-- PAGE 3 — Questions Form -->
      <div class="glass-body" id="page3" style="display:none;">
        <form method="post" action="papi_process.php" id="frm" onsubmit="return check();">

          <!-- Alert inside form (same element, re-used by JS) -->
          <div id="alert-form-wrap" style="display:none; margin-bottom:16px;">
            <div class="glass-alert visible" id="alert-form">
              <div>
                <strong>⚠ Peringatan!</strong>
                <span id="msg-form"></span>
              </div>
              <button type="button" class="alert-close"
                onclick="document.getElementById('alert-form-wrap').style.display='none';">&times;</button>
            </div>
          </div>

          <!-- Progress bar -->
          <div class="progress-wrap">
            <div class="progress-label" id="progress-label">Bagian 1 / <?php echo $total_page; ?></div>
            <div class="progress-track">
              <div class="progress-fill" id="progress-fill" style="width:<?php echo round(100/$total_page); ?>%;"></div>
            </div>
          </div>

          <?php
          $x = 0;
          foreach ($_SESSION['papiq'] as $no => $data) {
              if (($no - 1) % $view_num === 0 || $no === 1) {
                  if ($no > 1) echo '</tbody></table>';
                  $hidden = ($no !== 1) ? ' style="display:none;"' : '';
                  echo "<table class='q-table' id='t{$x}'{$hidden}>";
                  echo "<thead><tr><th>No</th><th colspan='2'>Pernyataan</th></tr></thead><tbody>";
                  $x++;
              }
              echo "
              <tr class='q-sep'>
                <td class='q-num' rowspan='2'>[{$no}]</td>
                <td class='q-radio'><input type='radio' id='s_{$no}_0' name='s[{$no}]' value='{$data[0]}'></td>
                <td>{$data[1]}</td>
              </tr>
              <tr>
                <td class='q-radio'><input type='radio' id='s_{$no}_1' name='s[{$no}]' value='{$data[2]}'></td>
                <td>{$data[3]}</td>
              </tr>";
          }
          echo '</tbody></table>';
          ?>

          <!-- Navigation buttons -->
          <div class="btn-row" style="padding-top:20px; border-top: 1px solid rgba(255,255,255,0.08); margin-top:16px;">
            <button type="button" onclick="trans(-1);" id="prev" class="btn btn-primary" disabled>
              &larr; Prev
            </button>
            <button type="button" onclick="trans(1);"  id="next" class="btn btn-primary">
              Next &rarr;
            </button>
            <button type="submit" id="submit" class="btn btn-success hidden">
              ✔ Submit
            </button>
          </div>

        </form>
      </div><!-- /page3 -->

    </div><!-- /dynamic_content -->

    <!-- Footer -->
    <footer class="glass-footer">
      copyright &copy; <?php echo date('Y'); ?> by
      <a href="mailto:cahyadsn@gmail.com">cahyadsn</a>
    </footer>

  </div><!-- /glass-card -->
</div><!-- /glass-page -->

<script src="js/util.php?total_page=<?php echo $total_page; ?>"></script>
</body>
</html>

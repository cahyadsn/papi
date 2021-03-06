<?php
/*
================================================================================
 *  BISMILLAAHIRRAHMAANIRRAHIIM - In the Name of Allah, Most Gracious, Most Merciful
================================================================================
FILENAME     : papi_process.php
AUTHOR       : CAHYA DSN
CREATED DATE : 2017-04-09
UPDATED DATE : 2021-03-06
DEMO SITE    : http://psycho.cahyadsn.com/papi
SOURCE CODE  : https://github.com/cahyadsn/papi
================================================================================
This program is free software; you can redistribute it and/or modify it under the
terms of the MIT License.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

See the MIT License for more details

copyright (c) 2017-2021 by cahya dsn; cahyadsn@gmail.com
================================================================================ */
?>
<!DOCTYPE html><html><head><title>Prototype PAPIKostick Test</title><meta charset="utf-8" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><meta http-equiv="content-language" content="en" /><meta name="author" content="Cahya DSN" /><meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no" /><meta name="keywords" content="Psikotest, PAPI kostick, test, psychology" /><meta name="description" content="PAPI Kostick Test v1.0 Personality and Preference Inventory Test using PHP and MySQL by Cahya DSN" /><meta name="robots" content="index, follow" /><meta http-equiv="expires" content="<?php echo date('r');?>" /><meta http-equiv="pragma" content="no-cache" /><meta http-equiv="cache-control" content="no-cache" /><link rel="stylesheet" href="css/w3.css"></head><body><div class="w3-container w3-padding-16"><div class="w3-card-4"><header class="w3-container w3-blue"><h1>PAPI KOSTICK TEST</h1></header><div class="w3-container" id="result"><h3>Interprestation Result</h3><table class="w3-table-all"><tr class='w3-theme-d1'><th>No</th><th>ASPECT</th><th>ROLE</th><th>RESULT</th></tr><?php if($_POST['s']){ include 'inc/db.php';$data=array();foreach($_POST['s'] as $k=>$v){if(!isset($data[$v])) $data[$v]=0;$data[$v]+=1;} $values=array();foreach($data as $k=>$v) $values[]="({$k},{$v})"; $sql="TRUNCATE TABLE papi_results;INSERT INTO papi_results(role_id,value) VALUES".implode(',',$values).";SELECT c.aspect, b.role, a.interprestation FROM papi_rules a JOIN papi_roles b ON b.id=a.role_id	JOIN papi_aspects c ON c.id=b.aspect_id JOIN papi_results d ON d.role_id=b.id WHERE d.value BETWEEN a.low_value AND a.high_value ORDER BY c.id,b.id;";if ($db->multi_query($sql)) { do {if ($result = $db->store_result()){ if(is_object($result)){$aspect='';$no=0;foreach($result as $r){if($aspect!=$r['aspect']){$aspect=$r['aspect'];echo "<tr><td>".(++$no)."</td><td colspan='3'><b>{$aspect}</b></td></tr>";} echo "<tr><td></td><td>&nbsp;</td><td colspan='2'>{$r['role']}</td></tr><tr><td></td><td>&nbsp</td><td>&nbsp</td><td>-{$r['interprestation']}</td></tr>";}}$result->free();}if (!$db->more_results()) break;} while ($db->next_result());}}?></table></div><footer class="w3-container w3-indigo" style="text-align:center;"><h5>copyright &copy; <?php echo date('Y');?> by <a href='mailto:cahyadsn@gmail.com'>cahyadsn</a></h5></footer></div></div></body></head>

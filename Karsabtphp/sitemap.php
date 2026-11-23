<?php
require __DIR__.'/includes/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
$base=rtrim($config['domain'],'/');
$urls=[['/','weekly','1.0'],['/?page=company','monthly','0.8'],['/?page=brand','monthly','0.8'],['/?page=changes','monthly','0.7'],['/?page=licenses','monthly','0.8'],['/?page=limited','monthly','0.7'],['/?page=private','monthly','0.7'],['/?page=fees','monthly','0.6'],['/?page=contact','monthly','0.5'],['/maghale','weekly','0.8']];
$articles=public_articles(); foreach(array_keys($articles) as $slug)$urls[]=['/maghale/'.rawurlencode($slug),'monthly','0.6'];
$lastmods=[]; $db=karsabt_public_db();
if($db){try{foreach($db->query('SELECT page_key,updated_at FROM service_pages') as $r)$lastmods[$r['page_key']]=$r['updated_at'];}catch(Throwable $e){error_log('Karsabt sitemap: '.$e->getMessage());}}
$lastmods['home']=date('Y-m-d');
$pageKeys=['/'=>'home','/?page=company'=>'company','/?page=brand'=>'brand','/?page=changes'=>'changes','/?page=licenses'=>'licenses','/?page=limited'=>'limited','/?page=private'=>'private','/?page=fees'=>'fees','/?page=contact'=>'contact'];
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach($urls as [$path,$freq,$priority]){$loc=$base.($path==='/'?'/':$path);$last=$lastmods[$pageKeys[$path]??'']??date('Y-m-d');echo '<url><loc>'.htmlspecialchars($loc,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc><lastmod>'.htmlspecialchars(date('Y-m-d',strtotime($last)?:time()),ENT_XML1|ENT_QUOTES,'UTF-8').'</lastmod><changefreq>'.$freq.'</changefreq><priority>'.$priority.'</priority></url>';}
echo '</urlset>';

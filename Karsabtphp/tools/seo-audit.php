<?php
// Run from CLI: php tools/seo-audit.php. Lightweight SEO structure checks.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../includes/functions.php';
function seo_chars(string $text): int { return function_exists('mb_strlen') ? mb_strlen($text,'UTF-8') : (preg_match_all('/./us',$text,$m) ?: 0); }
$checks=[];
foreach($pages as $key=>$meta){
  $checks[]=['Page'=>$key,'Title chars'=>seo_chars($meta['title']),'Description chars'=>seo_chars($meta['description']),'Canonical path'=>$meta['path'],'Title status'=>seo_chars($meta['title'])>=20&&seo_chars($meta['title'])<=65?'OK':'Review','Description status'=>seo_chars($meta['description'])>=70&&seo_chars($meta['description'])<=170?'OK':'Review'];
}
echo "Karsabt SEO audit (basic metadata)\n";
foreach($checks as $c){printf("%-10s title=%3d desc=%3d | %s | %s\n",$c['Page'],$c['Title chars'],$c['Description chars'],$c['Title status'],$c['Description status']);}
echo "\nNote: This script does not replace Search Console, real crawl, Core Web Vitals, or legal content review.\n";

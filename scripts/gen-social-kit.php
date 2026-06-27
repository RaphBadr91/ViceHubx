<?php
/**
 * ViceHub X — Kit de templates réseaux (Instagram / Facebook / TikTok-Reels).
 * Produit des CADRES PNG à superposer sur tes vidéos/visuels Higgsfield :
 *  - centre transparent (tu déposes ta vidéo/photo derrière)
 *  - barres de marque (logo, catégorie, titre, @handle, CTA) façon GTA VI
 * + des exemples « remplis » pour voir le rendu.
 *   Usage : php scripts/gen-social-kit.php
 *   Sortie : public/assets/img/brand/kit/
 */
$ROOT = dirname(__DIR__);
$KIT  = $ROOT . '/public/assets/img/brand/kit';
@mkdir($KIT, 0775, true);

$FONT  = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
$FONTI = '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf';
if (!is_file($FONT))  { $FONT  = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'; }
if (!is_file($FONTI)) { $FONTI = $FONT; }
$SERIF = '/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf';
if (!is_file($SERIF)) { $SERIF = $FONT; }

function cc($im,$r,$g,$b,$a=0){ return imagecolorallocatealpha($im,(int)max(0,min(255,$r)),(int)max(0,min(255,$g)),(int)max(0,min(255,$b)),(int)max(0,min(127,$a))); }
function clampf($v,$lo,$hi){ return max($lo,min($hi,$v)); }
function stopColor($stops,$t){ $t=clampf($t,0,1); $n=count($stops);
    if($t<=$stops[0][0])return $stops[0][1]; if($t>=$stops[$n-1][0])return $stops[$n-1][1];
    for($i=0;$i<$n-1;$i++){ [$p0,$c0]=$stops[$i]; [$p1,$c1]=$stops[$i+1];
        if($t>=$p0&&$t<=$p1){ $f=($t-$p0)/max(1e-6,$p1-$p0); return [$c0[0]+($c1[0]-$c0[0])*$f,$c0[1]+($c1[1]-$c0[1])*$f,$c0[2]+($c1[2]-$c0[2])*$f]; } }
    return $stops[$n-1][1]; }
function tW($size,$font,$text){ $b=imagettfbbox($size,0,$font,$text); return abs($b[2]-$b[0]); }

/* Texte glossy dégradé (signature GTA VI). */
function gtaText($im,$font,$size,$x,$y,$text,$stops,$glow){
    for($i=0;$i<5;$i++){ $g=cc($im,$glow[0],$glow[1],$glow[2],(int)clampf(96-$i*9,50,120));
        foreach([[-3,0],[3,0],[0,-3],[0,3],[2,2],[-2,-2]] as $o) imagettftext($im,$size,0,$x+$o[0],$y+$o[1],$g,$font,$text); }
    $dk=cc($im,38,8,40);
    foreach([[-2,0],[2,0],[0,-2],[0,2]] as $o) imagettftext($im,$size,0,$x+$o[0],$y+$o[1],$dk,$font,$text);
    $b=imagettfbbox($size,0,$font,$text); $tw=abs($b[2]-$b[0]); $th=abs($b[7]-$b[1]); $pad=(int)($size*0.55);
    $w=$tw+$pad*2; $h=$th+$pad*2; $L=imagecreatetruecolor($w,$h); imagesavealpha($L,true); imagealphablending($L,false);
    imagefilledrectangle($L,0,0,$w,$h,cc($L,0,0,0,127)); imagealphablending($L,true);
    $by=$pad+$th; imagettftext($L,$size,0,$pad,$by,cc($L,255,255,255),$font,$text); imagealphablending($im,true);
    for($yy=0;$yy<$h;$yy++){ $t=clampf(($yy-$pad)/max(1,$th),0,1); $c=stopColor($stops,$t);
        $gl=exp(-pow($t-0.30,2)/(2*0.01))*0.5; $r=$c[0]+(255-$c[0])*$gl; $g=$c[1]+(255-$c[1])*$gl; $bl=$c[2]+(255-$c[2])*$gl;
        $dy=$y-$by+$yy; if($dy<0||$dy>=imagesy($im))continue;
        for($xx=0;$xx<$w;$xx++){ $a=(imagecolorat($L,$xx,$yy)>>24)&0x7F; if($a>=120)continue;
            $dx=$x+$xx-$pad; if($dx<0||$dx>=imagesx($im))continue; $al=(127-$a)/127;
            $ex=imagecolorat($im,$dx,$dy); $er=($ex>>16)&255; $eg=($ex>>8)&255; $eb=$ex&255;
            imagesetpixel($im,$dx,$dy,cc($im,$er*(1-$al)+$r*$al,$eg*(1-$al)+$g*$al,$eb*(1-$al)+$bl*$al)); } }
    imagedestroy($L); return $tw;
}
function rrect($im,$x1,$y1,$x2,$y2,$r,$c){
    imagefilledrectangle($im,$x1+$r,$y1,$x2-$r,$y2,$c); imagefilledrectangle($im,$x1,$y1+$r,$x2,$y2-$r,$c);
    imagefilledellipse($im,$x1+$r,$y1+$r,$r*2,$r*2,$c); imagefilledellipse($im,$x2-$r,$y1+$r,$r*2,$r*2,$c);
    imagefilledellipse($im,$x1+$r,$y2-$r,$r*2,$r*2,$c); imagefilledellipse($im,$x2-$r,$y2-$r,$r*2,$r*2,$c);
}
/* Fades sombres haut/bas pour lisibilité sur n'importe quelle vidéo. */
function fadeTop($im,$w,$ph,$maxA){ for($y=0;$y<$ph;$y++){ $a=(int)(127-(1-$y/$ph)*(127-$maxA)); imagefilledrectangle($im,0,$y,$w,$y,cc($im,8,5,18,clampf($a,$maxA,127))); } }
function fadeBottom($im,$w,$h,$ph,$maxA){ for($y=$h-$ph;$y<$h;$y++){ $t=($y-($h-$ph))/$ph; $a=(int)(127-$t*(127-$maxA)); imagefilledrectangle($im,0,$y,$w,$y,cc($im,8,5,18,clampf($a,$maxA,127))); } }

$G=[[0.0,[255,236,179]],[0.32,[255,138,170]],[0.66,[255,46,136]],[1.0,[182,38,140]]];   // rose→jaune (titre marque)
$GX=[[0.0,[198,245,255]],[0.5,[64,200,255]],[1.0,[40,120,235]]];                          // X cyan
$CATS=[ 'NEWS'=>[255,46,136], 'LEAK'=>[43,214,255], 'GUIDE'=>[57,255,170], 'HOT'=>[255,140,40], 'DECRYPTAGE'=>[170,90,255] ];

/* Petit logo VICEHUB X (gradient) centré horizontalement à y. */
function brandMark($im,$cx,$y,$size,$G,$GX,$FONT,$FONTI){
    $wW=tW($size,$FONT,'VICEHUB'); $gap=(int)($size*0.14); $wX=tW($size*1.12,$FONTI,'X');
    $x=(int)($cx-($wW+$gap+$wX)/2);
    gtaText($im,$FONT,$size,$x,$y,'VICEHUB',$G,[255,46,136]);
    gtaText($im,$FONTI,$size*1.12,$x+$wW+$gap,$y+(int)($size*0.04),'X',$GX,[43,170,255]);
}

/* Cadre transparent : barres de marque par-dessus la vidéo/photo. */
function overlay($w,$h,$cat,$opts=[]){
    global $G,$GX,$CATS,$FONT,$FONTI;
    $im=imagecreatetruecolor($w,$h); imagesavealpha($im,true); imagealphablending($im,false);
    imagefilledrectangle($im,0,0,$w,$h,cc($im,0,0,0,127)); imagealphablending($im,true);
    $isTall=$h/$w>1.4;
    $topH=(int)($h*($isTall?0.16:0.20)); $botH=(int)($h*($isTall?0.26:0.30));
    fadeTop($im,$w,$topH,80); fadeBottom($im,$w,$h,$botH,76);
    // logo en haut
    brandMark($im,(int)($w/2),(int)($topH*0.62),$isTall?44:46,$G,$GX,$FONT,$FONTI);
    $tag='G T A   V I   ·   F A N   H U B'; $ts=18; $tw=tW($ts,$FONT,$tag);
    imagettftext($im,$ts,0,(int)($w/2-$tw/2),(int)($topH*0.62)+44,cc($im,255,209,120),$FONT,$tag);
    // badge catégorie (haut gauche)
    $col=$CATS[$cat]??$CATS['NEWS']; $bs=30; $bw=tW($bs,$FONT,$cat); $bx=44; $by=(int)($topH*0.30);
    rrect($im,$bx,$by,$bx+$bw+44,$by+58,12,cc($im,$col[0],$col[1],$col[2]));
    imagettftext($im,$bs,0,$bx+22,$by+41,cc($im,12,6,18),$FONT,$cat);
    // bloc bas : titre + handle + CTA
    $tx=56; $tBase=$h-$botH+(int)($botH*0.34);
    $title=$opts['title']??'TON TITRE ACCROCHEUR ICI';
    $size=$isTall?54:46; imagettftext($im,$size,0,$tx,$tBase,cc($im,255,255,255),$FONT,$title);
    imagettftext($im,$size,0,$tx,$tBase+(int)($size*1.25),cc($im,255,255,255),$FONT,$opts['title2']??'(2e ligne ici)');
    // accent line
    rrect($im,$tx,$tBase+(int)($size*1.6),$tx+120,$tBase+(int)($size*1.6)+8,4,cc($im,$col[0],$col[1],$col[2]));
    // handle + CTA
    imagettftext($im,26,0,$tx,$h-72,cc($im,255,255,255),$FONT,'@vicehubx  ·  vicehubx.fr');
    imagettftext($im,22,0,$tx,$h-36,cc($im,255,209,120),$FONT,$opts['cta']??'> TOUTE L\'ACTU GTA VI');
    return $im;
}

/* Fond coucher de soleil (exemples remplis). */
function sunset($w,$h){
    $im=imagecreatetruecolor($w,$h); imagealphablending($im,true);
    $stops=[[0.0,[18,16,58]],[0.18,[40,18,82]],[0.40,[96,28,104]],[0.56,[190,46,108]],[0.63,[255,118,72]],[0.70,[120,34,72]],[0.86,[22,12,38]],[1.0,[7,5,15]]];
    for($y=0;$y<$h;$y++){ $c=stopColor($stops,$y/$h); imagefilledrectangle($im,0,$y,$w,$y,cc($im,$c[0],$c[1],$c[2])); }
    $cx=(int)($w/2); $cy=(int)($h*0.5); $sr=(int)(min($w,$h)*0.14);
    for($i=(int)($sr*2.4);$i>0;$i-=3){ $a=clampf(86-($sr*2.4-$i)*0.55,46,96); imagefilledellipse($im,$cx,$cy,(int)($i*1.5),(int)($i*1.5),cc($im,255,140,72,(int)$a)); }
    for($y=-$sr;$y<=$sr;$y++){ $t=($y+$sr)/(2*$sr); $half=(int)sqrt(max(0,$sr*$sr-$y*$y)); imagefilledrectangle($im,$cx-$half,$cy+$y,$cx+$half,$cy+$y,cc($im,255,228*(1-$t)+95*$t,95*(1-$t)+150*$t)); }
    return $im;
}
function compose($base,$ov){ imagecopy($base,$ov,0,0,0,0,imagesx($ov),imagesy($ov)); return $base; }

$made=[];
/* --- TikTok / Reels / Stories 9:16 — 3 catégories --- */
foreach(['NEWS','LEAK','GUIDE'] as $c){ $f="$KIT/reel-9x16-".strtolower($c).".png"; imagepng(overlay(1080,1920,$c),$f); $made[]=$f; }
imagepng(compose(sunset(1080,1920),overlay(1080,1920,'NEWS',['title'=>'GTA VI : LES','title2'=>'PRÉCOMMANDES !'])),"$KIT/reel-9x16-exemple.png"); $made[]="$KIT/reel-9x16-exemple.png";

/* --- Instagram carré 1:1 --- */
imagepng(overlay(1080,1080,'NEWS'),"$KIT/ig-square.png"); $made[]="$KIT/ig-square.png";
imagepng(compose(sunset(1080,1080),overlay(1080,1080,'LEAK',['title'=>'CE LEAK EST','title2'=>'UN FAKE ?'])),"$KIT/ig-square-exemple.png"); $made[]="$KIT/ig-square-exemple.png";

/* --- Instagram portrait 4:5 (nouveau format) 1080x1350 --- */
imagepng(overlay(1080,1350,'NEWS'),"$KIT/ig-portrait-4x5.png"); $made[]="$KIT/ig-portrait-4x5.png";
imagepng(compose(sunset(1080,1350),overlay(1080,1350,'GUIDE',['title'=>'STANDARD OU','title2'=>'ULTIMATE ?'])),"$KIT/ig-portrait-4x5-exemple.png"); $made[]="$KIT/ig-portrait-4x5-exemple.png";

/* --- Facebook carré 1:1 (réutilise le carré, libellé FB) --- */
imagepng(overlay(1080,1080,'HOT'),"$KIT/fb-square.png"); $made[]="$KIT/fb-square.png";

/* --- Notice d'utilisation --- */
file_put_contents("$KIT/LISEZ-MOI.txt",
"KIT TEMPLATES VICEHUB X (style GTA VI)\n".
"=====================================\n\n".
"Les fichiers '*-overlay' (reel-9x16-*, ig-square, ig-portrait-4x5, fb-square) ont un CENTRE TRANSPARENT.\n".
"Dans Canva / CapCut / InShot : mets ta VIDEO ou ta PHOTO en fond, puis pose le PNG du template par-dessus.\n".
"Les barres (logo, catégorie, titre, @vicehubx, CTA) s'affichent par-dessus ta vidéo.\n\n".
"Zones de texte à remplacer (par-dessus, avec ton outil de montage) :\n".
"  - Badge catégorie en haut a gauche : NEWS / LEAK / GUIDE / HOT (versions fournies)\n".
"  - Gros titre en bas : remplace 'TON TITRE ACCROCHEUR ICI'\n".
"  - @vicehubx · vicehubx.fr (modifiable)\n\n".
"Formats :\n".
"  - reel-9x16-*.png  -> TikTok / Reels / Stories (1080x1920)\n".
"  - ig-square.png    -> Instagram & Facebook (1080x1080)\n".
"  - ig-portrait-4x5  -> Instagram nouveau format portrait (1080x1350)\n".
"  - *-exemple.png    -> apercu du rendu fini\n");
$made[]="$KIT/LISEZ-MOI.txt";

echo "OK — kit réseaux généré (".count($made)." fichiers) dans /public/assets/img/brand/kit/ :\n";
foreach($made as $f) echo "  ".basename($f)."\n";

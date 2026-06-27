<?php
/**
 * ViceHub X — Overlay logo MINIMAL & premium (transparent) pour réseaux.
 * À poser par-dessus n'importe quel visuel/vidéo photoréaliste (Canva/CapCut)
 * pour un feed Instagram cohérent et beau, sans surcharge.
 *   Usage : php scripts/gen-overlay-min.php
 *   Sortie : public/assets/img/brand/kit/overlay-min-*.png
 */
$ROOT = dirname(__DIR__);
$KIT  = $ROOT . '/public/assets/img/brand/kit';
@mkdir($KIT, 0775, true);
$FONT  = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
$FONTI = '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf';
if (!is_file($FONT))  { $FONT  = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'; }
if (!is_file($FONTI)) { $FONTI = $FONT; }

function cc($im,$r,$g,$b,$a=0){ return imagecolorallocatealpha($im,(int)max(0,min(255,$r)),(int)max(0,min(255,$g)),(int)max(0,min(255,$b)),(int)max(0,min(127,$a))); }
function clampf($v,$lo,$hi){ return max($lo,min($hi,$v)); }
function stopColor($s,$t){ $t=clampf($t,0,1); $n=count($s); if($t<=$s[0][0])return $s[0][1]; if($t>=$s[$n-1][0])return $s[$n-1][1];
    for($i=0;$i<$n-1;$i++){ [$p0,$c0]=$s[$i]; [$p1,$c1]=$s[$i+1]; if($t>=$p0&&$t<=$p1){ $f=($t-$p0)/max(1e-6,$p1-$p0); return [$c0[0]+($c1[0]-$c0[0])*$f,$c0[1]+($c1[1]-$c0[1])*$f,$c0[2]+($c1[2]-$c0[2])*$f]; } } return $s[$n-1][1]; }
function tW($size,$font,$text){ $b=imagettfbbox($size,0,$font,$text); return abs($b[2]-$b[0]); }
function gtaText($im,$font,$size,$x,$y,$text,$stops,$glow){
    for($i=0;$i<5;$i++){ $g=cc($im,$glow[0],$glow[1],$glow[2],(int)clampf(92-$i*9,50,120)); foreach([[-3,0],[3,0],[0,-3],[0,3],[2,2],[-2,-2]] as $o) imagettftext($im,$size,0,$x+$o[0],$y+$o[1],$g,$font,$text); }
    $dk=cc($im,38,8,40); foreach([[-2,0],[2,0],[0,-2],[0,2]] as $o) imagettftext($im,$size,0,$x+$o[0],$y+$o[1],$dk,$font,$text);
    $b=imagettfbbox($size,0,$font,$text); $tw=abs($b[2]-$b[0]); $th=abs($b[7]-$b[1]); $pad=(int)($size*0.55); $w=$tw+$pad*2; $h=$th+$pad*2;
    $L=imagecreatetruecolor($w,$h); imagesavealpha($L,true); imagealphablending($L,false); imagefilledrectangle($L,0,0,$w,$h,cc($L,0,0,0,127)); imagealphablending($L,true);
    $by=$pad+$th; imagettftext($L,$size,0,$pad,$by,cc($L,255,255,255),$font,$text); imagealphablending($im,true);
    for($yy=0;$yy<$h;$yy++){ $t=clampf(($yy-$pad)/max(1,$th),0,1); $c=stopColor($stops,$t); $gl=exp(-pow($t-0.30,2)/0.02)*0.5;
        $r=$c[0]+(255-$c[0])*$gl; $g=$c[1]+(255-$c[1])*$gl; $bl=$c[2]+(255-$c[2])*$gl; $dy=$y-$by+$yy; if($dy<0||$dy>=imagesy($im))continue;
        for($xx=0;$xx<$w;$xx++){ $a=(imagecolorat($L,$xx,$yy)>>24)&0x7F; if($a>=120)continue; $dx=$x+$xx-$pad; if($dx<0||$dx>=imagesx($im))continue; $al=(127-$a)/127;
            $ex=imagecolorat($im,$dx,$dy); imagesetpixel($im,$dx,$dy,cc($im,(($ex>>16)&255)*(1-$al)+$r*$al,(($ex>>8)&255)*(1-$al)+$g*$al,($ex&255)*(1-$al)+$bl*$al)); } }
    imagedestroy($L); return $tw;
}
function rrect($im,$x1,$y1,$x2,$y2,$r,$c){ imagefilledrectangle($im,$x1+$r,$y1,$x2-$r,$y2,$c); imagefilledrectangle($im,$x1,$y1+$r,$x2,$y2-$r,$c);
    imagefilledellipse($im,$x1+$r,$y1+$r,$r*2,$r*2,$c); imagefilledellipse($im,$x2-$r,$y1+$r,$r*2,$r*2,$c); imagefilledellipse($im,$x1+$r,$y2-$r,$r*2,$r*2,$c); imagefilledellipse($im,$x2-$r,$y2-$r,$r*2,$r*2,$c); }

$G=[[0.0,[255,236,179]],[0.32,[255,138,170]],[0.66,[255,46,136]],[1.0,[182,38,140]]];
$GX=[[0.0,[198,245,255]],[0.5,[64,200,255]],[1.0,[40,120,235]]];

function overlayMin($w,$h){
    global $G,$GX,$FONT,$FONTI;
    $im=imagecreatetruecolor($w,$h); imagesavealpha($im,true); imagealphablending($im,false);
    imagefilledrectangle($im,0,0,$w,$h,cc($im,0,0,0,127)); imagealphablending($im,true);
    // scrims très légers (lisibilité, sans masquer le visuel)
    $topH=(int)($h*0.13); for($y=0;$y<$topH;$y++){ $a=(int)(127-(1-$y/$topH)*58); imagefilledrectangle($im,0,$y,$w,$y,cc($im,6,4,14,clampf($a,69,127))); }
    $botH=(int)($h*0.22); for($y=$h-$botH;$y<$h;$y++){ $t=($y-($h-$botH))/$botH; $a=(int)(127-$t*70); imagefilledrectangle($im,0,$y,$w,$y,cc($im,6,4,14,clampf($a,57,127))); }
    // logo haut-centre
    $s=40; $wW=tW($s,$FONT,'VICEHUB'); $gap=(int)($s*0.14); $wX=tW($s*1.12,$FONTI,'X'); $x=(int)($w/2-($wW+$gap+$wX)/2);
    gtaText($im,$FONT,$s,$x,(int)($topH*0.66),'VICEHUB',$G,[255,46,136]);
    gtaText($im,$FONTI,$s*1.12,$x+$wW+$gap,(int)($topH*0.66)+2,'X',$GX,[43,170,255]);
    $tag='G T A   V I   ·   F A N   H U B'; $ts=16; $tw=tW($ts,$FONT,$tag);
    imagettftext($im,$ts,0,(int)($w/2-$tw/2),(int)($topH*0.66)+34,cc($im,255,209,120),$FONT,$tag);
    // bas : zone titre (placeholder) + handle + accent
    $tx=56; $base=$h-$botH+(int)($botH*0.42);
    imagettftext($im,$h/$w>1.4?52:44,0,$tx,$base,cc($im,255,255,255),$FONT,'TON TITRE ICI');
    rrect($im,$tx,$base+22,$tx+110,$base+30,4,cc($im,255,46,136));
    imagettftext($im,24,0,$tx,$h-54,cc($im,255,255,255),$FONT,'@vicehubx  ·  vicehubx.fr');
    return $im;
}
$made=[];
foreach([[1080,1920,'9x16'],[1080,1350,'4x5'],[1080,1080,'1x1']] as [$w,$h,$tag]){ $f="$KIT/overlay-min-$tag.png"; imagepng(overlayMin($w,$h),$f); $made[]=$f; }
file_put_contents("$KIT/OVERLAY-MIN-LISEZMOI.txt",
"OVERLAY LOGO MINIMAL VICEHUB X\n==============================\n\nPNG transparents a poser PAR-DESSUS tes visuels/videos photorealistes (Canva/CapCut).\nLe logo VICEHUB X + @vicehubx + zone titre s'affichent sur ton image, sans la masquer.\nFormats : 9x16 (Reels/TikTok), 4x5 (feed Instagram), 1x1 (carre).\nRemplace 'TON TITRE ICI' avec ton outil de montage.\n");
$made[]="$KIT/OVERLAY-MIN-LISEZMOI.txt";
echo "OK — overlay minimal genere :\n"; foreach($made as $f) echo "  ".basename($f)."\n";

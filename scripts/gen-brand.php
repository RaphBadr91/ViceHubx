<?php
/**
 * ViceHub X — Identité de marque façon GTA VI (couleurs + style) via GD.
 * Pas de chiffre « VI ». Le wordmark VICEHUB X reprend la signature GTA VI :
 * lettrage glossy en dégradé crème→rose→magenta, sur un coucher de soleil
 * Vice City épuré (sun glow, palmiers, ciel violet→magenta→orange).
 *   Usage : php scripts/gen-brand.php
 */
$ROOT  = dirname(__DIR__);
$IMG   = $ROOT . '/public/assets/img';
$BRAND = $IMG . '/brand';
@mkdir($BRAND, 0775, true);

$FONT  = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
$FONTI = '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf';
if (!is_file($FONT))  { $FONT  = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'; }
if (!is_file($FONTI)) { $FONTI = $FONT; }

function cc($im,$r,$g,$b,$a=0){ return imagecolorallocatealpha($im,(int)max(0,min(255,$r)),(int)max(0,min(255,$g)),(int)max(0,min(255,$b)),(int)$a); }
function clampf($v,$lo,$hi){ return max($lo,min($hi,$v)); }

/* Interpolation multi-paliers : $stops = [[pos,[r,g,b]], ...] triés. */
function stopColor($stops,$t){
    $t=clampf($t,0,1); $n=count($stops);
    if($t<=$stops[0][0]) return $stops[0][1];
    if($t>=$stops[$n-1][0]) return $stops[$n-1][1];
    for($i=0;$i<$n-1;$i++){ [$p0,$c0]=$stops[$i]; [$p1,$c1]=$stops[$i+1];
        if($t>=$p0 && $t<=$p1){ $f=($t-$p0)/max(1e-6,$p1-$p0);
            return [$c0[0]+($c1[0]-$c0[0])*$f,$c0[1]+($c1[1]-$c0[1])*$f,$c0[2]+($c1[2]-$c0[2])*$f]; } }
    return $stops[$n-1][1];
}

/* Fond coucher de soleil GTA VI (dégradé vertical riche + lueur de soleil). */
function background($w,$h){
    $im=imagecreatetruecolor($w,$h); imagealphablending($im,true);
    // Palette GTA VI : teal/indigo en haut -> violet -> magenta -> orange au ras de l'horizon.
    $stops=[[0.0,[18,16,58]],[0.18,[40,18,82]],[0.40,[96,28,104]],[0.56,[190,46,108]],[0.63,[255,118,72]],[0.70,[120,34,72]],[0.86,[22,12,38]],[1.0,[7,5,15]]];
    for($y=0;$y<$h;$y++){ $c=stopColor($stops,$y/$h); imagefilledrectangle($im,0,$y,$w,$y,cc($im,$c[0],$c[1],$c[2])); }
    $horizon=(int)($h*0.62);
    $cx=(int)($w/2); $cy=(int)($horizon*0.94);
    // lueur de soleil : large et basse (accent à l'horizon, pas un gros disque central)
    $R=(int)(min($w,$h)*0.40);
    for($i=$R;$i>0;$i-=3){ $a=clampf(96-($R-$i)*0.7,44,104); imagefilledellipse($im,$cx,$cy,(int)($i*2.3),(int)($i*1.25),cc($im,255,140,72,(int)$a)); }
    // disque solaire compact
    $sr=(int)(min($w,$h)*0.135);
    for($y=-$sr;$y<=$sr;$y++){ $t=($y+$sr)/(2*$sr); $half=(int)sqrt(max(0,$sr*$sr-$y*$y));
        imagefilledrectangle($im,$cx-$half,$cy+$y,$cx+$half,$cy+$y,cc($im,255,228*(1-$t)+95*$t,95*(1-$t)+150*$t)); }
    // reflet sur l'eau
    for($y=$horizon;$y<$h;$y++){ $a=clampf(54-($y-$horizon)*0.3,0,54); if($a<=0) break;
        imagefilledrectangle($im,$cx-(int)($w*0.05),$y,$cx+(int)($w*0.05),$y,cc($im,255,130,80,(int)(127-$a))); }
    return [$im,$horizon,$cx,$cy];
}

/* Palmier silhouette avec frondes feuillues. */
function palm($im,$bx,$by,$s,$dark){
    $seg=12; $prev=[$bx,$by];
    for($i=1;$i<=$seg;$i++){ $t=$i/$seg; $x=$bx+sin($t*1.05)*$s*0.17; $y=$by-$t*$s;
        imagesetthickness($im,(int)max(2,(1-$t)*$s*0.07)); imageline($im,(int)$prev[0],(int)$prev[1],(int)$x,(int)$y,$dark); $prev=[$x,$y]; }
    imagesetthickness($im,1); $tx=$prev[0]; $ty=$prev[1];
    $fronds=[[-205,1.0],[-238,1.05],[-268,1.1],[-292,1.05],[-322,1.0],[-352,0.85],[-18,0.8]];
    foreach($fronds as [$a,$lf]){ $rad=deg2rad($a); $len=$s*0.46*$lf;
        $ex=$tx+cos($rad)*$len; $ey=$ty+sin($rad)*$len+$s*0.14;
        $mx=($tx+$ex)/2+cos($rad+1.57)*$s*0.07; $my=($ty+$ey)/2+sin($rad+1.57)*$s*0.07-$s*0.04;
        $wd=$s*0.055;
        imagefilledpolygon($im,array_map('intval',[$tx,$ty,$mx+$wd,$my,$ex,$ey,$mx-$wd,$my]),$dark); }
}

/* Skyline discrète. */
function skyline($im,$w,$baseY,$dark){
    $x=0; $seed=[0.45,0.8,0.3,0.9,0.55,0.4,0.8,0.35,0.65,0.5,0.88,0.45]; $i=0;
    while($x<$w){ $bw=(int)($w*(0.03+($i%3)*0.01)); $bh=(int)($baseY*0.22*$seed[$i%count($seed)]);
        imagefilledrectangle($im,$x,$baseY-$bh,$x+$bw-2,$baseY,$dark); $x+=$bw+(int)($w*0.005); $i++; }
}

/* Texte glossy en dégradé multi-paliers (signature GTA VI) : contour + halo + gloss. */
function gtaText($im,$font,$size,$x,$y,$text,$stops,$glow){
    // halo néon
    for($i=0;$i<6;$i++){ $g=cc($im,$glow[0],$glow[1],$glow[2],clampf(92-$i*8,46,120));
        foreach([[-4,0],[4,0],[0,-4],[0,4],[3,3],[-3,3],[3,-3],[-3,-3]] as $o) imagettftext($im,$size,0,$x+$o[0],$y+$o[1],$g,$font,$text); }
    // contour foncé (définition)
    $dk=cc($im,40,8,40);
    foreach([[-2,0],[2,0],[0,-2],[0,2],[2,2],[-2,-2],[2,-2],[-2,2]] as $o) imagettftext($im,$size,0,$x+$o[0],$y+$o[1],$dk,$font,$text);
    // calque masque
    $b=imagettfbbox($size,0,$font,$text); $tw=abs($b[2]-$b[0]); $th=abs($b[7]-$b[1]); $pad=(int)($size*0.55);
    $w=$tw+$pad*2; $h=$th+$pad*2; $layer=imagecreatetruecolor($w,$h);
    imagesavealpha($layer,true); imagealphablending($layer,false);
    imagefilledrectangle($layer,0,0,$w,$h,cc($layer,0,0,0,127)); imagealphablending($layer,true);
    $baseY=$pad+$th; imagettftext($layer,$size,0,$pad,$baseY,cc($layer,255,255,255),$font,$text);
    imagealphablending($im,true);
    for($yy=0;$yy<$h;$yy++){
        $t=clampf(($yy-$pad)/max(1,$th),0,1); $c=stopColor($stops,$t);
        // gloss : bande lumineuse vers le haut + léger assombrissement en bas
        $gl=exp(-pow($t-0.30,2)/(2*0.10*0.10))*0.55;
        $r=$c[0]+(255-$c[0])*$gl; $g=$c[1]+(255-$c[1])*$gl; $bl=$c[2]+(255-$c[2])*$gl;
        if($t>0.82){ $d=1-($t-0.82)*0.9; $r*=$d;$g*=$d;$bl*=$d; }
        $dy=$y-$baseY+$yy; if($dy<0||$dy>=imagesy($im)) continue;
        for($xx=0;$xx<$w;$xx++){ $a=(imagecolorat($layer,$xx,$yy)>>24)&0x7F; if($a>=120) continue;
            $dx=$x+$xx-$pad; if($dx<0||$dx>=imagesx($im)) continue; $al=(127-$a)/127;
            $ex=imagecolorat($im,$dx,$dy); $er=($ex>>16)&255; $eg=($ex>>8)&255; $eb=$ex&255;
            imagesetpixel($im,$dx,$dy,cc($im,$er*(1-$al)+$r*$al,$eg*(1-$al)+$g*$al,$eb*(1-$al)+$bl*$al)); }
    }
    imagedestroy($layer); return $tw;
}
function tW($size,$font,$text){ $b=imagettfbbox($size,0,$font,$text); return abs($b[2]-$b[0]); }

/* Dégradé signature (crème → rose → magenta). */
$G=[[0.0,[255,236,179]],[0.32,[255,138,170]],[0.66,[255,46,136]],[1.0,[182,38,140]]];
$GX=[[0.0,[198,245,255]],[0.5,[64,200,255]],[1.0,[40,120,235]]];

function scene($w,$h,$withPalms=true){
    [$im,$hz,$cx,$cy]=background($w,$h);
    $dark=cc($im,10,7,24);
    skyline($im,$w,$hz+1,$dark);
    if($withPalms){ palm($im,(int)($w*0.085),$hz+(int)($h*0.05),(int)($h*0.42),$dark);
        palm($im,(int)($w*0.915),$hz+(int)($h*0.05),(int)($h*0.42),$dark); }
    return [$im,$hz];
}
function vignette($im,$w,$h){ for($i=0;$i<80;$i++){ $a=120-$i; if($a<0)break; imagerectangle($im,$i,$i,$w-$i-1,$h-$i-1,cc($im,0,0,8,(int)clampf(127-$a/2.4,58,127))); } }

/* Wordmark VICEHUB X centré (taille auto pour rentrer dans $maxW). */
function wordmark($im,$cxImg,$cy,$font,$fonti,$size,$G,$GX,$tagline=true){
    $wWord=tW($size,$font,'VICEHUB'); $gap=(int)($size*0.14); $wX=tW($size*1.12,$fonti,'X');
    $total=$wWord+$gap+$wX; $x=(int)($cxImg-$total/2);
    gtaText($im,$font,$size,$x,$cy,'VICEHUB',$G,[255,46,136]);
    gtaText($im,$fonti,$size*1.12,$x+$wWord+$gap,$cy+(int)($size*0.04),'X',$GX,[43,170,255]);
    if($tagline){ $tag='G T A   V I   ·   F A N   H U B'; $ts=max(11,(int)($size*0.19)); $tw=tW($ts,$font,$tag);
        imagettftext($im,$ts,0,(int)($cxImg-$tw/2),$cy+(int)($size*0.52),cc($im,255,209,120),$font,$tag); }
    return $total;
}
function fitSize($font,$fonti,$text,$xtext,$maxW){
    for($s=160;$s>30;$s-=2){ $tot=tW($s,$font,$text)+(int)($s*0.14)+tW($s*1.12,$fonti,$xtext); if($tot<=$maxW) return $s; }
    return 40;
}

/* ---- 1) Carré post (Instagram / TikTok / Facebook) 1080² ---- */
[$sq,]=scene(1080,1080);
wordmark($sq,540,580,$FONT,$FONTI,116,$G,$GX,true);
vignette($sq,1080,1080); imagepng($sq,"$BRAND/logo-square.png");

/* ---- 2) Avatar rond (profil IG / TikTok / FB) 1080² ---- */
[$pf,]=scene(1080,1080,false);
$ps=fitSize($FONT,$FONTI,'VICEHUB','X',640);
wordmark($pf,540,560,$FONT,$FONTI,$ps,$G,$GX,true);
for($y=0;$y<1080;$y++) for($x=0;$x<1080;$x++){ if(sqrt(($x-540)**2+($y-540)**2)>532) imagesetpixel($pf,$x,$y,cc($pf,0,0,0,127)); }
imagesavealpha($pf,true);
for($r=522;$r<=534;$r++) imageellipse($pf,540,540,$r*2,$r*2,cc($pf,255,46,136,22));
imagepng($pf,"$BRAND/logo-profile.png");
imagepng(imagescale($pf,512,512),"$IMG/icon-512.png");
imagepng(imagescale($pf,192,192),"$IMG/icon-192.png");
imagepng(imagescale($pf,180,180),"$IMG/apple-touch-icon.png");

/* ---- 3) Bannière Facebook 1640 x 624 ---- */
[$cov,]=scene(1640,624);
wordmark($cov,820,330,$FONT,$FONTI,118,$G,$GX,true);
vignette($cov,1640,624); imagepng($cov,"$BRAND/fb-cover.png");

/* ---- 4) Vertical 9:16 (TikTok / Stories) 1080 x 1920 ---- */
[$st,]=scene(1080,1920);
wordmark($st,540,1000,$FONT,$FONTI,120,$G,$GX,true);
vignette($st,1080,1920); imagepng($st,"$BRAND/story-9x16.png");

/* ---- 5) Favicon : monogramme X glossy ---- */
[$fav,]=scene(256,256,false);
$xw=tW(150,$FONTI,'X'); gtaText($fav,$FONTI,150,(int)(128-$xw/2),178,'X',$G,[255,46,136]);
imagepng($fav,"$IMG/favicon-256.png"); imagepng(imagescale($fav,32,32),"$IMG/favicon-32.png");

echo "OK — identité GTA VI (sans chiffre VI) générée :\n";
foreach(["$BRAND/logo-square.png","$BRAND/logo-profile.png","$BRAND/fb-cover.png","$BRAND/story-9x16.png","$IMG/icon-512.png","$IMG/favicon-256.png"] as $f)
    echo "  ".str_replace($ROOT,'',$f)." (".(is_file($f)?filesize($f).' o':'ERR').")\n";

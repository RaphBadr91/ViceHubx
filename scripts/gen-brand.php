<?php
/**
 * ViceHub X — Identité de marque « GTA VI » (logo, réseaux, favicon) via GD.
 * Style : clé visuelle Vice City — soleil rétro, grille néon, palmiers, flamant,
 * chiffre romain VI glossy et wordmark en dégradé rose→jaune (signature GTA VI).
 *   Usage : php scripts/gen-brand.php
 */
$ROOT = dirname(__DIR__);
$IMG  = $ROOT . '/public/assets/img';
$BRAND = $IMG . '/brand';
@mkdir($BRAND, 0775, true);

$FONT  = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
$FONTI = '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf';
if (!is_file($FONT))  { $FONT  = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'; }
if (!is_file($FONTI)) { $FONTI = $FONT; }

function col($im,$r,$g,$b,$a=0){ return imagecolorallocatealpha($im,(int)$r,(int)$g,(int)$b,(int)$a); }
function clampi($v,$lo,$hi){ return max($lo,min($hi,$v)); }

/* Ciel GTA VI : violet profond -> magenta -> orange. */
function sky($im,$w,$h,$horizon){
    for($y=0;$y<$horizon;$y++){
        $t=$y/max(1,$horizon);
        $r=43+(255-43)*pow($t,1.6); $g=11+(120-11)*pow($t,2.3); $b=63+(70-63)*(1-$t)+ (90)*(1-$t);
        imagefilledrectangle($im,0,$y,$w,$y,col($im,clampi($r,0,255),clampi($g,0,255),clampi($b,0,255)));
    }
    for($y=$horizon;$y<$h;$y++){
        $t=($y-$horizon)/max(1,$h-$horizon);
        imagefilledrectangle($im,0,$y,$w,$y,col($im,(int)(30*(1-$t)+8),6,(int)(52*(1-$t)+16)));
    }
}
/* Soleil rétro à bandes (jaune -> magenta). */
function retroSun($im,$cx,$cy,$R){
    for($y=-$R;$y<=$R;$y++){
        $t=($y+$R)/(2*$R);
        $r=255; $g=230*(1-$t)+40*$t; $b=70*(1-$t)+150*$t;
        $half=(int)sqrt(max(0,$R*$R-$y*$y));
        imagefilledrectangle($im,$cx-$half,$cy+$y,$cx+$half,$cy+$y,col($im,$r,$g,$b));
    }
    for($i=0;$i<8;$i++){
        $by=$cy+(int)($R*0.14)+$i*(int)($R*0.12);
        $half=(int)sqrt(max(0,$R*$R-($by-$cy)*($by-$cy)));
        imagefilledrectangle($im,$cx-$half,$by,$cx+$half,$by+max(2,(int)($R*0.025)+$i),col($im,28,8,46));
    }
}
function neonGrid($im,$w,$h,$horizon,$pink,$cyan){
    for($i=-14;$i<=14;$i++){ $x=$w/2+$i*($w/14); imageline($im,(int)($w/2),$horizon,(int)$x,$h,$cyan); }
    for($i=1;$i<=11;$i++){ $p=$i/11; $y=$horizon+(int)(pow($p,2.2)*($h-$horizon)); imageline($im,0,$y,$w,$y,$pink); }
}
function skyline($im,$w,$h,$baseY,$dark){
    $x=0; $seed=[0.5,0.85,0.35,0.95,0.6,0.45,0.85,0.4,0.7,0.55,0.92,0.5,0.78,0.6,0.4,0.82,0.5]; $i=0;
    while($x<$w){ $bw=(int)($w*(0.035+($i%3)*0.011)); $bh=(int)($h*0.17*$seed[$i%count($seed)]);
        imagefilledrectangle($im,$x,$baseY-$bh,$x+$bw-2,$baseY,$dark); $x+=$bw+(int)($w*0.006); $i++; }
}
function palm($im,$x,$y,$s,$dark){
    $pts=[]; for($t=0;$t<=1;$t+=0.1){ $pts[]=[$x+(int)(sin($t*1.2)*$s*0.18),$y-(int)($t*$s)]; }
    imagesetthickness($im,max(2,(int)($s*0.05)));
    for($k=0;$k<count($pts)-1;$k++) imageline($im,$pts[$k][0],$pts[$k][1],$pts[$k+1][0],$pts[$k+1][1],$dark);
    $top=end($pts);
    foreach([200,228,256,286,316,338] as $a){ $rad=deg2rad($a);
        $ex=$top[0]+(int)(cos($rad)*$s*0.5); $ey=$top[1]+(int)(sin($rad)*$s*0.42);
        imageline($im,$top[0],$top[1],(int)$ex,(int)$ey,$dark); }
    imagesetthickness($im,1);
}
/* Flamant néon stylisé (silhouette rose). */
function flamingo($im,$cx,$cy,$s,$c){
    imagesetthickness($im,max(3,(int)($s*0.06)));
    // pattes
    imageline($im,$cx-(int)($s*0.05),$cy+(int)($s*0.3),$cx-(int)($s*0.12),$cy+(int)($s*0.95),$c);
    imageline($im,$cx+(int)($s*0.05),$cy+(int)($s*0.3),$cx+(int)($s*0.10),$cy+(int)($s*0.95),$c);
    // corps
    imagefilledellipse($im,$cx,$cy+(int)($s*0.18),(int)($s*0.5),(int)($s*0.32),$c);
    // cou en S
    $neck=[[$cx-(int)($s*0.08),$cy+(int)($s*0.05)],[$cx-(int)($s*0.22),$cy-(int)($s*0.18)],
           [$cx-(int)($s*0.05),$cy-(int)($s*0.42)],[$cx+(int)($s*0.16),$cy-(int)($s*0.52)]];
    for($k=0;$k<count($neck)-1;$k++) imageline($im,$neck[$k][0],$neck[$k][1],$neck[$k+1][0],$neck[$k+1][1],$c);
    // tête + bec
    imagefilledellipse($im,$cx+(int)($s*0.18),$cy-(int)($s*0.54),(int)($s*0.14),(int)($s*0.12),$c);
    imagefilledpolygon($im,[$cx+(int)($s*0.26),$cy-(int)($s*0.56),$cx+(int)($s*0.42),$cy-(int)($s*0.46),$cx+(int)($s*0.27),$cy-(int)($s*0.48)],$c);
    imagesetthickness($im,1);
}
/* Halo lumineux radial. */
function glow($im,$cx,$cy,$R,$r,$g,$b){
    for($i=$R;$i>0;$i-=2){ $a=clampi(118-(int)(($R-$i)*0.9),60,126);
        imagefilledellipse($im,$cx,$cy,$i*2,$i*2,col($im,$r,$g,$b,$a)); }
}

/* Texte rempli d'un dégradé vertical (gloss) + halo néon — signature GTA VI. */
function gradientText($im,$font,$size,$x,$y,$text,$top,$bot,$glow){
    $bbox=imagettfbbox($size,0,$font,$text);
    $tw=abs($bbox[2]-$bbox[0]); $th=abs($bbox[7]-$bbox[1]); $pad=(int)($size*0.5);
    $w=$tw+$pad*2; $h=$th+$pad*2;
    // halo sur l'image principale
    for($i=0;$i<6;$i++){ $g=col($im,$glow[0],$glow[1],$glow[2],clampi(86-$i*7,44,120));
        foreach([[-3,0],[3,0],[0,-3],[0,3],[3,3],[-3,-3],[4,0],[0,4]] as $o)
            imagettftext($im,$size,0,$x+$o[0],$y+$o[1],$g,$font,$text); }
    // calque texte blanc (masque)
    $layer=imagecreatetruecolor($w,$h); imagesavealpha($layer,true); imagealphablending($layer,false);
    imagefilledrectangle($layer,0,0,$w,$h,col($layer,0,0,0,127)); imagealphablending($layer,true);
    $baseY=$pad+$th;
    imagettftext($layer,$size,0,$pad,$baseY,col($layer,255,255,255),$font,$text);
    imagealphablending($im,true);
    for($yy=0;$yy<$h;$yy++){
        $t=clampi(($yy-$pad)/max(1,$th),0,1);
        // gloss : éclaircit le tiers supérieur
        $gl=$t<0.34?(1-$t/0.34)*0.5:0;
        $r=$top[0]*(1-$t)+$bot[0]*$t; $g=$top[1]*(1-$t)+$bot[1]*$t; $b=$top[2]*(1-$t)+$bot[2]*$t;
        $r=clampi($r+(255-$r)*$gl,0,255); $g=clampi($g+(255-$g)*$gl,0,255); $b=clampi($b+(255-$b)*$gl,0,255);
        $dy=$y-$baseY+$yy; if($dy<0||$dy>=imagesy($im)) continue;
        for($xx=0;$xx<$w;$xx++){
            $a=(imagecolorat($layer,$xx,$yy)>>24)&0x7F;
            if($a>=120) continue;
            $dx=$x+$xx-$pad; if($dx<0||$dx>=imagesx($im)) continue;
            $al=(127-$a)/127;
            $ex=imagecolorat($im,$dx,$dy); $er=($ex>>16)&255; $eg=($ex>>8)&255; $eb=$ex&255;
            imagesetpixel($im,$dx,$dy,col($im,$er*(1-$al)+$r*$al,$eg*(1-$al)+$g*$al,$eb*(1-$al)+$b*$al));
        }
    }
    imagedestroy($layer);
    return $tw;
}
function textW($size,$font,$text){ $b=imagettfbbox($size,0,$font,$text); return abs($b[2]-$b[0]); }

/* Construit la scène Vice City (sans texte). */
function scene($w,$h){
    $im=imagecreatetruecolor($w,$h); imagealphablending($im,true);
    $horizon=(int)($h*0.60);
    sky($im,$w,$h,$horizon);
    $pink=col($im,255,46,136,74); $cyan=col($im,43,214,255,80); $dark=col($im,12,8,28);
    // halo soleil
    glow($im,(int)($w/2),(int)($horizon*0.72),(int)(min($w,$h)*0.34),255,120,60);
    retroSun($im,(int)($w/2),(int)($horizon*0.72),(int)(min($w,$h)*0.19));
    neonGrid($im,$w,$h,$horizon,$pink,$cyan);
    skyline($im,$w,$h,$horizon+1,$dark);
    palm($im,(int)($w*0.07),$horizon+(int)($h*0.03),(int)($h*0.36),$dark);
    palm($im,(int)($w*0.93),$horizon+(int)($h*0.03),(int)($h*0.36),$dark);
    return [$im,$horizon];
}
function vignette($im,$w,$h){
    for($i=0;$i<70;$i++){ $a=110-$i; if($a<0)break; imagerectangle($im,$i,$i,$w-$i-1,$h-$i-1,col($im,0,0,8,clampi(127-(int)($a/2.2),60,127))); }
}

/* Dégradé signature GTA VI (jaune chaud -> rose magenta). */
$GT=[255,214,77]; $GB=[255,40,120];

/* Wordmark VICEHUB X + chiffre VI glossy + tagline. */
function wordmark($im,$w,$cy,$font,$fonti,$size,$GT,$GB,$tagline=true){
    $cx=(int)($w/2);
    $wWord=textW($size,$font,'VICEHUB'); $wX=textW($size*1.18,$fonti,'X'); $gap=(int)($size*0.16);
    $total=$wWord+$gap+$wX; $x=$cx-(int)($total/2);
    gradientText($im,$font,$size,$x,$cy,'VICEHUB',$GT,$GB,[255,46,136]);
    gradientText($im,$fonti,$size*1.18,$x+$wWord+$gap,$cy+(int)($size*0.05),'X',[120,230,255],[43,140,255],[43,214,255]);
    if($tagline){
        $tag='G T A   V I   ·   F A N   H U B'; $ts=max(11,(int)($size*0.2)); $tw=textW($ts,$font,$tag);
        imagettftext($im,$ts,0,$cx-(int)($tw/2),$cy+(int)($size*0.5),col($im,255,209,102),$font,$tag);
    }
}

/* ---- Asset 1 : carré post Instagram/TikTok 1080² ---- */
[$sq,$hz]=scene(1080,1080);
// gros VI glossy en filigrane derrière le wordmark
gradientText($sq,'/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf',360,540-300,560,'VI',[255,225,120],[181,60,255],[181,60,255]);
flamingo($sq,150,720,150,col($sq,255,60,150));
wordmark($sq,1080,640,$GLOBALS['FONT'],$GLOBALS['FONTI'],92,$GT,$GB,true);
vignette($sq,1080,1080);
imagepng($sq,"$BRAND/logo-square.png");

/* ---- Asset 2 : photo de profil ronde IG/TikTok (emblème VI) ---- */
[$pf,]=scene(1080,1080);
gradientText($pf,'/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf',560,540-330,720,'VI',[255,225,120],[255,40,120],[255,46,136]);
$tw=textW(58,$GLOBALS['FONT'],'VICEHUB X');
imagettftext($pf,58,0,540-(int)($tw/2),900,col($pf,255,255,255),$GLOBALS['FONT'],'VICEHUB X');
// masque circulaire + anneau néon
$mask=imagecreatetruecolor(1080,1080); imagesavealpha($mask,true); imagealphablending($mask,false);
for($y=0;$y<1080;$y++) for($x=0;$x<1080;$x++){
    $d=sqrt(($x-540)**2+($y-540)**2);
    if($d>530){ imagesetpixel($mask,$x,$y,imagecolorat($pf,$x,$y)); imagesetpixel($pf,$x,$y,col($pf,0,0,0,127)); }
}
imagedestroy($mask);
imagealphablending($pf,true);
for($r=520;$r<=532;$r++) imageellipse($pf,540,540,$r*2,$r*2,col($pf,255,46,136,20));
imagepng($pf,"$BRAND/logo-profile.png");
imagepng(imagescale($pf,512,512),"$IMG/icon-512.png");
imagepng(imagescale($pf,192,192),"$IMG/icon-192.png");
imagepng(imagescale($pf,180,180),"$IMG/apple-touch-icon.png");

/* ---- Asset 3 : bannière Facebook 1640 x 624 ---- */
$SERIF='/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf';
[$cov,]=scene(1640,624);
$viW=textW(270,$SERIF,'VI');
gradientText($cov,$SERIF,270,(int)(820-$viW/2),360,'VI',[255,225,120],[181,60,255],[181,60,255]);
flamingo($cov,200,440,165,col($cov,255,60,150));
wordmark($cov,1640,360,$GLOBALS['FONT'],$GLOBALS['FONTI'],104,$GT,$GB,true);
vignette($cov,1640,624);
imagepng($cov,"$BRAND/fb-cover.png");

/* ---- Asset 4 : story / TikTok 9:16 (1080 x 1920) ---- */
[$st,$sh]=scene(1080,1920);
gradientText($st,'/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf',460,540-280,1180,'VI',[255,225,120],[181,60,255],[181,60,255]);
flamingo($st,200,1320,200,col($st,255,60,150));
wordmark($st,1080,1000,$GLOBALS['FONT'],$GLOBALS['FONTI'],96,$GT,$GB,true);
vignette($st,1080,1920);
imagepng($st,"$BRAND/story-9x16.png");

/* ---- Asset 5 : favicon (emblème VI carré) ---- */
[$fav,]=scene(256,256);
gradientText($fav,'/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf',150,128-78,185,'VI',[255,225,120],[255,40,120],[255,46,136]);
imagepng($fav,"$IMG/favicon-256.png");
imagepng(imagescale($fav,32,32),"$IMG/favicon-32.png");

echo "OK — identité GTA VI générée :\n";
foreach(["$BRAND/logo-square.png","$BRAND/logo-profile.png","$BRAND/fb-cover.png","$BRAND/story-9x16.png","$IMG/icon-512.png","$IMG/favicon-256.png"] as $f)
    echo "  ".str_replace($ROOT,'',$f)." (".(is_file($f)?filesize($f).' o':'ERR').")\n";

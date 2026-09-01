<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$SEO_TITLE = 'BAWSAQ 2026 — ' . ($fr ? 'la Bourse de Vice City' : 'the Vice City Stock Market') . ' · ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'BAWSAQ 2026 : notre concept fan de la bourse boursière qui pourrait débarquer dans GTA VI. Cours en direct, indice VICE 50, plus fortes hausses et baisses de Vice City.'
    : 'BAWSAQ 2026: our fan concept of the stock market that could land in GTA VI. Live prices, VICE 50 index, top gainers and losers of Vice City.';

// Sociétés fictives (satire façon GTA) — symbole, nom, secteur, prix d'ouverture, volatilité
$stocks = [
    ['VAPD', 'Vapid Motors',     $fr ? 'Automobile' : 'Auto',        142.50, 1.6],
    ['MZBT', 'Maibatsu',         $fr ? 'Automobile' : 'Auto',         88.20, 1.8],
    ['SPNK', 'Sprunk',           $fr ? 'Boissons'   : 'Beverages',    54.10, 1.1],
    ['ECLA', 'eCola',            $fr ? 'Boissons'   : 'Beverages',    61.75, 1.0],
    ['AMUN', 'Ammu-Nation',      $fr ? 'Défense'    : 'Defense',     210.40, 2.2],
    ['CLKB', "Cluckin' Bell",    $fr ? 'Restauration' : 'Food',       33.90, 1.4],
    ['VNGL', 'Vangelico',        $fr ? 'Luxe'       : 'Luxury',      305.00, 2.6],
    ['BLTR', 'Bleeter',          $fr ? 'Réseaux'    : 'Social',       77.30, 3.1],
    ['FRUT', 'Fruit Computers',  'Tech',                            188.60, 2.4],
    ['RONO', 'RON Oil',          $fr ? 'Énergie'    : 'Energy',       96.40, 1.9],
    ['TNKL', 'Tinkle',           $fr ? 'Télécom'    : 'Telecom',      29.85, 1.3],
    ['PISW', 'Pißwasser',        $fr ? 'Boissons'   : 'Beverages',    22.15, 1.5],
];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section bawsaq">
    <div class="bawsaq__head">
        <span class="eyebrow"><?= vhx_icon('chart') ?> <?= $fr ? 'Concept fan · Vision 2026' : 'Fan concept · 2026 vision' ?></span>
        <h1 class="bawsaq__logo">BAWSAQ<span>26</span></h1>
        <p class="muted bawsaq__tag"><?= $fr
            ? 'Et si la bourse de GTA VI ressemblait à ça ? Voici notre vision animée du BAWSAQ nouvelle génération — cours en direct, indice VICE 50 et folie de Vice City.'
            : 'What if the GTA VI stock market looked like this? Our animated vision of the next-gen BAWSAQ — live prices, VICE 50 index and Vice City madness.' ?></p>
        <span class="bawsaq__disclaimer">⚠️ <?= $fr ? 'Concept de fan non officiel. Sociétés fictives, cours simulés.' : 'Unofficial fan concept. Fictional companies, simulated prices.' ?></span>
    </div>

    <!-- Ruban défilant -->
    <div class="bw-ticker" aria-hidden="true"><div class="bw-ticker__track" data-bw-ticker></div></div>

    <!-- Indice VICE 50 -->
    <div class="bw-index glass">
        <div class="bw-index__main">
            <span class="bw-index__name">VICE&nbsp;50</span>
            <span class="bw-index__val" data-bw-index>1000.00</span>
            <span class="bw-index__chg" data-bw-index-chg>+0.00%</span>
        </div>
        <canvas class="bw-index__spark" data-bw-index-spark width="600" height="90" aria-hidden="true"></canvas>
        <div class="bw-index__meta">
            <span>🟢 <?= $fr ? 'Marché ouvert' : 'Market open' ?> · LEONIDA</span>
            <span data-bw-clock>--:--:--</span>
        </div>
    </div>

    <!-- Plus fortes variations -->
    <div class="bw-movers">
        <div class="bw-movers__col glass"><h2>🚀 <?= $fr ? 'Plus fortes hausses' : 'Top gainers' ?></h2><ul data-bw-gainers></ul></div>
        <div class="bw-movers__col glass"><h2>📉 <?= $fr ? 'Plus fortes baisses' : 'Top losers' ?></h2><ul data-bw-losers></ul></div>
    </div>

    <!-- Grille des valeurs -->
    <h2 class="bw-grid-title"><?= $fr ? 'Cotations en direct' : 'Live quotes' ?></h2>
    <div class="bw-grid" data-bw-grid></div>

    <p class="muted" style="margin-top:2rem;font-size:.82rem;border-top:1px solid var(--glass-brd);padding-top:1rem">
        <?= e(t('legal_disclaimer')) ?>
    </p>
</section>

<script>
(function(){
  'use strict';
  var SEED = <?= json_encode(array_map(fn($s) => ['sym'=>$s[0],'name'=>$s[1],'sector'=>$s[2],'open'=>$s[3],'vol'=>$s[4]], $stocks), JSON_UNESCAPED_UNICODE) ?>;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var M = SEED.map(function(s){ return { sym:s.sym, name:s.name, sector:s.sector, open:s.open, price:s.open, hist:[s.open] }; });
  var grid = document.querySelector('[data-bw-grid]');
  var ticker = document.querySelector('[data-bw-ticker]');
  var idxEl = document.querySelector('[data-bw-index]');
  var idxChg = document.querySelector('[data-bw-index-chg]');
  var gainersEl = document.querySelector('[data-bw-gainers]');
  var losersEl = document.querySelector('[data-bw-losers]');
  var clockEl = document.querySelector('[data-bw-clock]');
  var idxHist = [1000];

  function fmt(n){ return n.toFixed(2); }
  function pct(p){ return (p>=0?'+':'') + p.toFixed(2) + '%'; }
  function chgOf(s){ return (s.price - s.open) / s.open * 100; }
  function spark(hist, w, h){
    if(hist.length<2) return '';
    var min=Math.min.apply(null,hist), max=Math.max.apply(null,hist), rng=(max-min)||1;
    return hist.map(function(v,i){ return (i/(hist.length-1)*w).toFixed(1)+','+(h-((v-min)/rng)*h).toFixed(1); }).join(' ');
  }

  // Construit les cartes
  M.forEach(function(s,i){
    var c=document.createElement('article'); c.className='bw-stock glass'; c.dataset.i=i;
    c.innerHTML='<div class="bw-stock__top"><div><b class="bw-stock__sym">'+s.sym+'</b><span class="bw-stock__name">'+s.name+'</span></div>'+
      '<span class="bw-stock__sector">'+s.sector+'</span></div>'+
      '<svg class="bw-spark" viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true"><polyline data-spark fill="none" stroke-width="1.6" points=""></polyline></svg>'+
      '<div class="bw-stock__bot"><span class="bw-stock__price" data-price>'+fmt(s.price)+'</span><span class="bw-stock__chg" data-chg>+0.00%</span></div>';
    grid.appendChild(c);
  });
  var cards = Array.prototype.slice.call(grid.children);

  function render(){
    // Cartes
    M.forEach(function(s,i){
      var card=cards[i], up=chgOf(s)>=0;
      card.classList.toggle('up',up); card.classList.toggle('down',!up);
      card.querySelector('[data-price]').textContent=fmt(s.price);
      card.querySelector('[data-chg]').textContent=pct(chgOf(s));
      card.querySelector('[data-spark]').setAttribute('points', spark(s.hist,100,32));
    });
    // Indice
    var idx = M.reduce(function(a,s){ return a + s.price/s.open; },0)/M.length*1000;
    idxHist.push(idx); if(idxHist.length>120) idxHist.shift();
    var ich=(idx-1000)/1000*100;
    idxEl.textContent=fmt(idx); idxChg.textContent=pct(ich);
    idxChg.className='bw-index__chg '+(ich>=0?'up':'down');
    drawIndex();
    // Movers
    var sorted=M.slice().sort(function(a,b){ return chgOf(b)-chgOf(a); });
    function li(s){ var u=chgOf(s)>=0; return '<li><span>'+s.sym+'</span><b class="'+(u?'up':'down')+'">'+pct(chgOf(s))+'</b></li>'; }
    gainersEl.innerHTML=sorted.slice(0,4).map(li).join('');
    losersEl.innerHTML=sorted.slice(-4).reverse().map(li).join('');
    // Ticker
    var tape=M.map(function(s){ var u=chgOf(s)>=0; return '<span class="bw-tk '+(u?'up':'down')+'">'+s.sym+' '+fmt(s.price)+' '+(u?'▲':'▼')+pct(chgOf(s))+'</span>'; }).join('');
    ticker.innerHTML=tape+tape;
  }

  var ictx=null, icanvas=document.querySelector('[data-bw-index-spark]');
  function drawIndex(){
    if(!icanvas) return; if(!ictx) ictx=icanvas.getContext('2d');
    var w=icanvas.width,h=icanvas.height,hist=idxHist;
    ictx.clearRect(0,0,w,h);
    if(hist.length<2) return;
    var min=Math.min.apply(null,hist),max=Math.max.apply(null,hist),rng=(max-min)||1;
    var up=hist[hist.length-1]>=1000;
    var grad=ictx.createLinearGradient(0,0,0,h);
    grad.addColorStop(0, up?'rgba(57,255,170,.45)':'rgba(255,59,92,.45)'); grad.addColorStop(1,'rgba(0,0,0,0)');
    ictx.beginPath();
    hist.forEach(function(v,i){ var x=i/(hist.length-1)*w, y=h-((v-min)/rng)*(h-8)-4; i?ictx.lineTo(x,y):ictx.moveTo(x,y); });
    ictx.lineTo(w,h); ictx.lineTo(0,h); ictx.closePath(); ictx.fillStyle=grad; ictx.fill();
    ictx.beginPath();
    hist.forEach(function(v,i){ var x=i/(hist.length-1)*w, y=h-((v-min)/rng)*(h-8)-4; i?ictx.lineTo(x,y):ictx.moveTo(x,y); });
    ictx.strokeStyle=up?'#39ffaa':'#ff3b5c'; ictx.lineWidth=2; ictx.stroke();
  }

  function step(){
    M.forEach(function(s){
      var drift=(Math.random()-0.5)*2*(SEED.find(function(x){return x.sym===s.sym;}).vol);
      s.price=Math.max(1, s.price*(1+drift/100));
      s.hist.push(s.price); if(s.hist.length>40) s.hist.shift();
    });
    render();
  }
  function tickClock(){ var d=new Date(),p=function(n){return(n<10?'0':'')+n;}; if(clockEl) clockEl.textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds()); }

  render(); tickClock(); setInterval(tickClock,1000);
  if(!reduced){
    var timer=setInterval(step, 1600);
    document.addEventListener('visibilitychange',function(){ if(document.hidden){clearInterval(timer);timer=null;} else if(!timer){timer=setInterval(step,1600);} });
  }
})();
</script>
<?php require ROOT_PATH . '/includes/footer.php'; ?>

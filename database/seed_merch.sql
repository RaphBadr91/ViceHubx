-- ViceHub X — Goodies (t-shirts, mugs, stylo, carnet…) — généré par scripts/gen-merch.php
INSERT INTO products (name, slug, description, category, price, currency, image, sale_type, merchant, badge, featured, cta, sort, lang) VALUES
('Stylo Vice City','shop-pen','Stylo bille mat, accents néon rose et cyan. Glisse parfaitement sur le papier.','accessory',6.90,'EUR','/public/assets/img/shop/shop-pen.png','stripe','ViceHub Store','Nouveau',0,1,400,'fr'),
('Carnet Synthwave','shop-notebook','Carnet à couverture rigide, design coucher de soleil néon. 120 pages lignées.','accessory',12.90,'EUR','/public/assets/img/shop/shop-notebook.png','stripe','ViceHub Store','Nouveau',0,1,401,'fr'),
('T-shirt « Palm Sunset »','shop-tshirt-palm','T-shirt premium 100% coton, graphique palmier & coucher de soleil néon. Coupe unisexe.','apparel',24.90,'EUR','/public/assets/img/shop/shop-tshirt-palm.png','stripe','ViceHub Store','Best-seller',1,1,402,'fr'),
('T-shirt « Neon Flamingo »','shop-tshirt-flamingo','T-shirt blanc premium, flamant rose néon sur la poitrine. Coupe unisexe.','apparel',24.90,'EUR','/public/assets/img/shop/shop-tshirt-flamingo.png','stripe','ViceHub Store',NULL,0,0,403,'fr'),
('Mug « Skyline »','shop-mug-skyline','Mug céramique noir, skyline néon tout autour. 33 cl, passe au lave-vaisselle.','accessory',14.90,'EUR','/public/assets/img/shop/shop-mug-skyline.png','stripe','ViceHub Store',NULL,1,1,404,'fr'),
('Mug émaillé « Palm »','shop-mug-enamel','Mug émaillé style camping, palmier & soleil. Increvable, pour la route.','accessory',16.90,'EUR','/public/assets/img/shop/shop-mug-enamel.png','stripe','ViceHub Store',NULL,0,0,405,'fr'),
('Tote bag « Vice City »','shop-tote','Sac en toile naturelle, skyline néon imprimée. Solide et spacieux.','accessory',17.90,'EUR','/public/assets/img/shop/shop-tote.png','stripe','ViceHub Store',NULL,0,0,406,'fr'),
('Pack de stickers néon','shop-stickers','Lot de stickers vinyle brillants : palmiers, flamant, sunset et formes synthwave.','accessory',8.90,'EUR','/public/assets/img/shop/shop-stickers.png','stripe','ViceHub Store','Pack',0,1,407,'fr'),
('Coque téléphone Néon','shop-phonecase','Coque smartphone, skyline synthwave néon. Protection et style Vice City.','accessory',18.90,'EUR','/public/assets/img/shop/shop-phonecase.png','stripe','ViceHub Store',NULL,0,0,408,'fr'),
('Porte-clés Palmier','shop-keychain','Porte-clés acrylique translucide en forme de palmier néon.','accessory',6.90,'EUR','/public/assets/img/shop/shop-keychain.png','stripe','ViceHub Store',NULL,0,0,409,'fr');

-- Propulse aussi quelques wallpapers vedette en CTA (variété dans les articles)
UPDATE products SET cta=1 WHERE category='wallpaper' AND featured=1;

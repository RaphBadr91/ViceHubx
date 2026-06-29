<?php
/**
 * ViceHub X — Correspondance « nom de fichier → URL CDN publique ».
 * Permet d'afficher les visuels IA directement depuis le CDN tant que les
 * fichiers locaux ne sont pas présents (voir img_src() dans functions.php).
 * Dès que les scripts (make-hero-video.sh / make-wallpapers.sh) ont été lancés,
 * les fichiers locaux prennent automatiquement le relais.
 */
$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E';

return [
    // --- Vidéo d'accueil (hero) : plan d'ouverture du montage (trafic dense + passants) ---
    // Le vrai hero est un MONTAGE de 5 scènes recréé par make-hero.php (ffmpeg).
    // Ceci est le repli (1er plan du montage) si make-hero.php n'a pas tourné.
    'hero.mp4'          => "$CDN/hf_20260622_163349_83ec4749-81dc-406e-a59c-bd961d63db73.mp4",

    // --- Scènes / univers (16:9) ---
    'night.png'         => "$CDN/hf_20260622_091459_e851b3ac-912c-4cfe-a04e-264f17f2fc5c.png",
    'beach-cruise.png'  => "$CDN/hf_20260622_093428_1b56209f-dc8e-4d0c-90f9-940c7eef4a14.png",
    'aerial.png'        => "$CDN/hf_20260622_093430_c3918a3d-0345-4e6f-95ba-fbd3f76002bb.png",
    'police.png'        => "$CDN/hf_20260622_130727_e28cfd20-aeab-4f18-9e8f-cace0ad0de40.png",
    'heli.png'          => "$CDN/hf_20260622_130728_b0a5ded5-d988-4809-8a23-58ac81d030ae.png",
    'plane.png'         => "$CDN/hf_20260622_130844_81ba4d71-ed7e-4fdd-b9e2-4536e5daa40e.png",
    'marina.png'        => "$CDN/hf_20260622_130830_b4ff6360-6bf4-41a0-a68c-2414f21752a3.png",
    'beachlife.png'     => "$CDN/hf_20260622_130831_13fde152-b662-485a-9258-94fc8cc5b2c5.png",
    'nightlife.png'     => "$CDN/hf_20260623_051157_81551adf-03cf-4346-936f-d77d2190aa67.png",
    'ocean-drive.png'   => "$CDN/hf_20260623_051158_9f51914e-0425-4ca5-9ef7-f0b75efa7fe7.png",
    'drift.png'         => "$CDN/hf_20260623_051159_b98712ec-6cae-486a-8eeb-bcf43d64abd0.png",
    'sunset-cruise.png' => "$CDN/hf_20260623_051201_1ebbcf04-acd2-4bf4-a55f-84d42f419da4.png",
    'downtown.png'      => "$CDN/hf_20260623_051203_8930c6dd-13cd-47f6-9a4c-aeb930e6ddca.png",
    'airboat.png'       => "$CDN/hf_20260623_051204_ab8e903f-4e4d-4cb4-9a80-45b50ec3bf19.png",
    'rain-neon.png'     => "$CDN/hf_20260623_051206_1ed13e5f-9eec-481b-9447-038f94e4f6f8.png",
    'marina-aerial.png' => "$CDN/hf_20260623_051207_3b156ad6-e433-495e-abdf-fb371e443010.png",
    'street-market.png' => "$CDN/hf_20260623_051209_eb2cb1ec-db91-4cb3-ad4d-47b9b75fd741.png",
    'artdeco.png'       => "$CDN/hf_20260623_051210_2f185463-a755-4792-9f9b-79cb47342876.png",
    'peninsula.png'     => "$CDN/hf_20260623_051212_8e2e00cb-cc03-4ff5-99eb-6ec9013beaa8.png",
    'graffiti.png'      => "$CDN/hf_20260623_051213_ff78c309-7dfa-42fd-9752-3d98dbd3b3dd.png",
    'pool-party.png'    => "$CDN/hf_20260623_051240_70d3f9f2-4d9a-49e4-82f0-775592fb6305.png",
    'beach-sunset.png'  => "$CDN/hf_20260623_051242_b40cffae-58bd-4d8e-b8f7-289dfadab34c.png",
    'gas-station.png'   => "$CDN/hf_20260623_051244_7d6a53a8-37f6-454c-91bc-0d633e60f4e7.png",
    'muscle-diner.png'  => "$CDN/hf_20260623_051245_1730735e-4514-4c58-8dd5-33116874b3e8.png",
    'heli-night.png'    => "$CDN/hf_20260623_051246_3d6f79b6-f3e3-426d-b75a-524655ca321b.png",
    'desert-road.png'   => "$CDN/hf_20260623_051248_eb431cd2-1e9a-4bdf-9999-565d46751b80.png",
    'bridge.png'        => "$CDN/hf_20260623_051250_23208920-343a-463c-98d8-e00a54050510.png",
    'casino.png'        => "$CDN/hf_20260623_051251_b5e4911e-e0e6-4a09-8e00-d0f27e089be5.png",
    'boardwalk.png'     => "$CDN/hf_20260623_051253_b029b684-5601-4fda-a0ae-b871d715ece5.png",
    'storm.png'         => "$CDN/hf_20260623_051254_94804c3a-30d2-4b12-9a67-31138c601a7f.png",

    // --- Véhicules ---
    'veh-supercar.png'  => "$CDN/hf_20260622_221229_4a7dd5a9-3bd6-4c05-a01d-8f1731e6b58e.png",
    'veh-muscle.png'    => "$CDN/hf_20260622_221231_d13edf33-58a7-4409-91d1-66526e8ab0ac.png",
    'veh-boat.png'      => "$CDN/hf_20260622_221413_6620c79c-ce34-4e6e-bfb6-f85868a62be7.png",
    'veh-swamp.png'     => "$CDN/hf_20260622_221415_dc91409f-d15d-4d6e-9d6d-739c52d3b0f3.png",
    'veh-vtol.png'      => "$CDN/hf_20260622_221417_f592d488-c0b3-4de3-aba5-91762ab1495c.png",

    // --- Boutique (produits physiques) ---
    'poster-synthwave.png' => "$CDN/hf_20260623_051256_5940fb4d-72d5-4aa3-bfc8-eb7ced303f63.png",
    'poster-palmsunset.png'=> "$CDN/hf_20260623_051258_5f9cbaa3-21d7-4a62-aa63-653021fa2974.png",
    'poster-supercar.png'  => "$CDN/hf_20260623_051259_03868fcf-8787-479d-b5d0-b7222921bfa8.png",
    'poster-skyline.png'   => "$CDN/hf_20260623_051302_5c3d29b1-9f56-44ea-9f08-8c96edc60c66.png",
    'poster-flamingo.png'  => "$CDN/hf_20260623_051303_dadacee3-b366-4b7d-8167-dee26053df47.png",
    'tshirt.png'   => "$CDN/hf_20260623_051506_74f1c249-4a41-432a-bee5-f34c666b4c39.png",
    'hoodie.png'   => "$CDN/hf_20260623_051507_cf1b9312-a44b-43cf-85f6-0a01087eb831.jpeg",
    'cap.png'      => "$CDN/hf_20260623_051508_db7ccee0-e525-4993-b13b-04eff3b90cc2.png",
    'mug.png'      => "$CDN/hf_20260623_051510_bb9e1069-7423-4063-a4d5-6f456e279d3d.png",
    'mousepad.png' => "$CDN/hf_20260623_051510_ce83737a-c826-492b-a139-e17bc53be523.png",
    'console.png'  => "$CDN/hf_20260623_051512_ec364c7e-92be-4538-8d78-8456a695a966.png",
    'game-case.png'=> "$CDN/hf_20260623_051513_7d55e51b-c7ff-4365-858a-afc72116bffa.png",

    // --- Identité de marque GTA VI (générée par IA, choisie par l'admin) ---
    'brand-logo.png'    => "$CDN/hf_20260626_182850_0592a8bf-3d33-4cdd-a807-3274feb08f15.png", // Concept 2 (carré)
    'brand-cover.png'   => "$CDN/hf_20260626_182852_ecc79a27-8e81-41be-ade6-c0c28719e5e3.png", // Concept 3 (bannière FB)
    'brand-profile.png' => "$CDN/hf_20260626_182854_c081a544-8908-4025-995e-88a829cbc378.png", // Concept 4 (profil rond)

    // --- Goodies (t-shirts, mugs, stylo, carnet, tote, stickers…) ---
    'shop-pen.png'             => "$CDN/hf_20260625_155811_6e93c6d1-3e81-42d6-b65b-6ff5ede215f3.jpeg",
    'shop-notebook.png'        => "$CDN/hf_20260625_155815_525a4f1a-5cc2-4fb3-b55f-fb8dc523483f.png",
    'shop-tshirt-palm.png'     => "$CDN/hf_20260625_155820_ea5d2938-71c3-4437-ba33-6f03af076c21.png",
    'shop-tshirt-flamingo.png' => "$CDN/hf_20260625_155823_434729fa-0674-47b3-aee6-980c3567a803.png",
    'shop-mug-skyline.png'     => "$CDN/hf_20260625_155826_3fc394b8-4e51-4c32-800c-07a9c546623c.png",
    'shop-mug-enamel.png'      => "$CDN/hf_20260625_155828_72b3b1a0-e4c1-45e6-8098-4c2408197f79.jpeg",
    'shop-tote.png'            => "$CDN/hf_20260625_155830_64bcbc9a-9e13-4c9e-b639-d470c35cd1e4.jpeg",
    'shop-stickers.png'        => "$CDN/hf_20260625_155832_361f133b-dd7f-47c1-aa1f-303231335e8e.jpeg",
    'shop-phonecase.png'       => "$CDN/hf_20260625_155834_9985ccce-c488-49b3-9d98-6e7fbbe741c0.jpeg",
    'shop-keychain.png'        => "$CDN/hf_20260625_155836_f0940fe4-2acf-4f70-aea4-6015d69635fc.jpeg",
];

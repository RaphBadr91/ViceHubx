<?php
/**
 * ViceHub X — Sources CDN des wallpapers (fichiers propres).
 * Si le fichier local /storage/wallpapers/<nom>.png est absent, le serveur le
 * télécharge automatiquement depuis ces URL (puis le met en cache localement).
 * Le fichier propre reste privé : seul un aperçu filigrané est exposé.
 */
$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E';

return [
    // Collection originale (8 wallpapers)
    'wall-skyline'   => "$CDN/hf_20260623_172315_397149d2-53ae-4220-a743-24d7d3a84772.png",
    'wall-beach'     => "$CDN/hf_20260623_172340_91a21628-212b-4981-839b-87bc2342942c.png",
    'wall-supercar'  => "$CDN/hf_20260623_172342_3e4d7750-f193-4071-9e34-49f99fbe1f8f.png",
    'wall-aerial'    => "$CDN/hf_20260623_172345_bc983d30-35a6-4e84-a0dc-614d8b9c76b2.png",
    'wall-synthwave' => "$CDN/hf_20260623_172348_9c7d9133-abf1-4bfa-8b48-f85f1b8102ef.png",
    'wall-nightlife' => "$CDN/hf_20260623_172351_d3bd540a-1165-4f07-84e8-d6a15a9f016e.png",
    'wall-marina'    => "$CDN/hf_20260623_172353_b5db090c-aff9-45fb-8daa-70865c61e5fd.png",
    'wall-flamingo'  => "$CDN/hf_20260623_172356_17c8f58c-9bd4-4442-ae37-a76cfa377f9f.png",

    // Nouvelle collection « Monde de Vice City » (20 wallpapers HD)
    'wp-aerial-sunset' => "$CDN/hf_20260624_160213_a8dde56e-a7b6-4675-b6cc-19b00f8b2fb3.png",
    'wp-rain-street'   => "$CDN/hf_20260624_160215_66a3f2d1-c245-47df-80b4-d6d3e6123382.png",
    'wp-pink-cruiser'  => "$CDN/hf_20260624_160216_daf84359-c67f-4d83-b979-ae393d07e3d3.png",
    'wp-downtown-blue' => "$CDN/hf_20260624_160218_751a7b1a-6add-4a53-9209-54d6c3f86380.png",
    'wp-marina-dusk'   => "$CDN/hf_20260624_160219_a0b0cda5-b676-482d-b119-db37b0b95729.png",
    'wp-synthwave'     => "$CDN/hf_20260624_160221_e37257d0-aeda-4a30-9e82-0bad61a4c462.png",
    'wp-club-alley'    => "$CDN/hf_20260624_160222_b0dd88f5-f1e8-485b-84f7-75730758b931.png",
    'wp-speedboat'     => "$CDN/hf_20260624_160223_69abedff-85f7-4778-985f-e280b829c21f.png",
    'wp-muscle-diner'  => "$CDN/hf_20260624_160225_63bd84e6-8307-4424-b424-ce53a25e3095.png",
    'wp-heli-night'    => "$CDN/hf_20260624_160225_855abbb8-c647-3646-9646-61bbbaf3c9a2.png",
    'wp-airboat'       => "$CDN/hf_20260624_160236_0a0829d1-61af-4224-a85f-6aaf3077cf6c.png",
    'wp-ocean-drive'   => "$CDN/hf_20260624_160237_398a98fd-1f0a-4373-9a30-deb8540184ca.png",
    'wp-desert-road'   => "$CDN/hf_20260624_160241_44820ee6-bf15-47e5-8807-0bb28d751e01.png",
    'wp-casino'        => "$CDN/hf_20260624_160244_f7cb9572-3cbc-407a-8c4d-e097c5470e61.png",
    'wp-pool-party'    => "$CDN/hf_20260624_160245_21c5bd5c-fa68-428e-af24-6154599e270d.png",
    'wp-flamingo'      => "$CDN/hf_20260624_160246_d956a298-92c5-4cd3-be4a-b95729cd7d7f.png",
    'wp-storm-bay'     => "$CDN/hf_20260624_160247_c844ed48-a990-469b-bc09-0e88aaba1f34.png",
    'wp-street-market' => "$CDN/hf_20260624_160250_6ccf60d2-cbbf-4ba1-92f4-8992dcae825c.png",
    'wp-beach-sunset'  => "$CDN/hf_20260624_160251_2bef069b-d4a6-4bc4-a6a7-a826f93b12ae.png",
    'wp-bridge'        => "$CDN/hf_20260624_160905_dd09256f-693f-43cd-95d8-00cd52c3e074.png",

    // Collection thématique « Voiture / Avion / Ville / Nuit / Fille » (30 wallpapers)
    'wp-car-pink-vice'      => "$CDN/hf_20260625_153324_4270dd01-04f4-4b75-9a89-8e7cf05c5824.png",
    'wp-car-supercar-night' => "$CDN/hf_20260625_153350_f1920296-c799-4bc1-beef-f52f1e010e60.png",
    'wp-car-muscle-beach'   => "$CDN/hf_20260625_153352_e217b78c-88c5-456c-9675-7c48394ac7fc.png",
    'wp-car-lowrider'       => "$CDN/hf_20260625_153354_6cfd80f2-6ae3-4b48-b66d-2c6246e90946.png",
    'wp-car-offroad-glades' => "$CDN/hf_20260625_153356_7d64c9b5-03b7-4da7-9e14-1a19bc8a044c.png",
    'wp-car-classic-deco'   => "$CDN/hf_20260625_153359_4595764a-7139-4365-a397-5b21025bba92.png",
    'wp-plane-seaplane'     => "$CDN/hf_20260625_153405_dda4e098-c92b-4ef7-a5f0-f27fb1b5ee4e.png",
    'wp-plane-jet-skyline'  => "$CDN/hf_20260625_153409_7b217230-57f9-40ad-92c0-6b05475fcc1e.png",
    'wp-plane-biplane'      => "$CDN/hf_20260625_153411_d8215436-4dde-4bab-8e29-06eaee8b5afc.png",
    'wp-plane-fighter-storm'=> "$CDN/hf_20260625_153442_c53fae51-c6b7-4b56-b19e-26e5ba280ea6.png",
    'wp-plane-heli-city'    => "$CDN/hf_20260625_153414_a2935ee5-c201-41b7-8d6e-1b6f74a662eb.png",
    'wp-city-aerial-day'    => "$CDN/hf_20260625_153444_8b78dd17-c41b-46ab-82f5-54add050dd73.png",
    'wp-city-downtown-dusk' => "$CDN/hf_20260625_153447_5bcf1dca-dcc3-43cb-876c-1c9b11571485.png",
    'wp-city-canal-district'=> "$CDN/hf_20260625_153449_037c8326-e46d-4461-ab84-6d07b391e2ee.png",
    'wp-city-skyline-water' => "$CDN/hf_20260625_153452_e36bbed2-e99b-4c17-9695-e02385f27502.png",
    'wp-city-bridge-sunset' => "$CDN/hf_20260625_153500_e2df5398-785a-4c3a-8402-07a7fcf37bab.png",
    'wp-city-market-street' => "$CDN/hf_20260625_153515_13d735cf-6918-4ade-bf72-648f5874740c.png",
    'wp-night-neon-strip'   => "$CDN/hf_20260625_153519_b00a1ca7-207a-4d71-93c4-c7c29d60f778.png",
    'wp-night-rooftop-pool' => "$CDN/hf_20260625_153521_56ed2dd4-2423-4c7b-8f91-7357b00e9b27.png",
    'wp-night-rain-street'  => "$CDN/hf_20260625_153524_0973bb07-de0a-440e-8306-ac507eff7b7d.png",
    'wp-night-club-alley'   => "$CDN/hf_20260625_153554_2fbfcdcb-6f2d-4c32-b5e4-eb8ce114ac88.png",
    'wp-night-pier-lights'  => "$CDN/hf_20260625_153558_8f6d8664-bbc4-4a18-a723-1311e3a9e7c0.png",
    'wp-night-skyline-storm'=> "$CDN/hf_20260625_153601_1f7229ac-a2cb-40fd-9f06-93fad3e2f16d.png",
    'wp-girl-convertible'   => "$CDN/hf_20260625_153603_a8a04fd4-f653-49ec-998e-50d795c2df4a.png",
    'wp-girl-beach-sunset'  => "$CDN/hf_20260625_153608_a01e87d3-70bc-40c7-8460-0cf7fa2783e9.png",
    'wp-girl-neon-portrait' => "$CDN/hf_20260625_153611_579a60b1-c67e-40d1-9a8a-d5c7be25b36a.png",
    'wp-girl-rooftop'       => "$CDN/hf_20260625_153614_d42c3d37-946f-425e-be15-833e87d45aca.png",
    'wp-girl-poolside'      => "$CDN/hf_20260625_153616_9546b806-66c1-440e-ae3d-2d33f50f177d.png",
    'wp-girl-biker'         => "$CDN/hf_20260625_153620_ccd7d6d3-e0d5-421f-b450-6a8ae37874f9.png",
    'wp-girl-marina'        => "$CDN/hf_20260625_153622_233812be-fd61-4e3d-9ace-c411567877ee.png",
];

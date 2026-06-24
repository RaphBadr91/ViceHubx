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
];

<?php
/**
 * ViceHub X — Sources CDN des wallpapers (fichiers propres).
 * Si le fichier local /storage/wallpapers/<nom>.png est absent, le serveur le
 * télécharge automatiquement depuis ces URL (puis le met en cache localement).
 * Le fichier propre reste privé : seul un aperçu filigrané est exposé.
 */
$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E';

return [
    'wall-skyline'   => "$CDN/hf_20260623_172315_397149d2-53ae-4220-a743-24d7d3a84772.png",
    'wall-beach'     => "$CDN/hf_20260623_172340_91a21628-212b-4981-839b-87bc2342942c.png",
    'wall-supercar'  => "$CDN/hf_20260623_172342_3e4d7750-f193-4071-9e34-49f99fbe1f8f.png",
    'wall-aerial'    => "$CDN/hf_20260623_172345_bc983d30-35a6-4e84-a0dc-614d8b9c76b2.png",
    'wall-synthwave' => "$CDN/hf_20260623_172348_9c7d9133-abf1-4bfa-8b48-f85f1b8102ef.png",
    'wall-nightlife' => "$CDN/hf_20260623_172351_d3bd540a-1165-4f07-84e8-d6a15a9f016e.png",
    'wall-marina'    => "$CDN/hf_20260623_172353_b5db090c-aff9-45fb-8daa-70865c61e5fd.png",
    'wall-flamingo'  => "$CDN/hf_20260623_172356_17c8f58c-9bd4-4442-ae37-a76cfa377f9f.png",
];

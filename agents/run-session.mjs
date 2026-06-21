#!/usr/bin/env node
/**
 * ViceHub X — Orchestrateur runtime (plan de données) du "Web Scraper Agent".
 *
 * Crée une SESSION sur l'agent pré-créé (cf. agents/setup.sh), ouvre le flux
 * d'événements, puis répond aux appels d'outils custom :
 *   - browser_use_extract  → appelle Browser Use Cloud côté hôte (clé jamais
 *                            exposée au conteneur de l'agent).
 *   - submit_extraction    → récupère le payload final normalisé.
 *
 * Prérequis :
 *   npm i @anthropic-ai/sdk
 *   export ANTHROPIC_API_KEY="sk-ant-..."
 *   export BROWSER_USE_API_KEY="..."            # optionnel : sinon fallback fetch
 *   # AGENT_ID / ENV_ID lus depuis agents/.agent-ids (généré par setup.sh)
 *
 * Usage :
 *   node agents/run-session.mjs "Scrape les 5 derniers articles de https://exemple.com/news"
 */
import Anthropic from '@anthropic-ai/sdk';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));

/* ---- IDs (agents/.agent-ids ou variables d'env) ---- */
function loadIds() {
  const out = { AGENT_ID: process.env.AGENT_ID, ENV_ID: process.env.ENV_ID };
  try {
    for (const line of readFileSync(join(here, '.agent-ids'), 'utf8').split('\n')) {
      const [k, v] = line.split('=');
      if (k && v && !out[k.trim()]) out[k.trim()] = v.trim();
    }
  } catch { /* fichier optionnel */ }
  return out;
}

/* ---- Browser Use Cloud (exécuté côté hôte avec la clé hôte) ---- */
async function browserUseExtract({ url, extraction_goal }) {
  const key = process.env.BROWSER_USE_API_KEY;
  const api = process.env.BROWSER_USE_API_URL || 'https://api.browser-use.com/api/v1/run-task';
  if (!key) {
    // Fallback hors-ligne : simple fetch (pages statiques uniquement).
    const res = await fetch(url, { headers: { 'User-Agent': 'ViceHubX-Scraper/1.0' } });
    const text = await res.text();
    return { engine: 'fetch-fallback', status: res.status, goal: extraction_goal, html: text.slice(0, 20000) };
  }
  const res = await fetch(api, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${key}` },
    body: JSON.stringify({ task: `${extraction_goal} — URL: ${url}` }),
  });
  return await res.json();
}

async function main() {
  const prompt = process.argv.slice(2).join(' ').trim();
  if (!prompt) {
    console.error('Usage: node agents/run-session.mjs "<requête de scraping>"');
    process.exit(1);
  }
  const { AGENT_ID, ENV_ID } = loadIds();
  if (!AGENT_ID || !ENV_ID) {
    console.error('AGENT_ID / ENV_ID manquants. Lancez d’abord : bash agents/setup.sh');
    process.exit(1);
  }

  const client = new Anthropic(); // lit ANTHROPIC_API_KEY
  const session = await client.beta.sessions.create({
    agent: AGENT_ID,
    environment_id: ENV_ID,
    title: 'ViceHub X — scraping run',
  });
  console.log(`Session : https://platform.claude.com/workspaces/default/sessions/${session.id}\n`);

  // Stream-first puis envoi du message (cf. patterns Managed Agents).
  const stream = await client.beta.sessions.events.stream(session.id);
  await client.beta.sessions.events.send(session.id, {
    events: [{ type: 'user.message', content: [{ type: 'text', text: prompt }] }],
  });

  let finalPayload = null;

  for await (const event of stream) {
    switch (event.type) {
      case 'agent.message':
        for (const b of event.content) if (b.type === 'text') process.stdout.write(b.text);
        break;

      case 'agent.custom_tool_use': {
        let result;
        if (event.name === 'browser_use_extract') {
          result = await browserUseExtract(event.input);
        } else if (event.name === 'submit_extraction') {
          finalPayload = event.input;
          result = { ok: true };
        } else {
          result = { error: `Outil inconnu : ${event.name}` };
        }
        await client.beta.sessions.events.send(session.id, {
          events: [{
            type: 'user.custom_tool_result',
            custom_tool_use_id: event.id,
            content: [{ type: 'text', text: JSON.stringify(result) }],
          }],
        });
        break;
      }

      case 'session.status_terminated':
        return finish(finalPayload);

      case 'session.status_idle':
        if (event.stop_reason?.type !== 'requires_action') return finish(finalPayload);
        break;
    }
  }
  finish(finalPayload);
}

function finish(payload) {
  console.log('\n\n=== Extraction finale ===');
  console.log(JSON.stringify(payload ?? { data: [], notes: 'Aucun payload soumis.' }, null, 2));
  process.exit(0);
}

main().catch((err) => { console.error(err); process.exit(1); });

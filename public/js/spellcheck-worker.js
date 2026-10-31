/*
 * Etude World – spell checker that runs in the background (a "web worker"),
 * so typing never slows down. A word counts as correct if it is valid in
 * Canadian, US or British English (CELPIP accepts all three).
 *
 * Dictionaries: public/vendor/spellcheck (Hunspell dictionaries, used with Typo.js)
 */
importScripts('../vendor/spellcheck/typo.js');

const BASE = '../vendor/spellcheck/';
const dicts = [];
const cache = new Map();

function load(code) {
    const get = file => { const x = new XMLHttpRequest(); x.open('GET', BASE + file, false); x.send(); return x.responseText; };
    dicts.push(new Typo(code, get(code + '.aff'), get(code + '.dic')));
}

try { ['en_CA', 'en_US', 'en_GB'].forEach(load); } catch (e) { /* if loading fails, nothing gets underlined */ }

function isCorrect(word) {
    if (cache.has(word)) return cache.get(word);
    let ok = dicts.length === 0 || dicts.some(d => d.check(word));
    // Accept words written in capitals (e.g. "CELPIP") and simple possessives ("Canada's").
    if (!ok && /^[A-Z]{2,}$/.test(word)) ok = true;
    if (!ok && /'s$/i.test(word)) ok = dicts.some(d => d.check(word.slice(0, -2)));
    cache.set(word, ok);
    return ok;
}

self.onmessage = e => {
    const { id, text } = e.data;
    const wrong = [];
    // Words: letters with optional inner apostrophes. Numbers, emails and web addresses are skipped.
    const re = /[A-Za-zÀ-ÿ]+(?:['’][A-Za-zÀ-ÿ]+)*/g;
    let m;
    while ((m = re.exec(text))) {
        const before = text[m.index - 1] || '';
        const after = text[m.index + m[0].length] || '';
        if (/[\d@/._]/.test(before) || /[\d@/_]/.test(after)) continue;   // part of a number, email or link
        const word = m[0].replace(/’/g, "'");
        if (word.length < 2) continue;
        if (!isCorrect(word)) wrong.push([m.index, m.index + m[0].length]);
    }
    self.postMessage({ id, wrong, ready: dicts.length > 0 });
};

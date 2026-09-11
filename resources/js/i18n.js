import { createI18n } from 'vue-i18n';

import en from './locales/en.json';
import fr from './locales/fr.json';
import es from './locales/es.json';
import de from './locales/de.json';
import it from './locales/it.json';
import ja from './locales/ja.json';
import ptBr from './locales/pt-br.json';

const messages = {
    en,
    fr,
    es,
    de,
    it,
    ja,
    'pt-br': ptBr,
};

// Récupère la locale sauvegardée ou celle du navigateur, sinon fallback en anglais
function getInitialLocale() {
    const saved = localStorage.getItem('app_locale');
    if (saved && messages[saved]) {
        return saved;
    }

    const browserLocale = navigator.language.toLowerCase();
    if (messages[browserLocale]) {
        return browserLocale;
    }

    const shortLocale = browserLocale.split('-')[0];
    if (messages[shortLocale]) {
        return shortLocale;
    }

    return 'en';
}

export const i18n = createI18n({
    legacy: false, // active la Composition API
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    messages,
});
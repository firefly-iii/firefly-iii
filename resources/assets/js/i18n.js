// Create VueI18n instance with options
module.exports = new vuei18n({
    locale: document.documentElement.lang, // set locale
    fallbackLocale: 'en',
    messages: {
        'cs': require('./locales/cs.json'),
        'de': require('./locales/de.json'),
        'en': require('./locales/en.json'),
        'es': require('./locales/es.json'),
        'el': require('./locales/el.json'),
        'fr': require('./locales/fr.json'),
        'hu': require('./locales/hu.json'),
        'id': require('./locales/id.json'),
        'it': require('./locales/it.json'),
        'nl': require('./locales/nl.json'),
        'no': require('./locales/no.json'),
        'pl': require('./locales/pl.json'),
        'fi': require('./locales/fi.json'),
        'pt-br': require('./locales/pt-br.json'),
        'ro': require('./locales/ro.json'),
        'ru': require('./locales/ru.json'),
        'zh': require('./locales/zh.json'),
        'zh-tw': require('./locales/zh-tw.json'),
        'zh-cn': require('./locales/zh-cn.json'),
        'sv': require('./locales/sv.json'),
        'vi': require('./locales/vi.json'),
    }
});

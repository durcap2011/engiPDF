import { createI18n } from 'vue-i18n'
import en from './locales/en'
import it from './locales/it'
import es from './locales/es'
import de from './locales/de'
import fr from './locales/fr'

const saved = localStorage.getItem('engipdf-lang') as string | null
const defaultLang = saved || 'en'

const i18n = createI18n({
  legacy: false,
  locale: defaultLang,
  fallbackLocale: 'en',
  messages: { en, it, es, de, fr },
})

export function setLocale(lang: string) {
  ;(i18n.global.locale as any).value = lang
  localStorage.setItem('engipdf-lang', lang)
}

export default i18n

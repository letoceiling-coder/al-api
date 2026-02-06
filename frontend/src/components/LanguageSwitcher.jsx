import { useTranslation } from 'react-i18next'
import './LanguageSwitcher.css'

const LanguageSwitcher = () => {
  const { i18n } = useTranslation()
  const currentLang = i18n.language

  const switchLanguage = (lang) => {
    i18n.changeLanguage(lang)
  }

  return (
    <div className="language-switcher">
      <button
        className={`lang-btn ${currentLang === 'ru' ? 'active' : ''}`}
        onClick={() => switchLanguage('ru')}
      >
        RU
      </button>
      <button
        className={`lang-btn ${currentLang === 'en' ? 'active' : ''}`}
        onClick={() => switchLanguage('en')}
      >
        EN
      </button>
    </div>
  )
}

export default LanguageSwitcher

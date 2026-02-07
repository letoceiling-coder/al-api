import { useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import './Page.css'

const Swagger = () => {
  const { t } = useTranslation()

  useEffect(() => {
    // Redirect to Swagger UI
    window.location.href = 'https://api.siteaccess.ru/api/documentation'
  }, [])

  return (
    <div className="page">
      <div className="page-container">
        <h1>📘 {t('swagger.title')}</h1>
        <p>{t('swagger.redirecting')}</p>
      </div>
    </div>
  )
}

export default Swagger

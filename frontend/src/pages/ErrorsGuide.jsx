import { useTranslation } from 'react-i18next'
import './Page.css'

const ErrorsGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>⚠️ {t('errors.title')}</h1>
        <p>{t('errors.subtitle')}</p>
        
        <section>
          <h2>{t('errors.errorFormat')}</h2>
          <pre className="code-block">
{`{
  "type": "https://api.siteaccess.ru/errors/validation",
  "title": "Validation Error",
  "status": 422,
  "detail": "The given data was invalid.",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000"
}`}
          </pre>
        </section>

        <section>
          <h2>{t('errors.errorTypes')}</h2>
          <div className="error-types">
            <div className="error-type">
              <strong>401</strong> - {t('errors.unauthorized')}
            </div>
            <div className="error-type">
              <strong>422</strong> - {t('errors.validationError')}
            </div>
            <div className="error-type">
              <strong>429</strong> - {t('errors.rateLimitExceeded')}
            </div>
            <div className="error-type">
              <strong>500</strong> - {t('errors.serverError')}
            </div>
          </div>
        </section>
      </div>
    </div>
  )
}

export default ErrorsGuide

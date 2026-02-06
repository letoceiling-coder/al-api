import { useTranslation } from 'react-i18next'
import './Page.css'

const ErrorsGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>⚠️ Error Handling (RFC 7807)</h1>
        <p>Стандартизированная обработка ошибок API</p>
        
        <section>
          <h2>Формат ошибки</h2>
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
          <h2>Типы ошибок</h2>
          <div className="error-types">
            <div className="error-type">
              <strong>401</strong> - Unauthorized
            </div>
            <div className="error-type">
              <strong>422</strong> - Validation Error
            </div>
            <div className="error-type">
              <strong>429</strong> - Rate Limit Exceeded
            </div>
            <div className="error-type">
              <strong>500</strong> - Server Error
            </div>
          </div>
        </section>
      </div>
    </div>
  )
}

export default ErrorsGuide

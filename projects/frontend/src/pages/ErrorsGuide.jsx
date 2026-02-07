import { useTranslation } from 'react-i18next'
import './Page.css'

const ErrorsGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>{t('errors.title')}</h1>
        <p className="subtitle">
          {t('errors.subtitle')} <span className="spec-badge">{t('errors.specBadge')}</span>
        </p>

        <section>
          <h2>{t('errors.overview')}</h2>
          <p>{t('errors.overviewDesc')}</p>

          <h3>{t('errors.errorFormat')}</h3>
          <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/error-type",
  "title": "Human-readable error title",
  "status": 400,
  "detail": "Detailed explanation of the error",
  "instance": "/api/v1/endpoint",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": "2026-02-06T16:00:00Z"
}`}</code></pre>

          <h3>{t('errors.responseHeaders')}</h3>
          <table>
            <thead>
              <tr>
                <th>Header</th>
                <th>{t('common.description')}</th>
                <th>Example</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>Content-Type</code></td>
                <td>{t('errors.alwaysProblemJson')}</td>
                <td><code>application/problem+json</code></td>
              </tr>
              <tr>
                <td><code>X-Trace-ID</code></td>
                <td>{t('errors.uniqueIdentifier')}</td>
                <td><code>550e8400-...</code></td>
              </tr>
              <tr>
                <td><code>X-API-Version</code></td>
                <td>{t('errors.apiVersionUsed')}</td>
                <td><code>v1</code></td>
              </tr>
              <tr>
                <td><code>Retry-After</code></td>
                <td>{t('errors.secondsToWait')}</td>
                <td><code>60</code></td>
              </tr>
            </tbody>
          </table>
        </section>

        <section>
          <h2>{t('errors.errorTypes')}</h2>

          <div className="error-card">
            <h4><span className="error-code">401</span> {t('errors.authRequired')}</h4>
            <p><strong>{t('errors.type')}:</strong> <code>/docs/errors/authentication-required</code></p>
            <p><strong>{t('errors.authRequiredWhen')}</strong> {t('errors.authRequiredWhenDesc')}</p>
            <p><strong>{t('errors.authRequiredSolution')}</strong> {t('errors.authRequiredSolutionDesc')}</p>
            <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/authentication-required",
  "title": "Authentication Required",
  "status": 401,
  "detail": "Unauthenticated. Please provide a valid bearer token.",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z"
}`}</code></pre>
          </div>

          <div className="error-card">
            <h4><span className="error-code">401</span> {t('errors.invalidApiKey')}</h4>
            <p><strong>{t('errors.type')}:</strong> <code>/docs/errors/invalid-api-key</code></p>
            <p><strong>{t('errors.invalidApiKeyWhen')}</strong> {t('errors.invalidApiKeyWhenDesc')}</p>
            <p><strong>{t('errors.invalidApiKeySolution')}</strong> {t('errors.invalidApiKeySolutionDesc')}</p>
            <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/invalid-api-key",
  "title": "Invalid API Key",
  "status": 401,
  "detail": "The provided Gemini API key is invalid or expired",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z",
  "provider": "gemini"
}`}</code></pre>
          </div>

          <div className="error-card">
            <h4><span className="error-code">404</span> {t('errors.notFound')}</h4>
            <p><strong>{t('errors.type')}:</strong> <code>/docs/errors/not-found</code></p>
            <p><strong>{t('errors.notFoundWhen')}</strong> {t('errors.notFoundWhenDesc')}</p>
            <p><strong>{t('errors.notFoundSolution')}</strong> {t('errors.notFoundSolutionDesc')}</p>
            <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/not-found",
  "title": "Not Found",
  "status": 404,
  "detail": "The requested resource was not found.",
  "instance": "/api/v1/user/keys/999",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z"
}`}</code></pre>
          </div>

          <div className="error-card">
            <h4><span className="error-code">422</span> {t('errors.validationError')}</h4>
            <p><strong>{t('errors.type')}:</strong> <code>/docs/errors/validation-error</code></p>
            <p><strong>{t('errors.validationErrorWhen')}</strong> {t('errors.validationErrorWhenDesc')}</p>
            <p><strong>{t('errors.validationErrorSolution')}</strong> {t('errors.validationErrorSolutionDesc')}</p>
            <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/validation-error",
  "title": "Validation Error",
  "status": 422,
  "detail": "The given data was invalid.",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z",
  "errors": {
    "provider": ["The provider field is required."],
    "model": ["The model field is required."],
    "prompt": ["The prompt field must be at least 1 character."]
  }
}`}</code></pre>
          </div>

          <div className="error-card">
            <h4><span className="error-code">429</span> {t('errors.rateLimitExceeded')}</h4>
            <p><strong>{t('errors.type')}:</strong> <code>/docs/errors/rate-limit-exceeded</code></p>
            <p><strong>{t('errors.rateLimitExceededWhen')}</strong> {t('errors.rateLimitExceededWhenDesc')}</p>
            <p><strong>{t('errors.rateLimitExceededSolution')}</strong> {t('errors.rateLimitExceededSolutionDesc')}</p>
            <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/rate-limit-exceeded",
  "title": "Rate Limit Exceeded",
  "status": 429,
  "detail": "Daily request limit of 100 exceeded. Resets tomorrow.",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z",
  "retry_after": 86400
}`}</code></pre>
            <p><strong>{t('errors.rateLimitExceededHeaders')}</strong> <code>Retry-After: 86400</code></p>
          </div>

          <div className="error-card">
            <h4><span className="error-code">500</span> {t('errors.internalError')}</h4>
            <p><strong>{t('errors.type')}:</strong> <code>/docs/errors/internal-error</code></p>
            <p><strong>{t('errors.internalErrorWhen')}</strong> {t('errors.internalErrorWhenDesc')}</p>
            <p><strong>{t('errors.internalErrorSolution')}</strong> {t('errors.internalErrorSolutionDesc')}</p>
            <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/internal-error",
  "title": "Internal Server Error",
  "status": 500,
  "detail": "An unexpected error occurred",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z"
}`}</code></pre>
          </div>

          <div className="error-card">
            <h4><span className="error-code">502</span> {t('errors.providerError')}</h4>
            <p><strong>{t('errors.type')}:</strong> <code>/docs/errors/ai-provider-error</code></p>
            <p><strong>{t('errors.providerErrorWhen')}</strong> {t('errors.providerErrorWhenDesc')}</p>
            <p><strong>{t('errors.providerErrorSolution')}</strong> {t('errors.providerErrorSolutionDesc')}</p>
            <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/ai-provider-error",
  "title": "AI Provider Error",
  "status": 502,
  "detail": "Gemini API returned an error: Invalid request",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z",
  "provider": "gemini"
}`}</code></pre>
          </div>
        </section>

        <section>
          <h2>{t('errors.usingTraceId')}</h2>
          <p>{t('errors.usingTraceIdDesc')}</p>
          <ul>
            <li>{t('errors.usingTraceIdItem1')}</li>
            <li>{t('errors.usingTraceIdItem2')}</li>
            <li>{t('errors.usingTraceIdItem3')}</li>
          </ul>
          <p>{t('errors.usingTraceIdCustom')}</p>
          <pre className="code-block"><code>X-Trace-ID: your-custom-trace-id-12345</code></pre>
        </section>

        <section>
          <h2>{t('errors.debugMode')}</h2>
          <p>{t('errors.debugModeDesc')}</p>
          <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/docs/errors/internal-error",
  "title": "Internal Server Error",
  "status": 500,
  "detail": "Call to undefined method...",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "timestamp": "2026-02-06T16:00:00Z",
  "debug": {
    "exception": "ErrorException",
    "file": "/var/www/AL/app/Services/AI/GeminiService.php",
    "line": 45,
    "trace": [...]
  }
}`}</code></pre>
          <p><strong>{t('errors.debugModeWarning')}</strong></p>
        </section>

        <section>
          <h2>{t('errors.bestPractices')}</h2>
          <ul>
            <li><strong>{t('errors.bestPractice1')}</strong></li>
            <li><strong>{t('errors.bestPractice2')}</strong></li>
            <li><strong>{t('errors.bestPractice3')}</strong></li>
            <li><strong>{t('errors.bestPractice4')}</strong></li>
            <li><strong>{t('errors.bestPractice5')}</strong></li>
          </ul>
        </section>
      </div>
    </div>
  )
}

export default ErrorsGuide

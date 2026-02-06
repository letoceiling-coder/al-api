import { useState, useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import api from '../services/api'
import './Documentation.css'

const Documentation = () => {
  const { t } = useTranslation()
  const [loading, setLoading] = useState(false)
  const [testResult, setTestResult] = useState(null)

  const testAPI = async () => {
    setLoading(true)
    try {
      const response = await api.get('/test')
      setTestResult({ success: true, data: response.data })
    } catch (error) {
      setTestResult({ success: false, error: error.message })
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="documentation">
      <div className="doc-container">
        <h1>{t('docs.title')}</h1>

        <section className="doc-section">
          <h2>{t('docs.baseUrl')}</h2>
          <code className="code-block">https://api.siteaccess.ru/api</code>
        </section>

        <section className="doc-section">
          <h2>{t('docs.authentication')}</h2>
          <p>Все endpoints требуют Bearer token:</p>
          <code className="code-block">
            Authorization: Bearer YOUR_TOKEN
          </code>
          <button onClick={testAPI} className="test-btn" disabled={loading}>
            {loading ? t('common.loading') : 'Test API'}
          </button>
          {testResult && (
            <div className={`test-result ${testResult.success ? 'success' : 'error'}`}>
              <pre>{JSON.stringify(testResult, null, 2)}</pre>
            </div>
          )}
        </section>

        <section className="doc-section">
          <h2>{t('docs.endpoints')}</h2>
          <div className="endpoints-list">
            <div className="endpoint">
              <span className="method get">GET</span>
              <code>/api/v1/test</code>
              <span>Health check</span>
            </div>
            <div className="endpoint">
              <span className="method post">POST</span>
              <code>/api/v1/ai/process</code>
              <span>Process AI request</span>
            </div>
            <div className="endpoint">
              <span className="method post">POST</span>
              <code>/api/v1/ai/stream</code>
              <span>Streaming AI response</span>
            </div>
          </div>
        </section>

        <section className="doc-section">
          <h2>{t('docs.examples')}</h2>
          <div className="code-example">
            <h3>cURL</h3>
            <pre className="code-block">
{`curl -X POST https://api.siteaccess.ru/api/v1/ai/process \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "prompt": "Hello, AI!"
  }'`}
            </pre>
          </div>
        </section>
      </div>
    </div>
  )
}

export default Documentation

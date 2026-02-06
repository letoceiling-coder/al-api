import { useTranslation } from 'react-i18next'
import './Page.css'

const StreamingGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>📡 {t('streaming.title')}</h1>
        <p>{t('streaming.subtitle')}</p>
        
        <section>
          <h2>{t('streaming.endpoint')}</h2>
          <code className="code-block">POST /api/v1/ai/stream</code>
        </section>

        <section>
          <h2>{t('streaming.usageExample')}</h2>
          <pre className="code-block">
{`const eventSource = new EventSource(
  'https://api.siteaccess.ru/api/v1/ai/stream',
  {
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN',
      'Content-Type': 'application/json'
    }
  }
);

eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log(data);
};`}
          </pre>
        </section>
      </div>
    </div>
  )
}

export default StreamingGuide

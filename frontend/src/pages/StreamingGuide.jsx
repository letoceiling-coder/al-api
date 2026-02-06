import { useTranslation } from 'react-i18next'
import './Page.css'

const StreamingGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>📡 Streaming Guide (SSE)</h1>
        <p>Server-Sent Events для потоковой передачи ответов AI</p>
        
        <section>
          <h2>Endpoint</h2>
          <code className="code-block">POST /api/v1/ai/stream</code>
        </section>

        <section>
          <h2>Пример использования</h2>
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

import { useTranslation } from 'react-i18next'
import './Page.css'

const MultipartGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>📎 {t('multipart.title')}</h1>
        <p>{t('multipart.subtitle')}</p>
        
        <section>
          <h2>{t('multipart.advantages')}</h2>
          <ul>
            <li>{t('multipart.advantage1')}</li>
            <li>{t('multipart.advantage2')}</li>
            <li>{t('multipart.advantage3')}</li>
          </ul>
        </section>

        <section>
          <h2>{t('multipart.example')}</h2>
          <pre className="code-block">
{`const formData = new FormData();
formData.append('provider', 'gemini');
formData.append('model', 'gemini-1.5-pro');
formData.append('prompt', 'Analyze this image');
formData.append('files[]', fileInput.files[0]);

fetch('https://api.siteaccess.ru/api/v1/ai/process', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: formData
});`}
          </pre>
        </section>
      </div>
    </div>
  )
}

export default MultipartGuide

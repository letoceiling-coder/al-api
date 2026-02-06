import { useTranslation } from 'react-i18next'
import './Page.css'

const MultipartGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>📎 Multipart File Upload</h1>
        <p>Эффективная загрузка файлов через multipart/form-data</p>
        
        <section>
          <h2>Преимущества</h2>
          <ul>
            <li>33% экономия трафика по сравнению с Base64</li>
            <li>Поддержка больших файлов (до 100MB)</li>
            <li>Автоматическая обработка MIME типов</li>
          </ul>
        </section>

        <section>
          <h2>Пример</h2>
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

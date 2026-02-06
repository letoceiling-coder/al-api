import { useTranslation } from 'react-i18next'
import './Page.css'

const MultipartGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>{t('multipart.title')}</h1>
        <p className="subtitle">
          {t('multipart.subtitle')}
          <span className="badge new">{t('multipart.badgeNew')}</span>
          <span className="badge better">{t('multipart.badgeFaster')}</span>
          <span className="badge">{t('multipart.badgeV1')}</span>
        </p>

        <section>
          <h2>{t('multipart.whyMultipart')}</h2>
          
          <div className="success-box">
            <strong>{t('multipart.advantages')}</strong><br />
            • <strong>{t('multipart.advantage1')}</strong><br />
            • <strong>{t('multipart.advantage2')}</strong><br />
            • <strong>{t('multipart.advantage3')}</strong><br />
            • <strong>{t('multipart.advantage4')}</strong>
          </div>

          <div className="comparison">
            <div className="old">
              <h3>{t('multipart.oldWay')}</h3>
              <strong>{t('multipart.oldWayProblems')}</strong>
              <ul>
                <li>{t('multipart.oldWayProblem1')}</li>
                <li>{t('multipart.oldWayProblem2')}</li>
                <li>{t('multipart.oldWayProblem3')}</li>
              </ul>
            </div>
            <div className="new">
              <h3>{t('multipart.newWay')}</h3>
              <strong>{t('multipart.newWayPros')}</strong>
              <ul>
                <li>{t('multipart.newWayPro1')}</li>
                <li>{t('multipart.newWayPro2')}</li>
                <li>{t('multipart.newWayPro3')}</li>
              </ul>
            </div>
          </div>
        </section>

        <section>
          <h2>{t('multipart.endpoints')}</h2>
          
          <table>
            <thead>
              <tr>
                <th>Endpoint</th>
                <th>Content-Type</th>
                <th>{t('multipart.fileFormat')}</th>
                <th>{t('multipart.maxSize')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>/api/v1/ai/process</code></td>
                <td><code>application/json</code></td>
                <td>Base64 (legacy)</td>
                <td>10 MB</td>
              </tr>
              <tr>
                <td><code>/api/v1/ai/process</code></td>
                <td><code>multipart/form-data</code></td>
                <td>Binary (new)</td>
                <td>100 MB</td>
              </tr>
              <tr>
                <td><code>/api/v1/ai/stream</code></td>
                <td><code>multipart/form-data</code></td>
                <td>Binary (new)</td>
                <td>100 MB</td>
              </tr>
            </tbody>
          </table>

          <div className="info-box">
            <strong>{t('multipart.compatibility')}</strong><br />
            {t('multipart.compatibilityDesc')}
          </div>
        </section>

        <section>
          <h2>{t('multipart.supportedTypes')}</h2>

          <table>
            <thead>
              <tr>
                <th>{t('common.type')}</th>
                <th>Extensions</th>
                <th>MIME Types</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>{t('multipart.images')}</strong></td>
                <td><code>jpg, jpeg, png, gif, webp</code></td>
                <td><code>image/jpeg, image/png, image/gif, image/webp</code></td>
              </tr>
              <tr>
                <td><strong>{t('multipart.documents')}</strong></td>
                <td><code>pdf, txt, doc, docx</code></td>
                <td><code>application/pdf, text/plain, application/msword</code></td>
              </tr>
              <tr>
                <td><strong>{t('multipart.audio')}</strong></td>
                <td><code>mp3, wav</code></td>
                <td><code>audio/mpeg, audio/wav</code></td>
              </tr>
              <tr>
                <td><strong>{t('multipart.video')}</strong></td>
                <td><code>mp4</code></td>
                <td><code>video/mp4</code></td>
              </tr>
            </tbody>
          </table>
        </section>

        <section>
          <h2>{t('multipart.requestFormat')}</h2>

          <h3>{t('multipart.multipartStructure')}</h3>
          <pre className="code-block"><code>{`POST /api/v1/ai/process
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary...
Authorization: Bearer {your-token}

------WebKitFormBoundary...
Content-Disposition: form-data; name="provider"

gemini
------WebKitFormBoundary...
Content-Disposition: form-data; name="model"

gemini-1.5-pro
------WebKitFormBoundary...
Content-Disposition: form-data; name="prompt"

Analyze this image and describe what you see
------WebKitFormBoundary...
Content-Disposition: form-data; name="parameters"

{"temperature": 0.7, "max_tokens": 1024}
------WebKitFormBoundary...
Content-Disposition: form-data; name="uploaded_files[]"; filename="photo.jpg"
Content-Type: image/jpeg

[binary file data]
------WebKitFormBoundary...--`}</code></pre>

          <h3>{t('multipart.fieldNames')}</h3>
          <table>
            <thead>
              <tr>
                <th>Field</th>
                <th>{t('common.type')}</th>
                <th>{t('common.required')}</th>
                <th>{t('common.description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>provider</code></td>
                <td>string</td>
                <td>✅ {t('common.yes')}</td>
                <td>AI provider (gemini, openai)</td>
              </tr>
              <tr>
                <td><code>model</code></td>
                <td>string</td>
                <td>✅ {t('common.yes')}</td>
                <td>Model name (e.g., gemini-1.5-pro)</td>
              </tr>
              <tr>
                <td><code>prompt</code></td>
                <td>string</td>
                <td>✅ {t('common.yes')}</td>
                <td>{t('multipart.textPrompt')}</td>
              </tr>
              <tr>
                <td><code>uploaded_files[]</code></td>
                <td>file[]</td>
                <td>❌ {t('common.no')}</td>
                <td>{t('multipart.arrayOfFiles')}</td>
              </tr>
              <tr>
                <td><code>parameters</code></td>
                <td>JSON string</td>
                <td>❌ {t('common.no')}</td>
                <td>{t('multipart.modelParameters')}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section>
          <h2>{t('multipart.clientExamples')}</h2>

          <h3>{t('multipart.javascriptBrowser')}</h3>
          <pre className="code-block"><code>{`async function uploadWithFiles() {
  const formData = new FormData();
  
  // ${t('multipart.addTextFields')}
  formData.append('provider', 'gemini');
  formData.append('model', 'gemini-1.5-pro');
  formData.append('prompt', 'Analyze this image');
  
  // ${t('multipart.addParameters')}
  formData.append('parameters', JSON.stringify({
    temperature: 0.7,
    max_tokens: 1024
  }));
  
  // ${t('multipart.addFiles')}
  const fileInput = document.getElementById('fileInput');
  for (const file of fileInput.files) {
    formData.append('uploaded_files[]', file);
  }
  
  // ${t('multipart.sendRequest')}
  const response = await fetch('https://api.siteaccess.ru/api/v1/ai/process', {
    method: 'POST',
    headers: {
      'Authorization': \`Bearer \${token}\`
      // ${t('multipart.doNotSetContentType')}
    },
    body: formData
  });
  
  const result = await response.json();
  console.log(result);
}`}</code></pre>

          <h3>{t('multipart.python')}</h3>
          <pre className="code-block"><code>{`import requests

def upload_with_files():
    url = 'https://api.siteaccess.ru/api/v1/ai/process'
    
    headers = {
        'Authorization': f'Bearer {token}'
        # ${t('multipart.doNotSetContentTypePython')}
    }
    
    # ${t('multipart.formData')}
    data = {
        'provider': 'gemini',
        'model': 'gemini-1.5-pro',
        'prompt': 'Analyze this image',
        'parameters': '{"temperature": 0.7, "max_tokens": 1024}'
    }
    
    # ${t('multipart.filesToUpload')}
    files = [
        ('uploaded_files[]', ('photo.jpg', open('photo.jpg', 'rb'), 'image/jpeg')),
        ('uploaded_files[]', ('document.pdf', open('document.pdf', 'rb'), 'application/pdf'))
    ]
    
    response = requests.post(url, headers=headers, data=data, files=files)
    result = response.json()
    
    print(result)

upload_with_files()`}</code></pre>

          <h3>{t('multipart.curl')}</h3>
          <pre className="code-block"><code>{`curl -X POST https://api.siteaccess.ru/api/v1/ai/process \\
  -H "Authorization: Bearer your-token" \\
  -F "provider=gemini" \\
  -F "model=gemini-1.5-pro" \\
  -F "prompt=Analyze these images" \\
  -F 'parameters={"temperature": 0.7}' \\
  -F "uploaded_files[]=@photo1.jpg" \\
  -F "uploaded_files[]=@photo2.png" \\
  -F "uploaded_files[]=@document.pdf"`}</code></pre>
        </section>

        <section>
          <h2>{t('multipart.responseFormat')}</h2>
          <p>{t('multipart.responseFormatDesc')}</p>
          <pre className="code-block"><code>{`{
  "success": true,
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "response": {
      "text": "I can see a beautiful landscape with mountains and a lake...",
      "finish_reason": "stop"
    }
  },
  "usage": {
    "prompt_tokens": 1250,
    "completion_tokens": 180,
    "total_tokens": 1430,
    "estimated_cost": 0.00215
  },
  "metadata": {
    "processing_time": 3.456,
    "timestamp": "2026-02-06T16:30:00Z",
    "api_key_source": "internal",
    "files_processed": 2
  }
}`}</code></pre>
        </section>

        <section>
          <h2>{t('multipart.validation')}</h2>

          <table>
            <thead>
              <tr>
                <th>{t('multipart.limit')}</th>
                <th>Value</th>
                <th>{t('multipart.configurable')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{t('multipart.maxFilesPerRequest')}</td>
                <td>10</td>
                <td>❌ {t('common.no')}</td>
              </tr>
              <tr>
                <td>{t('multipart.maxFileSize')}</td>
                <td>100 MB</td>
                <td>✅ {t('common.yes')} (.env)</td>
              </tr>
              <tr>
                <td>{t('multipart.totalRequestSize')}</td>
                <td>100 MB</td>
                <td>✅ {t('common.yes')} (Nginx)</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section>
          <h2>{t('multipart.errorHandling')}</h2>

          <h3>{t('multipart.validationErrors')}</h3>
          <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/errors#validation-error",
  "title": "Unprocessable Entity",
  "status": 422,
  "detail": "The given data was invalid.",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-...",
  "errors": {
    "uploaded_files.0": [
      "The file must not be greater than 102400 kilobytes."
    ],
    "uploaded_files.1": [
      "The file must be a file of type: jpeg, jpg, png, gif, webp, pdf, txt."
    ]
  }
}`}</code></pre>
        </section>

        <section>
          <h2>{t('multipart.bestPractices')}</h2>

          <div className="success-box">
            <strong>{t('multipart.bestPracticesDesc')}</strong><br />
            1. <strong>{t('multipart.bestPractice1')}</strong><br />
            2. <strong>{t('multipart.bestPractice2')}</strong><br />
            3. <strong>{t('multipart.bestPractice3')}</strong><br />
            4. <strong>{t('multipart.bestPractice4')}</strong><br />
            5. <strong>{t('multipart.bestPractice5')}</strong>
          </div>
        </section>

        <section>
          <h2>{t('multipart.migration')}</h2>

          <h3>{t('multipart.migrationBefore')}</h3>
          <pre className="code-block"><code>{`// ${t('multipart.oldWayBase64')}
const fileInput = document.getElementById('file');
const file = fileInput.files[0];

const reader = new FileReader();
reader.onload = async (e) => {
  const base64 = e.target.result.split(',')[1];
  
  const response = await fetch('/api/v1/ai/process', {
    method: 'POST',
    headers: {
      'Authorization': \`Bearer \${token}\`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      provider: 'gemini',
      model: 'gemini-1.5-pro',
      prompt: 'Analyze this image',
      files: [{
        type: 'image',
        content: \`data:image/jpeg;base64,\${base64}\`,
        mime_type: 'image/jpeg',
        name: file.name
      }]
    })
  });
};
reader.readAsDataURL(file);`}</code></pre>

          <h3>{t('multipart.migrationAfter')}</h3>
          <pre className="code-block"><code>{`// ${t('multipart.newWayMultipart')}
const fileInput = document.getElementById('file');
const formData = new FormData();

formData.append('provider', 'gemini');
formData.append('model', 'gemini-1.5-pro');
formData.append('prompt', 'Analyze this image');
formData.append('uploaded_files[]', fileInput.files[0]);

const response = await fetch('/api/v1/ai/process', {
  method: 'POST',
  headers: {
    'Authorization': \`Bearer \${token}\`
  },
  body: formData
});`}</code></pre>

          <div className="warning-box">
            <strong>{t('multipart.migrationImportant')}</strong><br />
            • {t('multipart.migrationImportant1')}<br />
            • {t('multipart.migrationImportant2')}<br />
            • {t('multipart.migrationImportant3')}
          </div>
        </section>
      </div>
    </div>
  )
}

export default MultipartGuide

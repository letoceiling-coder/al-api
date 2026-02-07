import { useState } from 'react'
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
      const response = await api.get('/v1/test')
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
          <p><code>{t('docs.baseUrlValue')}</code></p>
        </section>
        
        <section className="doc-section">
          <h2>{t('docs.authentication')}</h2>
          <p>{t('docs.authDesc')}</p>
          <pre className="code-block"><code>{t('docs.authHeader')}</code></pre>
          <button onClick={testAPI} className="test-btn" disabled={loading}>
            {loading ? t('common.loading') : t('common.testAPI')}
          </button>
          {testResult && (
            <div className={`test-result ${testResult.success ? 'success' : 'error'}`}>
              <pre>{JSON.stringify(testResult, null, 2)}</pre>
            </div>
          )}
        </section>
        
        <section className="doc-section">
          <h2>{t('docs.endpoints')}</h2>
          
          <h3>1. {t('docs.testEndpoint')}</h3>
          <p><strong>GET</strong> <code>/api/test</code></p>
          <p>{t('docs.testEndpointDesc')}</p>
          <pre className="code-block"><code>curl https://api.siteaccess.ru/api/test</code></pre>
          
          <h3>2. {t('docs.processEndpoint')}</h3>
          <p><strong>POST</strong> <code>/api/v1/ai/process</code></p>
          <p>{t('docs.processEndpointDesc')}</p>
        </section>
        
        <hr className="section-divider" />
        
        <section className="doc-section">
          <h2>{t('docs.requestDetails')}</h2>
          
          <h3>{t('docs.requiredFields')}</h3>
          
          <h4>1. {t('docs.provider')} ({t('common.required')})</h4>
          <p>{t('docs.providerDesc')}</p>
          <ul>
            <li><code>{t('docs.providerGemini')}</code></li>
            <li><code>{t('docs.providerOpenai')}</code></li>
          </ul>
          
          <h4>2. {t('docs.model')} ({t('common.required')})</h4>
          <p>{t('docs.modelDesc')}</p>
          
          <table className="models-table">
            <thead>
              <tr>
                <th>{t('docs.provider')}</th>
                <th>{t('docs.model')}</th>
                <th>{t('common.description')}</th>
                <th>Vision</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td rowSpan="3">Gemini</td>
                <td><code>gemini-1.5-pro</code></td>
                <td>{t('docs.modelsGemini')} - Best quality, vision support</td>
                <td>✅</td>
              </tr>
              <tr>
                <td><code>gemini-1.5-flash</code></td>
                <td>{t('docs.modelsGemini')} - Fast, cheap, vision support</td>
                <td>✅</td>
              </tr>
              <tr>
                <td><code>gemini-pro-vision</code></td>
                <td>{t('docs.modelsGemini')} - Legacy vision model</td>
                <td>✅</td>
              </tr>
              <tr>
                <td rowSpan="4">OpenAI</td>
                <td><code>gpt-4-turbo-preview</code></td>
                <td>{t('docs.modelsOpenai')} - Latest GPT-4, best quality</td>
                <td>❌</td>
              </tr>
              <tr>
                <td><code>gpt-4</code></td>
                <td>{t('docs.modelsOpenai')} - Standard GPT-4</td>
                <td>❌</td>
              </tr>
              <tr>
                <td><code>gpt-3.5-turbo</code></td>
                <td>{t('docs.modelsOpenai')} - Fast and cheap</td>
                <td>❌</td>
              </tr>
              <tr>
                <td><code>gpt-4-vision-preview</code></td>
                <td>{t('docs.modelsOpenai')} - GPT-4 with vision</td>
                <td>✅</td>
              </tr>
            </tbody>
          </table>
          
          <h4>3. {t('docs.prompt')} ({t('common.required')}, {t('common.minimum')} 1 {t('common.character')})</h4>
          <p><strong>{t('docs.promptDesc')}</strong></p>
          
          <p><strong>{t('docs.promptTips')}</strong></p>
          <ul>
            <li>{t('docs.promptTip1')}</li>
            <li>{t('docs.promptTip2')}</li>
            <li>{t('docs.promptTip3')}</li>
            <li>{t('docs.promptTip4')}</li>
          </ul>
          
          <p><strong>{t('docs.promptExamples')}</strong></p>
          <pre className="code-block"><code>{`// ${t('common.simple')} ${t('common.request')}
"${t('docs.promptExample1')}"

// ${t('common.request')} ${t('common.with')} ${t('common.context')}
"${t('docs.promptExample2')}"

// ${t('common.request')} ${t('common.for')} ${t('common.image')} ${t('common.analysis')}
"${t('docs.promptExample3')}"

// ${t('common.request')} ${t('common.for')} ${t('common.code')} ${t('common.generation')}
"${t('docs.promptExample4')}"`}</code></pre>
        </section>
        
        <hr className="section-divider" />
        
        <section className="doc-section">
          <h2>{t('docs.files')}</h2>
          
          <h3>{t('docs.filesGeneral')}</h3>
          <p>{t('docs.filesDesc')}</p>
          
          <h3>{t('docs.filesLimits')}</h3>
          <table className="limits-table">
            <thead>
              <tr>
                <th>{t('docs.parameter')}</th>
                <th>{t('docs.limit')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{t('docs.filesMaxFiles')}</td>
                <td><strong>10 {t('common.files')}</strong></td>
              </tr>
              <tr>
                <td>{t('docs.filesMaxSize')}</td>
                <td><strong>10 MB</strong> ({t('common.default')}, {t('common.configurable')} {t('common.in')} .env)</td>
              </tr>
              <tr>
                <td>{t('docs.filesFormat')}</td>
                <td><strong>Base64</strong> ({t('common.required')})</td>
              </tr>
            </tbody>
          </table>
          
          <h3>{t('docs.filesTypes')}</h3>
          
          <h4>1. {t('docs.filesImages')}</h4>
          <p><strong>{t('docs.filesImagesFormats')}</strong></p>
          <ul>
            <li><code>image/jpeg</code> - JPEG {t('common.images')} (.jpg, .jpeg)</li>
            <li><code>image/png</code> - PNG {t('common.images')} (.png)</li>
            <li><code>image/gif</code> - GIF {t('common.images')} (.gif)</li>
            <li><code>image/webp</code> - WebP {t('common.images')} (.webp)</li>
            <li><code>image/bmp</code> - BMP {t('common.images')} (.bmp)</li>
          </ul>
          <p><strong>{t('docs.filesImagesTips')}</strong></p>
          <ul>
            <li>{t('docs.filesImagesTip1')}</li>
            <li>{t('docs.filesImagesTip2')}</li>
            <li>{t('docs.filesImagesTip3')}</li>
          </ul>
          
          <h4>2. {t('docs.filesAudio')}</h4>
          <p><strong>{t('docs.filesAudioFormats')}</strong></p>
          <ul>
            <li><code>audio/mpeg</code> - MP3 {t('common.files')} (.mp3)</li>
            <li><code>audio/wav</code> - WAV {t('common.files')} (.wav)</li>
            <li><code>audio/ogg</code> - OGG {t('common.files')} (.ogg)</li>
            <li><code>audio/webm</code> - WebM {t('common.audio')} (.webm)</li>
          </ul>
          <p><strong>{t('docs.filesAudioNote')}</strong></p>
          
          <h4>3. {t('docs.filesDocuments')}</h4>
          <p><strong>{t('docs.filesDocumentsFormats')}</strong></p>
          <ul>
            <li><code>application/pdf</code> - PDF {t('common.documents')} (.pdf)</li>
            <li><code>text/plain</code> - {t('common.text')} {t('common.files')} (.txt)</li>
            <li><code>text/markdown</code> - Markdown {t('common.files')} (.md)</li>
          </ul>
          
          <h3>{t('docs.filesFormatObject')}</h3>
          <pre className="code-block"><code>{`{
  "type": "image",                    // ${t('common.required')}: "image" | "audio" | "document"
  "content": "base64_encoded_string", // ${t('common.required')}: ${t('common.file')} ${t('common.in')} Base64 ${t('common.format')}
  "mime_type": "image/jpeg",         // ${t('common.required')}: MIME ${t('common.type')} ${t('common.of')} ${t('common.file')}
  "name": "photo.jpg"                 // ${t('common.optional')}: ${t('common.file')} ${t('common.name')} (${t('common.max')} 255 ${t('common.characters')})
}`}</code></pre>
          
          <h3>{t('docs.filesPrepare')}</h3>
          
          <h4>JavaScript/Node.js:</h4>
          <pre className="code-block"><code>{`// ${t('common.reading')} ${t('common.file')} ${t('common.and')} ${t('common.conversion')} ${t('common.to')} Base64
const fs = require('fs');
const imageBuffer = fs.readFileSync('image.jpg');
const base64Image = imageBuffer.toString('base64');

// ${t('common.forming')} ${t('common.request')}
const request = {
  provider: "gemini",
  model: "gemini-1.5-pro",
  prompt: "${t('docs.promptExampleImage')}",
  files: [{
    type: "image",
    content: base64Image,
    mime_type: "image/jpeg",
    name: "image.jpg"
  }]
};`}</code></pre>
          
          <h4>Python:</h4>
          <pre className="code-block"><code>{`import base64

# ${t('common.reading')} ${t('common.file')} ${t('common.and')} ${t('common.conversion')} ${t('common.to')} Base64
with open('image.jpg', 'rb') as f:
    image_data = f.read()
    base64_image = base64.b64encode(image_data).decode('utf-8')

# ${t('common.forming')} ${t('common.request')}
request = {
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "prompt": "${t('docs.promptExampleImage')}",
    "files": [{
        "type": "image",
        "content": base64_image,
        "mime_type": "image/jpeg",
        "name": "image.jpg"
    }]
}`}</code></pre>
        </section>
        
        <hr className="section-divider" />
        
        <section className="doc-section">
          <h2>{t('docs.parameters')}</h2>
          
          <p>{t('docs.parametersDesc')}</p>
          
          <table className="parameters-table">
            <thead>
              <tr>
                <th>{t('docs.parameter')}</th>
                <th>{t('common.type')}</th>
                <th>{t('docs.range')}</th>
                <th>{t('common.description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>temperature</code></td>
                <td>number</td>
                <td>0.0 - 2.0</td>
                <td>{t('docs.temperatureDesc')}</td>
              </tr>
              <tr>
                <td><code>max_tokens</code></td>
                <td>integer</td>
                <td>1 - 1,000,000</td>
                <td>{t('docs.maxTokensDesc')}</td>
              </tr>
              <tr>
                <td><code>top_p</code></td>
                <td>number</td>
                <td>0.0 - 1.0</td>
                <td>{t('docs.topPDesc')}</td>
              </tr>
              <tr>
                <td><code>top_k</code></td>
                <td>integer</td>
                <td>≥ 1</td>
                <td>{t('docs.topKDesc')}</td>
              </tr>
            </tbody>
          </table>
          
          <p><strong>{t('docs.parametersRecommendations')}</strong></p>
          <ul>
            <li><strong>{t('docs.parametersTemp1')}</strong></li>
            <li><strong>{t('docs.parametersTemp2')}</strong></li>
            <li><strong>{t('docs.parametersTemp3')}</strong></li>
            <li><strong>{t('docs.parametersMaxTokens')}</strong></li>
          </ul>
        </section>
        
        <hr className="section-divider" />
        
        <section className="doc-section">
          <h2>{t('docs.apiKeys')}</h2>
          
          <h3>{t('docs.apiKeysVariants')}</h3>
          
          <h4>{t('docs.apiKeysVariant1')}</h4>
          <p>{t('docs.apiKeysVariant1Desc')}</p>
          <pre className="code-block"><code>{`{
  "provider": "gemini",
  "model": "gemini-1.5-pro",
  "prompt": "Hello!"
  // ${t('docs.keyNotNeeded')}
}`}</code></pre>
          
          <h4>{t('docs.apiKeysVariant2')}</h4>
          <p>{t('docs.apiKeysVariant2Desc')}</p>
          <pre className="code-block"><code>{`{
  "provider": "gemini",
  "model": "gemini-1.5-pro",
  "prompt": "Hello!",
  "user_api_key": "AIzaSyBUwkCahleq0ukUxQVskCGC29CA5EoWTg8"
}`}</code></pre>
          
          <h4>{t('docs.apiKeysVariant3')}</h4>
          <p>{t('docs.apiKeysVariant3Desc')}</p>
          <pre className="code-block"><code>{`{
  "provider": "gemini",
  "model": "gemini-1.5-pro",
  "prompt": "Hello!",
  "use_saved_key": true,
  "saved_key_id": 5  // ${t('docs.keyIdFrom')} /api/v1/user/keys
}`}</code></pre>
        </section>
        
        <hr className="section-divider" />
        
        <section className="doc-section">
          <h2>{t('docs.examples')}</h2>
          
          <h3>{t('docs.example1')}</h3>
          <pre className="code-block"><code>{`curl -X POST \\
  -H 'Authorization: Bearer YOUR_TOKEN' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "prompt": "${t('docs.example1Prompt')}"
  }' \\
  https://api.siteaccess.ru/api/v1/ai/process`}</code></pre>
          
          <h3>{t('docs.example2')}</h3>
          <pre className="code-block"><code>{`curl -X POST \\
  -H 'Authorization: Bearer YOUR_TOKEN' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "provider": "openai",
    "model": "gpt-4-turbo-preview",
    "prompt": "${t('docs.example2Prompt')}",
    "parameters": {
      "temperature": 0.8,
      "max_tokens": 500,
      "top_p": 0.9
    }
  }' \\
  https://api.siteaccess.ru/api/v1/ai/process`}</code></pre>
        </section>
        
        <hr className="section-divider" />
        
        <section className="doc-section">
          <h2>{t('docs.response')}</h2>
          
          <h3>{t('docs.responseSuccess')}</h3>
          <pre className="code-block"><code>{`{
  "success": true,
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "response": {
      "text": "${t('docs.responseExampleText')}",
      "finish_reason": "stop"
    }
  },
  "usage": {
    "prompt_tokens": 150,
    "completion_tokens": 300,
    "total_tokens": 450,
    "estimated_cost": 0.000675
  },
  "metadata": {
    "processing_time": 2.345,
    "timestamp": "2026-02-06T14:30:00Z",
    "api_key_source": "internal"
  },
  "limits": {
    "daily_requests_used": 45,
    "daily_requests_limit": 100,
    "daily_requests_remaining": 55
  }
}`}</code></pre>
          
          <h4>{t('docs.responseFields')}</h4>
          <table className="response-table">
            <thead>
              <tr>
                <th>{t('common.field')}</th>
                <th>{t('common.type')}</th>
                <th>{t('common.description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>success</code></td>
                <td>boolean</td>
                <td>{t('docs.successDesc')}</td>
              </tr>
              <tr>
                <td><code>request_id</code></td>
                <td>string (UUID)</td>
                <td>{t('docs.requestIdDesc')}</td>
              </tr>
              <tr>
                <td><code>data.response.text</code></td>
                <td>string</td>
                <td>{t('docs.responseTextDesc')}</td>
              </tr>
              <tr>
                <td><code>usage.prompt_tokens</code></td>
                <td>integer</td>
                <td>{t('docs.promptTokensDesc')}</td>
              </tr>
              <tr>
                <td><code>usage.completion_tokens</code></td>
                <td>integer</td>
                <td>{t('docs.completionTokensDesc')}</td>
              </tr>
              <tr>
                <td><code>usage.estimated_cost</code></td>
                <td>number</td>
                <td>{t('docs.estimatedCostDesc')}</td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>
    </div>
  )
}

export default Documentation

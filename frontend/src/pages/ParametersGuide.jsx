import { useTranslation } from 'react-i18next'
import './Page.css'

const ParametersGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>{t('parameters.title')}</h1>
        <p className="subtitle">
          {t('parameters.subtitle')}
          <span className="badge">{t('parameters.badgeV1')}</span>
        </p>

        <div className="info-box">
          <strong>{t('parameters.note')}</strong><br />
          {t('parameters.noteDesc')}
        </div>

        <section>
          <h2>{t('parameters.commonParameters')}</h2>
          <p>{t('parameters.commonParametersDesc')}</p>

          <table className="param-table">
            <thead>
              <tr>
                <th>{t('parameters.parameter')}</th>
                <th>{t('common.type')}</th>
                <th>{t('parameters.range')}</th>
                <th>{t('parameters.default')}</th>
                <th>{t('common.description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span className="param-name">temperature</span></td>
                <td><span className="param-type">float</span></td>
                <td>0.0 - 2.0</td>
                <td>1.0 (Gemini)<br />1.0 (OpenAI)</td>
                <td>{t('parameters.temperatureDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">top_p</span></td>
                <td><span className="param-type">float</span></td>
                <td>0.0 - 1.0</td>
                <td>0.95 (Gemini)<br />1.0 (OpenAI)</td>
                <td>{t('parameters.topPDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">max_tokens</span></td>
                <td><span className="param-type">integer</span></td>
                <td>1 - model max</td>
                <td>1024</td>
                <td>{t('parameters.maxTokensDesc')}</td>
              </tr>
            </tbody>
          </table>

          <h3>{t('parameters.exampleUsage')}</h3>
          <pre className="code-block"><code>{`{
  "provider": "gemini",
  "model": "gemini-1.5-pro",
  "prompt": "Write a creative story",
  "parameters": {
    "temperature": 1.2,
    "top_p": 0.9,
    "max_tokens": 2048
  }
}`}</code></pre>
        </section>

        <div className="provider-section">
          <h2><span className="badge gemini">Gemini</span> {t('parameters.geminiSpecific')}</h2>

          <table className="param-table">
            <thead>
              <tr>
                <th>{t('parameters.parameter')}</th>
                <th>{t('common.type')}</th>
                <th>{t('parameters.rangeValues')}</th>
                <th>{t('parameters.default')}</th>
                <th>{t('common.description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span className="param-name">top_k</span></td>
                <td><span className="param-type">integer</span></td>
                <td>1 - 100</td>
                <td>40</td>
                <td>{t('parameters.topKDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">max_output_tokens</span></td>
                <td><span className="param-type">integer</span></td>
                <td>1 - 8192</td>
                <td>2048</td>
                <td>{t('parameters.maxOutputTokensDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">stop_sequences</span></td>
                <td><span className="param-type">array</span></td>
                <td>{t('parameters.max5Sequences')}</td>
                <td>[]</td>
                <td>{t('parameters.stopSequencesDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">candidate_count</span></td>
                <td><span className="param-type">integer</span></td>
                <td>1 - 8</td>
                <td>1</td>
                <td>{t('parameters.candidateCountDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">safety_settings</span></td>
                <td><span className="param-type">array</span></td>
                <td>{t('parameters.seeBelow')}</td>
                <td>{t('parameters.defaultSafety')}</td>
                <td>{t('parameters.safetySettingsDesc')}</td>
              </tr>
            </tbody>
          </table>

          <h3>{t('parameters.safetySettings')}</h3>
          <pre className="code-block"><code>{`{
  "safety_settings": [
    {
      "category": "HARM_CATEGORY_HARASSMENT",
      "threshold": "BLOCK_MEDIUM_AND_ABOVE"
    },
    {
      "category": "HARM_CATEGORY_HATE_SPEECH",
      "threshold": "BLOCK_ONLY_HIGH"
    },
    {
      "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
      "threshold": "BLOCK_MEDIUM_AND_ABOVE"
    },
    {
      "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
      "threshold": "BLOCK_MEDIUM_AND_ABOVE"
    }
  ]
}`}</code></pre>

          <h4>{t('parameters.safetyCategories')}</h4>
          <ul>
            <li><code>HARM_CATEGORY_HARASSMENT</code></li>
            <li><code>HARM_CATEGORY_HATE_SPEECH</code></li>
            <li><code>HARM_CATEGORY_SEXUALLY_EXPLICIT</code></li>
            <li><code>HARM_CATEGORY_DANGEROUS_CONTENT</code></li>
          </ul>

          <h4>{t('parameters.safetyThresholds')}</h4>
          <ul>
            <li><code>BLOCK_NONE</code> - {t('parameters.blockNone')}</li>
            <li><code>BLOCK_ONLY_HIGH</code> - {t('parameters.blockOnlyHigh')}</li>
            <li><code>BLOCK_MEDIUM_AND_ABOVE</code> - {t('parameters.blockMediumAndAbove')}</li>
            <li><code>BLOCK_LOW_AND_ABOVE</code> - {t('parameters.blockLowAndAbove')}</li>
          </ul>

          <h3>{t('parameters.geminiExample')}</h3>
          <pre className="code-block"><code>{`{
  "provider": "gemini",
  "model": "gemini-1.5-pro",
  "prompt": "Explain quantum computing in simple terms",
  "parameters": {
    "temperature": 0.7,
    "top_p": 0.95,
    "top_k": 40,
    "max_output_tokens": 1024,
    "stop_sequences": ["END", "STOP"],
    "candidate_count": 1,
    "safety_settings": [
      {
        "category": "HARM_CATEGORY_HARASSMENT",
        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
      }
    ]
  }
}`}</code></pre>
        </div>

        <div className="provider-section">
          <h2><span className="badge openai">OpenAI</span> {t('parameters.openaiSpecific')}</h2>

          <table className="param-table">
            <thead>
              <tr>
                <th>{t('parameters.parameter')}</th>
                <th>{t('common.type')}</th>
                <th>{t('parameters.rangeValues')}</th>
                <th>{t('parameters.default')}</th>
                <th>{t('common.description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span className="param-name">presence_penalty</span></td>
                <td><span className="param-type">float</span></td>
                <td>-2.0 to 2.0</td>
                <td>0</td>
                <td>{t('parameters.presencePenaltyDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">frequency_penalty</span></td>
                <td><span className="param-type">float</span></td>
                <td>-2.0 to 2.0</td>
                <td>0</td>
                <td>{t('parameters.frequencyPenaltyDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">n</span></td>
                <td><span className="param-type">integer</span></td>
                <td>1 - 10</td>
                <td>1</td>
                <td>{t('parameters.nDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">stop</span></td>
                <td><span className="param-type">array</span></td>
                <td>{t('parameters.max4Sequences')}</td>
                <td>null</td>
                <td>{t('parameters.stopDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">logit_bias</span></td>
                <td><span className="param-type">object</span></td>
                <td>{t('parameters.tokenIdRange')}</td>
                <td>{'{}'}</td>
                <td>{t('parameters.logitBiasDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">user</span></td>
                <td><span className="param-type">string</span></td>
                <td>{t('parameters.max255Chars')}</td>
                <td>null</td>
                <td>{t('parameters.userDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">seed</span></td>
                <td><span className="param-type">integer</span></td>
                <td>{t('parameters.anyInteger')}</td>
                <td>null</td>
                <td>{t('parameters.seedDesc')}</td>
              </tr>
              <tr>
                <td><span className="param-name">response_format</span></td>
                <td><span className="param-type">object</span></td>
                <td>{t('parameters.seeBelow')}</td>
                <td>null</td>
                <td>{t('parameters.responseFormatDesc')}</td>
              </tr>
            </tbody>
          </table>

          <h3>{t('parameters.responseFormat')}</h3>
          <pre className="code-block"><code>{`{
  "response_format": {
    "type": "json_object"
  }
}`}</code></pre>
          <div className="warning-box">
            <strong>{t('parameters.responseFormatImportant')}</strong><br />
            {t('parameters.responseFormatImportantDesc')}
          </div>

          <h3>{t('parameters.logitBias')}</h3>
          <p>{t('parameters.logitBiasDesc')}</p>
          <pre className="code-block"><code>{`{
  "logit_bias": {
    "1234": 50,
    "5678": -50
  }
}`}</code></pre>

          <h3>{t('parameters.openaiExample')}</h3>
          <pre className="code-block"><code>{`{
  "provider": "openai",
  "model": "gpt-4-turbo-preview",
  "prompt": "Write a function to calculate fibonacci numbers. Return as JSON.",
  "parameters": {
    "temperature": 0.5,
    "top_p": 1.0,
    "max_tokens": 1024,
    "presence_penalty": 0.3,
    "frequency_penalty": 0.3,
    "n": 1,
    "stop": ["END", "STOP"],
    "user": "user-123",
    "seed": 42,
    "response_format": {
      "type": "json_object"
    }
  }
}`}</code></pre>
        </div>

        <section>
          <h2>{t('parameters.combinations')}</h2>

          <h3>1. {t('parameters.creativeWriting')}</h3>
          <pre className="code-block"><code>{`{
  "parameters": {
    "temperature": 1.5,
    "top_p": 0.9,
    "top_k": 50,          // Gemini
    "presence_penalty": 0.6  // OpenAI
  }
}`}</code></pre>

          <h3>2. {t('parameters.factualTechnical')}</h3>
          <pre className="code-block"><code>{`{
  "parameters": {
    "temperature": 0.3,
    "top_p": 0.9,
    "top_k": 20,          // Gemini
    "frequency_penalty": 0.0  // OpenAI
  }
}`}</code></pre>

          <h3>3. {t('parameters.codeGeneration')}</h3>
          <pre className="code-block"><code>{`{
  "parameters": {
    "temperature": 0.2,
    "top_p": 0.95,
    "seed": 42,           // OpenAI
    "max_tokens": 2048
  }
}`}</code></pre>

          <h3>4. {t('parameters.jsonOutput')}</h3>
          <pre className="code-block"><code>{`{
  "prompt": "Return user data as JSON with fields: name, age, email",
  "parameters": {
    "temperature": 0.5,
    "response_format": {  // OpenAI only
      "type": "json_object"
    }
  }
}`}</code></pre>
        </section>

        <section>
          <h2>{t('parameters.modelLimits')}</h2>

          <h3>{t('parameters.geminiModels')}</h3>
          <table>
            <thead>
              <tr>
                <th>{t('parameters.model')}</th>
                <th>{t('parameters.maxInput')}</th>
                <th>{t('parameters.maxOutput')}</th>
                <th>Vision</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>gemini-1.5-pro</td>
                <td>1,000,000 tokens</td>
                <td>8,192 tokens</td>
                <td>✅ {t('common.yes')}</td>
              </tr>
              <tr>
                <td>gemini-1.5-flash</td>
                <td>1,000,000 tokens</td>
                <td>8,192 tokens</td>
                <td>✅ {t('common.yes')}</td>
              </tr>
              <tr>
                <td>gemini-pro-vision</td>
                <td>30,720 tokens</td>
                <td>2,048 tokens</td>
                <td>✅ {t('common.yes')}</td>
              </tr>
            </tbody>
          </table>

          <h3>{t('parameters.openaiModels')}</h3>
          <table>
            <thead>
              <tr>
                <th>{t('parameters.model')}</th>
                <th>{t('parameters.maxInput')}</th>
                <th>{t('parameters.maxOutput')}</th>
                <th>Vision</th>
                <th>{t('parameters.jsonMode')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>gpt-4-turbo-preview</td>
                <td>128,000 tokens</td>
                <td>4,096 tokens</td>
                <td>❌ {t('common.no')}</td>
                <td>✅ {t('common.yes')}</td>
              </tr>
              <tr>
                <td>gpt-4</td>
                <td>8,192 tokens</td>
                <td>8,192 tokens</td>
                <td>❌ {t('common.no')}</td>
                <td>❌ {t('common.no')}</td>
              </tr>
              <tr>
                <td>gpt-3.5-turbo</td>
                <td>16,385 tokens</td>
                <td>4,096 tokens</td>
                <td>❌ {t('common.no')}</td>
                <td>✅ {t('common.yes')}</td>
              </tr>
              <tr>
                <td>gpt-4-vision-preview</td>
                <td>128,000 tokens</td>
                <td>4,096 tokens</td>
                <td>✅ {t('common.yes')}</td>
                <td>❌ {t('common.no')}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section>
          <h2>{t('parameters.errorHandling')}</h2>

          <h3>{t('parameters.invalidParameter')}</h3>
          <pre className="code-block"><code>{`{
  "type": "https://api.siteaccess.ru/errors#validation-error",
  "title": "Unprocessable Entity",
  "status": 422,
  "detail": "The given data was invalid.",
  "errors": {
    "parameters.temperature": [
      "The parameters.temperature must be between 0 and 2."
    ]
  }
}`}</code></pre>

          <h3>{t('parameters.unsupportedParameter')}</h3>
          <div className="warning-box">
            <strong>{t('parameters.unsupportedParameterNote')}</strong><br />
            {t('parameters.unsupportedParameterNoteDesc')}
          </div>
        </section>

        <section>
          <h2>{t('parameters.tips')}</h2>

          <div className="info-box">
            <strong>{t('parameters.tipsBestPractices')}</strong><br /><br />
            <strong>{t('parameters.tipsTemp')}</strong> {t('parameters.tipsTempDesc')}<br />
            • {t('parameters.tipsTemp1')}<br />
            • {t('parameters.tipsTemp2')}<br />
            • {t('parameters.tipsTemp3')}<br /><br />
            
            <strong>{t('parameters.tipsTopP')}</strong><br />
            • {t('parameters.tipsTopPDesc1')}<br />
            • {t('parameters.tipsTopPDesc2')}<br />
            • {t('parameters.tipsTopPDesc3')}<br /><br />
            
            <strong>{t('parameters.tipsPenalties')}</strong><br />
            • {t('parameters.tipsPenaltiesDesc1')}<br />
            • {t('parameters.tipsPenaltiesDesc2')}<br />
            • {t('parameters.tipsPenaltiesDesc3')}<br /><br />
            
            <strong>{t('parameters.tipsMaxTokens')}</strong><br />
            • {t('parameters.tipsMaxTokensDesc1')}<br />
            • {t('parameters.tipsMaxTokensDesc2')}<br />
            • {t('parameters.tipsMaxTokensDesc3')}<br /><br />
            
            <strong>{t('parameters.tipsSafety')}</strong><br />
            • {t('parameters.tipsSafetyDesc1')}<br />
            • {t('parameters.tipsSafetyDesc2')}<br />
            • {t('parameters.tipsSafetyDesc3')}
          </div>
        </section>
      </div>
    </div>
  )
}

export default ParametersGuide

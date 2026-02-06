import { useTranslation } from 'react-i18next'
import './Page.css'

const ParametersGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>⚙️ Model Parameters</h1>
        <p>Все параметры для настройки AI моделей</p>
        
        <section>
          <h2>Gemini Parameters</h2>
          <ul>
            <li><strong>temperature</strong>: 0.0 - 2.0 (default: 0.7)</li>
            <li><strong>top_p</strong>: 0.0 - 1.0 (default: 1.0)</li>
            <li><strong>top_k</strong>: 1+ (default: 40)</li>
            <li><strong>candidate_count</strong>: 1-8 (default: 1)</li>
          </ul>
        </section>

        <section>
          <h2>OpenAI Parameters</h2>
          <ul>
            <li><strong>temperature</strong>: 0.0 - 2.0 (default: 0.7)</li>
            <li><strong>max_tokens</strong>: 1+ (default: model specific)</li>
            <li><strong>frequency_penalty</strong>: -2.0 - 2.0 (default: 0.0)</li>
            <li><strong>presence_penalty</strong>: -2.0 - 2.0 (default: 0.0)</li>
          </ul>
        </section>
      </div>
    </div>
  )
}

export default ParametersGuide

import { useTranslation } from 'react-i18next'
import './Page.css'

const ParametersGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>⚙️ {t('parameters.title')}</h1>
        <p>{t('parameters.subtitle')}</p>
        
        <section>
          <h2>{t('parameters.geminiParams')}</h2>
          <ul>
            <li><strong>{t('parameters.temperature')}</strong>: 0.0 - 2.0 ({t('parameters.default')}: 0.7)</li>
            <li><strong>{t('parameters.topP')}</strong>: 0.0 - 1.0 ({t('parameters.default')}: 1.0)</li>
            <li><strong>{t('parameters.topK')}</strong>: 1+ ({t('parameters.default')}: 40)</li>
            <li><strong>{t('parameters.candidateCount')}</strong>: 1-8 ({t('parameters.default')}: 1)</li>
          </ul>
        </section>

        <section>
          <h2>{t('parameters.openaiParams')}</h2>
          <ul>
            <li><strong>{t('parameters.temperature')}</strong>: 0.0 - 2.0 ({t('parameters.default')}: 0.7)</li>
            <li><strong>{t('parameters.maxTokens')}</strong>: 1+ ({t('parameters.default')}: {t('parameters.modelSpecific')})</li>
            <li><strong>{t('parameters.frequencyPenalty')}</strong>: -2.0 - 2.0 ({t('parameters.default')}: 0.0)</li>
            <li><strong>{t('parameters.presencePenalty')}</strong>: -2.0 - 2.0 ({t('parameters.default')}: 0.0)</li>
          </ul>
        </section>
      </div>
    </div>
  )
}

export default ParametersGuide

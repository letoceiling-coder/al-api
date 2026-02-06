import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import './Home.css'

const Home = () => {
  const { t } = useTranslation()

  return (
    <div className="home">
      <div className="home-hero">
        <h1 className="hero-title">{t('home.title')}</h1>
        <p className="hero-subtitle">{t('home.subtitle')}</p>
        <p className="hero-description">{t('home.description')}</p>
      </div>

      <div className="home-content">
        <div className="home-section">
          <h2>📚 {t('home.documentation')}</h2>
          <div className="cards-grid">
            <Link to="/guide" className="card">
              <div className="card-icon">📖</div>
              <h3>{t('home.cardApiDocs')}</h3>
              <p>{t('home.cardApiDocsDesc')}</p>
            </Link>
            <Link to="/streaming" className="card">
              <div className="card-icon">📡</div>
              <h3>{t('home.cardStreaming')}</h3>
              <p>{t('home.cardStreamingDesc')}</p>
            </Link>
            <Link to="/multipart" className="card">
              <div className="card-icon">📎</div>
              <h3>{t('home.cardFileUpload')}</h3>
              <p>{t('home.cardFileUploadDesc')}</p>
            </Link>
            <Link to="/parameters" className="card">
              <div className="card-icon">⚙️</div>
              <h3>{t('home.cardParameters')}</h3>
              <p>{t('home.cardParametersDesc')}</p>
            </Link>
            <Link to="/errors" className="card">
              <div className="card-icon">⚠️</div>
              <h3>{t('home.cardErrors')}</h3>
              <p>{t('home.cardErrorsDesc')}</p>
            </Link>
            <Link to="/swagger" className="card">
              <div className="card-icon">📘</div>
              <h3>{t('home.cardSwagger')}</h3>
              <p>{t('home.cardSwaggerDesc')}</p>
            </Link>
          </div>
        </div>

        <div className="home-section">
          <h2>🚀 {t('home.quickStart')}</h2>
          <div className="quick-start">
            <div className="step">
              <div className="step-number">1</div>
              <div className="step-content">
                <h3>{t('home.step1Title')}</h3>
                <p>{t('home.step1Desc')}</p>
              </div>
            </div>
            <div className="step">
              <div className="step-number">2</div>
              <div className="step-content">
                <h3>{t('home.step2Title')}</h3>
                <p>{t('home.step2Desc')}</p>
              </div>
            </div>
            <div className="step">
              <div className="step-number">3</div>
              <div className="step-content">
                <h3>{t('home.step3Title')}</h3>
                <p>{t('home.step3Desc')}</p>
              </div>
            </div>
          </div>
        </div>

        <div className="home-section">
          <h2>🔗 {t('home.apiReference')}</h2>
          <div className="api-info">
            <div className="info-item">
              <strong>{t('home.baseUrl')}:</strong>
              <code>https://api.siteaccess.ru/api</code>
            </div>
            <div className="info-item">
              <strong>{t('home.version')}:</strong>
              <code>v1</code>
            </div>
            <div className="info-item">
              <strong>{t('home.authentication')}:</strong>
              <code>Bearer Token (Sanctum)</code>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Home

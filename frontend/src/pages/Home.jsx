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
              <h3>API Документация</h3>
              <p>Полное описание всех endpoints, примеры запросов и ответов</p>
            </Link>
            <Link to="/streaming" className="card">
              <div className="card-icon">📡</div>
              <h3>Streaming</h3>
              <p>Server-Sent Events для потоковой передачи ответов</p>
            </Link>
            <Link to="/multipart" className="card">
              <div className="card-icon">📎</div>
              <h3>Загрузка файлов</h3>
              <p>Multipart/form-data для эффективной загрузки файлов</p>
            </Link>
            <Link to="/parameters" className="card">
              <div className="card-icon">⚙️</div>
              <h3>Параметры моделей</h3>
              <p>Все параметры для Gemini и OpenAI моделей</p>
            </Link>
            <Link to="/errors" className="card">
              <div className="card-icon">⚠️</div>
              <h3>Обработка ошибок</h3>
              <p>RFC 7807 стандарт для ошибок API</p>
            </Link>
            <Link to="/swagger" className="card">
              <div className="card-icon">📘</div>
              <h3>Swagger UI</h3>
              <p>Интерактивная документация API</p>
            </Link>
          </div>
        </div>

        <div className="home-section">
          <h2>🚀 {t('home.quickStart')}</h2>
          <div className="quick-start">
            <div className="step">
              <div className="step-number">1</div>
              <div className="step-content">
                <h3>Получите токен</h3>
                <p>Создайте аккаунт и получите Bearer token для доступа к API</p>
              </div>
            </div>
            <div className="step">
              <div className="step-number">2</div>
              <div className="step-content">
                <h3>Отправьте запрос</h3>
                <p>Используйте POST /api/v1/ai/process для отправки запросов к AI</p>
              </div>
            </div>
            <div className="step">
              <div className="step-number">3</div>
              <div className="step-content">
                <h3>Получите ответ</h3>
                <p>Получите структурированный ответ с метаданными и usage</p>
              </div>
            </div>
          </div>
        </div>

        <div className="home-section">
          <h2>🔗 {t('home.apiReference')}</h2>
          <div className="api-info">
            <div className="info-item">
              <strong>Base URL:</strong>
              <code>https://api.siteaccess.ru/api</code>
            </div>
            <div className="info-item">
              <strong>Version:</strong>
              <code>v1</code>
            </div>
            <div className="info-item">
              <strong>Authentication:</strong>
              <code>Bearer Token (Sanctum)</code>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Home
